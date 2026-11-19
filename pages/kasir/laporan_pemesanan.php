<?php
require_once __DIR__ . '/../../config/database.php';

// Ambil seluruh transaksi
$query = mysqli_query(
    $conn,
    "SELECT t.id_transaksi,
            t.tanggal_transaksi,
            t.total,
            t.metode_pembayaran,
            t.status,
            u.nama
     FROM transaksi t
     LEFT JOIN users u ON t.id_user = u.id_user
     ORDER BY t.tanggal_transaksi DESC"
);

$totalTransaksi = $query ? mysqli_num_rows($query) : 0;

function rupiahLaporan($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function labelStatus($status) {
    switch (strtolower($status)) {
        case 'pending': return 'Menunggu Diproses';
        case 'diproses': return 'Sedang Diproses';
        case 'selesai': return 'Selesai';
        case 'dibatalkan':
        case 'batal': return 'Dibatalkan';
        default: return ucfirst($status);
    }
}
?>

<style>
.laporan-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:25px}
.jumlah-transaksi{background:#f2e3d7;color:#9b5c38;padding:9px 15px;border-radius:20px;font-size:12px;font-weight:bold}
.laporan-card{background:#fff;padding:22px;border:1px solid #f0e4dc;border-radius:14px;margin-bottom:18px;box-shadow:0 3px 12px #00000005}
.laporan-top{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:20px}
.laporan-top h3{color:#9b5c38;font-size:17px;margin:0 0 8px}
.laporan-top small{display:block;color:#806b5d;margin-top:5px}
.status-laporan{padding:9px 13px;border-radius:20px;font-size:12px;font-weight:bold;white-space:nowrap}
.status-pending{background:#fff0d8;color:#a66a12}
.status-diproses{background:#e5efff;color:#3569a8}
.status-selesai{background:#e3f5e8;color:#26834a}
.status-dibatalkan{background:#fde8e7;color:#b33a32}
.laporan-total{display:flex;justify-content:space-between;align-items:center;padding:18px 0;border-top:1px solid #f0e4dc}
.laporan-total strong{color:#9b5c38;font-size:20px}
.laporan-actions{display:flex;justify-content:flex-end;gap:10px}
.btn-nota{display:inline-block;padding:11px 16px;border-radius:8px;text-decoration:none;font-size:13px;border:1px solid #ead4c5;background:#f8eee7;color:#8b4f31;cursor:pointer}
.btn-cetak{background:#9b5c38;border-color:#9b5c38;color:#fff;font-weight:bold}
.btn-nota:hover{opacity:.86}
.laporan-kosong{background:#fff;padding:50px 20px;text-align:center;border:1px solid #f0e4dc;border-radius:14px}
.laporan-kosong p{color:#806b5d;margin-top:10px}

/* Modal detail nota: menggunakan target agar tetap berfungsi saat konten dimuat via AJAX */
.nota-modal{display:none;position:fixed;inset:0;z-index:99999;background:rgba(35,25,20,.58);padding:22px;align-items:center;justify-content:center}
.nota-modal:target{display:flex}
.nota-modal-box{position:relative;width:min(760px,100%);max-height:90vh;overflow:auto;background:#fffdfa;border:1px solid #ead9cc;border-radius:18px;box-shadow:0 18px 55px rgba(35,25,20,.25);padding:28px}
.nota-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding-bottom:18px;border-bottom:1px solid #eee0d6}
.nota-modal-head h2{margin:0;color:#754b36;font-size:22px}
.nota-modal-head p{margin:6px 0 0;color:#8b7567;font-size:13px}
.nota-close{display:flex;align-items:center;justify-content:center;width:36px;height:36px;flex:0 0 36px;border-radius:50%;background:#f5e9e0;color:#795548;text-decoration:none;font-size:23px;line-height:1}
.nota-close:hover{background:#ead8ca}
.nota-info{display:grid;grid-template-columns:1fr 1fr;gap:14px 24px;padding:20px 0}
.nota-info div{font-size:14px;color:#806b5d}
.nota-info strong{display:block;color:#49382e;margin-top:5px;font-weight:600}
.nota-section-title{margin:8px 0 12px;color:#754b36;font-size:16px}
.nota-table-wrap{overflow-x:auto;border:1px solid #eee0d6;border-radius:10px}
.nota-table{width:100%;border-collapse:collapse;min-width:540px}
.nota-table th,.nota-table td{padding:12px 13px;border-bottom:1px solid #eee5de;text-align:left;font-size:13px}
.nota-table th{background:#fbf3ed;color:#87583e;font-weight:700}
.nota-table tr:last-child td{border-bottom:0}
.nota-number{text-align:right!important;white-space:nowrap}
.nota-total{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-top:20px;padding:18px 0;border-top:2px solid #ead9cc;color:#49382e}
.nota-total strong{font-size:22px;color:#9b5c38}
.nota-modal-foot{display:flex;justify-content:flex-end;margin-top:8px}
.nota-modal-foot a{padding:10px 16px;border-radius:8px;background:#9b5c38;color:#fff;text-decoration:none;font-size:13px;font-weight:600}
.nota-modal-foot a:hover{background:#795548}
.nota-no-detail{padding:18px;color:#806b5d;background:#fbf3ed;border-radius:10px}
@media(max-width:600px){
 .laporan-header{align-items:flex-start;flex-direction:column}
 .laporan-top{align-items:flex-start;flex-direction:column}
 .laporan-actions{flex-direction:column}
 .btn-nota{text-align:center}
 .nota-modal{padding:10px}
 .nota-modal-box{padding:19px;border-radius:14px}
 .nota-info{grid-template-columns:1fr;gap:12px}
 .nota-total strong{font-size:18px}
}
</style>

<div class="laporan-header">
    <h2>Kelola Laporan Pemesanan</h2>
    <span class="jumlah-transaksi"><?= $totalTransaksi ?> Transaksi</span>
</div>

<?php if (!$query || $totalTransaksi === 0): ?>
    <div class="laporan-kosong">
        <h3>📋 Belum Ada Laporan</h3>
        <p>Data transaksi akan muncul setelah pelanggan membuat pesanan.</p>
    </div>
<?php else: ?>
    <?php while ($pesanan = mysqli_fetch_assoc($query)): ?>
        <?php
        $id = (int)$pesanan['id_transaksi'];
        $status = strtolower((string)$pesanan['status']);
        $kelasStatus = [
            'pending' => 'status-pending',
            'diproses' => 'status-diproses',
            'selesai' => 'status-selesai',
            'dibatalkan' => 'status-dibatalkan',
            'batal' => 'status-dibatalkan'
        ][$status] ?? 'status-pending';
        ?>

        <div class="laporan-card">
            <div class="laporan-top">
                <div>
                    <h3>Pesanan #<?= $id ?></h3>
                    <small>Pembeli: <?= htmlspecialchars($pesanan['nama'] ?? '-') ?></small>
                    <small><?= !empty($pesanan['tanggal_transaksi']) ? date('d-m-Y H:i', strtotime($pesanan['tanggal_transaksi'])) : '-' ?></small>
                </div>
                <span class="status-laporan <?= $kelasStatus ?>">
                    <?= htmlspecialchars(labelStatus($status)) ?>
                </span>
            </div>

            <div class="laporan-total">
                <span>Total Pesanan</span>
                <strong><?= rupiahLaporan($pesanan['total']) ?></strong>
            </div>

            <?php if (in_array($status, ['diproses', 'selesai'], true)): ?>
                <div class="laporan-actions">
                    <a class="btn-nota" href="#detail-nota-<?= $id ?>">Lihat Detail Nota</a>
                    <a class="btn-nota btn-cetak"
                       href="cetak_nota.php?id=<?= $id ?>"
                       target="_blank" rel="noopener">Cetak Nota</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (in_array($status, ['diproses', 'selesai'], true)): ?>
            <?php
            $stmtDetail = mysqli_prepare($conn, "
                SELECT p.nama_produk, d.jumlah, d.harga_satuan, d.subtotal
                FROM detail_transaksi d
                JOIN produk p ON d.id_produk = p.id_produk
                WHERE d.id_transaksi = ?
            ");
            mysqli_stmt_bind_param($stmtDetail, "i", $id);
            mysqli_stmt_execute($stmtDetail);
            $detailNota = mysqli_stmt_get_result($stmtDetail);
            $adaDetail = $detailNota && mysqli_num_rows($detailNota) > 0;
            ?>
            <div class="nota-modal" id="detail-nota-<?= $id ?>">
                <div class="nota-modal-box">
                    <div class="nota-modal-head">
                        <div>
                            <h2>Detail Nota #<?= $id ?></h2>
                            <p>Ringkasan pesanan Bakery &amp; Cake</p>
                        </div>
                        <a class="nota-close" href="#" aria-label="Tutup">&times;</a>
                    </div>

                    <div class="nota-info">
                        <div>Nama Pembeli<strong><?= htmlspecialchars($pesanan['nama'] ?? '-') ?></strong></div>
                        <div>Tanggal Pesanan<strong><?= !empty($pesanan['tanggal_transaksi']) ? date('d-m-Y H:i', strtotime($pesanan['tanggal_transaksi'])) : '-' ?></strong></div>
                        <div>Metode Pembayaran<strong><?= htmlspecialchars(ucfirst($pesanan['metode_pembayaran'] ?? '-')) ?></strong></div>
                        <div>Status Pesanan<strong><?= htmlspecialchars(labelStatus($status)) ?></strong></div>
                    </div>

                    <h3 class="nota-section-title">Rincian Produk</h3>
                    <?php if ($adaDetail): ?>
                        <div class="nota-table-wrap">
                            <table class="nota-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="nota-number">Jumlah</th>
                                        <th class="nota-number">Harga Satuan</th>
                                        <th class="nota-number">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($item = mysqli_fetch_assoc($detailNota)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                                        <td class="nota-number"><?= (int)$item['jumlah'] ?></td>
                                        <td class="nota-number"><?= rupiahLaporan($item['harga_satuan']) ?></td>
                                        <td class="nota-number"><?= rupiahLaporan($item['subtotal']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="nota-no-detail">Rincian produk untuk pesanan ini belum tersedia.</div>
                    <?php endif; ?>

                    <div class="nota-total">
                        <span>Total Pembayaran</span>
                        <strong><?= rupiahLaporan($pesanan['total']) ?></strong>
                    </div>
                    <div class="nota-modal-foot">
                        <a href="cetak_nota.php?id=<?= $id ?>" target="_blank" rel="noopener">Cetak Nota</a>
                    </div>
                </div>
            </div>
            <?php mysqli_stmt_close($stmtDetail); ?>
        <?php endif; ?>
    <?php endwhile; ?>
<?php endif; ?>
