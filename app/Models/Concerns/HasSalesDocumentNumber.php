<?php

namespace App\Models\Concerns;

use Illuminate\Database\QueryException;
use RuntimeException;

trait HasSalesDocumentNumber
{
    abstract protected static function numberColumn(): string;

    abstract protected static function numberPrefix(): string;

    public static function generateDocumentNumber(): string
    {
        $column = static::numberColumn();
        $prefix = static::numberPrefix() . '-' . now()->format('Ym') . '-';
        $last = static::withTrashed()
            ->where($column, 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc($column)
            ->value($column);

        $sequence = $last ? ((int) substr($last, -4) + 1) : 1;
        do {
            $number = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where($column, $number)->exists();
            $sequence++;
        } while ($exists && $sequence <= 9999);

        return $number;
    }

    public static function createWithUniqueNumber(array $attributes): self
    {
        $column = static::numberColumn();
        unset($attributes[$column]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $attributes[$column] = static::generateDocumentNumber();
            try {
                return static::create($attributes);
            } catch (QueryException $exception) {
                if (!str_contains(strtolower($exception->getMessage()), strtolower($column))) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Nomor dokumen unik gagal dibuat. Silakan simpan ulang.');
    }
}
