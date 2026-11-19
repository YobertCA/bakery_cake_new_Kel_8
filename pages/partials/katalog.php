
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

// ==========================================
// PROSES TAMBAH KE KERANJANG
// ==========================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['aksi'] ?? '') === 'tambah_keranjang'
) {
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 0);

    if ($jumlah < 1) {
        $pesan = 'Jumlah pembelian tidak valid!';
    } else {
        $stmt = mysqli_prepare($conn, "
            SELECT id_produk, nama_produk, harga, stok
            FROM produk
            WHERE id_produk = ?
            AND status = 'tersedia'
            AND stok > 0
        ");

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($hasil);

        mysqli_stmt_close($stmt);

        if (!$produk) {
            $pesan = 'Produk tidak tersedia!';
        } else {
            $jumlah_lama =
                $_SESSION['keranjang'][$id_produk]['jumlah'] ?? 0;

            $jumlah_baru = $jumlah_lama + $jumlah;

            if ($jumlah_baru > (int)$produk['stok']) {
                $pesan = 'Jumlah melebihi stok yang tersedia!';
            } else {
                $_SESSION['keranjang'][$id_produk] = [
                    'id_produk' => (int)$produk['id_produk'],
                    'nama_produk' => $produk['nama_produk'],
                    'harga' => (float)$produk['harga'],
                    'jumlah' => $jumlah_baru
                ];

                $pesan = 'Produk berhasil ditambahkan ke keranjang!';
                $berhasil = true;
            }
        }
    }
}

// ==========================================
// AMBIL DATA PRODUK
// ==========================================
$query = mysqli_query($conn, "
    SELECT *
    FROM produk
    WHERE status = 'tersedia'
    AND stok > 0
    ORDER BY nama_produk ASC
");

if (!$query) {
    exit('Gagal mengambil produk: ' . mysqli_error($conn));
}

$daftar_produk = [];

while ($row = mysqli_fetch_assoc($query)) {
    $daftar_produk[] = $row;
}

// ==========================================
// HITUNG JUMLAH KERANJANG
// ==========================================
$total_keranjang = 0;

foreach ($_SESSION['keranjang'] as $item) {
    $total_keranjang += (int)$item['jumlah'];
}

// ==========================================
// GAMBAR SETIAP PRODUK
// ==========================================
$gambar_produk = [
    'Chocolate Cake' => '01_chocolate_cake.jpg',
    'Strawberry Cake' => '02_strawberry_cake.jpg',
    'Red Velvet Cake' => '04_red_velvet_cake.jpg',
    'Vanilla Cake' => '03_blueberry_cheesecake.jpg',
    'Black Forest Cake' => '05_tiramisu_cake.jpg',

    'Roti Cokelat' => '07_roti_cokelat.jpg',
    'Roti Keju' => '08_roti_keju.jpg',
    'Roti Sosis' => '09_roti_sosis.jpg',
    'Roti Pisang' => '10_roti_kismis.jpg',
    'Garlic Bread' => '06_roti_sobek.jpg',

    'Butter Croissant' => '11_almond_croissant.jpg',
    'Almond Croissant' => '11_almond_croissant.jpg',
    'Cinnamon Roll' => '13_fruit_danish.jpg',
    'Danish Pastry' => '14_chocolate_puff.jpg',
    'Apple Turnover' => '12_apple_turnover.jpg'
];

$emoji_kategori = [
    'Cake' => '🎂',
    'Roti' => '🍞',
    'Pastry' => '🥐'
];
?>

<style>
.katalog {
    color: #49372e;
}

.katalog-header {
    margin-bottom: 25px;
}

.katalog-header h2 {
    font-size: 27px;
    margin-bottom: 8px;
}

.katalog-header p {
    color: #806b5d;
    font-size: 14px;
}

.keranjang-info {
    display: inline-block;
    margin-top: 12px;
    background: #f7eee7;
    color: #9b5c38;
    padding: 9px 15px;
    border-radius: 8px;
}

.katalog-tools {
    display: flex;
    gap: 12px;
    margin-bottom: 25px;
}

.katalog-tools input,
.katalog-tools select {
    padding: 13px 15px;
    border: 1px solid #e5d5c9;
    border-radius: 9px;
    background: white;
    color: #49372e;
    outline: none;
}

.katalog-tools input {
    flex: 1;
    min-width: 0;
}

.katalog-tools input:focus,
.katalog-tools select:focus {
    border-color: #9b5c38;
}

.katalog-alert {
    padding: 13px 16px;
    border-radius: 9px;
    margin-bottom: 20px;
    background: #e5f5e9;
    color: #26723c;
}

.katalog-alert.error {
    background: #fde8e7;
    color: #a52a2a;
}

.katalog-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}

.produk-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 3px 15px #00000008;
    transition: .25s;
}

.produk-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px #00000012;
}

.produk-gambar {
    height: 210px;
    background: #f2e5da;
    overflow: hidden;
    position: relative;
}

.produk-gambar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: .3s;
}

.produk-card:hover .produk-gambar img {
    transform: scale(1.05);
}

.produk-emoji {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 75px;
}

.produk-info {
    padding: 20px;
}

.produk-kategori {
    display: inline-block;
    background: #f7eee7;
    color: #9b5c38;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    margin-bottom: 10px;
}

.produk-info h3 {
    font-size: 17px;
    margin-bottom: 8px;
}

.produk-deskripsi {
    color: #806b5d;
    font-size: 13px;
    line-height: 1.6;
    min-height: 42px;
}

.produk-harga {
    font-size: 19px;
    font-weight: bold;
    color: #9b5c38;
    margin-top: 15px;
}

.produk-stok {
    color: #66865d;
    font-size: 13px;
    margin: 8px 0 15px;
}

.produk-form {
    display: flex;
    gap: 8px;
}

.produk-form input {
    width: 65px;
    padding: 10px;
    border: 1px solid #e5d5c9;
    border-radius: 7px;
}

.produk-form button {
    flex: 1;
    border: none;
    border-radius: 7px;
    background: #9b5c38;
    color: white;
    font-weight: 600;
    cursor: pointer;
    padding: 10px;
}

.produk-form button:hover {
    background: #795548;
}

.produk-kosong {
    background: white;
    padding: 35px;
    border-radius: 12px;
    text-align: center;
    color: #806b5d;
}

@media(max-width: 850px) {
    .katalog-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width: 550px) {
    .katalog-grid {
        grid-template-columns: 1fr;
    }

    .katalog-tools {
        flex-direction: column;
    }
}
</style>

<section class="katalog">

    <!-- HEADER -->
    <div class="katalog-header">
        <h2>Katalog Produk 🧁</h2>

        <p>
            Temukan roti dan kue favoritmu di Bakery & Cake.
        </p>

        <div class="keranjang-info">
            🛒 Keranjang:
            <strong><?= $total_keranjang ?> item</strong>
        </div>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($pesan !== ''): ?>
        <div class="katalog-alert <?= $berhasil ? '' : 'error' ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <!-- PENCARIAN DAN FILTER -->
    <div class="katalog-tools">

        <input
            type="text"
            id="cariProduk"
            placeholder="Cari nama produk..."
        >

        <select id="filterKategori">
            <option value="">Semua Kategori</option>
            <option value="Cake">Cake</option>
            <option value="Roti">Roti</option>
            <option value="Pastry">Pastry</option>
        </select>

    </div>

    <!-- DAFTAR PRODUK -->
    <div class="katalog-grid" id="daftarProduk">

        <?php foreach ($daftar_produk as $produk): ?>

            <?php
            $kategori = $produk['kategori'];
            $nama_produk = $produk['nama_produk'];

            $nama_file = $gambar_produk[$nama_produk] ?? '';

            // Prioritaskan gambar dari database jika tersedia
            if (!empty($produk['gambar'])) {
                $nama_file = $produk['gambar'];
            }

            // Lokasi gambar dari halaman dashboard pembeli
            $gambar = '../assets/produk/' . $nama_file;

            $ikon = $emoji_kategori[$kategori] ?? '🧁';
            ?>

            <article
                class="produk-card"
                data-nama="<?= htmlspecialchars(strtolower($nama_produk)) ?>"
                data-kategori="<?= htmlspecialchars($kategori) ?>"
            >

                <div class="produk-gambar">

                    <?php if ($nama_file !== ''): ?>

                        <img
                            src="<?= htmlspecialchars($gambar) ?>"
                            alt="<?= htmlspecialchars($nama_produk) ?>"
                            loading="lazy"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                        >

                        <div
                            class="produk-emoji"
                            style="display:none;"
                        >
                            <?= $ikon ?>
                        </div>

                    <?php else: ?>

                        <div class="produk-emoji">
                            <?= $ikon ?>
                        </div>

                    <?php endif; ?>

                </div>

                <div class="produk-info">

                    <span class="produk-kategori">
                        <?= htmlspecialchars($kategori) ?>
                    </span>

                    <h3>
                        <?= htmlspecialchars($nama_produk) ?>
                    </h3>

                    <p class="produk-deskripsi">
                        <?= htmlspecialchars($produk['deskripsi'] ?? '') ?>
                    </p>

                    <div class="produk-harga">
                        Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                    </div>

                    <div class="produk-stok">
                        Stok tersedia: <?= (int)$produk['stok'] ?>
                    </div>

                    <form method="POST" class="produk-form">

                        <input
                            type="hidden"
                            name="aksi"
                            value="tambah_keranjang"
                        >

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
                            value="1"
                            required
                        >

                        <button type="submit">
                            + Keranjang
                        </button>

                    </form>

                </div>

            </article>

        <?php endforeach; ?>

        <?php if (count($daftar_produk) === 0): ?>
            <div class="produk-kosong">
                Belum ada produk yang tersedia.
            </div>
        <?php endif; ?>

    </div>

    <p id="hasilKosong" class="produk-kosong" hidden>
        Produk yang kamu cari tidak ditemukan.
    </p>

</section>

<script>
(function() {

    const cari = document.getElementById('cariProduk');
    const filter = document.getElementById('filterKategori');

    const kartu = document.querySelectorAll(
        '#daftarProduk .produk-card'
    );

    const hasilKosong = document.getElementById('hasilKosong');

    if (!cari || !filter || !hasilKosong) {
        return;
    }

    function filterProduk() {

        const kata = cari.value.toLowerCase().trim();
        const kategori = filter.value;

        let jumlahTampil = 0;

        kartu.forEach(function(card) {

            const nama = card.dataset.nama;
            const jenis = card.dataset.kategori;

            const cocokNama = nama.includes(kata);
            const cocokKategori = !kategori || jenis === kategori;

            const tampil = cocokNama && cocokKategori;

            card.style.display = tampil ? '' : 'none';

            if (tampil) {
                jumlahTampil++;
            }

        });

        hasilKosong.hidden = jumlahTampil > 0;
    }

    cari.addEventListener('input', filterProduk);
    filter.addEventListener('change', filterProduk);

})();
</script>
