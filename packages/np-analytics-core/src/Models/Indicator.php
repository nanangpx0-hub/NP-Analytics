<?php

namespace NpAnalytics\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use NpAnalytics\Core\Enums\PreferredDirection;
use NpAnalytics\Core\Support\DeterministicUuid;

/**
 * @property int $id
 * @property string $uuid
 * @property int $category_id
 * @property string $series_key
 * @property string $title
 * @property string $value
 * @property string|null $unit
 * @property int $year
 * @property string|null $trend
 * @property bool $is_higher_better
 * @property string|null $description
 * @property string|null $image_path
 * @property string|null $region_level
 * @property string|null $frequency
 * @property \Illuminate\Support\Carbon|null $period_start
 * @property \Illuminate\Support\Carbon|null $period_end
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $source_document
 * @property string|null $source_table
 * @property string|null $source_page
 * @property string|null $revision
 * @property string|null $quality_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $trend_color
 * @property-read string $trend_icon
 * @property-read string $formatted_trend
 * @property-read string $formatted_value
 * @property-read string $preferred_direction
 * @property-read \Illuminate\Database\Eloquent\Collection<int, static> $history
 */
abstract class Indicator extends Model
{
    use SoftDeletes;

    public static bool $cascadeRestore = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'category_id',
        'series_key',
        'title',
        'value',
        'unit',
        'year',
        'trend',
        'is_higher_better',
        'description',
        'image_path',
        'region_level',
        'frequency',
        'period_start',
        'period_end',
        'published_at',
        'source_document',
        'source_table',
        'source_page',
        'revision',
        'quality_note',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'trend' => 'decimal:4',
            'is_higher_better' => 'boolean',
            'year' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'published_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Indicator $indicator): void {
            if (empty($indicator->series_key) && $indicator->title) {
                $indicator->series_key = DeterministicUuid::seriesKey((string) $indicator->title);
            }

            if (empty($indicator->uuid)) {
                $indicator->uuid = $indicator->series_key && $indicator->year
                    ? DeterministicUuid::indicator((string) $indicator->series_key, (int) $indicator->year)
                    : (string) Str::uuid();
            }
        });

        static::deleting(function (Indicator $indicator): void {
            if ($indicator->isForceDeleting()) {
                return;
            }

            $indicator->phenomena()->each(function (Model $phenomenon): void {
                $phenomenon->delete();
            });
        });

        static::restoring(function (Indicator $indicator): void {
            if (! static::$cascadeRestore) {
                return;
            }

            $deletedAt = $indicator->deleted_at;

            $indicator->phenomena()
                ->withTrashed()
                ->where('deleted_at', $deletedAt)
                ->get()
                ->each(function (Model $phenomenon): void {
                    $phenomenon->restore();
                });
        });
    }

    abstract public function category(): BelongsTo;

    abstract public function phenomena(): HasMany;

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
            return 'gray';
        }

        $isPositiveTrend = $this->trend > 0;

        if ($this->is_higher_better) {
            return $isPositiveTrend ? 'green' : 'red';
        }

        return $isPositiveTrend ? 'red' : 'green';
    }

    /**
     * Get the trend icon (arrow up/down).
     */
    public function getTrendIconAttribute(): string
    {
        if ($this->trend === null || $this->trend == 0) {
            return 'minus';
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

        return $sign.number_format($this->trend, 2).'%';
    }

    /**
     * Get formatted value with unit.
     */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->value, 2).' '.$this->unit;
    }

    /**
     * Arah perbaikan diturunkan dari satu sumber kebenaran: is_higher_better.
     */
    public function getPreferredDirectionAttribute(): string
    {
        return PreferredDirection::fromBool((bool) $this->is_higher_better)->value;
    }

    /**
     * Get historical records of this same indicator across years.
     */
    public function getHistoryAttribute()
    {
        return static::where('series_key', $this->series_key)
            ->whereNull('deleted_at')
            ->orderBy('year', 'asc')
            ->get();
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
