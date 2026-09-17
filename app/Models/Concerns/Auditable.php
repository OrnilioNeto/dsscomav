<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            app(AuditLogger::class)->logModel('created', $model);
        });

        static::updated(function ($model) {
            app(AuditLogger::class)->logModel('updated', $model);
        });

        static::deleted(function ($model) {
            app(AuditLogger::class)->logModel('deleted', $model);
        });
    }
}
