<?php

namespace App\Filament\Exports;

use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Base exporter pakai openspout/openspout (dependency built-in Filament 3).
 * Tidak butuh install package tambahan.
 */
abstract class BaseExporter
{
    abstract protected static function fileName(): string;
    abstract protected static function headers(): array;
    abstract protected static function rows(): Collection;

    public static function download(): StreamedResponse
    {
        $fileName = static::fileName() . '_' . now()->format('Ymd_His') . '.xlsx';
        $headers  = static::headers();
        $rows     = static::rows();

        return response()->streamDownload(function () use ($headers, $rows) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            // Header style: bold putih + bg indigo
            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontSize(11)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('5B5FC7');

            // Header row
            $writer->addRow(Row::fromValues($headers, $headerStyle));

            // Data rows
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values((array) $row)));
            }

            $writer->close();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
