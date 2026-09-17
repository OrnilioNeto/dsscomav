<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditoriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:auditoria,view');
    }

    public function index(Request $request)
    {
        $logs = $this->filtered($request)->paginate(25)->withQueryString();

        $eventos = AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event');
        $modulos = AuditLog::query()->whereNotNull('module')->select('module')->distinct()->orderBy('module')->pluck('module');

        $userIds = AuditLog::query()->whereNotNull('user_id')->select('user_id')->distinct()->pluck('user_id');
        $usuarios = User::whereIn('id', $userIds)->orderBy('nome')->get(['id', 'nome']);

        return view('admin.auditoria.index', compact('logs', 'eventos', 'modulos', 'usuarios'));
    }

    public function export(Request $request): StreamedResponse
    {
        app(AuditLogger::class)->log('exported', [
            'module' => 'auditoria',
            'description' => 'Exportou a trilha de auditoria em CSV',
        ]);

        $query = $this->filtered($request);
        $filename = 'auditoria-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Data/Hora', 'Usuário', 'Evento', 'Módulo', 'Descrição',
                'Registro', 'IP', 'Rota', 'Método', 'Valores anteriores', 'Valores novos',
            ], ';');

            $query->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->created_at?->format('d/m/Y H:i:s'),
                        $log->user?->nome ?? '—',
                        $log->event,
                        $log->module,
                        $log->description,
                        $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '',
                        $log->ip,
                        $log->route,
                        $log->method,
                        $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '',
                        $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '',
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        $query = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('inicio')) {
            $query->whereDate('created_at', '>=', $request->input('inicio'));
        }

        if ($request->filled('fim')) {
            $query->whereDate('created_at', '<=', $request->input('fim'));
        }

        if ($request->filled('busca')) {
            $busca = trim((string) $request->input('busca'));

            $query->where(function ($q) use ($busca) {
                $q->where('description', 'like', '%'.$busca.'%')
                    ->orWhere('route', 'like', '%'.$busca.'%')
                    ->orWhere('ip', 'like', '%'.$busca.'%');
            });
        }

        return $query;
    }
}
