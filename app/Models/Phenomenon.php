<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Phenomenon extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     * (Laravel plural untuk 'phenomenon' adalah 'phenomena')
     *
     * @var string
     */
    protected $table = 'phenomena';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'indicator_id',
        'title',
        'description',
        'impact',
        'source',  // Field baru: sumber referensi
        'order',   // Field baru: urutan tampilan
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Phenomenon $phenomenon): void {
            if (empty($phenomenon->uuid)) {
                $phenomenon->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the indicator that owns this phenomenon.
     */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    /**
     * Check if the impact is positive.
     */
    public function isPositive(): bool
    {
        return $this->impact === 'positive';
    }

    /**
     * Check if the impact is negative.
     */
    public function isNegative(): bool
    {
        return $this->impact === 'negative';
    }

    /**
     * Check if the impact is neutral.
     */
    public function isNeutral(): bool
    {
        return $this->impact === 'neutral';
    }

    /**
     * Get the impact color for UI.
     * 
     * - positive = green
     * - negative = red
     * - neutral = gray
     */
    public function getImpactColorAttribute(): string
    {
        return match ($this->impact) {
            'positive' => 'green',
            'negative' => 'red',
            'neutral' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get the impact icon for UI.
     */
    public function getImpactIconAttribute(): string
    {
        return match ($this->impact) {
            'positive' => 'trending-up',
            'negative' => 'trending-down',
            'neutral' => 'minus',
            default => 'minus',
        };
    }

    /**
     * Get the impact label in Indonesian.
     */
    public function getImpactLabelAttribute(): string
    {
        return match ($this->impact) {
            'positive' => 'Positif',
            'negative' => 'Negatif',
            'neutral' => 'Netral',
            default => 'Netral',
        };
    }

    /**
     * Scope: Filter by positive impact.
     */
    public function scopePositive($query)
    {
        return $query->where('impact', 'positive');
    }

    /**
     * Scope: Filter by negative impact.
     */
    public function scopeNegative($query)
    {
        return $query->where('impact', 'negative');
    }

    /**
     * Scope: Filter by neutral impact.
     */
    public function scopeNeutral($query)
    {
        return $query->where('impact', 'neutral');
    }

    /**
     * Scope: Order by the 'order' field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
