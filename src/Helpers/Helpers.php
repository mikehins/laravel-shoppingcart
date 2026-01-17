<?php

declare(strict_types=1);

namespace Mikehins\Cart\Helpers;

final class Helpers
{
    public static function normalizePrice(mixed $price): float
    {
        return (float) $price;
    }

    public static function isMultiArray(array $array, bool $recursive = false): bool
    {
        if ($recursive) {
            return count($array) !== count($array, COUNT_RECURSIVE);
        }

        foreach ($array as $v) {
            return is_array($v);
        }

        return false;
    }

    public static function formatValue(float $value, bool $format_numbers, array $config): string|float
    {
        if ($format_numbers && ($config['format_numbers'] ?? false)) {
            return number_format($value, $config['decimals'] ?? 2, $config['dec_point'] ?? '.', $config['thousands_sep'] ?? '');
        }

        return $value;
    }
}
