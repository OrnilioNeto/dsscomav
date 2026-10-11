<?php

namespace App\Modules\LembretesWhatsapp\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrainingReminder extends Model
{
    use Auditable;
    use BelongsToTenant;

    public const STATUS_FILA = 'fila';

    public const STATUS_ENVIADO = 'enviado';

    public const STATUS_FALHOU = 'falhou';

    public const STATUS_PULADO = 'pulado';

    public const STATUS_CANCELADO = 'cancelado';

    protected $table = 'training_reminders';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'training_id',
        'telefone',
        'jid',
        'mensagem',
        'status',
        'delivery_status',
        'agendado_para',
        'enviado_em',
        'delivered_at',
        'gateway_message_id',
        'erro',
        'created_by',
    ];

    protected $casts = [
        'agendado_para' => 'datetime',
        'enviado_em' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeNaFila(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FILA);
    }

    public function scopeVencidos(Builder $query): Builder
    {
        return $query->naFila()
            ->whereNotNull('agendado_para')
            ->where('agendado_para', '<=', now());
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_FILA => 'Na fila',
            self::STATUS_ENVIADO => 'Enviado',
            self::STATUS_FALHOU => 'Falhou',
            self::STATUS_PULADO => 'Pulado',
            self::STATUS_CANCELADO => 'Cancelado',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_FILA => 'yellow',
            self::STATUS_ENVIADO => 'green',
            self::STATUS_FALHOU => 'red',
            self::STATUS_PULADO => 'slate',
            self::STATUS_CANCELADO => 'gray',
            default => 'gray',
        };
    }

    public function getEntregaLabelAttribute(): ?string
    {
        return match ($this->delivery_status) {
            'PENDING' => 'Pendente',
            'SENT' => 'Enviado (sem confirmação)',
            'DELIVERED' => 'Entregue',
            'READ' => 'Lido',
            'FAILED' => 'Falha na entrega',
            null, '' => null,
            default => ucfirst(strtolower((string) $this->delivery_status)),
        };
    }
}
