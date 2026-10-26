<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncConflict extends Model
{
    protected $fillable = [
        'record_type',
        'record_uuid',
        'local_payload',
        'server_payload',
        'server_version',
        'resolved_at',
    ];

    protected $casts = [
        'local_payload' => 'array',
        'server_payload' => 'array',
        'server_version' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public const TYPE_LABELS = [
        'category' => 'Kategori',
        'indicator' => 'Indikator',
        'phenomenon' => 'Fenomena',
    ];

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->record_type] ?? $this->record_type;
    }

    public function getTitleAttribute(): string
    {
        $payload = $this->local_payload ?? $this->server_payload ?? [];

        return (string) ($payload['title'] ?? $payload['name'] ?? $this->record_uuid);
    }
}
