<?php
require './vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

/**
 * Token generated from profile
 */
$token = $_ENV['TOKEN'];

$url = $_ENV['LINK'];

// Koneksi ke database
$host = $_ENV['DB'];
$dbname = $_ENV['DBNAME'];
$username = $_ENV['USERNAME'];
$password = $_ENV['PASSWORD'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && !empty($_FILES['file']['name'][0])) {
        $type = $_POST['type'] ?? '';

        if (empty($type)) {
            die('Error: Document type is required.');
        }

        if (empty($token)) {
            die('Error: API token is not set.');
        }

        $client = new \GuzzleHttp\Client();
        $processedResults = [];

        foreach ($_FILES['file']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['file']['error'][$key] !== UPLOAD_ERR_OK) {
                continue; // Skip jika ada error di file ini
            }

            $fileResource = fopen($tmpName, 'rb');
            if (!$fileResource) {
                continue;
            }

            try {
                $response = $client->request('PUT', "$url/$type", [
                    'headers' => [
                        'Authentication' => "bearer $token"
                    ],
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => $fileResource,
                            'filename' => $_FILES['file']['name'][$key]
                        ],
                    ]
                ]);

                $result = json_decode($response->getBody()->getContents(), true);

                $nik = $result['result']['nik']['value'] ?? null;
                $nama = $result['result']['nama']['value'] ?? null;

                if ($nik && $nama) {
                    // Save image to folder
                    $imageDir = __DIR__ . "/image/";
                    if (!file_exists($imageDir)) {
                        mkdir($imageDir, 0777, true);
                    }

                    $newImageName = '(' . $nama . ') ' . $nik . ".jpg";
                    move_uploaded_file($tmpName, $imageDir . $newImageName);

                    // Simpan ke array
                    $processedResults[] = [
                        'provinsi' => $result['result']['provinsi']['value'] ?? '',
                        'kabupaten_kota' => $result['result']['kabupatenKota']['value'] ?? '',
                        'tanggal_diterbitkan' => !empty($result['result']['tanggalDiterbitkan']['value'])
                            ? date('Y-m-d', strtotime($result['result']['tanggalDiterbitkan']['value']))
                            : '',
                        'tempat_diterbitkan' => $result['result']['tempatDiterbitkan']['value'] ?? '',
                        'nik' => $nik,
                        'nama' => $nama,
                        'jenis_kelamin' => $result['result']['jenisKelamin']['value'] ?? '',
                        'tanggal_lahir' => !empty($result['result']['tanggalLahir']['value'])
                            ? date('Y-m-d', strtotime($result['result']['tanggalLahir']['value']))
                            : '',
                        'tempat_lahir' => $result['result']['tempatLahir']['value'] ?? '',
                        'rt' => $result['result']['rt']['value'] ?? '',
                        'rw' => $result['result']['rw']['value'] ?? '',
                        'alamat' => $result['result']['alamat']['value'] ?? '',
                        'kelurahan_desa' => $result['result']['kelurahanDesa']['value'] ?? '',
                        'kecamatan' => $result['result']['kecamatan']['value'] ?? '',
                        'agama' => $result['result']['agama']['value'] ?? '',
                        'status_perkawinan' => $result['result']['statusPerkawinan']['value'] ?? '',
                        'kewarganegaraan' => $result['result']['kewarganegaraan']['value'] ?? '',
                        'pekerjaan' => $result['result']['pekerjaan']['value'] ?? '',
                        'berlaku_hingga' => $result['result']['berlakuHingga']['value'] ?? '',
                        'image_name' => $newImageName
                    ];
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                // Bisa catat error per file kalau mau
                continue;
            } catch (Exception $e) {
                continue;
            }
        }

        // Jika tidak ada data berhasil
        if (empty($processedResults)) {
            die('No valid file processed.');
        }

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Provinsi',
            'Kabupaten/Kota',
            'Tanggal Diterbitkan',
            'Tempat Diterbitkan',
            'NIK',
            'Nama',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Tempat Lahir',
            'RT',
            'RW',
            'Alamat',
            'Kelurahan/Desa',
            'Kecamatan',
            'Agama',
            'Status Perkawinan',
            'Kewarganegaraan',
            'Pekerjaan',
            'Berlaku Hingga',
            'Nama Gambar'
        ];
        $sheet->fromArray($headers, NULL, 'A1');

        $rowNum = 2;
        foreach ($processedResults as $item) {
            $sheet->fromArray([
                $item['provinsi'],
                $item['kabupaten_kota'],
                null, // sementara kosongin tanggal diterbitkan
                $item['tempat_diterbitkan'],
                null, // sementara kosongin NIK, nanti kita isi pakai setCellValueExplicit
                $item['nama'],
                $item['jenis_kelamin'],
                null, // sementara kosongin tanggal lahir
                $item['tempat_lahir'],
                $item['rt'],
                $item['rw'],
                $item['alamat'],
                $item['kelurahan_desa'],
                $item['kecamatan'],
                $item['agama'],
                $item['status_perkawinan'],
                $item['kewarganegaraan'],
                $item['pekerjaan'],
                $item['berlaku_hingga'],
                $item['image_name'],
            ], NULL, 'A' . $rowNum);

            $tanggal_diterbitkan = \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($item['tanggal_diterbitkan']);
            $sheet->setCellValue('C' . $rowNum, $tanggal_diterbitkan);
            // Atur style cell supaya tampil sebagai tanggal
            $sheet->getStyle('C' . $rowNum)->getNumberFormat()->setFormatCode('yyyy/mm/dd');

            $timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($item['tanggal_lahir']);
            $sheet->setCellValue('H' . $rowNum, $timestamp);
            // Atur style cell supaya tampil sebagai tanggal
            $sheet->getStyle('H' . $rowNum)->getNumberFormat()->setFormatCode('yyyy/mm/dd'); // kamu bisa ganti format sesuai mau

            // Sekarang khusus untuk kolom E (NIK), set sebagai TEXT
            $sheet->setCellValueExplicit('E' . $rowNum, $item['nik'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $rowNum++;
        }

        // Output file Excel untuk download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="hasil_upload_ktp.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } else {
        die('No file uploaded.');
    }
} else {
    die('Invalid request.');
}
