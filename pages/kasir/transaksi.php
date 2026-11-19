
<?php
require_once __DIR__ . '/../../config/database.php';

$query = mysqli_query(
    $conn,
    "SELECT t.id_transaksi, t.id_user, t.tanggal_transaksi,
            t.total, t.metode_pembayaran, t.status, u.nama
     FROM transaksi t
     JOIN users u ON t.id_user = u.id_user
     WHERE t.status IN ('pending', 'diproses')
     ORDER BY t.tanggal_transaksi DESC"
);

function rupiahTransaksi($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
?>

<style>
.transaksi-header { margin-bottom: 25px; }
.transaksi-header h2 { margin-bottom: 8px; }
.transaksi-header p { color: #806b5d; font-size: 14px; }

.transaksi-card {
    background: white;
    padding: 25px;
    border: 1px solid #f0e4dc;
    border-radius: 14px;
    margin-bottom: 20px;
    box-shadow: 0 3px 12px #00000005;
}

.transaksi-top {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 20px;
}

.transaksi-top h3 {
    color: #9b5c38;
    font-size: 17px;
    margin-bottom: 8px;
}

.transaksi-top small {
    display: block;
    color: #806b5d;
    margin-top: 5px;
}

.status-transaksi {
    height: fit-content;
    padding: 9px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}

.status-pending {
    background: #fff0d8;
    color: #a66a12;
}

.status-diproses {
    background: #e5efff;
    color: #3569a8;
}

.detail-produk {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 0;
    border-top: 1px solid #f0e4dc;
}

.detail-produk small {
    display: block;
    color: #806b5d;
    margin-top: 5px;
}

.metode-pembayaran {
    padding: 15px 0;
    border-top: 1px solid #f0e4dc;
    color: #806b5d;
    font-size: 14px;
}

.total-transaksi {
    display: flex;
    justify-content: space-between;
    padding-top: 17px;
    border-top: 1px solid #f0e4dc;
}

.total-transaksi strong {
    color: #9b5c38;
    font-size: 18px;
}

.aksi-transaksi {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn-proses {
    background: #9b5c38;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 22px;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s;
}

.btn-proses:hover {
    background: #795548;
    transform: translateY(-1px);
}

.kosong-transaksi {
    background: white;
    padding: 50px 20px;
    text-align: center;
    border-radius: 14px;
    border: 1px solid #f0e4dc;
}

.kosong-transaksi h3 { margin-bottom: 10px; }
.kosong-transaksi p { color: #806b5d; }

@media(max-width: 600px) {
    .transaksi-card { padding: 18px; }
    .transaksi-top { flex-direction: column; }
}
</style>


<?php if (!$query || mysqli_num_rows($query) === 0): ?>

    <div class="kosong-transaksi">
        <h3>📭 Tidak Ada Pesanan</h3>
        <p>Belum ada pesanan yang menunggu atau sedang diproses.</p>
    </div>

<?php else: ?>

    <?php while ($pesanan = mysqli_fetch_assoc($query)): ?>

        <div class="transaksi-card">

            <div class="transaksi-top">
                <div>
                    <h3>
                        Pesanan #<?= (int)$pesanan['id_transaksi'] ?>
                    </h3>

                    <small>
                        Pembeli:
                        <?= htmlspecialchars($pesanan['nama']) ?>
                    </small>

                    <small>
                        <?= date(
                            'd-m-Y H:i',
                            strtotime($pesanan['tanggal_transaksi'])
                        ) ?>
                    </small>
                </div>

                <span class="status-transaksi <?= $pesanan['status'] === 'pending'
                    ? 'status-pending'
                    : 'status-diproses' ?>">
                    <?= $pesanan['status'] === 'pending'
                        ? 'Menunggu Diproses'
                        : 'Sedang Diproses' ?>
                </span>
            </div>

            <?php
            $idTransaksi = (int)$pesanan['id_transaksi'];

            $detail = mysqli_query(
                $conn,
                "SELECT d.jumlah, d.harga_satuan,
                        d.subtotal, p.nama_produk
                 FROM detail_transaksi d
                 JOIN produk p ON d.id_produk = p.id_produk
                 WHERE d.id_transaksi = $idTransaksi"
            );
            ?>

            <?php while ($produk = mysqli_fetch_assoc($detail)): ?>
                <div class="detail-produk">
                    <div>
                        <strong>
                            <?= htmlspecialchars($produk['nama_produk']) ?>
                        </strong>

                        <small>
                            <?= (int)$produk['jumlah'] ?> ×
                            <?= rupiahTransaksi($produk['harga_satuan']) ?>
                        </small>
                    </div>

                    <strong>
                        <?= rupiahTransaksi($produk['subtotal']) ?>
                    </strong>
                </div>
            <?php endwhile; ?>

            <div class="metode-pembayaran">
                Metode pembayaran:
                <strong>
                    <?= ucfirst(htmlspecialchars(
                        $pesanan['metode_pembayaran'] ?? '-'
                    )) ?>
                </strong>
            </div>

            <div class="total-transaksi">
                <span>Total Pesanan</span>
                <strong>
                    <?= rupiahTransaksi($pesanan['total']) ?>
                </strong>
            </div>

            <?php if ($pesanan['status'] === 'pending'): ?>
                <div class="aksi-transaksi">
                    <form method="POST" action="proses_transaksi.php"
                          onsubmit="return confirm('Proses pesanan ini sekarang?')">

                        <input type="hidden" name="id_transaksi"
                               value="<?= (int)$pesanan['id_transaksi'] ?>">

                        <button type="submit" class="btn-proses">
                            Proses Pesanan
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>

    <?php endwhile; ?>

<?php endif; ?>
