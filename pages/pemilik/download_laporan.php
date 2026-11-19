
<?php
ob_start();

session_start();

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pemilik') {
    ob_end_clean();
    exit('Akses ditolak.');
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

try {
    $query = mysqli_query($conn, "
        SELECT
            t.id_transaksi,
            u.nama AS nama_pelanggan,
            t.tanggal_transaksi,
            t.total,
            t.metode_pembayaran,
            t.status
        FROM transaksi t
        LEFT JOIN users u ON t.id_user = u.id_user
        ORDER BY t.tanggal_transaksi DESC
    ");

    if (!$query) {
        throw new Exception(mysqli_error($conn));
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setTitle('Laporan Pemesanan');

    // Judul laporan
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', 'LAPORAN PEMESANAN BAKERY & CAKE');

    $sheet->getStyle('A1:F1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 16,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '795548']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);

    $sheet->getRowDimension(1)->setRowHeight(30);

    // Header tabel
    $header = [
        'ID Transaksi',
        'Nama Pelanggan',
        'Tanggal Pemesanan',
        'Total Pembayaran',
        'Metode Pembayaran',
        'Status'
    ];

    $sheet->fromArray($header, null, 'A3');

    $sheet->getStyle('A3:F3')->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '9B5C38']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ]);

    // Isi semua transaksi
    $baris = 4;

    while ($data = mysqli_fetch_assoc($query)) {
        $sheet->setCellValue('A' . $baris, $data['id_transaksi']);
        $sheet->setCellValue('B' . $baris, $data['nama_pelanggan'] ?? '-');
        $sheet->setCellValue('C' . $baris, $data['tanggal_transaksi']);
        $sheet->setCellValue('D' . $baris, (float)$data['total']);
        $sheet->setCellValue('E' . $baris, $data['metode_pembayaran'] ?? '-');
        $sheet->setCellValue('F' . $baris, $data['status']);

        $baris++;
    }

    // Format rupiah
    if ($baris > 4) {
        $sheet->getStyle('D4:D' . ($baris - 1))
            ->getNumberFormat()
            ->setFormatCode('"Rp" #,##0');
    }

    // Atur lebar kolom
    foreach (range('A', 'F') as $kolom) {
        $sheet->getColumnDimension($kolom)->setAutoSize(true);
    }

    $sheet->freezePane('A4');
    $sheet->setAutoFilter('A3:F' . max(3, $baris - 1));

    // Bersihkan semua output sebelum mengirim file
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $nama_file = 'Laporan_Pemesanan_Bakery_Cake.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nama_file . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    $spreadsheet->disconnectWorksheets();
    exit;

} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    error_log($e->getMessage());
    http_response_code(500);
    exit('Gagal membuat file Excel. Periksa PHP error log.');
}
