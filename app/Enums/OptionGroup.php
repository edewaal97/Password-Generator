<?php

namespace App\Enums;

enum OptionGroup: string
{
    case ZERO = '0';
    case ONE = '1';
    case ONEORMORE = '2';
    case ZEROORMORE = '3';

    public function label(): string
    {
        return match ($this) {
            self::ZERO => 'Geen',
            self::ONE => '1',
            self::ONEORMORE => '1 of meer',
            self::ZEROORMORE => '0 of meer',
        };
    }
}
