<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kasir') {
    header("Location: ../../auth/login.php");
    exit;
}

$id_transaksi = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "
    SELECT t.*, u.nama AS nama_pembeli
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    WHERE t.id_transaksi = ? AND t.status = 'selesai'
");

mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$transaksi) {
    exit('Nota tidak ditemukan atau pesanan belum selesai.');
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Nota</title>

<style>
body {
    font-family: Arial, sans-serif;
    color: #333;
}

.nota {
    width: 320px;
    margin: 20px auto;
    padding: 15px;
}

.center {
    text-align: center;
}

hr {
    border: none;
    border-top: 1px dashed #777;
    margin: 12px 0;
}

.row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin: 9px 0;
    font-size: 13px;
}

.produk {
    margin: 14px 0;
    font-size: 13px;
}

.total {
    font-weight: bold;
    font-size: 15px;
}

@media print {
    .no-print {
        display: none;
    }

    body {
        margin: 0;
    }

    .nota {
        margin: 0 auto;
    }
}
</style>
</head>

<body>

<div class="nota">

    <div class="center">
        <h2>🥐 Bakery & Cake</h2>
        <p>Nota Pemesanan</p>
    </div>

    <hr>

    <div class="row">
        <span>No. Pesanan</span>
        <strong>#<?= $transaksi['id_transaksi'] ?></strong>
    </div>

    <div class="row">
        <span>Pembeli</span>
        <strong><?= htmlspecialchars($transaksi['nama_pembeli']) ?></strong>
    </div>

    <div class="row">
        <span>Tanggal</span>
        <span><?= date('d-m-Y H:i', strtotime($transaksi['tanggal_transaksi'])) ?></span>
    </div>

    <div class="row">
        <span>Pembayaran</span>
        <strong><?= ucfirst($transaksi['metode_pembayaran']) ?></strong>
    </div>

    <hr>

    <?php while ($item = mysqli_fetch_assoc($detail)): ?>
        <div class="produk">
            <strong><?= htmlspecialchars($item['nama_produk']) ?></strong>

            <div class="row">
                <span>
                    <?= $item['jumlah'] ?> x
                    Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?>
                </span>

                <strong>
                    Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                </strong>
            </div>
        </div>
    <?php endwhile; ?>

    <hr>

    <div class="row total">
        <span>TOTAL</span>
        <span>Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></span>
    </div>

    <hr>

    <div class="center">
        <p>Terima kasih telah berbelanja!</p>
        <small>Bakery & Cake - Made with Love</small>
    </div>

</div>

<div class="center no-print">
    <button onclick="window.print()">Cetak Nota</button>
</div>

<script>
window.onload = function () {
    window.print();
};
</script>

</body>
</html>