<?php

namespace App\Enums;

enum LogType: string
{
    case ACCESS = 'access';
    case APPLICATION = 'application';
    case EXCEPTION = 'exception';
    case SYSTEM = 'system';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::ACCESS => 'cyan',
            self::APPLICATION => 'green',
            self::EXCEPTION => 'red',
            self::SYSTEM => 'blue',
        };
    }
}
