<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
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
        'name',
        'icon',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if (empty($category->uuid)) {
                $category->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function (Category $category): void {
            if ($category->isForceDeleting()) {
                return;
            }

            $category->indicators()->each(function (Indicator $indicator): void {
                $indicator->delete();
            });
        });

        static::restoring(function (Category $category): void {
            $category->indicators()
                ->withTrashed()
                ->get()
                ->each(function (Indicator $indicator): void {
                    $indicator->restore();
                });
        });
    }

    /**
     * Get all indicators for this category.
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    /**
     * Get the count of indicators in this category.
     */
    public function getIndicatorCountAttribute(): int
    {
        return $this->indicators()->count();
    }
}
