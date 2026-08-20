<?php

namespace App\Http\Controllers;

use App\Models\ProjetoPedagogico;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use TCPDF;

class ProjetoPedagogicoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:projeto_pedagogico,view')->only(['index', 'show']);
        $this->middleware('permission:projeto_pedagogico,edit')->except(['index', 'show']);
    }

    /**
     * Página universal: lista os projetos pedagógicos e os treinamentos atendidos por cada um.
     */
    public function index()
    {
        $projetos = ProjetoPedagogico::with('trainings')
            ->orderBy('updated_at', 'desc')
            ->get();

        $totalTreinamentos = Training::count();
        $treinamentosSemPP = $this->availableTrainings()->count();

        return view('projetos_pedagogicos.index', compact('projetos', 'totalTreinamentos', 'treinamentosSemPP'));
    }

    /**
     * Formulário de cadastro de um novo projeto pedagógico (pode atender a vários treinamentos).
     */
    public function create()
    {
        $templates = $this->getTemplates();
        $disponiveis = $this->availableTrainings();

        return view('projetos_pedagogicos.form', [
            'pp' => null,
            'templates' => $templates,
            'disponiveis' => $disponiveis,
            'selecionados' => [],
        ]);
    }

    /**
     * Salva um novo projeto pedagógico e vincula os treinamentos selecionados.
     */
    public function store(Request $request)
    {
        $request->validate([
            'trainings' => 'required|array|min:1',
            'trainings.*' => 'exists:trainings,id',
            'versao' => 'nullable|string|max:20',
            'objetivo_geral' => 'nullable|string',
            'principios_sst' => 'nullable|string',
            'estrategia_pedagogica' => 'nullable|string',
            'conteudo_programatico_pp' => 'nullable|string',
            'objetivo_modulos' => 'nullable|string',
            'carga_horaria_pp' => 'nullable|string|max:100',
            'tempo_minimo_diario' => 'nullable|string|max:100',
            'prazo_maximo_conclusao' => 'nullable|string|max:100',
            'publico_alvo' => 'nullable|string',
            'material_didatico' => 'nullable|string',
            'instrumentos_aprendizado' => 'nullable|string',
            'avaliacao_aprendizagem' => 'nullable|string',
            'instrutores' => 'nullable|string|max:255',
            'infraestrutura_operacional' => 'nullable|string',
            'responsavel_tecnico_nome' => 'nullable|string|max:255',
            'responsavel_tecnico_qualificacao' => 'nullable|string|max:255',
            'data_validacao' => 'nullable|date',
            'data_proxima_revisao' => 'nullable|date|after_or_equal:data_validacao',
            'arquivo_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $selecionados = $this->validarTreinamentosDisponiveis($request->input('trainings', []), null);
        if ($selecionados === null) {
            return redirect()->back()
                ->withErrors(['trainings' => 'Um ou mais treinamentos selecionados já possuem projeto pedagógico. Para alterá-los, edite o PP específico do treinamento.'])
                ->withInput();
        }

        $dados = $this->coletarDados($request);

        $pp = new ProjetoPedagogico();
        $pp->fill($dados);
        $pp->training_id = $selecionados[0];
        $pp->save();

        $this->salvarAssinatura($request, $pp);
        $this->salvarArquivoPdf($request, $pp);
        $pp->trainings()->sync($selecionados);

        return redirect()->route('projetos-pedagogicos.index')->with('success', 'Projeto pedagógico cadastrado e vinculado aos treinamentos selecionados!');
    }

    /**
     * Formulário de edição de um projeto pedagógico (pode atender a vários treinamentos).
     */
    public function edit(ProjetoPedagogico $pp)
    {
        $pp->load('trainings');
        $templates = $this->getTemplates();
        $disponiveis = $this->availableTrainings($pp);
        $selecionados = $pp->training_ids;

        return view('projetos_pedagogicos.form', compact('pp', 'templates', 'disponiveis', 'selecionados'));
    }

    /**
     * Atualiza um projeto pedagógico e re-vincula os treinamentos selecionados.
     * Regra de segurança: treinamentos que já possuem outro PP não são alterados.
     */
    public function update(Request $request, ProjetoPedagogico $pp)
    {
        $request->validate([
            'trainings' => 'required|array|min:1',
            'trainings.*' => 'exists:trainings,id',
            'versao' => 'nullable|string|max:20',
            'objetivo_geral' => 'nullable|string',
            'principios_sst' => 'nullable|string',
            'estrategia_pedagogica' => 'nullable|string',
            'conteudo_programatico_pp' => 'nullable|string',
            'objetivo_modulos' => 'nullable|string',
            'carga_horaria_pp' => 'nullable|string|max:100',
            'tempo_minimo_diario' => 'nullable|string|max:100',
            'prazo_maximo_conclusao' => 'nullable|string|max:100',
            'publico_alvo' => 'nullable|string',
            'material_didatico' => 'nullable|string',
            'instrumentos_aprendizado' => 'nullable|string',
            'avaliacao_aprendizagem' => 'nullable|string',
            'instrutores' => 'nullable|string|max:255',
            'infraestrutura_operacional' => 'nullable|string',
            'responsavel_tecnico_nome' => 'nullable|string|max:255',
            'responsavel_tecnico_qualificacao' => 'nullable|string|max:255',
            'data_validacao' => 'nullable|date',
            'data_proxima_revisao' => 'nullable|date|after_or_equal:data_validacao',
            'arquivo_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $selecionados = $this->validarTreinamentosDisponiveis($request->input('trainings', []), $pp);
        if ($selecionados === null) {
            return redirect()->back()
                ->withErrors(['trainings' => 'Um ou mais treinamentos selecionados já possuem projeto pedagógico. Para alterá-los, edite o PP específico do treinamento.'])
                ->withInput();
        }

        $pp->fill($this->coletarDados($request));
        $pp->training_id = $selecionados[0];
        $pp->save();

        $this->salvarAssinatura($request, $pp);
        $this->salvarArquivoPdf($request, $pp);
        $pp->trainings()->sync($selecionados);

        return redirect()->route('projetos-pedagogicos.index')->with('success', 'Projeto pedagógico atualizado com sucesso!');
    }

    /**
     * Exclui um projeto pedagógico (os treinamentos ficam livres para novo vínculo).
     */
    public function destroy(ProjetoPedagogico $pp)
    {
        $pp->delete();

        return redirect()->route('projetos-pedagogicos.index')->with('success', 'Projeto pedagógico excluído com sucesso!');
    }

    /**
     * Gera o PDF padrão do projeto pedagógico com os itens do Anexo II 3.1.
     * O bloco de assinaturas é desenhado de forma nativa (TCPDF) para garantir
     * alinhamento e visual profissional (o HTML do TCPDF não suporta layout de imagem inline).
     */
    public function download(ProjetoPedagogico $pp)
    {
        $pp->load('trainings');

        $html = view('projetos_pedagogicos.pp_pdf', ['pp' => $pp, 'treinamentos' => $pp->trainings_list])->render();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Plataforma DSS');
        $pdf->SetAuthor('Plataforma DSS');
        $pdf->SetTitle('Projeto Pedagógico');
        $pdf->SetSubject('Projeto Pedagógico (NR-01 Anexo II 3.1)');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $this->desenharBlocoAssinaturas($pdf, $pp);
        $this->desenharRodape($pdf);

        $nome = 'projeto-pedagogico-' . \Illuminate\Support\Str::slug($pp->versao ?? '1.0') . '.pdf';

        return response($pdf->Output($nome, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $nome . '"');
    }

    /**
     * Desenha o bloco de assinaturas (responsável técnico × validação) com métodos nativos do TCPDF.
     * Utiliza uma página dedicada ao final do documento para garantir alinhamento e visual profissional.
     */
    private function desenharBlocoAssinaturas(TCPDF $pdf, ProjetoPedagogico $pp): void
    {
        $pdf->addPage();

        $pageWidth = $pdf->getPageWidth();
        $marginLeft = $pdf->getMargins()['left'];
        $marginRight = $pdf->getMargins()['right'];
        $colWidth = ($pageWidth - $marginLeft - $marginRight) / 2;

        $imgAlt = 22;
        $imgLarg = 60;
        $y0 = 40;

        // Título da página de aprovação
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(21, 59, 46);
        $pdf->SetY($y0 - 18);
        $pdf->Cell(0, 8, 'APROVAÇÃO E VALIDAÇÃO DO PROJETO PEDAGÓGICO', 0, 1, 'C');

        // Coluna 1: Responsável técnico (com imagem da assinatura)
        $x1 = $marginLeft + ($colWidth - $imgLarg) / 2;
        $centro1 = $marginLeft + $colWidth / 2;

        if ($pp->assinatura_rt) {
            $bin = $this->decodificarDataUri($pp->assinatura_rt);
            if ($bin !== null) {
                $pdf->Image('@' . $bin, $x1, $y0, $imgLarg, $imgAlt, 'JPEG', '', '', false, 300);
            }
        }

        $yLinha = $y0 + $imgAlt + 6;
        $this->desenharLinhaAssinatura($pdf, $centro1 - 55, $centro1 + 55, $yLinha);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(21, 59, 46);
        $pdf->SetXY($marginLeft, $yLinha + 3);
        $pdf->Cell($colWidth, 6, 'Responsável técnico pela capacitação', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(55, 65, 81);
        $nomeRt = $pp->assinatura_rt_nome ?? $pp->responsavel_tecnico_nome ?? '—';
        $linha1 = $nomeRt . ($pp->responsavel_tecnico_qualificacao ? "\n" . $pp->responsavel_tecnico_qualificacao : '');
        if ($pp->assinatura_rt_data) {
            $linha1 .= "\nAssinado em: " . $pp->assinatura_rt_data->format('d/m/Y H:i');
        }
        $pdf->SetX($marginLeft);
        $pdf->MultiCell($colWidth, 4.5, $linha1, 0, 'C');

        // Coluna 2: Validação (reserva o mesmo espaço da imagem para alinhar as linhas)
        $x2 = $marginLeft + $colWidth;
        $centro2 = $marginLeft + $colWidth + $colWidth / 2;

        $this->desenharLinhaAssinatura($pdf, $centro2 - 55, $centro2 + 55, $yLinha);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(21, 59, 46);
        $pdf->SetXY($x2, $yLinha + 3);
        $pdf->Cell($colWidth, 6, 'Validação do projeto pedagógico', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(55, 65, 81);
        $linha2 = ($pp->data_validacao ? 'Validado em: ' . $pp->data_validacao->format('d/m/Y') : '—')
            . "\nRevisão prevista: " . ($pp->data_proxima_revisao?->format('d/m/Y') ?? '—');
        $pdf->SetX($x2);
        $pdf->MultiCell($colWidth, 4.5, $linha2, 0, 'C');
    }

    /**
     * Desenha uma linha de assinatura horizontal.
     */
    private function desenharLinhaAssinatura(TCPDF $pdf, float $xInicio, float $xFim, float $y): void
    {
        $pdf->SetDrawColor(55, 65, 81);
        $pdf->SetLineWidth(0.5);
        $pdf->Line($xInicio, $y, $xFim, $y);
    }

    /**
     * Desenha o rodapé do documento de forma nativa.
     */
    private function desenharRodape(TCPDF $pdf): void
    {
        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();
        $marginLeft = $pdf->getMargins()['left'];

        $y = $pageHeight - 25;
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($marginLeft, $y, $pageWidth - $marginLeft, $y);

        $pdf->SetY($y + 3);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->Cell(0, 4, 'Documento gerado eletronicamente pela Plataforma DSS em ' . now()->format('d/m/Y H:i') . ' — válido conforme NR-01 Anexo II, item 4.1.', 0, 0, 'C');
    }

    /**
     * Extrai o conteúdo binário de uma data URI (ex.: data:image/jpeg;base64,...).
     */
    private function decodificarDataUri(string $dataUri): ?string
    {
        $partes = explode(',', $dataUri, 2);
        $bin = base64_decode($partes[1] ?? '');

        return ($bin !== false && $bin !== '') ? $bin : null;
    }

    /**
     * Baixa o PDF assinado enviado pelo responsável técnico (se houver).
     */
    public function downloadArquivo(ProjetoPedagogico $pp)
    {
        if (!$pp->arquivo_pdf || !Storage::disk('public')->exists($pp->arquivo_pdf)) {
            abort(404, 'Arquivo do projeto pedagógico não encontrado.');
        }

        return response()->download(Storage::disk('public')->path($pp->arquivo_pdf));
    }

    /**
     * Treinamentos disponíveis para vincular a um PP (que ainda não possuem PP).
     * Na edição, os treinamentos já vinculados a este PP continuam selecionáveis.
     */
    private function availableTrainings(?ProjetoPedagogico $pp = null)
    {
        $ocupados = $this->trainingIdsOcupados();

        if ($pp) {
            $ocupados = $ocupados->diff($pp->training_ids);
        }

        $query = Training::orderBy('titulo');

        if (!$ocupados->isEmpty()) {
            $query->whereNotIn('id', $ocupados);
        }

        return $query->get();
    }

    /**
     * IDs de todos os treinamentos que já possuem projeto pedagógico (pivot + vínculo legado).
     */
    private function trainingIdsOcupados()
    {
        $ids = collect();

        if (Schema::hasTable('projeto_pedagogico_trainings')) {
            $ids = $ids->merge(DB::table('projeto_pedagogico_trainings')->pluck('training_id'));
        }

        if (Schema::hasTable('training_projetos_pedagogicos') && Schema::hasColumn('training_projetos_pedagogicos', 'training_id')) {
            $ids = $ids->merge(DB::table('training_projetos_pedagogicos')->whereNotNull('training_id')->pluck('training_id'));
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values();
    }

    /**
     * Valida que os treinamentos selecionados estão disponíveis (regra de segurança).
     * Retorna a lista validada ou null quando algum já pertence a outro PP.
     */
    private function validarTreinamentosDisponiveis(array $selecionados, ?ProjetoPedagogico $pp)
    {
        $selecionados = array_values(array_unique(array_map('intval', $selecionados)));

        if (empty($selecionados)) {
            return null;
        }

        $disponiveisIds = $this->availableTrainings($pp)->pluck('id')->all();

        foreach ($selecionados as $id) {
            if (!in_array($id, $disponiveisIds)) {
                return null;
            }
        }

        return $selecionados;
    }

    /**
     * Coleta os campos de estruturação pedagógica e identificação do formulário.
     */
    private function coletarDados(Request $request): array
    {
        $dados = $request->only([
            'versao', 'objetivo_geral', 'principios_sst', 'estrategia_pedagogica', 'conteudo_programatico_pp',
            'objetivo_modulos', 'carga_horaria_pp', 'tempo_minimo_diario', 'prazo_maximo_conclusao',
            'publico_alvo', 'material_didatico', 'instrumentos_aprendizado', 'avaliacao_aprendizagem',
            'instrutores', 'infraestrutura_operacional', 'responsavel_tecnico_nome', 'responsavel_tecnico_qualificacao',
            'data_validacao', 'data_proxima_revisao',
        ]);

        // Se não informou a data de revisão, sugere automaticamente 2 anos após a validação (Anexo II 3.3)
        if (empty($dados['data_proxima_revisao']) && !empty($dados['data_validacao'])) {
            $dados['data_proxima_revisao'] = \Carbon\Carbon::parse($dados['data_validacao'])->addYears(2)->format('Y-m-d');
        }

        return $dados;
    }

    /**
     * Processa a assinatura do responsável técnico (canvas) ou a remoção da assinatura salva.
     */
    private function salvarAssinatura(Request $request, ProjetoPedagogico $pp): void
    {
        if ($request->boolean('remover_assinatura')) {
            $pp->assinatura_rt = null;
            $pp->assinatura_rt_nome = null;
            $pp->assinatura_rt_data = null;
            $pp->save();

            return;
        }

        if ($request->filled('assinatura_rt')) {
            $assinatura = trim((string) $request->input('assinatura_rt'));

            if (str_starts_with($assinatura, 'data:image')) {
                $pp->assinatura_rt = $this->normalizarAssinaturaParaPdf($assinatura);
                $pp->assinatura_rt_nome = auth()->user()->nome ?? $pp->responsavel_tecnico_nome;
                $pp->assinatura_rt_data = now();
                $pp->save();
            }
        }
    }

    /**
     * Processa o upload opcional do PDF assinado.
     */
    private function salvarArquivoPdf(Request $request, ProjetoPedagogico $pp): void
    {
        if ($request->hasFile('arquivo_pdf')) {
            $arquivo = $request->file('arquivo_pdf');
            $caminho = $arquivo->storeAs(
                'projetos-pedagogicos/pp-' . $pp->id,
                'projeto-pedagogico-' . $pp->id . '-' . time() . '.pdf',
                'public'
            );
            $pp->arquivo_pdf = $caminho;
            $pp->save();
        }
    }

    /**
     * Converte a assinatura em data URI para JPEG (sem canal alfa) usando GD,
     * garantindo a renderização correta no PDF gerado pelo TCPDF.
     */
    private function normalizarAssinaturaParaPdf(string $dataUri): string
    {
        $partes = explode(',', $dataUri, 2);
        $binario = base64_decode($partes[1] ?? '');

        if ($binario === false || $binario === '') {
            return $dataUri;
        }

        if (!function_exists('imagecreatefromstring')) {
            return $dataUri;
        }

        $imagem = @imagecreatefromstring($binario);
        if ($imagem === false) {
            return $dataUri;
        }

        ob_start();
        imagejpeg($imagem, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($imagem);

        if ($jpeg === false || $jpeg === '') {
            return $dataUri;
        }

        return 'data:image/jpeg;base64,' . base64_encode($jpeg);
    }

    /**
     * Modelos de preenchimento automático do Projeto Pedagógico por tipo de treinamento.
     * O gestor seleciona o treinamento no formulário e o sistema pré-preenche os campos
     * do Anexo II 3.1 para análise e ajuste antes de salvar.
     */
    private function getTemplates(): array
    {
        $infra = 'Ambiente Virtual de Aprendizagem (AVA) da Plataforma DSS, com acesso individual por CPF e senha, registro de tempo de estudo (logs), bloqueio de adiantamento de vídeo, re-identificação por senha antes da avaliação, emissão de certificado com QR de validação e canal de dúvidas via WhatsApp.';
        $avaliacaoBase = 'Prova online com questões de múltipla escolha envolvendo situações práticas da rotina. Aprovação com nota mínima de 70% (conceito satisfatório), com até 2 tentativas; após 2 falhas, o aluno reassiste o conteúdo e refaz a avaliação.';
        $instrumentos = 'Videoaula, quiz de fixação, estudo de casos reais da rotina, material didático em PDF e canal de dúvidas via WhatsApp.';

        return [
            'nr06' => [
                'nome' => 'NR-06 — EPI (Equipamento de Proteção Individual)',
                'objetivo_geral' => 'Capacitar os colaboradores a identificar, selecionar, usar, higienizar e conservar corretamente os Equipamentos de Proteção Individual (EPI), conforme a NR-06, promovendo a prevenção de acidentes e doenças ocupacionais.',
                'principios_sst' => 'O curso adota os princípios da NR-01 (itens 1.4.1 e 1.5), priorizando a eliminação e o controle dos riscos e o uso do EPI como medida complementar de proteção, com participação do trabalhador na prevenção.',
                'estrategia_pedagogica' => 'EAD com videoaula, apresentação narrada, material didático em PDF e avaliação final por múltipla escolha; a parte prática (ajuste, vestimenta e conservação) é demonstrada em vídeo e verificada por situações da rotina na avaliação.',
                'conteudo_programatico_pp' => "1. O que é EPI e sua finalidade (NR-06);\n2. Tipos de EPI e riscos protegidos (cabeça, olhos, face, vias respiratórias, audição, mãos, pés e corpo);\n3. Certificado de Aprovação (CA) e verificação de validade;\n4. Seleção do EPI adequado à atividade;\n5. Uso correto, ajuste e vestimenta;\n6. Higienização, guarda e conservação;\n7. Responsabilidades do empregador e do empregado;\n8. Treinamento e revisão periódica.",
                'objetivo_modulos' => "Módulo 1 – Conceitos: ao final, o aluno identifica o que é EPI e sua finalidade. Módulo 2 – Tipos e riscos: ao final, o aluno associa cada EPI ao risco correspondente. Módulo 3 – CA e seleção: ao final, o aluno verifica a validade do CA e escolhe o EPI adequado. Módulo 4 – Uso e conservação: ao final, o aluno sabe ajustar, higienizar e guardar o EPI.",
                'carga_horaria_pp' => '04 horas',
                'tempo_minimo_diario' => '30 minutos',
                'prazo_maximo_conclusao' => '15 dias corridos a partir da liberação',
                'publico_alvo' => 'Todos os colaboradores operacionais e administrativos, motoristas e terceirizados que utilizam EPI nas suas atividades.',
                'material_didatico' => 'Apostila em PDF, vídeo da aula, check-list de verificação do EPI e guia de referência rápida de uso e conservação.',
                'instrumentos_aprendizado' => $instrumentos,
                'avaliacao_aprendizagem' => $avaliacaoBase . ' Ex.: identificar o EPI correto para cada atividade e a validade do CA.',
                'infraestrutura_operacional' => $infra,
            ],
            'plano_emergencia' => [
                'nome' => 'Plano de Emergência (NR-01 1.5.6 / NR-23)',
                'objetivo_geral' => 'Capacitar os colaboradores a agir corretamente em situações de emergência (abandono, princípio de incêndio, primeiros socorros e acionamento dos recursos), conforme o plano de emergência da empresa e a NR-01 (1.5.6) e a NR-23.',
                'principios_sst' => 'O curso adota os princípios da NR-01 (itens 1.4.3, 1.5.6 e 1.5.7), preparando o trabalhador para resposta a cenários de emergência e priorizando a preservação da vida e da saúde.',
                'estrategia_pedagogica' => 'EAD com videoaula, simulações narradas de cenários de emergência, material didático em PDF e avaliação final com situações práticas da rotina de evacuação e resposta.',
                'conteudo_programatico_pp' => "1. Conceitos de emergência e plano de abandono;\n2. Cenários de emergência da empresa (incêndio, vazamento, emergência médica, ameaças);\n3. Rotas de fuga, pontos de encontro e alarmes;\n4. Acionamento da brigada, bombeiros e atendimento de emergência;\n5. Procedimentos de abandono e reunião em ponto seguro;\n6. Noções de primeiros socorros e encaminhamento de acidentados;\n7. Responsabilidades dos ocupantes e da brigada;\n8. Exercícios simulados e revisão periódica.",
                'objetivo_modulos' => "Módulo 1 – Conceitos: ao final, o aluno identifica os cenários de emergência e o plano da empresa. Módulo 2 – Rotas e alarmes: ao final, o aluno sabe os caminhos de fuga e o significado dos alarmes. Módulo 3 – Resposta: ao final, o aluno executa o abandono seguro e aciona os recursos corretos. Módulo 4 – Primeiros socorros: ao final, o aluno aplica noções básicas de atendimento e encaminhamento.",
                'carga_horaria_pp' => '04 horas',
                'tempo_minimo_diario' => '30 minutos',
                'prazo_maximo_conclusao' => '15 dias corridos a partir da liberação',
                'publico_alvo' => 'Todos os colaboradores do estabelecimento, incluindo membros da brigada de incêndio e equipe de emergência.',
                'material_didatico' => 'Apostila em PDF, vídeo da aula, planta de rotas de fuga e guia de resposta a emergências.',
                'instrumentos_aprendizado' => $instrumentos,
                'avaliacao_aprendizagem' => $avaliacaoBase . ' Ex.: identificar a rota de fuga correta e a sequência de acionamento em um cenário de incêndio.',
                'infraestrutura_operacional' => $infra,
            ],
            'primeiros_socorros' => [
                'nome' => 'Primeiros Socorros',
                'objetivo_geral' => 'Capacitar os colaboradores a prestar os primeiros socorros de forma segura e acionar os recursos adequados até a chegada do atendimento especializado.',
                'principios_sst' => 'O curso adota os princípios da NR-01 (1.5.6) e da NR-07, com foco na preservação da vida, na segurança do socorrista e no encaminhamento adequado do acidentado.',
                'estrategia_pedagogica' => 'EAD com videoaula, demonstração em vídeo das técnicas, material didático em PDF e avaliação com situações práticas de atendimento.',
                'conteudo_programatico_pp' => "1. Legislação e responsabilidades;\n2. Avaliação da cena e segurança do socorrista;\n3. Acionamento do serviço de emergência (SAMU 192 / bombeiros 193);\n4. Desobstrução de vias aéreas e posição lateral de segurança;\n5. Ressuscitação cardiopulmonar (RCP) e uso de DEA;\n6. Hemorragias, ferimentos, queimaduras e fraturas;\n7. Convulsões, desmaios e engasgos;\n8. Limites do socorrista leigo e transferência do cuidado.",
                'objetivo_modulos' => "Módulo 1 – Cena e acionamento: ao final, o aluno avalia a cena com segurança e aciona o socorro. Módulo 2 – Suporte básico: ao final, o aluno aplica RCP, posição lateral e desobstrução. Módulo 3 – Traumas: ao final, o aluno age diante de hemorragias, queimaduras e fraturas. Módulo 4 – Casos específicos: ao final, o aluno atende engasgos, desmaios e convulsões.",
                'carga_horaria_pp' => '04 horas',
                'tempo_minimo_diario' => '30 minutos',
                'prazo_maximo_conclusao' => '15 dias corridos a partir da liberação',
                'publico_alvo' => 'Colaboradores voluntários da equipe de primeiros socorros e demais interessados, conforme definição da empresa.',
                'material_didatico' => 'Apostila em PDF, vídeo da aula e cartão de referência rápida de procedimentos de emergência.',
                'instrumentos_aprendizado' => $instrumentos,
                'avaliacao_aprendizagem' => $avaliacaoBase . ' Ex.: indicar a conduta correta diante de um engasgo ou de uma hemorragia grave.',
                'infraestrutura_operacional' => $infra,
            ],
            'combate_incendio' => [
                'nome' => 'Combate a Incêndio',
                'objetivo_geral' => 'Capacitar os colaboradores a identificar princípios de incêndio, operar corretamente os extintores e agir de forma segura na proteção de pessoas e patrimônio.',
                'principios_sst' => 'O curso adota os princípios da NR-01 (1.5.6) e da NR-23, com foco na prevenção, no combate inicial e na evacuação segura.',
                'estrategia_pedagogica' => 'EAD com videoaula, demonstração em vídeo do uso de extintores e avaliação com situações práticas de decisão.',
                'conteudo_programatico_pp' => "1. Teoria do fogo (triângulo do fogo);\n2. Classes de incêndio (A, B, C e D);\n3. Métodos de extinção;\n4. Tipos de extintores e agentes extintores;\n5. Técnica de uso do extintor (P.A.S.S.);\n6. Hidrantes e rede de combate a incêndio;\n7. Procedimentos em caso de incêndio e evacuação;\n8. Papel da brigada de incêndio.",
                'objetivo_modulos' => "Módulo 1 – Teoria do fogo: ao final, o aluno identifica os elementos e as classes de incêndio. Módulo 2 – Equipamentos: ao final, o aluno seleciona o extintor correto para cada classe. Módulo 3 – Combate: ao final, o aluno opera o extintor pela técnica P.A.S.S. Módulo 4 – Evacuação: ao final, o aluno executa o abandono seguro e aciona a brigada.",
                'carga_horaria_pp' => '04 horas',
                'tempo_minimo_diario' => '30 minutos',
                'prazo_maximo_conclusao' => '15 dias corridos a partir da liberação',
                'publico_alvo' => 'Todos os colaboradores e, especialmente, os membros da brigada de incêndio da empresa.',
                'material_didatico' => 'Apostila em PDF, vídeo da aula e guia ilustrado de uso dos extintores.',
                'instrumentos_aprendizado' => $instrumentos,
                'avaliacao_aprendizagem' => $avaliacaoBase . ' Ex.: escolher o extintor correto para um incêndio classe B e aplicar a técnica de uso.',
                'infraestrutura_operacional' => $infra,
            ],
            'seguranca_conducao' => [
                'nome' => 'DSS — Segurança na Condução',
                'objetivo_geral' => 'Reforçar a condução defensiva e o cumprimento das normas de trânsito para reduzir acidentes e riscos na operação de veículos.',
                'principios_sst' => 'O curso adota os princípios da NR-01 (1.4.1 e 1.5), aplicados à gestão de riscos no trânsito e à segurança dos condutores.',
                'estrategia_pedagogica' => 'EAD com videoaula, material didático em PDF e avaliação com situações práticas de direção defensiva.',
                'conteudo_programatico_pp' => "1. Direção defensiva e preventiva;\n2. Legislação de trânsito aplicada;\n3. Riscos no trânsito e fatores humanos (fadiga, distração, álcool);\n4. Manutenção preventiva do veículo;\n5. Procedimentos em caso de acidente;\n6. Comunicação e registro de ocorrências.",
                'objetivo_modulos' => "Módulo 1 – Conceitos: ao final, o aluno entende os princípios da direção defensiva. Módulo 2 – Riscos: ao final, o aluno identifica fatores de risco no trânsito. Módulo 3 – Prática: ao final, o aluno aplica técnicas preventivas de condução e pós-acidente.",
                'carga_horaria_pp' => '02 horas',
                'tempo_minimo_diario' => '20 minutos',
                'prazo_maximo_conclusao' => '10 dias corridos a partir da liberação',
                'publico_alvo' => 'Motoristas e demais colaboradores que conduzem veículos da empresa.',
                'material_didatico' => 'Apostila em PDF e vídeo da aula.',
                'instrumentos_aprendizado' => $instrumentos,
                'avaliacao_aprendizagem' => $avaliacaoBase . ' Ex.: identificar a conduta correta diante de uma situação de risco no trânsito.',
                'infraestrutura_operacional' => $infra,
            ],
            'normas_seguranca' => [
                'nome' => 'Normas de Segurança (Conteúdo Geral)',
                'objetivo_geral' => 'Conscientizar os colaboradores sobre as normas de segurança e saúde no trabalho e sobre a importância da prevenção de acidentes e doenças ocupacionais.',
                'principios_sst' => 'O curso adota os princípios da NR-01 (1.4 e 1.5): comunicação dos riscos, medidas de prevenção e participação do trabalhador.',
                'estrategia_pedagogica' => 'EAD com videoaula, material didático em PDF e avaliação final por múltipla escolha.',
                'conteudo_programatico_pp' => "1. Noções de legislação de SST (NRs);\n2. Riscos ocupacionais e formas de prevenção;\n3. Ordens de serviço e comunicação de riscos;\n4. Uso de EPI e medidas coletivas;\n5. Procedimentos em situação de emergência;\n6. Responsabilidades do empregador e do empregado.",
                'objetivo_modulos' => "Módulo 1 – Legislação: ao final, o aluno compreende o papel das NRs. Módulo 2 – Riscos: ao final, o aluno identifica riscos e medidas de prevenção. Módulo 3 – Responsabilidades: ao final, o aluno conhece seus deveres e direitos em SST.",
                'carga_horaria_pp' => '02 horas',
                'tempo_minimo_diario' => '20 minutos',
                'prazo_maximo_conclusao' => '10 dias corridos a partir da liberação',
                'publico_alvo' => 'Todos os colaboradores da empresa, conforme definição do empregador.',
                'material_didatico' => 'Apostila em PDF e vídeo da aula.',
                'instrumentos_aprendizado' => $instrumentos,
                'avaliacao_aprendizagem' => $avaliacaoBase . ' Ex.: identificar o procedimento correto diante de um risco identificado no posto de trabalho.',
                'infraestrutura_operacional' => $infra,
            ],
            'generico' => [
                'nome' => 'Modelo Genérico',
                'objetivo_geral' => 'Capacitar os colaboradores nos conteúdos e procedimentos descritos no conteúdo programático deste treinamento, promovendo a segurança e a saúde no trabalho.',
                'principios_sst' => 'O curso adota os princípios da NR-01 (itens 1.4.1 e 1.5): comunicação dos riscos, hierarquia das medidas de prevenção e participação dos trabalhadores.',
                'estrategia_pedagogica' => 'EAD com videoaula, material didático em PDF e avaliação final por múltipla escolha com situações práticas da rotina.',
                'conteudo_programatico_pp' => '1. Introdução ao tema; 2. Conceitos e legislação aplicável; 3. Procedimentos operacionais; 4. Riscos e medidas de prevenção; 5. Avaliação e revisão do conteúdo.',
                'objetivo_modulos' => 'Cada módulo apresenta um tema do conteúdo programático, com objetivo de aprendizagem específico e verificação ao final de cada bloco.',
                'carga_horaria_pp' => '02 horas',
                'tempo_minimo_diario' => '20 minutos',
                'prazo_maximo_conclusao' => '10 dias corridos a partir da liberação',
                'publico_alvo' => 'Colaboradores indicados pelo empregador para este treinamento.',
                'material_didatico' => 'Apostila em PDF e vídeo da aula.',
                'instrumentos_aprendizado' => $instrumentos,
                'avaliacao_aprendizagem' => $avaliacaoBase,
                'infraestrutura_operacional' => $infra,
            ],
        ];
    }
}