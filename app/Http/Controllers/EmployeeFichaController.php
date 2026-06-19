<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmployeeTraining;
use App\Models\EmployeeEpi;
use Illuminate\Http\Request;
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
            'employeeEpis' => function ($query) {
                $query->orderBy('data_entrega', 'desc');
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

        return view('ficha_publica', compact('usuario', 'dssCertificates', 'plataformaTrainings'));
    }

    public function manage($id)
    {
        $usuario = User::with([
            'employeeTrainings' => function ($query) {
                $query->orderBy('data_treinamento', 'desc');
            },
            'employeeEpis' => function ($query) {
                $query->orderBy('data_entrega', 'desc');
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

    public function storeEpi(Request $request, $userId)
    {
        $usuario = User::findOrFail($userId);

        $request->validate([
            'nome' => 'required|string|max:255',
            'ca' => 'nullable|string|max:255',
            'quantidade' => 'required|integer|min:1',
            'data_entrega' => 'required|date',
            'observacoes' => 'nullable|string',
        ]);

        $usuario->employeeEpis()->create($request->all());

        return redirect()->back()->with('success', 'Entrega de EPI registrada com sucesso!');
    }

    public function destroyEpi($id)
    {
        $epi = EmployeeEpi::findOrFail($id);
        $epi->delete();

        return redirect()->back()->with('success', 'EPI removido com sucesso!');
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
