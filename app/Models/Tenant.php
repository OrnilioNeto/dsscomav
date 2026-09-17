<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'nome',
        'slug',
        'dominio',
        'status',
        'plano',
        'nome_exibicao',
        'logo',
        'logo_certificado',
        'cor_primaria',
        'cor_secundaria',
        'email_remetente',
        'instrutor_nome',
        'instrutor_qualificacao',
        'instrutor_rg',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function modules()
    {
        return $this->hasMany(TenantModule::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['ativo', 'trial'], true);
    }

    public function getNomeExibicao(): string
    {
        return $this->nome_exibicao ?: $this->nome;
    }

    public function getCorPrimaria(): string
    {
        return $this->cor_primaria ?: '#153B2E';
    }

    public function getCorSecundaria(): string
    {
        return $this->cor_secundaria ?: '#0F2B22';
    }

    public function getAccentColor(): string
    {
        return '#F28C2B';
    }

    public function getInstrutorNome(): string
    {
        return $this->instrutor_nome ?: 'Ornilio Machado Neto';
    }

    public function getInstrutorQualificacao(): string
    {
        return $this->instrutor_qualificacao ?: 'Tec Segurança do Trabalho';
    }

    public function getInstrutorRg(): string
    {
        return $this->instrutor_rg ?: '10827';
    }

    /**
     * Caminho absoluto de um arquivo de logo do tenant, se existir no disco.
     */
    public function logoFilePath(string $campo = 'logo'): ?string
    {
        $relativo = $this->{$campo};
        if (! $relativo) {
            return null;
        }

        $path = public_path($relativo);

        return file_exists($path) ? $path : null;
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? asset($this->logo) : null;
    }

    public function logoCertificadoUrl(): ?string
    {
        return $this->logo_certificado ? asset($this->logo_certificado) : null;
    }

    public static function slugUnico(string $base): string
    {
        $slug = $base;
        $contador = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$contador++;
        }

        return $slug;
    }

    public function hasModule(string $module): bool
    {
        return $this->modules()
            ->where('module', $module)
            ->where('enabled', true)
            ->exists();
    }
}
