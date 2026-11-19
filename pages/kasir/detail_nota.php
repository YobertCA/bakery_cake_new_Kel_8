<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

if (
    !isset($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'kasir'
) {
    header("Location: ../../auth/login.php");
    exit;
}

$id_transaksi = (int)($_GET['id'] ?? 0);

if ($id_transaksi <= 0) {
    http_response_code(400);
    exit('ID pesanan tidak valid.');
}

$stmt = mysqli_prepare($conn, "
    SELECT t.*, u.nama AS nama_pembeli
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    WHERE t.id_transaksi = ?
      AND t.status IN ('diproses', 'selesai')
");
mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$transaksi) {
    http_response_code(404);
    exit('Nota tidak ditemukan.');
}

$stmt = mysqli_prepare($conn, "
    SELECT p.nama_produk, d.jumlah, d.harga_satuan, d.subtotal
    FROM detail_transaksi d
    JOIN produk p ON d.id_produk = p.id_produk
    WHERE d.id_transaksi = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$detail = mysqli_stmt_get_result($stmt);

function rupiahDetailNota($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Detail Nota #<?= (int)$transaksi['id_transaksi'] ?> - Bakery &amp; Cake</title>
<style>
*{box-sizing:border-box;font-family:'Segoe UI',sans-serif}
body{margin:0;padding:30px 15px;background:#fff8f2;color:#44352d}
.container{max-width:760px;margin:auto;background:#fff;padding:30px;border:1px solid #f0e4dc;border-radius:16px;box-shadow:0 5px 25px #00000010}
h2{text-align:center;color:#9b5c38;margin:0}
.subtitle{text-align:center;color:#806b5d;margin:7px 0 22px}
.info{display:grid;grid-template-columns:1fr 1fr;gap:14px 20px;margin:22px 0;padding:18px;background:#fff8f2;border-radius:10px}
.info div{font-size:13px;color:#806b5d}
.info strong{display:block;color:#44352d;margin-top:5px;font-size:14px}
.table-wrap{overflow-x:auto}
table{width:100%;min-width:540px;border-collapse:collapse;margin-top:12px}
th,td{padding:13px 10px;border-bottom:1px solid #eee0d6;text-align:left;font-size:13px}
th{background:#fff8f2;color:#9b5c38}
.num{text-align:right;white-space:nowrap}
.total{display:flex;justify-content:space-between;gap:15px;margin-top:22px;padding-top:18px;border-top:2px solid #eee0d6;font-size:19px;font-weight:bold;color:#9b5c38}
.back{display:inline-block;margin-top:24px;padding:11px 18px;border-radius:8px;background:#eee0d6;color:#795548;text-decoration:none}
@media(max-width:600px){.container{padding:20px}.info{grid-template-columns:1fr}.total{font-size:16px}}
</style>
</head>
<body>
<div class="container">
    <h2>🥐 Bakery &amp; Cake</h2>
    <p class="subtitle">Detail Nota Pemesanan</p>

    <div class="info">
        <div>Nomor Pesanan<strong>#<?= (int)$transaksi['id_transaksi'] ?></strong></div>
        <div>Nama Pembeli<strong><?= htmlspecialchars($transaksi['nama_pembeli']) ?></strong></div>
        <div>Tanggal<strong><?= date('d-m-Y H:i', strtotime($transaksi['tanggal_transaksi'])) ?></strong></div>
        <div>Metode Pembayaran<strong><?= htmlspecialchars(ucfirst($transaksi['metode_pembayaran'] ?? '-')) ?></strong></div>
        <div>Status<strong><?= htmlspecialchars(ucfirst($transaksi['status'])) ?></strong></div>
    </div>

    <h3 style="color:#9b5c38">Rincian Produk</h3>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Produk</th><th class="num">Jumlah</th><th class="num">Harga</th><th class="num">Subtotal</th></tr></thead>
        <tbody>
        <?php while ($item = mysqli_fetch_assoc($detail)): ?>
            <tr>
                <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                <td class="num"><?= (int)$item['jumlah'] ?></td>
                <td class="num"><?= rupiahDetailNota($item['harga_satuan']) ?></td>
                <td class="num"><?= rupiahDetailNota($item['subtotal']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <div class="total"><span>Total Pesanan</span><span><?= rupiahDetailNota($transaksi['total']) ?></span></div>
    <a class="back" href="dashboard_kasir.php?halaman=laporan_pemesanan">Kembali ke Laporan</a>
</div>
</body>
</html>
