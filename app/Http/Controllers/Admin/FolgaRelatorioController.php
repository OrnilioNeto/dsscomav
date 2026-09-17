<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FolgaDia;
use App\Models\User;
use App\Services\FolgaRulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class FolgaRelatorioController extends Controller
{
    private FolgaRulesService $rules;

    public function __construct(FolgaRulesService $rules)
    {
        FolgaRulesService::ensureTablesExist();
        $this->rules = $rules;
        $this->middleware('permission:folgas,view');
    }

    public function index(Request $request)
    {
        $mes = (int) $request->input('month', now()->month);
        $ano = (int) $request->input('year', now()->year);
        $userId = $request->input('user_id');

        $motoristas = User::where('tipo_usuario', 'motorista')
            ->where('status', 'ativo')
            ->when($userId, fn ($q) => $q->where('id', $userId))
            ->orderBy('nome')
            ->get();

        $relatorio = [];
        foreach ($motoristas as $motorista) {
            $snapshot = $this->rules->computeSnapshot($motorista, $mes, $ano);

            $tiradasMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'folga')->count();
            $atestadosMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'atestado')->count();
            $licencasMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'licenca')->count();

            $relatorio[$motorista->id] = array_merge($snapshot, [
                'tiradas_mes' => $tiradasMes,
                'atestados_mes' => $atestadosMes,
                'licencas_mes' => $licencasMes,
            ]);
        }

        return view('admin.folgas.relatorios', compact('motoristas', 'relatorio', 'mes', 'ano', 'userId'));
    }

    public function exportCsv(Request $request)
    {
        $mes = (int) $request->input('month', now()->month);
        $ano = (int) $request->input('year', now()->year);

        $motoristas = User::where('tipo_usuario', 'motorista')
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();

        $csv = "CPF,Nome,Prévistas,Tiradas,Atestado,Licença,Ajustes,Saldo Anterior,Saldo Acumulado,Domingo Cumprido,Dias Trabalhados\n";

        foreach ($motoristas as $motorista) {
            $s = $this->rules->computeSnapshot($motorista, $mes, $ano);
            $tiradasMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'folga')->count();
            $atestadosMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'atestado')->count();
            $licencasMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'licenca')->count();

            $csv .= implode(',', [
                $motorista->cpf,
                '"'.str_replace('"', '""', $motorista->nome).'"',
                $s['previstas'],
                $tiradasMes,
                $atestadosMes,
                $licencasMes,
                $s['ajustes'],
                $s['saldo_anterior'],
                $s['saldo_acumulado'],
                $s['domingo_cumprido'] ? 'Sim' : 'Não',
                $s['dias_trabalhados'],
            ])."\n";
        }

        $filename = "relatorio_folgas_{$mes}_{$ano}.csv";

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportPdf(Request $request)
    {
        $mes = (int) $request->input('month', now()->month);
        $ano = (int) $request->input('year', now()->year);

        $motoristas = User::where('tipo_usuario', 'motorista')
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();

        $relatorio = [];
        foreach ($motoristas as $motorista) {
            $relatorio[] = [
                'motorista' => $motorista,
                'snapshot' => $this->rules->computeSnapshot($motorista, $mes, $ano),
            ];
        }

        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $titulo = "Relatório de Folgas — {$meses[$mes]}/{$ano}";

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>
            body { font-family: sans-serif; font-size: 11px; color: #333; }
            h1 { font-size: 18px; color: #153B2E; border-bottom: 2px solid #153B2E; padding-bottom: 8px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #153B2E; color: white; padding: 8px 6px; text-align: left; font-size: 10px; }
            td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
            tr:nth-child(even) { background: #f9fafb; }
            .saldo-positivo { color: #059669; font-weight: bold; }
            .saldo-negativo { color: #dc2626; font-weight: bold; }
            .footer { margin-top: 20px; font-size: 9px; color: #999; text-align: center; }
        </style></head><body>';
        $html .= "<h1>{$titulo}</h1>";
        $html .= '<p>Gerado em: '.now()->format('d/m/Y H:i').'</p>';
        $html .= '<table><thead><tr>';
        $html .= '<th>CPF</th><th>Nome</th><th>Prévistas</th><th>Tiradas</th><th>Atestado</th><th>Licença</th><th>Ajustes</th>';
        $html .= '<th>Saldo Anterior</th><th>Saldo Acumulado</th><th>Domingo</th><th>Dias Trab.</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($relatorio as $r) {
            $u = $r['motorista'];
            $s = $r['snapshot'];
            $saldoClass = $s['saldo_acumulado'] >= 0 ? 'saldo-positivo' : 'saldo-negativo';
            $html .= '<tr>';
            $html .= "<td>{$u->cpf}</td>";
            $html .= "<td>{$u->nome}</td>";
            $html .= "<td>{$s['previstas']}</td>";
            $html .= "<td>{$s['tiradas_mes']}</td>";
            $html .= "<td>{$s['atestados_mes']}</td>";
            $html .= "<td>{$s['licencas_mes']}</td>";
            $html .= "<td>{$s['ajustes']}</td>";
            $html .= "<td>{$s['saldo_anterior']}</td>";
            $html .= "<td class=\"{$saldoClass}\">{$s['saldo_acumulado']}</td>";
            $html .= '<td>'.($s['domingo_cumprido'] ? '✅' : '❌').'</td>';
            $html .= "<td>{$s['dias_trabalhados']}</td>";
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div class="footer">' . plataforma_nome() . ' — Relatório gerado automaticamente</div>';
        $html .= '</body></html>';

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(plataforma_nome());
        $pdf->SetTitle($titulo);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = "relatorio_folgas_{$mes}_{$ano}.pdf";
        $pdf->Output($filename, 'D');
    }
}
