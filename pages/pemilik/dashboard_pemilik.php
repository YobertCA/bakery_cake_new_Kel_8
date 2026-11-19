<?php
session_start();

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// STATISTIK DASHBOARD
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM produk"))['total'];
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi"))['total'];
$total_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(stok), 0) AS total FROM produk"))['total'];
$total_pengguna = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'];

// AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $halaman = $_GET['halaman'] ?? '';
    $daftar_halaman = [
        'laporan_pemesanan',
        'kelola_menu',
        'kelola_stok',
        'kelola_pengguna'
    ];

    if (in_array($halaman, $daftar_halaman, true)) {
        $file = __DIR__ . '/' . $halaman . '.php';

        if (file_exists($file)) {
            include $file;
        } else {
            echo "<p>Halaman belum dibuat.</p>";
        }
    } else {
        echo "<p>Halaman tidak ditemukan.</p>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pemilik - Bakery & Cake</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif}
body{background:#fff8f2;color:#4e342e}
.sidebar{width:250px;height:100vh;background:#795548;color:white;position:fixed;padding:25px 15px;overflow-y:auto}
.brand{text-align:center;margin-bottom:35px}
.brand h2{font-size:22px}
.brand p{font-size:12px;margin-top:7px;color:#f5dfd1}
.menu{display:flex;flex-direction:column;gap:10px}
.menu button{border:none;background:transparent;color:white;padding:13px 15px;text-align:left;border-radius:8px;cursor:pointer;font-size:14px}
.menu button:hover,.menu button.active{background:#a66b49}
.main{margin-left:250px;padding:30px}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px}
.header h1{font-size:25px}
.header p{color:#8d6e63;margin-top:6px}
.logout{background:#a64b3c;color:white;padding:11px 18px;text-decoration:none;border-radius:8px;font-size:13px}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:30px}
.card{background:white;padding:23px;border-radius:12px;box-shadow:0 3px 12px rgba(0,0,0,.05)}
.card p{color:#8d6e63;font-size:13px}
.card h2{margin-top:12px;font-size:28px}
.content{background:white;padding:25px;border-radius:12px;min-height:300px;box-shadow:0 3px 12px rgba(0,0,0,.05)}
.content h2{margin-bottom:12px}
.content p{color:#795548;line-height:1.7}
@media(max-width:900px){.sidebar{width:200px}.main{margin-left:200px}.cards{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.sidebar{position:static;width:100%;height:auto}.main{margin-left:0;padding:18px}.cards{grid-template-columns:1fr}}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="brand"><h2>🍰 Bakery & Cake</h2></div>
    <nav class="menu">
        <button class="active" onclick="tampilkan('dashboard', this)">🏠 Dashboard</button>
        <button onclick="tampilkan('laporan_pemesanan', this)">📋 Kelola Laporan Pemesanan</button>
        <button onclick="tampilkan('kelola_menu', this)">🍰 Kelola Menu</button>
        <button onclick="tampilkan('kelola_stok', this)">📦 Kelola Stok</button>
        <button onclick="tampilkan('kelola_pengguna', this)">👥 Kelola Pengguna</button>
    </nav>
</aside>

<main class="main">
    <div class="header">
        <div><h1 id="judul-halaman">Dashboard</h1></div>
        <a href="../../auth/logout.php" class="logout" onclick="return confirm('Yakin ingin keluar?')">Keluar</a>
    </div>

    <div class="cards" id="statistik">
        <div class="card"><p>Total Produk</p><h2><?= (int)$total_produk ?></h2></div>
        <div class="card"><p>Total Transaksi</p><h2><?= (int)$total_transaksi ?></h2></div>
        <div class="card"><p>Total Stok</p><h2><?= (int)$total_stok ?></h2></div>
        <div class="card"><p>Total Pengguna</p><h2><?= (int)$total_pengguna ?></h2></div>
    </div>

    <section class="content" id="konten">
        <h2>Selamat Datang di Panel Pemilik</h2>
    </section>
</main>

<script>
// NAVIGASI AJAX
function tampilkan(halaman, tombol) {
    document.querySelectorAll('.menu button').forEach(btn => btn.classList.remove('active'));
    tombol.classList.add('active');

    const konten = document.getElementById('konten');
    const statistik = document.getElementById('statistik');
    const judul = document.getElementById('judul-halaman');

    if (halaman === 'dashboard') {
        location.reload();
        return;
    }

    statistik.style.display = 'none';

    const daftarJudul = {
        laporan_pemesanan: 'Kelola Laporan Pemesanan',
        kelola_menu: 'Kelola Menu Produk',
        kelola_stok: 'Kelola Stok Produk',
        kelola_pengguna: 'Kelola Pengguna'
    };

    judul.textContent = daftarJudul[halaman] || 'Dashboard Pemilik';
    konten.innerHTML = '<p>Memuat halaman...</p>';

    fetch('dashboard_pemilik.php?ajax=1&halaman=' + encodeURIComponent(halaman))
        .then(response => {
            if (!response.ok) throw new Error('Gagal mengambil halaman');
            return response.text();
        })
        .then(data => { konten.innerHTML = data; })
        .catch(error => {
            console.error(error);
            konten.innerHTML = '<p>Gagal memuat halaman.</p>';
        });
}

// EDIT PRODUK
function editProduk(tombol) {
    const data = tombol.dataset;
    const form = document.getElementById('formProduk');

    if (!form) {
        alert('Form produk tidak ditemukan.');
        return;
    }

    document.getElementById('judul-form').textContent = 'Edit Produk';
    document.getElementById('aksi').value = 'edit';
    document.getElementById('id_produk').value = data.id;
    document.getElementById('nama_produk').value = data.nama;
    document.getElementById('kategori').value = data.kategori;
    document.getElementById('deskripsi').value = data.deskripsi || '';
    document.getElementById('harga').value = data.harga;
    document.getElementById('stok').value = data.stok;
    document.getElementById('btnSimpan').textContent = 'Simpan Perubahan';
    document.getElementById('btnBatal').hidden = false;

    form.scrollIntoView({behavior:'smooth', block:'center'});
}

function resetFormProduk() {
    const form = document.getElementById('formProduk');
    if (!form) return;

    form.reset();
    document.getElementById('aksi').value = 'tambah';
    document.getElementById('id_produk').value = '';
    document.getElementById('judul-form').textContent = 'Tambah Produk Baru';
    document.getElementById('btnSimpan').textContent = 'Simpan Produk';
    document.getElementById('btnBatal').hidden = true;
}

// EDIT PENGGUNA - DITEMPATKAN DI DASHBOARD UTAMA KARENA KONTEN DIMUAT VIA AJAX
function editUser(data) {
    const form = document.getElementById('formUser');
    if (!form) {
        alert('Form pengguna tidak ditemukan.');
        return;
    }

    document.getElementById('judulUser').textContent = 'Edit Pengguna';
    document.getElementById('aksiUser').value = 'edit';
    document.getElementById('id_user').value = data.id;
    document.getElementById('namaUser').value = data.nama;
    document.getElementById('emailUser').value = data.email;
    document.getElementById('roleUser').value = data.role;
    document.getElementById('passwordUser').value = '';
    document.getElementById('passwordUser').required = false;
    document.getElementById('btnUser').textContent = 'Simpan Perubahan';
    document.getElementById('btnBatalUser').hidden = false;

    form.scrollIntoView({behavior:'smooth', block:'center'});
}

function resetUser() {
    const form = document.getElementById('formUser');
    if (!form) return;

    form.reset();
    document.getElementById('aksiUser').value = 'tambah';
    document.getElementById('id_user').value = '';
    document.getElementById('passwordUser').required = false;
    document.getElementById('judulUser').textContent = 'Tambah Pengguna Baru';
    document.getElementById('btnUser').textContent = 'Simpan Pengguna';
    document.getElementById('btnBatalUser').hidden = true;
}
</script>
</body>
</html>
