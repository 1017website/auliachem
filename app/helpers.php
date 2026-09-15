<?php

function format_number($value, int $minimumDecimals = 0, string $language = 'id'): string
{
    $decimal = $language === 'en' ? '.' : ',';
    $formatted = number_format((float) $value, 3, $decimal, $language === 'en' ? ',' : '.');
    [$integer, $fraction] = explode($decimal, $formatted);
    $fraction = str_pad(rtrim($fraction, '0'), $minimumDecimals, '0');

    return $integer . ($fraction !== '' ? $decimal . $fraction : '');
}

function format_money($amount, string $currency = 'IDR', string $language = 'id'): string
{
    return ($currency === 'IDR' ? 'Rp' : $currency) . ' ' . format_number($amount, 2, $language);
}

if (!function_exists('idr')) {
    function idr($amount, bool $withDecimal = false): string
    {
        return format_money($amount);
    }
}

if (!function_exists('idrm')) {
    function idrm($amount): string
    {
        if (is_null($amount) || $amount === '') return 'Rp 0,00';

        $amount = (float) $amount;

        if ($amount >= 1_000_000_000) {
            $val = $amount / 1_000_000_000;
            return 'Rp ' . format_number($val) . 'M';
        }

        if ($amount >= 1_000_000) {
            $val = $amount / 1_000_000;
            return 'Rp ' . format_number($val) . ' Jt';
        }

        if ($amount >= 1_000) {
            $val = $amount / 1_000;
            return 'Rp ' . format_number($val) . ' Rb';
        }

        return format_money($amount);
    }
}

if (!function_exists('idr_input')) {
    function idr_input($amount): string
    {
        if (is_null($amount) || $amount === '') return '';
        return format_number($amount, 2);
    }
}
