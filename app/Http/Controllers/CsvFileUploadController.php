<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CsvFileUpload;
use App\Jobs\ProcessCsvJob;
use App\Models\CsvData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class CsvFileUploadController extends Controller
{
    public function index()
    {
        $csvFiles = CsvFileUpload::latest()->get();

        return view('upload', compact('csvFiles'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv|max:120000', 
        ]);
        $file = $request->file('csv');
        $fileName = $file->getClientOriginalName();
        $directory = 'csvs';

        // Expected header
        $requiredHeaders = [
            'UNIQUE_KEY', 'STYLE#', 'SIZE', 'COLOR_NAME',
            'PRODUCT_TITLE', 'PRODUCT_DESCRIPTION',
            'SANMAR_MAINFRAME_COLOR', 'PIECE_PRICE'
        ];
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        $normalizedHeader = array_map(fn($h) => strtoupper(trim($h)), $header);
        $normalizedRequired = array_map(fn($h) => strtoupper(trim($h)), $requiredHeaders);
        $missing = array_diff($normalizedRequired, $normalizedHeader);

        if (!$header || !empty($missing)) {
            return redirect('/')
                ->withErrors(['csv' => 'Missing required headers: '. implode(', ',  $missing)])
                ->withInput();        
        }
        fclose($handle);

        $path = $file->storeAs('csvs', uniqid() . '-' . $fileName,'local');

        $csv = CsvFileUpload::create([
            'filename' => $fileName,
            'path' => $path,
            'status' => 'pending',
        ]);

        dispatch(new ProcessCsvJob($csv));

        return redirect("/");
    }

    public function progress(CsvFileUpload $csvFile)
    {
        return response()->json([
            'processed' => $csvFile->processed_rows,
            'total' => $csvFile->total_rows,
            'status' => $csvFile->status,
        ]);
    }

}
