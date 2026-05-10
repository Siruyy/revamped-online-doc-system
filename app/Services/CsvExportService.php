<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $headers
     * @param  callable(TModel): array<int, scalar|null>  $map
     */
    public function stream(string $filename, Builder $query, array $headers, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $headers, $map): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $this->escapeFormulaCells($headers));

            $query->chunkById(500, function ($rows) use ($handle, $map): void {
                foreach ($rows as $row) {
                    fputcsv($handle, $this->escapeFormulaCells($map($row)));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, scalar|null>  $cells
     * @return array<int, scalar|null>
     */
    private function escapeFormulaCells(array $cells): array
    {
        return array_map(function (mixed $cell): mixed {
            if (! is_string($cell) || $cell === '') {
                return $cell;
            }

            return preg_match('/^[\p{Z}\p{C}]*[=+\-@]/u', $cell) === 1 ? "'".$cell : $cell;
        }, $cells);
    }
}
