<?php

namespace NpAnalytics\Core\Enums;

enum PreferredDirection: string
{
    case Higher = 'higher';
    case Lower = 'lower';

    public static function fromBool(bool $isHigherBetter): self
    {
        return $isHigherBetter ? self::Higher : self::Lower;
    }

    public function label(): string
    {
        return match ($this) {
            self::Higher => 'Semakin tinggi semakin baik',
            self::Lower => 'Semakin rendah semakin baik',
        };
    }
}
