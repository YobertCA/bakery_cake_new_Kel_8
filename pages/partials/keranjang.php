<?php
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$pesan = '';
$berhasil = false;

// Proses perubahan jumlah atau penghapusan produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';
    $id_produk = (int)($_POST['id_produk'] ?? 0);

    if ($aksi === 'ubah_jumlah') {

        $jumlah = (int)($_POST['jumlah'] ?? 0);

        if (isset($_SESSION['keranjang'][$id_produk])) {

            $stmt = mysqli_prepare(
                $conn,
                "SELECT stok FROM produk
                 WHERE id_produk = ? AND status = 'tersedia'"
            );

            mysqli_stmt_bind_param($stmt, "i", $id_produk);
            mysqli_stmt_execute($stmt);

            $hasil = mysqli_stmt_get_result($stmt);
            $produk = mysqli_fetch_assoc($hasil);
            mysqli_stmt_close($stmt);

            if ($jumlah < 1) {
                $pesan = 'Jumlah minimal adalah 1.';
            } elseif (!$produk) {
                unset($_SESSION['keranjang'][$id_produk]);
                $pesan = 'Produk sudah tidak tersedia dan dihapus dari keranjang.';
                $berhasil = true;
            } elseif ($jumlah > (int)$produk['stok']) {
                $pesan = 'Jumlah melebihi stok yang tersedia.';
            } else {
                $_SESSION['keranjang'][$id_produk]['jumlah'] = $jumlah;
                $pesan = 'Jumlah produk berhasil diperbarui.';
                $berhasil = true;
            }
        }

    } elseif ($aksi === 'hapus_produk') {

        if (isset($_SESSION['keranjang'][$id_produk])) {
            unset($_SESSION['keranjang'][$id_produk]);
            $pesan = 'Produk berhasil dihapus dari keranjang.';
            $berhasil = true;
        }
    }
}

// Ambil data produk terbaru dari database
$daftar_keranjang = [];
$total = 0;

$total_item = 0;

// Pemetaan gambar produk dari folder assets/produk.
// Path ini relatif dari halaman pages/dashboard_pembeli.php.
$gambar_produk = [
    'Chocolate Cake'    => '01_chocolate_cake.jpg',
    'Strawberry Cake'   => '02_strawberry_cake.jpg',
    'Vanilla Cake'      => '03_blueberry_cheesecake.jpg',
    'Red Velvet Cake'   => '04_red_velvet_cake.jpg',
    'Black Forest Cake' => '05_tiramisu_cake.jpg',

    'Roti Pisang'       => '06_roti_sobek.jpg',
    'Roti Cokelat'      => '07_roti_cokelat.jpg',
    'Roti Keju'         => '08_roti_keju.jpg',
    'Roti Sosis'        => '09_roti_sosis.jpg',
    'Garlic Bread'      => '10_roti_kismis.jpg',

    'Butter Croissant'  => '11_almond_croissant.jpg',
    'Almond Croissant'  => '11_almond_croissant.jpg',
    'Apple Turnover'    => '12_apple_turnover.jpg',
    'Cinnamon Roll'     => '13_fruit_danish.jpg',
    'Danish Pastry'     => '14_chocolate_puff.jpg'
];

foreach ($_SESSION['keranjang'] as $id => $item) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_produk, nama_produk, kategori, harga, stok, status
         FROM produk
         WHERE id_produk = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmt);

    if (!$produk || $produk['status'] !== 'tersedia' || (int)$produk['stok'] < 1) {
        unset($_SESSION['keranjang'][$id]);
        continue;
    }

    $jumlah = (int)$item['jumlah'];

    $subtotal = (float)$produk['harga'] * $jumlah;

    $produk['jumlah'] = $jumlah;
    $produk['subtotal'] = $subtotal;

    $daftar_keranjang[] = $produk;

    $total += $subtotal;
    $total_item += $jumlah;
}

// Ilustrasi produk
function ilustrasiProduk($kategori) {
    $ilustrasi = [
        'Roti' => '🥐',
        'Cake' => '🍰',
        'Pastry' => '🥨',
        'Lainnya' => '🧁'
    ];

    return $ilustrasi[$kategori] ?? '🧁';
}
?>

<style>
.keranjang-page {
    color: #49372e;
}

.keranjang-header {
    margin-bottom: 25px;
}

.keranjang-header h2 {
    font-size: 27px;
    margin-bottom: 8px;
}

.keranjang-header p {
    color: #806b5d;
    font-size: 14px;
}

.keranjang-alert {
    padding: 13px 16px;
    border-radius: 9px;
    margin-bottom: 20px;
    background: #e5f5e9;
    color: #26723c;
}

.keranjang-alert.error {
    background: #fde8e7;
    color: #a52a2a;
}

.keranjang-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 22px;
    align-items: start;
}

.keranjang-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.keranjang-card {
    background: white;
    border-radius: 13px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 3px 15px #00000008;
}

.keranjang-ilustrasi {
    width: 95px;
    height: 95px;
    flex-shrink: 0;
    background: #f2e5da;
    border-radius: 11px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 43px;
}

.keranjang-ilustrasi img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.keranjang-info {
    flex: 1;
    min-width: 0;
}

.keranjang-info h3 {
    font-size: 16px;
    margin-bottom: 6px;
}

.keranjang-info p {
    color: #806b5d;
    font-size: 13px;
    margin-bottom: 8px;
}

.keranjang-harga {
    color: #9b5c38;
    font-weight: bold;
    font-size: 16px;
}

.keranjang-aksi {
    display: flex;
    flex-direction: column;
    gap: 9px;
    align-items: flex-end;
}

.keranjang-aksi form {
    display: flex;
    gap: 6px;
}

.keranjang-aksi input {
    width: 65px;
    padding: 9px;
    border: 1px solid #e5d5c9;
    border-radius: 7px;
}

.btn-ubah,
.btn-hapus {
    border: none;
    padding: 9px 12px;
    border-radius: 7px;
    cursor: pointer;
    font-weight: 600;
}

.btn-ubah {
    background: #9b5c38;
    color: white;
}

.btn-hapus {
    background: #fde8e7;
    color: #a52a2a;
}

.ringkasan {
    background: white;
    padding: 23px;
    border-radius: 13px;
    box-shadow: 0 3px 15px #00000008;
    position: sticky;
    top: 105px;
}

.ringkasan h3 {
    margin-bottom: 20px;
}

.ringkasan-baris {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #806b5d;
}

.ringkasan-total {
    border-top: 1px solid #eee1d7;
    padding-top: 17px;
    margin-top: 17px;
    color: #49372e;
    font-size: 17px;
    font-weight: bold;
}

.btn-checkout {
    display: block;
    text-align: center;
    width: 100%;
    margin-top: 20px;
    padding: 13px;
    border-radius: 8px;
    background: #9b5c38;
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.btn-checkout:hover {
    background: #795548;
}

.btn-katalog {
    display: inline-block;
    margin-top: 15px;
    background: #9b5c38;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
}

.keranjang-kosong {
    background: white;
    padding: 55px 20px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 3px 15px #00000008;
}

.keranjang-kosong .ikon {
    font-size: 65px;
    margin-bottom: 15px;
}

.keranjang-kosong p {
    color: #806b5d;
    margin-top: 8px;
}

@media(max-width: 850px) {
    .keranjang-layout {
        grid-template-columns: 1fr;
    }

    .ringkasan {
        position: static;
    }
}

@media(max-width: 550px) {
    .keranjang-card {
        flex-wrap: wrap;
    }

    .keranjang-aksi {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}
</style>

<section class="keranjang-page">

    <div class="keranjang-header">
        <h2>Keranjang Belanja 🛒</h2>
        <p>Periksa kembali produk pilihanmu sebelum checkout.</p>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="keranjang-alert <?= $berhasil ? '' : 'error' ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if (count($daftar_keranjang) > 0): ?>

        <div class="keranjang-layout">

            <div class="keranjang-list">

                <?php foreach ($daftar_keranjang as $produk): ?>

                    <article class="keranjang-card">

                        <?php
                        $namaProduk = trim($produk['nama_produk']);
                        $namaFileGambar = $gambar_produk[$namaProduk] ?? '';
                        $pathGambar = '../assets/produk/' . $namaFileGambar;
                        ?>

                        <div class="keranjang-ilustrasi">
                            <?php if ($namaFileGambar !== ''): ?>
                                <img
                                    src="<?= htmlspecialchars($pathGambar) ?>"
                                    alt="<?= htmlspecialchars($namaProduk) ?>"
                                    onerror="this.style.display='none'; this.parentElement.textContent='<?= htmlspecialchars(ilustrasiProduk($produk['kategori']), ENT_QUOTES) ?>';"
                                >
                            <?php else: ?>
                                <?= ilustrasiProduk($produk['kategori']) ?>
                            <?php endif; ?>
                        </div>

                        <div class="keranjang-info">

                            <h3>
                                <?= htmlspecialchars($produk['nama_produk']) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($produk['kategori']) ?>
                            </p>

                            <div class="keranjang-harga">
                                Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                            </div>

                            <p>
                                Subtotal:
                                <strong>
                                    Rp <?= number_format($produk['subtotal'], 0, ',', '.') ?>
                                </strong>
                            </p>

                            <p>
                                Stok tersedia: <?= (int)$produk['stok'] ?>
                            </p>

                        </div>

                        <div class="keranjang-aksi">

                            <form method="POST" class="form-keranjang">

                                <input type="hidden" name="aksi" value="ubah_jumlah">

                                <input
                                    type="hidden"
                                    name="id_produk"
                                    value="<?= (int)$produk['id_produk'] ?>"
                                >

                                <input
                                    type="number"
                                    name="jumlah"
                                    min="1"
                                    max="<?= (int)$produk['stok'] ?>"
                                    value="<?= (int)$produk['jumlah'] ?>"
                                    required
                                >

                                <button type="submit" class="btn-ubah">
                                    Ubah
                                </button>

                            </form>

                            <form method="POST" class="form-keranjang">

                                <input type="hidden" name="aksi" value="hapus_produk">

                                <input
                                    type="hidden"
                                    name="id_produk"
                                    value="<?= (int)$produk['id_produk'] ?>"
                                >

                                <button type="submit" class="btn-hapus">
                                    Hapus
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <aside class="ringkasan">

                <h3>Ringkasan Belanja</h3>

                <div class="ringkasan-baris">
                    <span>Total Produk</span>
                    <strong><?= $total_item ?> item</strong>
                </div>

                <div class="ringkasan-baris ringkasan-total">
                    <span>Total Belanja</span>
                    <span>
                        Rp <?= number_format($total, 0, ',', '.') ?>
                    </span>
                </div>

                <a href="#" class="btn-checkout" id="tombolCheckout">
                    Lanjut Checkout
                </a>

            </aside>

        </div>

    <?php else: ?>

        <div class="keranjang-kosong">

            <div class="ikon">🛒</div>

            <h3>Keranjangmu masih kosong</h3>

            <p>
                Yuk, pilih roti dan kue favoritmu terlebih dahulu.
            </p>

            <a href="#" class="btn-katalog" id="kembaliKatalog">
                Lihat Katalog
            </a>

        </div>

    <?php endif; ?>

</section>