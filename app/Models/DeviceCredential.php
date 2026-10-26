<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $server_url
 * @property string $device_uuid
 * @property string $device_name
 * @property string $token
 * @property array<int, string>|null $abilities
 * @property \Illuminate\Support\Carbon|null $last_registered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DeviceCredential extends Model
{
    protected $fillable = [
        'server_url',
        'device_uuid',
        'device_name',
        'token',
        'abilities',
        'last_registered_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'token' => 'encrypted',
        'abilities' => 'array',
        'last_registered_at' => 'datetime',
    ];

    public static function active(): ?DeviceCredential
    {
        return static::query()->latest('id')->first();
    }

    public function hasAbility(string $ability): bool
    {
        return in_array($ability, (array) $this->abilities, true);
    }
}
