<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvFileUpload extends Model
{
        protected $fillable = [
        'filename',
        'path',
        'status',
        'processed_rows',
        'total_rows',
    ];

    /**
     * Get the data records associated with this file
     */
    public function csvData()
    {
        return $this->hasMany(CsvData::class, 'file_id');
    }
}
