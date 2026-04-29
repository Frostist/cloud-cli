<?php

namespace App\Support;

class Formatter
{
    public static function cents(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }

    public static function bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
