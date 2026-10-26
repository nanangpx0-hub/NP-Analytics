<?php

namespace NpAnalytics\Core\Enums;

enum InsightType: string
{
    case Statistik = 'statistik';
    case IsuTerkini = 'isu_terkini';
    case Rekomendasi = 'rekomendasi';

    public function label(): string
    {
        return match ($this) {
            self::Statistik => 'Statistik',
            self::IsuTerkini => 'Isu Terkini',
            self::Rekomendasi => 'Rekomendasi',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
