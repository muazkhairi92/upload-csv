<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSV Upload & Progress</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
            background-color: #f9f9f9;
        }

        form {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        input[type="file"] {
            display: inline-block;
            margin-bottom: 10px;
        }

        button {
            padding: 10px 16px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        h2 {
            color: #333;
            margin-top: 40px;
        }

        ul#csv-list {
            list-style: none;
            padding: 0;
        }

        ul#csv-list li {
            background: #fff;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }

        .progress-text {
            color: #555;
            margin-left: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    @if ($errors->any())
        <div style="color: red; background: #ffe5e5; padding: 10px; margin-bottom: 20px; border-radius: 8px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/upload" enctype="multipart/form-data">
        @csrf
        <label><strong>Upload CSV File:</strong></label><br>
        <div id="dropzone" style="
            border: 2px dashed #007bff;
            padding: 30px;
            text-align: center;
            background: #f0f8ff;
            color: #007bff;
            margin-bottom: 15px;
            border-radius: 12px;
            cursor: pointer;
        ">
            Drag & drop CSV file here or click to browse
            <input type="file" name="csv" id="csv-input" style="display: none;" required>
        </div>
        <p id="file-info" style="font-style: italic; color: #555;"></p>
        <br><br>
        <button type="submit">Upload</button>
    </form>

    <h2>Uploaded CSV Files</h2>

    @if ($csvFiles->isEmpty())
        <p>No CSV files uploaded yet.</p>
    @else
        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <thead style="background-color: #007bff; color: white;">
                <tr>
                    <th style="text-align: left; padding: 12px;">Time</th>
                    <th style="text-align: left; padding: 12px;">File</th>
                    <th style="text-align: left; padding: 12px;">Progress</th>
                </tr>
            </thead>
            <tbody id="csv-list">
                @foreach ($csvFiles as $csv)
                    <tr id="csv-{{ $csv->id }}" style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">{{ $csv->created_at->format('Y-m-d H:i:s') }}</td>
                        <td style="padding: 12px;">{{ $csv->filename }}</td>
                        <td style="padding: 12px;">
                            <div class="progress-bar" style="position: relative; height: 20px; background: #e0e0e0; border-radius: 10px; overflow: hidden;">
                                <div id="bar-{{ $csv->id }}" style="height: 100%; width: 0%; background: #007bff; transition: width 0.5s;"></div>
                                <span class="progress-label" id="label-{{ $csv->id }}" style="position: absolute; left: 10px; top: 0; color: white; font-size: 12px; line-height: 20px;">0%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif


    <script>
        const csvFiles = @json($csvFiles);

        function updateProgress(csv) {
            fetch(`/progress/${csv.id}`)
                .then(res => res.json())
                .then(data => {
                    const percent = Math.round((data.processed / data.total) * 100);
                    const bar = document.getElementById(`bar-${csv.id}`);
                    const label = document.getElementById(`label-${csv.id}`);

                    bar.style.width = percent + '%';
                    label.innerText = `${percent}% (${data.status})`;

                    if (data.status !== 'completed') {
                        setTimeout(() => updateProgress(csv), 2000);
                    }
                })
                .catch(err => {
                    const label = document.getElementById(`label-${csv.id}`);
                    label.innerText = 'Error';
                    label.style.color = 'red';
                });
        }


        csvFiles.forEach(csv => {
            updateProgress(csv);
        });

        const dropzone = document.getElementById('dropzone');
        const input = document.getElementById('csv-input');
        const fileInfo = document.getElementById('file-info');

        function displayFileInfo(file) {
            if (!file) {
                fileInfo.innerText = '';
                return;
            }

            const sizeKB = (file.size / 1024).toFixed(1);
            fileInfo.innerText = `Selected File: ${file.name} (${sizeKB} KB)`;
        }

        dropzone.addEventListener('click', () => input.click());

        input.addEventListener('change', function () {
            displayFileInfo(input.files[0]);
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.background = '#e0f7ff';
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.style.background = '#f0f8ff';
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.background = '#f0f8ff';

            const file = e.dataTransfer.files[0];
            if (file && file.type === 'text/csv') {
                input.files = e.dataTransfer.files;
                displayFileInfo(file);
            } else {
                alert('Only CSV files are allowed.');
            }
        });

    </script>

</body>
</html>
