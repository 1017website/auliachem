<?php

namespace App\Support;

use App\Models\Setting;
use InvalidArgumentException;

final class DocumentDownloadName
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

    public static function forDocument(string $documentType, string $documentNumber): string
    {
        return self::for($documentType) . '-' . self::sanitizeSegment($documentNumber);
    }

    public static function forExport(string $documentType): string
    {
        return self::for($documentType) . '-' . now()->format('Ymd-His');
    }

    private static function sanitizeSegment(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '-', trim($value)) ?: 'document';
    }
}
