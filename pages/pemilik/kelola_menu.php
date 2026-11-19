
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pemilik') {
    exit('Akses ditolak.');
}

require_once __DIR__ . '/../../config/database.php';

// PROSES TAMBAH DAN EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = trim($_POST['nama_produk'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = (float)($_POST['harga'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);

        if ($nama === '' || $kategori === '' || $harga < 0 || $stok < 0) {
            exit('Data produk tidak valid.');
        }

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "
                INSERT INTO produk
                (nama_produk, kategori, deskripsi, harga, stok, status)
                VALUES (?, ?, ?, ?, ?, 'aktif')
            ");

            mysqli_stmt_bind_param(
                $stmt, "sssdi",
                $nama, $kategori, $deskripsi, $harga, $stok
            );
        } else {
            $id = (int)($_POST['id_produk'] ?? 0);

            $stmt = mysqli_prepare($conn, "
                UPDATE produk
                SET nama_produk=?, kategori=?, deskripsi=?,
                    harga=?, stok=?
                WHERE id_produk=?
            ");

            mysqli_stmt_bind_param(
                $stmt, "sssdii",
                $nama, $kategori, $deskripsi, $harga, $stok, $id
            );
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: dashboard_pemilik.php");
        exit;
    }

    // PROSES HAPUS
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_produk'] ?? 0);

        $stmt = mysqli_prepare($conn, "
            SELECT COUNT(*) AS jumlah
            FROM detail_transaksi
            WHERE id_produk = ?
        ");

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($hasil);
        mysqli_stmt_close($stmt);

        if ((int)$data['jumlah'] > 0) {
            // Produk yang pernah dipesan tidak dihapus permanen.
            // Sembunyikan dari katalog dengan menonaktifkannya.
            $stmt = mysqli_prepare($conn, "
                UPDATE produk SET status='nonaktif'
                WHERE id_produk=?
            ");
            mysqli_stmt_bind_param($stmt, "i", $id);
        } else {
            $stmt = mysqli_prepare($conn, "
                DELETE FROM produk WHERE id_produk=?
            ");
            mysqli_stmt_bind_param($stmt, "i", $id);
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: dashboard_pemilik.php");
        exit;
    }
}

// AMBIL DATA PRODUK
$query = mysqli_query($conn, "
    SELECT id_produk, nama_produk, kategori,
           deskripsi, harga, stok
    FROM produk
    WHERE status <> 'nonaktif' OR status IS NULL
    ORDER BY id_produk DESC
");

if (!$query) {
    exit('Gagal mengambil data produk: ' . mysqli_error($conn));
}
?>

<style>
.menu-header {
    margin-bottom: 22px;
}
.menu-header h2 {
    margin-bottom: 7px;
    color: #4e342e;
}
.menu-header p {
    color: #8d6e63;
    font-size: 14px;
}
.form-produk {
    background: #fff8f2;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}
.form-produk h3 {
    margin-bottom: 18px;
    color: #4e342e;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.form-group label {
    font-size: 13px;
    font-weight: bold;
}
.form-group input,
.form-group textarea,
.form-group select {
    padding: 11px;
    border: 1px solid #ddc9bb;
    border-radius: 7px;
    outline: none;
    background: white;
}
.form-group textarea {
    resize: vertical;
}
.form-group.full {
    grid-column: 1 / -1;
}
.btn-simpan, .btn-batal {
    border: none;
    padding: 11px 20px;
    border-radius: 7px;
    cursor: pointer;
    margin-top: 15px;
}
.btn-simpan {
    background: #795548;
    color: white;
}
.btn-batal {
    background: #ddd;
    color: #333;
}
.tabel-wrapper {
    overflow-x: auto;
}
.tabel-menu {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.tabel-menu th {
    background: #795548;
    color: white;
    padding: 13px;
    text-align: left;
}
.tabel-menu td {
    padding: 12px;
    border-bottom: 1px solid #eee2da;
}
.tabel-menu tr:hover {
    background: #fff8f2;
}
.btn-edit, .btn-hapus {
    border: none;
    padding: 7px 10px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}
.btn-edit {
    background: #e9d3bd;
    color: #5d4037;
}
.btn-hapus {
    background: #ffe0e0;
    color: #a52a2a;
}
@media(max-width:650px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>


<div class="form-produk">
    <h3 id="judul-form">Tambah Produk Baru</h3>

    <form method="POST" action="kelola_menu.php" id="formProduk">
        <input type="hidden" name="aksi" id="aksi" value="tambah">
        <input type="hidden" name="id_produk" id="id_produk">

        <div class="form-grid">
            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="nama_produk" id="nama_produk" required>
            </div>

            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori" id="kategori" required>
                    <option value="">Pilih Kategori</option>
                    <option value="Cake">Cake</option>
                    <option value="Pastry">Pastry</option>
                    <option value="Roti">Roti</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>Harga (Rp)</label>
                <input type="number" name="harga" id="harga" min="0" required>
            </div>

            <div class="form-group">
                <label>Stok</label>
                <input type="number" name="stok" id="stok" min="0" required>
            </div>
        </div>

        <button type="submit" class="btn-simpan" id="btnSimpan">
            Simpan Produk
        </button>

        <button type="button" class="btn-batal"
                id="btnBatal" onclick="resetFormProduk()" hidden>
            Batal Edit
        </button>
    </form>
</div>

<h3 style="margin-bottom:15px;">Daftar Produk</h3>

<div class="tabel-wrapper">
<table class="tabel-menu">
    <thead>
        <tr>
            <th>No.</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
    <?php if (mysqli_num_rows($query) > 0): ?>
        <?php $no = 1; ?>
        <?php while ($produk = mysqli_fetch_assoc($query)): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
            <td><?= htmlspecialchars($produk['kategori']) ?></td>
            <td>Rp <?= number_format($produk['harga'], 0, ',', '.') ?></td>
            <td><?= (int)$produk['stok'] ?></td>
            <td>
                <button type="button" class="btn-edit"
                    onclick="editProduk(this)"
                    data-id="<?= (int)$produk['id_produk'] ?>"
                    data-nama="<?= htmlspecialchars($produk['nama_produk'], ENT_QUOTES) ?>"
                    data-kategori="<?= htmlspecialchars($produk['kategori'], ENT_QUOTES) ?>"
                    data-deskripsi="<?= htmlspecialchars($produk['deskripsi'] ?? '', ENT_QUOTES) ?>"
                    data-harga="<?= htmlspecialchars($produk['harga']) ?>"
                    data-stok="<?= (int)$produk['stok'] ?>">
                    Edit
                </button>

                <form method="POST" action="kelola_menu.php"
                      style="display:inline"
                      onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id_produk"
                           value="<?= (int)$produk['id_produk'] ?>">
                    <button type="submit" class="btn-hapus">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" style="text-align:center;padding:25px;">
                Belum ada produk.
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
