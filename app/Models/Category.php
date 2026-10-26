<?php

namespace App\Models;

use App\Models\Concerns\HasSyncState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NpAnalytics\Core\Models\Category as CoreCategory;

/**
 * @property bool $dirty
 * @property int $base_version
 * @property int $server_version
 */
class Category extends CoreCategory
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
     * Get all indicators for this category.
     *
     * @return HasMany<Indicator, $this>
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }
}
