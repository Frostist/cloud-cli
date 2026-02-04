<?php

namespace App\Enums;

enum LogLevel: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case DEBUG = 'debug';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => 'green',
            self::WARNING => 'yellow',
            self::ERROR => 'red',
            self::DEBUG => 'blue',
        };
    }
}
