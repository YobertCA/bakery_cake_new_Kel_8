
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pemilik') {
    exit('Akses ditolak.');
}

require_once __DIR__ . '/../../config/database.php';

// =====================================
// PROSES TAMBAH STOK
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $id_user = (int)$_SESSION['id_user'];

    if ($id_produk <= 0 || $jumlah <= 0) {
        die('Data penambahan stok tidak valid.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {

        mysqli_begin_transaction($conn);

        // Periksa dan kunci produk
        $stmt = mysqli_prepare($conn, "
            SELECT id_produk
            FROM produk
            WHERE id_produk = ?
            FOR UPDATE
        ");

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($hasil);

        mysqli_stmt_close($stmt);

        if (!$produk) {
            throw new Exception('Produk tidak ditemukan.');
        }

        // Tambahkan stok produk
        $stmt = mysqli_prepare($conn, "
            UPDATE produk
            SET stok = stok + ?
            WHERE id_produk = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $jumlah,
            $id_produk
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Catat riwayat stok masuk
        $keterangan = 'Penambahan stok oleh pemilik';

        $stmt = mysqli_prepare($conn, "
            INSERT INTO stok_masuk
            (id_produk, id_user, jumlah, keterangan)
            VALUES (?, ?, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "iiis",
            $id_produk,
            $id_user,
            $jumlah,
            $keterangan
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Simpan kedua perubahan
        mysqli_commit($conn);

        $_SESSION['pesan_stok'] = 'Stok berhasil ditambahkan dan dicatat!';

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        $_SESSION['pesan_stok'] = 'Gagal menambahkan stok: ' . $e->getMessage();
    }

    header("Location: dashboard_pemilik.php?halaman=kelola_stok");
    exit;
}

// =====================================
// PESAN HASIL PROSES
// =====================================
$pesan = $_SESSION['pesan_stok'] ?? '';
unset($_SESSION['pesan_stok']);

// =====================================
// AMBIL DATA PRODUK
// =====================================
$query = mysqli_query($conn, "
    SELECT id_produk, nama_produk, kategori, harga, stok, status
    FROM produk
    ORDER BY stok ASC, nama_produk ASC
");

if (!$query) {
    die('Gagal mengambil data stok: ' . mysqli_error($conn));
}

$data_produk = [];
$total_stok = 0;
$stok_menipis = 0;
$stok_habis = 0;

while ($produk = mysqli_fetch_assoc($query)) {

    $data_produk[] = $produk;

    $stok = (int)$produk['stok'];
    $total_stok += $stok;

    if ($stok === 0) {
        $stok_habis++;
    } elseif ($stok <= 5) {
        $stok_menipis++;
    }
}

$total_produk = count($data_produk);

// =====================================
// AMBIL RIWAYAT STOK MASUK
// =====================================
$query_riwayat = mysqli_query($conn, "
    SELECT
        sm.id_stok_masuk,
        sm.jumlah,
        sm.tanggal_masuk,
        sm.keterangan,
        p.nama_produk,
        u.nama AS nama_pengguna
    FROM stok_masuk sm
    LEFT JOIN produk p ON sm.id_produk = p.id_produk
    LEFT JOIN users u ON sm.id_user = u.id_user
    ORDER BY sm.tanggal_masuk DESC, sm.id_stok_masuk DESC
");

if (!$query_riwayat) {
    die('Gagal mengambil riwayat stok: ' . mysqli_error($conn));
}
?>

<style>
.stok-header {
    margin-bottom: 22px;
}

.stok-header h2 {
    margin-bottom: 7px;
    color: #4e342e;
}

.stok-header p {
    color: #8d6e63;
    font-size: 14px;
}

.pesan-stok {
    padding: 12px 15px;
    border-radius: 8px;
    background: #e3f5e6;
    color: #26723a;
    margin-bottom: 18px;
    font-size: 14px;
}

.stok-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.stok-card {
    background: #fff8f2;
    padding: 20px;
    border-radius: 10px;
}

.stok-card p {
    color: #8d6e63;
    font-size: 13px;
}

.stok-card h2 {
    margin-top: 10px;
    font-size: 26px;
    color: #4e342e;
}

.form-tambah-stok {
    background: #fff8f2;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.form-tambah-stok h3 {
    margin-bottom: 15px;
    color: #4e342e;
}

.form-grid-stok {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 12px;
    align-items: end;
}

.form-grid-stok label {
    display: block;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 7px;
}

.form-grid-stok select,
.form-grid-stok input {
    width: 100%;
    padding: 11px;
    border: 1px solid #ddc9bb;
    border-radius: 7px;
    background: white;
}

.btn-tambah-stok {
    border: none;
    background: #795548;
    color: white;
    padding: 12px 18px;
    border-radius: 7px;
    cursor: pointer;
}

.btn-tambah-stok:hover {
    background: #9b5c38;
}

.tabel-stok-wrapper {
    overflow-x: auto;
    margin-bottom: 30px;
}

.tabel-stok {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.tabel-stok th {
    background: #795548;
    color: white;
    padding: 13px;
    text-align: left;
    white-space: nowrap;
}

.tabel-stok td {
    padding: 13px;
    border-bottom: 1px solid #eee2da;
}

.tabel-stok tr:hover {
    background: #fff8f2;
}

.label-stok {
    padding: 6px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: bold;
}

.aman {
    background: #d9f3df;
    color: #26723a;
}

.menipis {
    background: #fff0c2;
    color: #946200;
}

.habis {
    background: #ffe0e0;
    color: #a52a2a;
}

@media(max-width: 700px) {
    .stok-cards {
        grid-template-columns: 1fr;
    }

    .form-grid-stok {
        grid-template-columns: 1fr;
    }
}
</style>


<?php if ($pesan !== ''): ?>
    <div class="pesan-stok">
        <?= htmlspecialchars($pesan) ?>
    </div>
<?php endif; ?>

<!-- STATISTIK -->
<div class="stok-cards">

    <div class="stok-card">
        <p>Total Produk</p>
        <h2><?= $total_produk ?></h2>
    </div>

    <div class="stok-card">
        <p>Total Persediaan</p>
        <h2><?= $total_stok ?></h2>
    </div>

    <div class="stok-card">
        <p>Produk Stok Menipis / Habis</p>
        <h2><?= $stok_menipis + $stok_habis ?></h2>
    </div>

</div>

<!-- FORM TAMBAH STOK -->
<div class="form-tambah-stok">

    <h3>Tambah Persediaan Stok</h3>

    <form method="POST"
          action="kelola_stok.php"
          onsubmit="return confirm('Tambahkan stok produk ini?')">

        <div class="form-grid-stok">

            <div>
                <label>Pilih Produk</label>

                <select name="id_produk" required>
                    <option value="">-- Pilih Produk --</option>

                    <?php foreach ($data_produk as $produk): ?>
                        <option value="<?= (int)$produk['id_produk'] ?>">
                            <?= htmlspecialchars($produk['nama_produk']) ?>
                            (Stok: <?= (int)$produk['stok'] ?>)
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label>Jumlah Tambahan</label>

                <input
                    type="number"
                    name="jumlah"
                    min="1"
                    placeholder="Masukkan jumlah"
                    required
                >
            </div>

            <button type="submit" class="btn-tambah-stok">
                + Tambah Stok
            </button>

        </div>

    </form>

</div>

<!-- DAFTAR PERSEDIAAN -->
<h3 style="margin-bottom:15px;">Daftar Persediaan Produk</h3>

<div class="tabel-stok-wrapper">

    <table class="tabel-stok">

        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Keterangan</th>
            </tr>
        </thead>

        <tbody>

        <?php if ($total_produk > 0): ?>

            <?php $no = 1; ?>

            <?php foreach ($data_produk as $produk): ?>

                <?php
                    $stok = (int)$produk['stok'];

                    if ($stok === 0) {
                        $keterangan = 'Habis';
                        $kelas = 'habis';
                    } elseif ($stok <= 5) {
                        $keterangan = 'Menipis';
                        $kelas = 'menipis';
                    } else {
                        $keterangan = 'Aman';
                        $kelas = 'aman';
                    }
                ?>

                <tr>
                    <td><?= $no++ ?></td>

                    <td>
                        <?= htmlspecialchars($produk['nama_produk']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($produk['kategori']) ?>
                    </td>

                    <td>
                        Rp <?= number_format(
                            (float)$produk['harga'],
                            0,
                            ',',
                            '.'
                        ) ?>
                    </td>

                    <td>
                        <strong><?= $stok ?></strong>
                    </td>

                    <td>
                        <span class="label-stok <?= $kelas ?>">
                            <?= $keterangan ?>
                        </span>
                    </td>
                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="6" style="text-align:center;padding:25px;">
                    Belum ada data produk.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<!-- RIWAYAT STOK MASUK -->
<h3 style="margin-bottom:15px;">Riwayat Stok Masuk</h3>

<div class="tabel-stok-wrapper">

    <table class="tabel-stok">

        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Produk</th>
                <th>Jumlah Masuk</th>
                <th>Tanggal</th>
                <th>Pengguna</th>
                <th>Keterangan</th>
            </tr>
        </thead>

        <tbody>

        <?php if (mysqli_num_rows($query_riwayat) > 0): ?>

            <?php $no = 1; ?>

            <?php while ($riwayat = mysqli_fetch_assoc($query_riwayat)): ?>

                <tr>
                    <td><?= $no++ ?></td>

                    <td>
                        <?= htmlspecialchars($riwayat['nama_produk'] ?? 'Produk tidak tersedia') ?>
                    </td>

                    <td>
                        <strong>+<?= (int)$riwayat['jumlah'] ?></strong>
                    </td>

                    <td>
                        <?= htmlspecialchars($riwayat['tanggal_masuk']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($riwayat['nama_pengguna'] ?? '-') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($riwayat['keterangan'] ?? '-') ?>
                    </td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="6" style="text-align:center;padding:25px;">
                    Belum ada riwayat stok masuk.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</div>
