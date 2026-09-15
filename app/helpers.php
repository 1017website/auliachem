<?php

if (!function_exists('idr')) {
    function idr($amount, bool $withDecimal = false): string
    {
        if (is_null($amount) || $amount === '') return 'Rp 0,000';

        $amount = (float) $amount;

        return 'Rp ' . number_format($amount, 3, ',', '.');
    }
}

if (!function_exists('idrm')) {
    function idrm($amount): string
    {
        if (is_null($amount) || $amount === '') return 'Rp 0,000';

        $amount = (float) $amount;

        if ($amount >= 1_000_000_000) {
            $val = $amount / 1_000_000_000;
            return 'Rp ' . number_format($val, 3, ',', '.') . 'M';
        }

        if ($amount >= 1_000_000) {
            $val = $amount / 1_000_000;
            return 'Rp ' . number_format($val, 3, ',', '.') . ' Jt';
        }

        if ($amount >= 1_000) {
            $val = $amount / 1_000;
            return 'Rp ' . number_format($val, 3, ',', '.') . ' Rb';
        }

        return 'Rp ' . number_format($amount, 3, ',', '.');
    }
}

if (!function_exists('idr_input')) {
    function idr_input($amount): string
    {
        if (is_null($amount) || $amount === '') return '';
        return number_format((float) $amount, 3, ',', '.');
    }
}
