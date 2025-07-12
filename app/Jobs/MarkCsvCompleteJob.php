<?php

namespace App\Jobs;

use App\Models\CsvFileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MarkCsvCompleteJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $csvId;

    public function __construct($csvId)
    {
        $this->csvId = $csvId;
    }

    public function handle()
    {
        $csv = CsvFileUpload::find($this->csvId);

        if (!$csv) {
            return;
        }

        if ($csv->processed_rows >= $csv->total_rows) {
            $csv->update(['status' => 'completed']);
        }
    }
}
