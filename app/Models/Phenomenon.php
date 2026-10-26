<?php

namespace App\Models;

use App\Models\Concerns\HasSyncState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NpAnalytics\Core\Models\Phenomenon as CorePhenomenon;

/**
 * @property bool $dirty
 * @property int $base_version
 * @property int $server_version
 * @property-read Indicator|null $indicator
 */
class Phenomenon extends CorePhenomenon
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
     * Get the indicator that owns this phenomenon.
     *
     * @return BelongsTo<Indicator, $this>
     */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
