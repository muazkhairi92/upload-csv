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

        if (!$header || !empty(array_diff($requiredHeaders, array_map('strtoupper', $header)))) {
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
            $rows[] = $row;
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
