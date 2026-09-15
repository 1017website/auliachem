<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalizeDecimalInputs
{
    private const FIELDS = ['qty', 'buy_price', 'sell_price', 'unit_price', 'current_stock',
        'minimum_stock', 'target', 'probability', 'rating', 'tax_percent'];

    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $normalize = function (array $values) use (&$normalize): array {
                foreach ($values as $key => $value) {
                    if (is_array($value)) {
                        $values[$key] = $normalize($value);
                    } elseif (in_array($key, self::FIELDS, true) && is_string($value)
                        && preg_match('/^[+-]?(?:\d+|\d{1,3}(?:\.\d{3})+),\d+$/', trim($value))) {
                        $values[$key] = str_replace(',', '.', str_replace('.', '', trim($value)));
                    }
                }
                return $values;
            };
            $request->merge($normalize($request->input()));
        }

        return $next($request);
    }
}
