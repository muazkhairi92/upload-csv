<?php

namespace App\Jobs;

use App\Models\CsvFileUpload;
use App\Models\CsvData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class ProcessCsvJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600;
    public $tries = 3;
    public $csv;

    public function __construct(CsvFileUpload $csv)
    {
        $this->csv = $csv;
    }

    public function handle()
    {
        $this->csv->update(['status' => 'processing']);

        $path = storage_path("app/{$this->csv->path}");
        if (!file_exists($path)) {
                throw new \Exception("File not found: " . $path);
        }
        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new \Exception("Cannot open CSV file at path: {$path}");
        }

        // Expected header
        $requiredHeaders = [
            'UNIQUE_KEY', 'STYLE#', 'SIZE', 'COLOR_NAME',
            'PRODUCT_TITLE', 'PRODUCT_DESCRIPTION',
            'SANMAR_MAINFRAME_COLOR', 'PIECE_PRICE'
        ];

        //Read header row
        $header = fgetcsv($handle);

        if (isset($header[0])) {
            // Remove BOM if present
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        $normalizedHeader = array_map(fn($h) => strtoupper(trim($h)), $header);
        $normalizedRequired = array_map(fn($h) => strtoupper(trim($h)), $requiredHeaders);
        $missing = array_diff($normalizedRequired, $normalizedHeader);

        if (!$header || !empty($missing)) {
            fclose($handle);
            throw new \Exception('Missing required headers. Required: ' . implode(', ', $requiredHeaders));
        }

        // Count total rows excluding header
        $rowCount = 0;
        while (fgets($handle)) {
            $rowCount++;
        }
        rewind($handle);
        fgetcsv($handle); // skip header again after rewind

        $this->csv->update(['total_rows' => $rowCount]);

        // Load and chunk rows
        $rows = [];
        $totalRows = 0;

        while (($row = fgetcsv($handle)) !== false) {

            if (count(array_filter($row)) === 0) {
                continue;
            }

            // Reindex row to match normalized headers
            $assoc = array_combine($normalizedHeader, $row);

            // Optional: Filter to only required keys (in correct order)
            $filtered = array_intersect_key($assoc, array_flip($normalizedRequired));
            $rows[] = $filtered;
            $totalRows++;

            if (count($rows) === 500) {
                ProcessCsvChunkJob::dispatch($rows, $this->csv->id);
                $rows = [];
            }
        }

        // Dispatch last batch if any
        if (count($rows) > 0) {
            ProcessCsvChunkJob::dispatch($rows, $this->csv->id);
        }

        fclose($handle);

        // Final update
        $this->csv->update([
            'processed_rows' => 0,
            'status' => 'queued',
        ]);

    }
}
