<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmployeeTraining;
use App\Models\EpiColaborador;
use App\Models\EpiEntrega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmployeeFichaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users,edit')->except('showPublic');
    }

    public function showPublic($token)
    {
        $usuario = User::with([
            'employeeTrainings' => function ($query) {
                $query->orderBy('data_treinamento', 'desc');
            },
            'certificates.training'
        ])->where('qrcode_token', $token)->firstOrFail();

        // Filtrar certificados da plataforma
        $dssCertificates = $usuario->certificates->filter(function ($cert) {
            return $cert->training && $cert->training->tipo === 'dss';
        })->sortByDesc('data_emissao');

        $plataformaTrainings = $usuario->certificates->filter(function ($cert) {
            return $cert->training && $cert->training->tipo === 'treinamento';
        })->sortByDesc('data_emissao');

        // Entregas de EPI registradas no módulo de Saúde e Segurança (NR-06),
        // vinculadas ao colaborador pelo CPF. Somente registros ativos.
        $epiModuloEntregas = collect();
        if (Schema::hasTable('ss_colaborador') && Schema::hasTable('ss_epi_entrega')) {
            $colaborador = EpiColaborador::where('ss_c_tx_cpf', $usuario->cpf)->first();
            if ($colaborador) {
                $epiModuloEntregas = EpiEntrega::with(['epi', 'variacao'])
                    ->where('ss_e_nb_colaborador_id', $colaborador->ss_c_nb_id)
                    ->where('ss_e_tx_status', '<>', 'inativo')
                    ->orderBy('ss_e_tx_data_entrega', 'desc')
                    ->orderBy('ss_e_nb_id', 'desc')
                    ->get();
            }
        }

        return view('ficha_publica', compact('usuario', 'dssCertificates', 'plataformaTrainings', 'epiModuloEntregas'));
    }

    public function manage($id)
    {
        $usuario = User::with([
            'employeeTrainings' => function ($query) {
                $query->orderBy('data_treinamento', 'desc');
            }
        ])->findOrFail($id);

        return view('usuarios.ficha_manage', compact('usuario'));
    }

    public function storeTraining(Request $request, $userId)
    {
        $usuario = User::findOrFail($userId);

        $request->validate([
            'nome' => 'required|string|max:255',
            'data_treinamento' => 'required|date',
            'data_validade' => 'nullable|date|after_or_equal:data_treinamento',
            'observacoes' => 'nullable|string',
        ]);

        $usuario->employeeTrainings()->create($request->all());

        return redirect()->back()->with('success', 'Treinamento registrado com sucesso!');
    }

    public function destroyTraining($id)
    {
        $training = EmployeeTraining::findOrFail($id);
        $training->delete();

        return redirect()->back()->with('success', 'Treinamento removido com sucesso!');
    }

    public function regenerateToken($userId)
    {
        $usuario = User::findOrFail($userId);
        $usuario->update([
            'qrcode_token' => Str::random(32)
        ]);

        return redirect()->back()->with('success', 'Novo QR Code gerado com sucesso!');
    }
}
