<?php

namespace NpAnalytics\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use NpAnalytics\Core\Support\DeterministicUuid;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $icon
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int $indicator_count
 */
abstract class Category extends Model
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
        'name',
        'icon',
    ];

    protected function casts(): array
    {
        return [];
    }

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if (empty($category->uuid)) {
                $category->uuid = $category->name
                    ? DeterministicUuid::category((string) $category->name)
                    : (string) Str::uuid();
            }
        });

        static::deleting(function (Category $category): void {
            if ($category->isForceDeleting()) {
                return;
            }

            $category->indicators()->each(function (Model $indicator): void {
                $indicator->delete();
            });
        });

        static::restoring(function (Category $category): void {
            if (! static::$cascadeRestore) {
                return;
            }

            $deletedAt = $category->deleted_at;

            $category->indicators()
                ->withTrashed()
                ->where('deleted_at', $deletedAt)
                ->get()
                ->each(function (Model $indicator): void {
                    $indicator->restore();
                });
        });
    }

    abstract public function indicators(): HasMany;

    /**
     * Get the count of indicators in this category.
     */
    public function getIndicatorCountAttribute(): int
    {
        return $this->indicators()->count();
    }
}
