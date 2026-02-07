<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'icon',
    ];

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
