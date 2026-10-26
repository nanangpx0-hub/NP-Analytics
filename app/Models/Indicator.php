<?php

namespace App\Models;

use App\Models\Concerns\HasSyncState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NpAnalytics\Core\Models\Indicator as CoreIndicator;

/**
 * @property bool $dirty
 * @property int $base_version
 * @property int $server_version
 * @property-read Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Phenomenon> $phenomena
 */
class Indicator extends CoreIndicator
{
    use HasFactory;
    use HasSyncState;

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'server_version' => 'integer',
            'base_version' => 'integer',
            'dirty' => 'boolean',
        ]);
    }

    /**
     * Get the category that owns the indicator.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all phenomena for this indicator.
     *
     * @return HasMany<Phenomenon, $this>
     */
    public function phenomena(): HasMany
    {
        return $this->hasMany(Phenomenon::class);
    }
}
