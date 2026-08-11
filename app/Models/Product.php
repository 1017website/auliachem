<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use RuntimeException;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_code', 'product_name', 'category', 'unit', 'description',
        'buy_price', 'sell_price', 'current_stock', 'minimum_stock', 'status',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'current_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
    ];

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->minimum_stock > 0
            && (float) $this->current_stock <= (float) $this->minimum_stock;
    }

    public function getStockValueAttribute(): float
    {
        return (float) $this->current_stock * (float) $this->buy_price;
    }

    public static function generateProductCode(): string
    {
        $last = static::withTrashed()->lockForUpdate()->orderByDesc('id')->value('product_code');
        $sequence = $last && preg_match('/BRG-(\d+)$/', $last, $match) ? ((int) $match[1] + 1) : 1;

        do {
            $code = 'BRG-' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where('product_code', $code)->exists();
            $sequence++;
        } while ($exists && $sequence <= 99999);

        return $code;
    }

    public static function createWithUniqueCode(array $attributes): self
    {
        unset($attributes['product_code']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $attributes['product_code'] = static::generateProductCode();
            try {
                return static::create($attributes);
            } catch (QueryException $exception) {
                if (!str_contains(strtolower($exception->getMessage()), 'product_code')) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Kode barang unik gagal dibuat. Silakan simpan ulang.');
    }
}
