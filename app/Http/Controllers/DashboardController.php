<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $this->dashboardSuperAdmin();
        } elseif ($user->isAdmin()) {
            return $this->dashboardAdmin();
        } else {
            return $this->dashboardUser();
        }
    }

    private function dashboardSuperAdmin()
    {
        $totalUsuarios = User::count();
        $totalTreinamentos = Training::count();
        $usuariosAtivos = User::where('status', 'ativo')->count();
        $certificadosEmitidos = Certificate::count();

        $usuariosPorTipo = User::groupBy('tipo_usuario')
            ->selectRaw('tipo_usuario, count(*) as total')
            ->get();

        $taxaConclusao = [];
        $treinamentos = Training::where('status', 'ativo')->take(5)->get();
        foreach ($treinamentos as $training) {
            $taxaConclusao[$training->id] = $training->getTaxaConclusao();
        }

        return view('dashboard.super_admin', [
            'totalUsuarios' => $totalUsuarios,
            'totalTreinamentos' => $totalTreinamentos,
            'usuariosAtivos' => $usuariosAtivos,
            'certificadosEmitidos' => $certificadosEmitidos,
            'usuariosPorTipo' => $usuariosPorTipo,
            'taxaConclusao' => $taxaConclusao,
            'treinamentos' => $treinamentos,
        ]);
    }

    private function dashboardAdmin()
    {
        $totalUsuarios = User::where('role_id', '<>', 1)->count();
        $totalTreinamentos = Training::count();
        $usuariosAtivos = User::where('status', 'ativo')->count();
        $certificadosEmitidos = Certificate::count();

        $treinamentosRecentes = Training::orderBy('created_at', 'desc')->take(5)->get();
        $usuariosRecentes = User::where('role_id', '<>', 1)->orderBy('created_at', 'desc')->take(5)->get();

        return view('dashboard.admin', [
            'totalUsuarios' => $totalUsuarios,
            'totalTreinamentos' => $totalTreinamentos,
            'usuariosAtivos' => $usuariosAtivos,
            'certificadosEmitidos' => $certificadosEmitidos,
            'treinamentosRecentes' => $treinamentosRecentes,
            'usuariosRecentes' => $usuariosRecentes,
        ]);
    }

    private function dashboardUser()
    {
        $user = auth()->user();

        $treinamentosDisponíveis = Training::where('status', 'ativo')
            ->get()
            ->filter(fn($t) => $user->canAccessTraining($t));

        $progresso = UserProgress::where('user_id', $user->id)->get();
        $certificados = Certificate::where('user_id', $user->id)->where('valido', true)->count();

        $tempoTotal = $progresso->sum('tempo_assistido');
        $tempoFormatado = gmdate('H:i:s', $tempoTotal);

        $treinamentosCompletos = $progresso->where('concluido', true)->count();

        // Separar em categorias
        $treinamentosConcluidos = [];
        $treinamentosPendentes = [];
        $treinamentosNaoIniciados = [];

        foreach ($treinamentosDisponíveis as $training) {
            $userProgress = $progresso->where('training_id', $training->id)->first();
            
            if (!$userProgress) {
                $treinamentosNaoIniciados[] = $training;
            } elseif ($userProgress->concluido) {
                $treinamentosConcluidos[] = $training;
            } else {
                $treinamentosPendentes[] = $training;
            }
        }

        return view('dashboard.usuario', [
            'treinamentosDisponíveis' => $treinamentosDisponíveis,
            'treinamentosConcluidos' => $treinamentosConcluidos,
            'treinamentosPendentes' => $treinamentosPendentes,
            'treinamentosNaoIniciados' => $treinamentosNaoIniciados,
            'progresso' => $progresso,
            'certificados' => $certificados,
            'tempoTotal' => $tempoFormatado,
            'treinamentosCompletos' => $treinamentosCompletos,
        ]);
    }
}
