<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvData extends Model
{
     protected $fillable = [
        'unique_key',
        'product_title',
        'product_description',
        'style',
        'sanmar_mainframe_color',
        'size',
        'color_name',
        'piece_price',
        'file_id'
    ];

    public function file()
    {
        return $this->belongsTo(CsvFileUpload::class, 'file_id');
    }
}
