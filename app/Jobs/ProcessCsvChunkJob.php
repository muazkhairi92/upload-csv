<?php

namespace App\Jobs;

use App\Models\CsvData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\CsvFileUpload;

class ProcessCsvChunkJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $rows;
    protected $fileId;

    public $timeout = 3600;
    public $tries = 3;

    public function __construct(array $rows, $fileId)
    {
        $this->rows = $rows;
        $this->fileId = $fileId;
    }

    public function handle()
    {
        foreach ($this->rows as $row) {
            $uniqueKey = $row[0];

            CsvData::updateOrCreate(
                ['unique_key' => $uniqueKey],
                [
                    'product_title' => $row[4] ?? null,
                    'product_description' => $row[5] ?? null,
                    'style' => $row[1] ?? null,
                    'sanmar_mainframe_color' => $row[6] ?? null,
                    'size' => $row[2] ?? null,
                    'color_name' => $row[3] ?? null,
                    'file_id' => $this->fileId,
                    'piece_price' => $row[7] ?? null,
                ]
            );
            
        }
        // Update processed row count
        $processedCount = count($this->rows);

        $csv = CsvFileUpload::find($this->fileId);
        if ($csv) {
            $csv->increment('processed_rows', $processedCount);

            // Dispatch finalization job if complete
            if ($csv->processed_rows + $processedCount >= $csv->total_rows) {
                MarkCsvCompleteJob::dispatch($this->fileId);
            }
        }
    }
}
