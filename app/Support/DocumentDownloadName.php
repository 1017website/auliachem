<?php

namespace App\Support;

final class DocumentDownloadName
{
    public static function for(string $documentType): string
    {
        return DocumentPrefix::for($documentType);
    }

    public static function forDocument(string $documentType, string $documentNumber): string
    {
        $prefix = self::for($documentType);
        $number = self::sanitizeSegment($documentNumber);

        return str_starts_with($number, $prefix . '-')
            ? $number
            : $prefix . '-' . $number;
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
