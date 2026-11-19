
<?php
require_once __DIR__ . '/../../config/database.php';

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
    die("Gagal mengambil data: " . mysqli_error($conn));
}
?>

<style>
.laporan-header {
    margin-bottom: 22px;
}

.laporan-header h2 {
    color: #4e342e;
    margin-bottom: 7px;
}

.laporan-header p {
    color: #8d6e63;
    font-size: 14px;
}

.btn-excel {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #795548;
    color: white;
    text-decoration: none;
    padding: 11px 18px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: bold;
    margin-top: 15px;
    margin-bottom: 20px;
    transition: .2s;
}

.btn-excel:hover {
    background: #9b5c38;
}

.tabel-wrapper {
    overflow-x: auto;
}

.tabel-laporan {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.tabel-laporan th {
    background: #795548;
    color: white;
    padding: 13px;
    text-align: left;
    white-space: nowrap;
}

.tabel-laporan td {
    padding: 13px;
    border-bottom: 1px solid #eee2da;
}

.tabel-laporan tr:hover {
    background: #fff8f2;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.pending {
    background: #fff0c2;
    color: #946200;
}

.diproses {
    background: #dcecff;
    color: #245c9b;
}

.selesai {
    background: #d9f3df;
    color: #26723a;
}

.batal {
    background: #ffe0e0;
    color: #a52a2a;
}

.kosong {
    text-align: center;
    padding: 25px;
    color: #8d6e63;
}
</style>

<div class="laporan-header">

    <a href="download_laporan.php" class="btn-excel">
        📥 Download Semua Laporan Excel
    </a>
</div>

<div class="tabel-wrapper">
    <table class="tabel-laporan">
        <thead>
            <tr>
                <th>No.</th>
                <th>ID Transaksi</th>
                <th>Pelanggan</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Pembayaran</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php if (mysqli_num_rows($query) > 0): ?>

            <?php $no = 1; ?>

            <?php while ($row = mysqli_fetch_assoc($query)): ?>

                <?php
                $status = strtolower($row['status']);

                $kelas_status = in_array(
                    $status,
                    ['pending', 'diproses', 'selesai', 'batal']
                ) ? $status : '';
                ?>

                <tr>
                    <td><?= $no++ ?></td>

                    <td>
                        #<?= (int)$row['id_transaksi'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['nama_pelanggan'] ?? '-') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['tanggal_transaksi']) ?>
                    </td>

                    <td>
                        Rp <?= number_format((float)$row['total'], 0, ',', '.') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['metode_pembayaran'] ?? '-') ?>
                    </td>

                    <td>
                        <span class="status <?= $kelas_status ?>">
                            <?= htmlspecialchars(ucfirst($status)) ?>
                        </span>
                    </td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="7" class="kosong">
                    Belum ada data pemesanan.
                </td>
            </tr>

        <?php endif; ?>
        </tbody>
    </table>
</div>
