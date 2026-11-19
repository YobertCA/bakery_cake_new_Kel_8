
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database
require_once __DIR__ . '/../../config/database.php';

// Pemeriksaan akses Kasir
if (
    !isset($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'kasir'
) {
    header("Location: ../../auth/login.php");
    exit;
}

// ======================================
// AJAX MEMUAT HALAMAN
// ======================================
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {

    $halaman = $_GET['halaman'] ?? '';

    $daftarHalaman = [
        'transaksi',
        'cek_stok',
        'laporan_pemesanan'
    ];

    if (in_array($halaman, $daftarHalaman, true)) {

        // File berada langsung di folder pages/kasir
        $file = __DIR__ . '/' . $halaman . '.php';

        if (file_exists($file)) {
            require $file;
        } else {
            http_response_code(404);
            echo '<p>File halaman tidak ditemukan.</p>';
        }

        exit;
    }

    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

// ======================================
// FUNGSI HITUNG PESANAN
// ======================================
function hitungPesanan($conn, $status) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS jumlah
         FROM transaksi
         WHERE status = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    return (int) ($data['jumlah'] ?? 0);
}

// Statistik pesanan
$pesananMasuk = hitungPesanan($conn, 'pending');
$sedangDiproses = hitungPesanan($conn, 'diproses');
$pesananSelesai = hitungPesanan($conn, 'selesai');

$totalPesanan = $pesananMasuk
              + $sedangDiproses
              + $pesananSelesai;

// Nama kasir
$nama = htmlspecialchars(
    $_SESSION['nama'] ?? 'Admin Kasir',
    ENT_QUOTES,
    'UTF-8'
);

// Pesan notifikasi
$pesan = '';
$jenisPesan = 'success';

if (isset($_GET['pesan']) && $_GET['pesan'] === 'berhasil') {
    $id = (int) ($_GET['id'] ?? 0);
    $pesan = "Pesanan #" . $id . " berhasil diproses.";
}

if (isset($_SESSION['error_proses'])) {
    $pesan = $_SESSION['error_proses'];
    $jenisPesan = 'error';
    unset($_SESSION['error_proses']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Kasir - Bakery & Cake</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    background: #fff8f2;
    color: #44352d;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 240px;
    background: #604333;
    color: white;
    position: fixed;
    height: 100vh;
    padding: 25px 18px;
    display: flex;
    flex-direction: column;
}

.brand {
    font-size: 21px;
    font-weight: bold;
    color: #ffe0c4;
    margin-bottom: 45px;
}

.menu-label {
    font-size: 11px;
    color: #d8bca8;
    letter-spacing: 1px;
    margin: 0 12px 15px;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.menu a {
    display: block;
    padding: 14px 13px;
    color: white;
    text-decoration: none;
    border-radius: 9px;
    font-size: 14px;
    transition: .2s;
}

.menu a:hover,
.menu a.active {
    background: #9b6545;
}

.logout {
    margin-top: auto;
    color: white;
    text-decoration: none;
    padding: 12px;
    font-size: 14px;
}

.logout:hover {
    background: #795548;
    border-radius: 9px;
}

.main {
    margin-left: 240px;
    width: calc(100% - 240px);
    min-height: 100vh;
}

.topbar {
    height: 72px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 35px;
    box-shadow: 0 2px 10px #00000008;
}

.topbar span {
    color: #9b5c38;
    font-size: 14px;
}

.content {
    padding: 40px;
}

.welcome {
    margin-bottom: 28px;
}

.welcome h2 {
    font-size: 25px;
    margin-bottom: 8px;
}

.welcome p {
    color: #806b5d;
    font-size: 14px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 14px;
    border: 1px solid #f0e4dc;
    box-shadow: 0 3px 12px #00000005;
}

.stat-card p {
    color: #806b5d;
    font-size: 13px;
    margin-bottom: 22px;
}

.stat-card h3 {
    color: #9b5c38;
    font-size: 27px;
}

.alert {
    padding: 14px 18px;
    border-radius: 9px;
    margin-bottom: 25px;
    background: #e7f4e9;
    color: #26733d;
    font-size: 14px;
}

.alert.error {
    background: #fde8e7;
    color: #a52a2a;
}

.footer {
    text-align: center;
    color: #a58b7b;
    font-size: 12px;
    margin-top: 85px;
}

#areaKonten {
    width: 100%;
}

@media(max-width: 900px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 600px) {
    .sidebar {
        width: 190px;
    }

    .main {
        margin-left: 190px;
        width: calc(100% - 190px);
    }

    .content {
        padding: 20px;
    }

    .stats {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="brand">🥐 Bakery & Cake</div>

        <nav class="menu">

            <a href="#" class="menu-link active"
               data-page="dashboard">
                ▦ &nbsp; Dashboard
            </a>

            <a href="#" class="menu-link"
               data-page="transaksi">
                🧾 &nbsp; Transaksi Pemesanan
            </a>

            <a href="#" class="menu-link"
               data-page="cek_stok">
                📦 &nbsp; Cek Stok
            </a>

            <a href="#" class="menu-link"
               data-page="laporan_pemesanan">
                📊 &nbsp; Kelola Laporan Pemesanan
            </a>

        </nav>

        <a href="../../auth/logout.php"
           class="logout"
           onclick="return confirm('Yakin ingin keluar?')">
            ↪ &nbsp; Keluar
        </a>

    </aside>

    <main class="main">

        <header class="topbar">

            <strong id="judulHalaman">Dashboard</strong>

        </header>

        <section class="content" id="areaKonten">

            <?php if ($pesan !== ''): ?>
                <div class="alert <?= $jenisPesan === 'error' ? 'error' : '' ?>">
                    <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="welcome">

                <h2>
                    Selamat Datang👋
                </h2>


            </div>

            <div class="stats">

                <div class="stat-card">
                    <p>Pesanan Masuk</p>
                    <h3><?= $pesananMasuk ?></h3>
                </div>

                <div class="stat-card">
                    <p>Sedang Diproses</p>
                    <h3><?= $sedangDiproses ?></h3>
                </div>

                <div class="stat-card">
                    <p>Pesanan Selesai</p>
                    <h3><?= $pesananSelesai ?></h3>
                </div>

                <div class="stat-card">
                    <p>Total Pesanan</p>
                    <h3><?= $totalPesanan ?></h3>
                </div>

            </div>

            <div class="footer">
                © 2026 Bakery & Cake. Made with Love.
            </div>

        </section>

    </main>

</div>

<script>
const menuLinks = document.querySelectorAll('.menu-link');
const areaKonten = document.getElementById('areaKonten');
const judulHalaman = document.getElementById('judulHalaman');

const judul = {
    dashboard: 'Dashboard',
    transaksi: 'Transaksi Pemesanan',
    cek_stok: 'Cek Stok',
    laporan_pemesanan: 'Kelola Laporan Pemesanan'
};

menuLinks.forEach(link => {

    link.addEventListener('click', async function(e) {

        e.preventDefault();

        const halaman = this.dataset.page;

        menuLinks.forEach(item => {
            item.classList.remove('active');
        });

        this.classList.add('active');

        judulHalaman.textContent = judul[halaman];

        if (halaman === 'dashboard') {
            location.reload();
            return;
        }

        areaKonten.innerHTML = '<p>Memuat halaman...</p>';

        try {

            const response = await fetch(
                'dashboard_kasir.php?ajax=1&halaman='
                + encodeURIComponent(halaman)
            );

            if (!response.ok) {
                throw new Error('Gagal memuat halaman');
            }

            const html = await response.text();

            areaKonten.innerHTML = html;

        } catch (error) {

            console.error(error);

            areaKonten.innerHTML = `
                <p style="color:#b33;">
                    Gagal memuat halaman. Silakan coba lagi.
                </p>
            `;
        }

    });

});
</script>

</body>
</html>
