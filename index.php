<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekrutmen VDNI | OCR </title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4" style="width: 100%; max-width: 500px;">
            <h2 class="card-title text-center mb-4">Optical Character Recognition</h2>
            <form id="uploadForm" action="postfile.php" method="post" enctype="multipart/form-data">
                <!-- Drag & Drop Zone -->
                <div id="dropZone" class="border border-primary border-dashed rounded p-4 text-center mb-3" style="cursor: pointer;">
                    <p class="text-muted">Drag & Drop files here or click to select</p>
                    <input type="file" name="file[]" id="file" class="form-control d-none" multiple required>
                </div>

                <!-- File List -->
                <div id="fileList" class="mb-3 small text-muted"></div>

                <!-- Select Document Type -->
                <div class="mb-3">
                    <label for="type" class="form-label">Select document type:</label>
                    <select name="type" id="type" class="form-select" required>
                        <option selected value="ktp">KTP</option>
                        <option value="npwp" disabled>NPWP</option>
                        <option value="sim-2019" disabled>SIM 2019</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100">Upload</button>
            </form>

            <!-- Loading Spinner -->
            <div id="loading" class="d-none text-center mt-3">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Uploading...</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function() {
            // Show the loading spinner
            document.getElementById('loading').classList.remove('d-none');

            // Hide the loading spinner after 5 seconds (5000 milliseconds)
            setTimeout(function() {
                document.getElementById('loading').classList.add('d-none');
            }, 5000);
        });
    </script>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('file');
        const fileList = document.getElementById('fileList');

        // Click on drop zone triggers file input
        dropZone.addEventListener('click', () => fileInput.click());

        // Drag over effect
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('bg-light');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('bg-light');
        });

        // Drop file handler
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('bg-light');

            fileInput.files = e.dataTransfer.files;
            displayFileNames(fileInput.files);
        });

        // Change handler for manual selection
        fileInput.addEventListener('change', () => {
            displayFileNames(fileInput.files);
        });

        // Display file names
        function displayFileNames(files) {
            if (!files.length) {
                fileList.innerHTML = '<em>No file chosen</em>';
                return;
            }

            const names = Array.from(files).map(f => `• ${f.name}`).join('<br>');
            fileList.innerHTML = names;
        }
    </script>

</body>

</html>