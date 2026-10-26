<?php

namespace App\Services;

use RuntimeException;

class SyncException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function notConfigured(): self
    {
        return new self(
            'not_configured',
            'Pengaturan Sync belum lengkap. Buka Pengaturan Sync dan aktifkan perangkat dengan kode dari admin.',
        );
    }

    public static function offline(?\Throwable $previous = null): self
    {
        return new self(
            'offline',
            'Tidak dapat terhubung ke server. Periksa koneksi internet lalu coba lagi.',
            $previous,
        );
    }

    public static function unauthorized(): self
    {
        return new self(
            'unauthorized',
            'Sesi perangkat tidak valid atau sudah dicabut. Aktifkan ulang perangkat dengan kode baru.',
        );
    }

    public static function serverError(?\Throwable $previous = null): self
    {
        return new self(
            'server_error',
            'Server sinkronisasi sedang bermasalah. Coba lagi beberapa saat lagi.',
            $previous,
        );
    }

    public static function rateLimited(): self
    {
        return new self(
            'rate_limited',
            'Terlalu banyak permintaan sinkronisasi. Tunggu satu menit lalu coba lagi.',
        );
    }

    public static function alreadyRunning(): self
    {
        return new self(
            'already_running',
            'Sinkronisasi lain sedang berjalan. Tunggu hingga selesai sebelum mencoba lagi.',
        );
    }
}
