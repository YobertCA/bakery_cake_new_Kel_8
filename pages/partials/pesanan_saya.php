
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

if (
    !isset($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'pembeli'
) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$id_user = (int) $_SESSION['id_user'];

// Mengambil riwayat transaksi pembeli
$query = "
    SELECT
        t.id_transaksi,
        t.tanggal_transaksi,
        t.total,
        t.metode_pembayaran,
        t.status,
        dt.id_detail,
        dt.jumlah,
        dt.harga_satuan,
        dt.subtotal,
        p.nama_produk
    FROM transaksi t
    LEFT JOIN detail_transaksi dt
        ON t.id_transaksi = dt.id_transaksi
    LEFT JOIN produk p
        ON dt.id_produk = p.id_produk
    WHERE t.id_user = ?
    ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$pesanan = [];

while ($row = mysqli_fetch_assoc($result)) {

    $id = $row['id_transaksi'];

    if (!isset($pesanan[$id])) {
        $pesanan[$id] = [
            'id_transaksi' => $id,
            'tanggal' => $row['tanggal_transaksi'],
            'total' => $row['total'],
            'metode' => $row['metode_pembayaran'],
            'status' => $row['status'],
            'items' => []
        ];
    }

    if ($row['id_detail'] !== null) {
        $pesanan[$id]['items'][] = [
            'nama' => $row['nama_produk'] ?? 'Produk tidak tersedia',
            'jumlah' => $row['jumlah'],
            'harga' => $row['harga_satuan'],
            'subtotal' => $row['subtotal']
        ];
    }
}

mysqli_stmt_close($stmt);

// Mengubah status menjadi tulisan yang mudah dipahami
function labelStatus($status)
{
    $status = strtolower($status);

    $daftar = [
        'pending' => 'Menunggu Diproses',
        'diproses' => 'Sedang Diproses',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan'
    ];

    return $daftar[$status] ?? ucfirst($status);
}

?>

<style>
.pesanan-header {
    margin-bottom: 25px;
}

.pesanan-header h2 {
    color: #49372e;
    margin-bottom: 8px;
}

.pesanan-header p {
    color: #806b5d;
}

.pesanan-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 20px;
    box-shadow: 0 3px 15px #00000008;
}

.pesanan-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding-bottom: 18px;
    border-bottom: 1px solid #eee2d9;
    margin-bottom: 15px;
}

.pesanan-top h3 {
    color: #9b5c38;
    margin-bottom: 6px;
}

.pesanan-top small {
    color: #806b5d;
}

.status-pesanan {
    background: #fff0d8;
    color: #94611d;
    padding: 8px 13px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
}

.item-pesanan {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #f2e9e3;
}

.item-pesanan small {
    display: block;
    color: #806b5d;
    margin-top: 5px;
}

.ringkasan-pesanan {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 18px;
    padding-top: 15px;
    border-top: 1px solid #eee2d9;
}

.ringkasan-pesanan strong {
    color: #9b5c38;
    font-size: 18px;
}

.metode-pesanan {
    color: #806b5d;
    font-size: 13px;
    margin-top: 10px;
}

.pesanan-kosong {
    background: white;
    padding: 55px 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 3px 15px #00000008;
}

.pesanan-kosong .icon {
    font-size: 55px;
    margin-bottom: 15px;
}

.pesanan-kosong p {
    color: #806b5d;
    margin: 10px 0 20px;
}

.btn-belanja {
    display: inline-block;
    background: #9b5c38;
    color: white;
    text-decoration: none;
    padding: 12px 22px;
    border-radius: 8px;
    cursor: pointer;
    border: none;
}

@media(max-width: 600px) {
    .pesanan-card {
        padding: 18px;
    }

    .pesanan-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .ringkasan-pesanan {
        flex-direction: column;
    }
}
</style>

<div class="pesanan-header">
    <h2>Pesanan Saya 🧾</h2>
    <p>Lihat riwayat dan status pesanan kamu di sini.</p>
</div>

<?php if (empty($pesanan)): ?>

    <div class="pesanan-kosong">
        <div class="icon">🛍️</div>

        <h3>Belum Ada Pesanan</h3>

        <p>
            Kamu belum memiliki riwayat pesanan.
            Yuk, pilih produk favoritmu!
        </p>

        <button
            type="button"
            class="btn-belanja"
            onclick="bukaHalaman('katalog')"
        >
            Mulai Belanja
        </button>
    </div>

<?php else: ?>

    <?php foreach ($pesanan as $data): ?>

        <div class="pesanan-card">

            <div class="pesanan-top">

                <div>
                    <h3>
                        Pesanan #<?= htmlspecialchars($data['id_transaksi']) ?>
                    </h3>

                    <small>
                        <?= date(
                            'd-m-Y H:i',
                            strtotime($data['tanggal'])
                        ) ?>
                    </small>
                </div>

                <span class="status-pesanan">
                    <?= htmlspecialchars(labelStatus($data['status'])) ?>
                </span>

            </div>

            <div>

                <?php foreach ($data['items'] as $item): ?>

                    <div class="item-pesanan">

                        <div>
                            <strong>
                                <?= htmlspecialchars($item['nama']) ?>
                            </strong>

                            <small>
                                <?= (int)$item['jumlah'] ?> × Rp
                                <?= number_format(
                                    $item['harga'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </small>
                        </div>

                        <strong>
                            Rp <?= number_format(
                                $item['subtotal'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </strong>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="metode-pesanan">
                Metode pembayaran:
                <strong>
                    <?= htmlspecialchars(
                        ucfirst($data['metode'] ?? '-')
                    ) ?>
                </strong>
            </div>

            <div class="ringkasan-pesanan">

                <span>Total Pesanan</span>

                <strong>
                    Rp <?= number_format(
                        $data['total'],
                        0,
                        ',',
                        '.'
                    ) ?>
                </strong>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>
