<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvFileUploadController;

Route::get('/', [CsvFileUploadController::class, 'index']);
Route::post('/upload', [CsvFileUploadController::class, 'upload']);
Route::get('/progress/{csvFile}', [CsvFileUploadController::class, 'progress']);
