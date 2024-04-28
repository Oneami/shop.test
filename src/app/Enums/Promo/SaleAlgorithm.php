<?php

namespace App\Enums\Promo;

use Filament\Support\Contracts\HasLabel;

enum SaleAlgorithm: int implements HasLabel
{
    case SIMPLE = 1;
    case COUNT = 2;
    case FAKE = 3;
    case ASCENDING = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FAKE => 'Ложная',
            self::SIMPLE => 'Простая',
            self::COUNT => 'От количества',
            self::ASCENDING => 'По возрастанию',
        };
    }

    public function isFake(): bool
    {
        return $this === self::FAKE;
    }

    public function isSimple(): bool
    {
        return $this === self::SIMPLE;
    }

    public function isCount(): bool
    {
        return $this === self::COUNT;
    }

    public function isAscending(): bool
    {
        return $this === self::ASCENDING;
    }
}
