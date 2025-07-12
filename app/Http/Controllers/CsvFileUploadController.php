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
        $validator = Validator::make($request->all(), [
            'csv' => 'required|file|mimes:csv|max:120000', 
        ]);
            
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
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

        if (!$header || !empty(array_diff($requiredHeaders, array_map('strtoupper', $header)))) {
            fclose($handle);
            throw new \Exception('Missing required headers. Required: ' . implode(', ', $requiredHeaders));
        }
        fclose($handle);

        $uniqueFilename = $this->getUniqueFilename($directory, $fileName);

        $path = $file->storeAs('csvs', uniqid() . '-' . $uniqueFilename,'local');

        $csv = CsvFileUpload::create([
            'filename' => $uniqueFilename,
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

    /**
     * Get a unique filename for the upload
     */
    private function getUniqueFilename($directory, $originalName)
    {
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $counter = 0;
        $newName = $originalName;

        while (Storage::exists($directory . '/' . $newName)) {
            $counter++;
            $newName = $filename . "($counter)." . $extension;
        }

        return $newName;
    }

}
