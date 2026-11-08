<?php

namespace App\Modules\Frota\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FrotaConfiguracao extends Model
{
    use BelongsToTenant;

    protected $table = 'frota_configuracoes';

    protected $fillable = [
        'tenant_id',
        'ativo',
        'base_url',
        'account_code',
        'subscription_key',
        'usuario',
        'senha',
        'ttl_segundos',
        'grace_minutos',
        'jornada_maxima_horas',
        'tipos_motorista',
        'fail_mode',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'subscription_key' => 'encrypted',
        'senha' => 'encrypted',
        'tipos_motorista' => 'array',
        'ttl_segundos' => 'integer',
        'grace_minutos' => 'integer',
        'jornada_maxima_horas' => 'integer',
    ];

    protected $hidden = [
        'subscription_key',
        'senha',
    ];

    /**
     * Configuração vigente do tenant atual.
     *
     * Se ainda não existe linha no banco, devolve uma instância em memória
     * com os valores do .env (não persiste nada até o gestor salvar).
     */
    public static function atual(): self
    {
        $config = static::query()->orderBy('id')->first();

        if ($config) {
            return $config;
        }

        return new static(self::padroesDoAmbiente());
    }

    /**
     * @return array<string, mixed>
     */
    public static function padroesDoAmbiente(): array
    {
        return [
            'ativo' => (bool) config('autotrac.enabled', false),
            'base_url' => (string) config('autotrac.base_url', 'https://aapi3.autotrac-online.com.br/aticapi'),
            'account_code' => config('autotrac.account_code'),
            'subscription_key' => config('autotrac.subscription_key'),
            'usuario' => config('autotrac.username'),
            'senha' => config('autotrac.password'),
            'ttl_segundos' => (int) config('autotrac.presence_ttl', 60),
            'grace_minutos' => (int) config('autotrac.grace_minutes', 30),
            'jornada_maxima_horas' => (int) config('autotrac.max_session_hours', 12),
            'tipos_motorista' => config('autotrac.tipos_motorista', ['motorista', 'motorista_monitor']),
            'fail_mode' => (string) config('autotrac.fail_mode', 'grace'),
        ];
    }

    public function podeAutenticar(): bool
    {
        return filled($this->base_url)
            && filled($this->subscription_key)
            && filled($this->usuario)
            && filled($this->senha);
    }

    public function estaConfigurada(): bool
    {
        return filled($this->account_code)
            && $this->podeAutenticar();
    }

    /**
     * @return array<int, string>
     */
    public function tiposMotorista(): array
    {
        $tipos = is_array($this->tipos_motorista) ? $this->tipos_motorista : [];
        $tipos = array_values(array_filter(array_map('strval', $tipos)));

        return $tipos !== [] ? $tipos : ['motorista', 'motorista_monitor'];
    }

    public function urlBase(): string
    {
        return rtrim((string) $this->base_url, '/');
    }

    public function chaveMascarada(): string
    {
        $chave = (string) $this->subscription_key;

        if ($chave === '') {
            return '—';
        }

        return mb_substr($chave, 0, 4).'…'.mb_substr($chave, -4);
    }

    public function usuarioMascarado(): string
    {
        $usuario = (string) $this->usuario;

        if ($usuario === '') {
            return '—';
        }

        $partes = explode('@', $usuario, 2);

        if (count($partes) === 2) {
            return mb_substr($partes[0], 0, 2).'***@'.$partes[1];
        }

        return mb_substr($usuario, 0, 2).'***';
    }
}
