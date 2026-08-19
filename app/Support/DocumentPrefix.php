<?php

namespace App\Support;

use App\Models\Setting;
use InvalidArgumentException;

final class DocumentPrefix
{
    private const TYPE_SUFFIXES = [
        'quotation' => '1',
        'invoice' => '2',
        'purchase_order' => '3',
    ];

    public static function for(string $documentType): string
    {
        if (! array_key_exists($documentType, self::TYPE_SUFFIXES)) {
            throw new InvalidArgumentException("Jenis dokumen tidak dikenal: {$documentType}");
        }

        $prefix = trim((string) Setting::get('document_file_prefix', 'AP'));
        $prefix = preg_replace('/[^A-Za-z0-9_-]/', '', $prefix) ?: 'AP';

        return $prefix . self::TYPE_SUFFIXES[$documentType];
    }
}
