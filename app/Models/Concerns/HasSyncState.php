<?php

namespace App\Models\Concerns;

trait HasSyncState
{
    /**
     * Record lokal baru atau yang diubah lewat aplikasi ditandai dirty.
     * Penerapan payload server memakai saveQuietly() sehingga tidak memicu
     * penandaan ini. UUID deterministik diisi oleh model dasar bersama.
     */
    public static function bootHasSyncState(): void
    {
        static::creating(function (self $model): void {
            if ($model->dirty === null) {
                $model->dirty = true;
            }

            if ($model->base_version === null) {
                $model->base_version = 0;
            }

            if ($model->server_version === null) {
                $model->server_version = 0;
            }
        });

        static::saving(function (self $model): void {
            if (! $model->exists) {
                return;
            }

            $model->dirty = true;
            $model->base_version = (int) ($model->getOriginal('server_version') ?? 0);
        });

        static::deleted(function (self $model): void {
            if ($model->isForceDeleting()) {
                return;
            }

            $baseVersion = (int) $model->server_version;

            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update([
                    'dirty' => true,
                    'base_version' => $baseVersion,
                ]);

            $model->forceFill([
                'dirty' => true,
                'base_version' => $baseVersion,
            ]);
        });
    }

    public function markSynced(int $serverVersion): void
    {
        $this->forceFill([
            'server_version' => $serverVersion,
            'base_version' => $serverVersion,
            'dirty' => false,
        ]);
    }
}
