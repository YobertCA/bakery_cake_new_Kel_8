
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Fungsi mengirim respons JSON
function kirimRespons($success, $message, $id_transaksi = null, $status = 400)
{
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'id_transaksi' => $id_transaksi
    ]);

    exit;
}

// Pastikan pengguna adalah pembeli
if (
    !isset($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'pembeli'
) {
    kirimRespons(false, 'Akses ditolak.', null, 403);
}

// Pastikan permintaan menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kirimRespons(false, 'Metode permintaan tidak valid.');
}

// Ambil data pembayaran
$id_user = (int) $_SESSION['id_user'];
$metode = $_POST['metode_pembayaran'] ?? '';

// Validasi metode pembayaran
if (!in_array($metode, ['tunai', 'transfer'], true)) {
    kirimRespons(false, 'Silakan pilih metode pembayaran.');
}

// Ambil keranjang
$keranjang = $_SESSION['keranjang'] ?? [];

if (empty($keranjang)) {
    kirimRespons(false, 'Keranjang masih kosong.');
}

// Mulai transaksi database
mysqli_begin_transaction($conn);

try {

    $items = [];
    $total = 0;

    // Periksa stok dan harga terbaru
    foreach ($keranjang as $id_produk => $item) {

        $id_produk = (int) $id_produk;
        $jumlah = (int) ($item['jumlah'] ?? 0);

        if ($id_produk < 1 || $jumlah < 1) {
            throw new Exception('Data produk atau jumlah tidak valid.');
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_produk, nama_produk, harga, stok, status
             FROM produk
             WHERE id_produk = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$produk) {
            throw new Exception('Produk tidak ditemukan.');
        }

        if ($produk['status'] !== 'tersedia') {
            throw new Exception(
                'Produk ' . $produk['nama_produk'] . ' tidak tersedia.'
            );
        }

        if ($jumlah > (int) $produk['stok']) {
            throw new Exception(
                'Stok ' . $produk['nama_produk'] .
                ' tidak mencukupi. Silakan periksa keranjang.'
            );
        }

        $harga = (float) $produk['harga'];
        $subtotal = $harga * $jumlah;

        $total += $subtotal;

        $items[] = [
            'id_produk' => $id_produk,
            'jumlah' => $jumlah,
            'harga' => $harga,
            'subtotal' => $subtotal
        ];
    }

    if (empty($items)) {
        throw new Exception('Tidak ada produk yang dapat diproses.');
    }

    // Simpan transaksi utama
    $status = 'pending';

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO transaksi
         (id_user, total, metode_pembayaran, status)
         VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "idss",
        $id_user,
        $total,
        $metode,
        $status
    );

    mysqli_stmt_execute($stmt);

    $id_transaksi = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    // Simpan detail transaksi
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO detail_transaksi
         (id_transaksi, id_produk, jumlah, harga_satuan, subtotal)
         VALUES (?, ?, ?, ?, ?)"
    );

    foreach ($items as $item) {

        mysqli_stmt_bind_param(
            $stmt,
            "iiidd",
            $id_transaksi,
            $item['id_produk'],
            $item['jumlah'],
            $item['harga'],
            $item['subtotal']
        );

        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);

    // Simpan transaksi
    mysqli_commit($conn);

    // Kosongkan keranjang setelah transaksi berhasil
    unset($_SESSION['keranjang']);

    // Kirim hasil ke dashboard
    kirimRespons(
        true,
        'Pesanan berhasil dibuat.',
        $id_transaksi,
        200
    );

} catch (Throwable $e) {

    mysqli_rollback($conn);

    error_log('Checkout error: ' . $e->getMessage());

    kirimRespons(
        false,
        $e->getMessage(),
        null,
        400
    );
}
