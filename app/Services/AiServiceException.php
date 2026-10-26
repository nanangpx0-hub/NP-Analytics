<?php

namespace App\Services;

use RuntimeException;

class AiServiceException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function userNotice(): string
    {
        return match ($this->reason) {
            'offline' => 'Server AI tidak dapat dihubungi (offline). Menampilkan analisis lokal berbasis data.',
            'unauthorized' => 'Sesi perangkat tidak valid atau sudah dicabut. Aktifkan ulang perangkat; sementara ini analisis lokal ditampilkan.',
            'rate_limited' => 'Batas permintaan analisis AI tercapai. Menampilkan analisis tersimpan atau analisis lokal.',
            'not_configured' => 'Perangkat belum diaktifkan. Buka Pengaturan Sync; sementara ini analisis lokal ditampilkan.',
            default => 'Layanan AI server sedang tidak tersedia. Menampilkan analisis tersimpan atau analisis lokal.',
        };
    }
}
