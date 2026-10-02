<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações globais da plataforma (sem contexto de tenant).
 */
class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $fillable = [
        'fundo_certificado',
    ];

    public function fundoCertificadoUrl(): ?string
    {
        return $this->fundo_certificado ? asset($this->fundo_certificado) : null;
    }

    public function fundoCertificadoFilePath(): ?string
    {
        if (! $this->fundo_certificado) {
            return null;
        }

        $path = public_path($this->fundo_certificado);

        return file_exists($path) ? $path : null;
    }

    /**
     * Caminho absoluto do fundo padrão global (se configurado), com guarda
     * para ambientes em que a migration ainda não rodou.
     */
    public static function fundoCertificadoGlobalPath(): ?string
    {
        try {
            if (! Schema::hasTable('platform_settings')) {
                return null;
            }

            $relativo = static::query()->value('fundo_certificado');

            if (! $relativo) {
                return null;
            }

            $path = public_path($relativo);

            return file_exists($path) ? $path : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
