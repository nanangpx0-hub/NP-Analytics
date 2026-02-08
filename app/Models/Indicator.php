<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Indicator extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'category_id',
        'title',
        'value',
        'unit',
        'year',
        'trend',
        'is_higher_better',
        'description',
        'image_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:4',
        'trend' => 'decimal:4',
        'is_higher_better' => 'boolean',
        'year' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Indicator $indicator): void {
            if (empty($indicator->uuid)) {
                $indicator->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function (Indicator $indicator): void {
            if ($indicator->isForceDeleting()) {
                return;
            }

            $indicator->phenomena()->each(function (Phenomenon $phenomenon): void {
                $phenomenon->delete();
            });
        });

        static::restoring(function (Indicator $indicator): void {
            $indicator->phenomena()
                ->withTrashed()
                ->get()
                ->each(function (Phenomenon $phenomenon): void {
                    $phenomenon->restore();
                });
        });
    }

    /**
     * Get the category that owns the indicator.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all phenomena for this indicator.
     */
    public function phenomena(): HasMany
    {
        return $this->hasMany(Phenomenon::class);
    }

    /**
     * Get the trend color based on Smart Trend Logic.
     * 
     * Logika:
     * - is_higher_better = true (Padi, PDRB): Naik = Hijau, Turun = Merah
     * - is_higher_better = false (Kemiskinan, Inflasi): Naik = Merah, Turun = Hijau
     */
    public function getTrendColorAttribute(): string
    {
        if ($this->trend === null || $this->trend == 0) {
            return 'gray'; // Netral
        }

        $isPositiveTrend = $this->trend > 0;

        if ($this->is_higher_better) {
            return $isPositiveTrend ? 'green' : 'red';
        } else {
            return $isPositiveTrend ? 'red' : 'green';
        }
    }

    /**
     * Get the trend icon (arrow up/down).
     */
    public function getTrendIconAttribute(): string
    {
        if ($this->trend === null || $this->trend == 0) {
            return 'minus'; // Netral
        }

        return $this->trend > 0 ? 'arrow-up' : 'arrow-down';
    }

    /**
     * Get formatted trend percentage.
     */
    public function getFormattedTrendAttribute(): string
    {
        if ($this->trend === null) {
            return '-';
        }

        $sign = $this->trend > 0 ? '+' : '';
        return $sign . number_format($this->trend, 2) . '%';
    }

    /**
     * Get formatted value with unit.
     */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->value, 2) . ' ' . $this->unit;
    }

    /**
     * Scope: Filter by year.
     */
    public function scopeOfYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeOfCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
