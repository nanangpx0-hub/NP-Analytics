<?php

namespace NpAnalytics\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use NpAnalytics\Core\Enums\Impact;
use NpAnalytics\Core\Support\DeterministicUuid;

/**
 * @property int $id
 * @property string $uuid
 * @property int $indicator_id
 * @property string $title
 * @property string|null $description
 * @property string $impact
 * @property string|null $source
 * @property int $order
 * @property string|null $insight_type
 * @property string|null $source_document
 * @property string|null $source_page
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $author
 * @property string|null $quality_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $impact_color
 * @property-read string $impact_icon
 * @property-read string $impact_label
 */
abstract class Phenomenon extends Model
{
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
        'source',
        'order',
        'insight_type',
        'source_document',
        'source_page',
        'published_at',
        'author',
        'quality_note',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'published_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Phenomenon $phenomenon): void {
            if (! empty($phenomenon->uuid)) {
                return;
            }

            $indicator = $phenomenon->indicator_id
                ? $phenomenon->indicator()->withTrashed()->first()
                : null;

            $phenomenon->uuid = $indicator?->series_key
                ? DeterministicUuid::phenomenon(
                    (string) $indicator->series_key,
                    (int) $indicator->year,
                    (int) ($phenomenon->order ?? 0),
                )
                : (string) Str::uuid();
        });
    }

    abstract public function indicator(): BelongsTo;

    public function isPositive(): bool
    {
        return $this->impact === Impact::Positive->value;
    }

    public function isNegative(): bool
    {
        return $this->impact === Impact::Negative->value;
    }

    public function isNeutral(): bool
    {
        return $this->impact === Impact::Neutral->value;
    }

    /**
     * Get the impact color for UI.
     */
    public function getImpactColorAttribute(): string
    {
        return (Impact::tryFrom((string) $this->impact) ?? Impact::Neutral)->color();
    }

    /**
     * Get the impact icon for UI.
     */
    public function getImpactIconAttribute(): string
    {
        return (Impact::tryFrom((string) $this->impact) ?? Impact::Neutral)->icon();
    }

    /**
     * Get the impact label in Indonesian.
     */
    public function getImpactLabelAttribute(): string
    {
        return (Impact::tryFrom((string) $this->impact) ?? Impact::Neutral)->label();
    }

    public function scopePositive($query)
    {
        return $query->where('impact', Impact::Positive->value);
    }

    public function scopeNegative($query)
    {
        return $query->where('impact', Impact::Negative->value);
    }

    public function scopeNeutral($query)
    {
        return $query->where('impact', Impact::Neutral->value);
    }

    /**
     * Scope: Order by the 'order' field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
