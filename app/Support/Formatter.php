<?php

namespace App\Support;

class Formatter
{
    public static function centsToDollars(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }

    public static function gigabyte(int|float $gigabytes): string
    {
        return round($gigabytes, 1).' GB';
    }
}
