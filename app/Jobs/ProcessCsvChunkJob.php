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
        
            $uniqueKey = $row['UNIQUE_KEY'];

            CsvData::updateOrCreate(
                ['unique_key' => $uniqueKey],
                [
                    'product_title' => $row['PRODUCT_TITLE'] ?? null,
                    'product_description' => $row['PRODUCT_DESCRIPTION'] ?? null,
                    'style' => $row['STYLE#'] ?? null,
                    'sanmar_mainframe_color' => $row['SANMAR_MAINFRAME_COLOR'] ?? null,
                    'size' => $row['SIZE'] ?? null,
                    'color_name' => $row['COLOR_NAME'] ?? null,
                    'file_id' => $this->fileId,
                    'piece_price' => $row['PIECE_PRICE'] ?? null,
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
