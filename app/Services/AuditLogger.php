<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\EmployeeTraining;
use App\Models\Epi;
use App\Models\EpiEntrega;
use App\Models\ProjetoPedagogico;
use App\Models\RankingCriterion;
use App\Models\RankingRule;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\SocialPost;
use App\Models\SplashContent;
use App\Models\Tenant;
use App\Models\Training;
use App\Models\TrainingMaterial;
use App\Models\User;
use App\Models\UserVacation;
use App\Support\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trilha de auditoria: grava quem fez o quê, quando e de onde.
 * Nunca lança exceção — falha de auditoria não pode quebrar o fluxo principal.
 */
class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'qrcode_token',
        'token',
        'access_token',
        'api_key',
        'secret',
    ];

    private const MAX_VALUE_LENGTH = 500;

    private const MODULES = [
        User::class => 'users',
        Role::class => 'permissions',
        RolePermission::class => 'permissions',
        Tenant::class => 'plataforma',
        Training::class => 'trainings',
        TrainingMaterial::class => 'trainings',
        Certificate::class => 'certificates',
        UserVacation::class => 'users',
        EmployeeTraining::class => 'users',
        SplashContent::class => 'splash',
        SocialPost::class => 'social',
        ProjetoPedagogico::class => 'projeto_pedagogico',
        Epi::class => 'epi',
        EpiEntrega::class => 'epi',
        RankingRule::class => 'rankings',
        RankingCriterion::class => 'rankings',
    ];

    private const EVENT_LABELS = [
        'created' => 'Criou',
        'updated' => 'Atualizou',
        'deleted' => 'Excluiu',
    ];

    public function log(string $event, array $context = []): ?AuditLog
    {
        try {
            $request = request();

            return AuditLog::create([
                'tenant_id' => $context['tenant_id'] ?? app(TenantManager::class)->id(),
                'user_id' => $context['user_id'] ?? (Auth::id() ?: $request->user()?->id),
                'event' => $event,
                'module' => $context['module'] ?? null,
                'auditable_type' => $context['auditable_type'] ?? null,
                'auditable_id' => $context['auditable_id'] ?? null,
                'description' => $this->truncate($context['description'] ?? null),
                'old_values' => $this->sanitize($context['old_values'] ?? null),
                'new_values' => $this->sanitize($context['new_values'] ?? null),
                'ip' => $context['ip'] ?? $request->ip(),
                'user_agent' => $this->truncate($request->userAgent(), 500),
                'route' => $context['route'] ?? ($request->route()?->getName() ?: $request->path()),
                'method' => $context['method'] ?? $request->method(),
            ]);
        } catch (\Throwable $e) {
            $this->reportFailure($e);

            return null;
        }
    }

    public function logModel(string $event, Model $model): ?AuditLog
    {
        try {
            if ($model instanceof AuditLog) {
                return null;
            }

            $changes = method_exists($model, 'getChanges') ? $model->getChanges() : [];
            unset($changes['updated_at'], $changes['created_at']);

            if ($event === 'updated' && empty($changes)) {
                return null;
            }

            if ($event === 'created') {
                $newValues = Arr::except($model->getAttributes(), ['created_at', 'updated_at']);
                $oldValues = null;
            } elseif ($event === 'deleted') {
                $newValues = null;
                $oldValues = Arr::except($model->getAttributes(), ['created_at', 'updated_at']);
            } else {
                $newValues = $changes;
                $oldValues = Arr::only($model->getOriginal(), array_keys($changes));
            }

            $label = self::EVENT_LABELS[$event] ?? ucfirst($event);
            $identifier = $model->getAttribute('nome')
                ?? $model->getAttribute('titulo')
                ?? $model->getAttribute('codigo_certificado')
                ?? ('#'.$model->getKey());

            return $this->log($event, [
                'module' => self::MODULES[get_class($model)] ?? Str::snake(class_basename($model)),
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'description' => $label.' '.class_basename($model).': '.$identifier,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            $this->reportFailure($e);

            return null;
        }
    }

    private function sanitize($values)
    {
        if (! is_array($values)) {
            return $this->truncate($values);
        }

        $result = [];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $result[$key] = '***';

                continue;
            }

            $result[$key] = is_array($value) ? $this->sanitize($value) : $this->truncate($value);
        }

        return $result;
    }

    private function truncate($value, int $limit = self::MAX_VALUE_LENGTH): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE) ?: null;
        }

        $value = (string) $value;

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    private function reportFailure(\Throwable $e): void
    {
        try {
            Log::channel('system')->warning('audit_log_failed', [
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $ignored) {
            // nunca relançar
        }
    }
}
