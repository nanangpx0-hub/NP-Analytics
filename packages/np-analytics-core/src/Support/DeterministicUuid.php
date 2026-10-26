<?php

namespace NpAnalytics\Core\Support;

use Illuminate\Support\Str;

final class DeterministicUuid
{
    /**
     * Namespace tetap untuk seluruh data NP Analytics. Jangan diubah karena
     * perubahan namespace akan mengubah UUID semua data dan memicu duplikasi.
     */
    public const NAMESPACE = '8f1c2e40-5d3b-5a7e-9c61-7b2d4a8f0e13';

    public static function category(string $name): string
    {
        return self::uuid5(self::NAMESPACE, 'category:'.self::normalizeName($name));
    }

    public static function indicator(string $seriesKey, int $year): string
    {
        return self::uuid5(self::NAMESPACE, 'indicator:'.self::seriesKey($seriesKey).':'.$year);
    }

    public static function phenomenon(string $indicatorSeriesKey, int $year, int $order): string
    {
        return self::uuid5(
            self::NAMESPACE,
            'phenomenon:'.self::seriesKey($indicatorSeriesKey).':'.$year.':'.$order,
        );
    }

    public static function seriesKey(string $value): string
    {
        return Str::limit(Str::slug($value), 150, '') ?: 'indikator';
    }

    public static function normalizeName(string $value): string
    {
        return Str::lower(trim((string) preg_replace('/\s+/u', ' ', $value)));
    }

    public static function uuid5(string $namespace, string $name): string
    {
        $hex = str_replace('-', '', $namespace);
        $hash = sha1(hex2bin($hex).$name, true);

        $hash[6] = chr((ord($hash[6]) & 0x0F) | 0x50);
        $hash[8] = chr((ord($hash[8]) & 0x3F) | 0x80);

        $hex = bin2hex($hash);

        return substr($hex, 0, 8)
            .'-'.substr($hex, 8, 4)
            .'-'.substr($hex, 12, 4)
            .'-'.substr($hex, 16, 4)
            .'-'.substr($hex, 20, 12);
    }
}
