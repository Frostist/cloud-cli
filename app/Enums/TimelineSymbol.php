<?php

namespace App\Enums;

enum TimelineSymbol: string
{
    case DOT = '•';
    case LINE = '│';
    case PENDING = '◆';
    case SUCCESS = '✔';
    case FAILURE = '✘';
    case WARNING = '⚠';

    public static function color(self $symbol): string
    {
        return match ($symbol) {
            self::DOT => 'cyan',
            self::LINE => 'gray',
            self::PENDING => 'yellow',
            self::SUCCESS => 'green',
            self::FAILURE => 'red',
            self::WARNING => 'yellow',
        };
    }
}
