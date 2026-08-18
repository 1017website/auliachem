<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class ExcelImport
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rawRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();

        if (count($rawRows) < 2) {
            return [];
        }

        $headers = array_map(
            fn ($header) => Str::slug(trim((string) $header), '_'),
            array_shift($rawRows)
        );

        $rows = [];
        foreach ($rawRows as $rawRow) {
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = $rawRow[$index] ?? null;
                }
            }

            if (collect($row)->contains(fn ($value) => trim((string) $value) !== '')) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @param array<string, mixed> $row */
    public static function value(array $row, string ...$aliases): mixed
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row)) {
                return $row[$alias];
            }
        }

        return null;
    }
}
