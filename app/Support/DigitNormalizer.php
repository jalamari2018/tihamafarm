<?php

namespace App\Support;

class DigitNormalizer
{
    public static function toEnglishDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    public static function toArabicIndicDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtr($value, [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
            '۰' => '٠', '۱' => '١', '۲' => '٢', '۳' => '٣', '۴' => '٤',
            '۵' => '٥', '۶' => '٦', '۷' => '٧', '۸' => '٨', '۹' => '٩',
        ]);
    }

    public static function normalizeSaudiPhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = self::toEnglishDigits($value);
        $normalized = preg_replace('/[\s\-\(\)]/', '', $normalized ?? '');

        if ($normalized === null || $normalized === '') {
            return $normalized;
        }

        if (str_starts_with($normalized, '+966')) {
            $normalized = '0'.substr($normalized, 4);
        } elseif (str_starts_with($normalized, '00966')) {
            $normalized = '0'.substr($normalized, 5);
        } elseif (str_starts_with($normalized, '966')) {
            $normalized = '0'.substr($normalized, 3);
        } elseif (str_starts_with($normalized, '5') && strlen($normalized) === 9) {
            $normalized = '0'.$normalized;
        }

        return $normalized;
    }
}
