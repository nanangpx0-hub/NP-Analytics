<?php

namespace NpAnalytics\Core\Enums;

enum Impact: string
{
    case Positive = 'positive';
    case Negative = 'negative';
    case Neutral = 'neutral';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'Positif',
            self::Negative => 'Negatif',
            self::Neutral => 'Netral',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Positive => 'green',
            self::Negative => 'red',
            self::Neutral => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Positive => 'trending-up',
            self::Negative => 'trending-down',
            self::Neutral => 'minus',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
