
<?php
session_start();

if (
    !isset($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'pembeli'
) {
    header("Location: ../auth/login.php");
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pembeli';

// Memuat halaman melalui AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {

    $halaman = $_GET['halaman'] ?? '';

    $daftarHalaman = [
        'katalog' => 'katalog.php',
        'keranjang' => 'keranjang.php',
        'checkout' => 'checkout.php',
        'pesanan' => 'pesanan_saya.php'
    ];

    if (isset($daftarHalaman[$halaman])) {

        $file = __DIR__ . '/partials/' . $daftarHalaman[$halaman];

        if (file_exists($file)) {
            require $file;
            exit;
        }
    }

    http_response_code(404);
    echo 'Halaman tidak ditemukan.';
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard - Bakery & Cake</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    background: #fff8f2;
    color: #49372e;
}

header {
    background: #ffffff;
    padding: 20px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 12px #0000000d;
    position: sticky;
    top: 0;
    z-index: 10;
}

.logo {
    color: #9b5c38;
    font-size: 23px;
    font-weight: 700;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 28px;
}

.nav-menu a {
    color: #795548;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: .2s;
    cursor: pointer;
}

.nav-menu a:hover,
.nav-menu a.active {
    color: #b36a3c;
}

.logout {
    background: #9b5c38;
    color: white !important;
    padding: 11px 19px;
    border-radius: 8px;
}

.container {
    max-width: 1250px;
    margin: auto;
    padding: 45px 25px;
    min-height: 450px;
}

.welcome {
    margin-bottom: 35px;
}

.welcome h1 {
    font-size: 30px;
    margin-bottom: 10px;
}

.welcome p {
    color: #806b5d;
    font-size: 15px;
}

.hero {
    background: #ead8c8;
    border-radius: 18px;
    padding: 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
}

.hero-content h2 {
    font-size: 27px;
    margin-bottom: 12px;
}

.hero-content p {
    color: #705447;
    line-height: 1.7;
    margin-bottom: 20px;
}

.hero-content a {
    display: inline-block;
    background: #9b5c38;
    color: white;
    text-decoration: none;
    padding: 12px 22px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: .2s;
}

.hero-content a:hover {
    background: #795548;
}

.hero-illustration {
    font-size: 105px;
    min-width: 150px;
    text-align: center;
}

footer {
    text-align: center;
    padding: 25px;
    color: #9a8375;
    font-size: 13px;
}

.loading {
    text-align: center;
    padding: 60px 20px;
    color: #9b5c38;
}

@media(max-width: 750px) {
    header {
        flex-direction: column;
        gap: 18px;
    }

    .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
        gap: 16px;
    }

    .hero {
        flex-direction: column;
        text-align: center;
    }
}

@media(max-width: 500px) {
    .welcome h1 {
        font-size: 23px;
    }

    .hero-content h2 {
        font-size: 22px;
    }

    .hero-illustration {
        font-size: 75px;
    }

    .container {
        padding: 30px 18px;
    }
}
</style>
</head>

<body>

<header>

    <div class="logo">🥐 Bakery & Cake</div>

    <nav class="nav-menu" id="navigasi">

        <a href="#" class="active" data-halaman="dashboard">
            Dashboard
        </a>

        <a href="#" data-halaman="katalog">
            Katalog
        </a>

        <a href="#" data-halaman="keranjang">
            Keranjang
        </a>

        <a href="#" data-halaman="pesanan">
            Pesanan Saya
        </a>

        <a href="../auth/logout.php" class="logout">
            Keluar
        </a>

    </nav>

</header>

<main class="container" id="areaKonten">

    <section class="welcome">
        <h1>
            Selamat Datang,
            <?= htmlspecialchars($nama) ?>! 👋
        </h1>

        <p>
            Senang melihatmu kembali. Yuk, temukan camilan favoritmu!
        </p>
    </section>

    <section class="hero">

        <div class="hero-content">

            <h2>Freshly Baked, Made with Love 🤎</h2>

            <p>
                Nikmati berbagai pilihan roti, cake, dan pastry
                yang dibuat untuk menemani setiap momen spesialmu.
            </p>

            <a href="#" id="tombolKatalog">
                Jelajahi Produk
            </a>

        </div>

        <div class="hero-illustration">
            🥐 🧁
        </div>

    </section>

</main>

<footer>
    © <?= date('Y') ?> Bakery & Cake. Made with Love.
</footer>

<script>
const areaKonten = document.getElementById('areaKonten');
const navigasi = document.getElementById('navigasi');

let halamanAktif = 'dashboard';

// Mengatur menu aktif
function aktifkanMenu(halaman) {

    document.querySelectorAll('#navigasi a[data-halaman]')
        .forEach(link => {

            link.classList.toggle(
                'active',
                link.dataset.halaman === halaman
            );

        });
}

// Membuka halaman dalam dashboard
async function bukaHalaman(halaman) {

    if (halaman === 'dashboard') {
        location.reload();
        return;
    }

    const halamanDiizinkan = [
        'katalog',
        'keranjang',
        'checkout',
        'pesanan'
    ];

    if (!halamanDiizinkan.includes(halaman)) {
        return;
    }

    halamanAktif = halaman;
    aktifkanMenu(halaman);

    areaKonten.innerHTML =
        '<div class="loading">Memuat halaman... 🥐</div>';

    await muatKonten(halaman);
}

// Mengambil konten dari PHP
async function muatKonten(halaman) {

    try {

        const response = await fetch(
            'dashboard_pembeli.php?ajax=1&halaman=' +
            encodeURIComponent(halaman)
        );

        if (!response.ok) {
            throw new Error('Gagal memuat halaman.');
        }

        const html = await response.text();

        areaKonten.innerHTML = html;

        if (halaman === 'katalog') {
            aktifkanFilterProduk();
        }

    } catch (error) {

        areaKonten.innerHTML =
            '<div class="loading">Gagal memuat halaman. Silakan coba lagi.</div>';

        console.error(error);
    }
}

// Klik menu navigasi
navigasi.addEventListener('click', function(event) {

    const link = event.target.closest('a[data-halaman]');

    if (!link) return;

    event.preventDefault();

    bukaHalaman(link.dataset.halaman);
});

// Klik tombol Jelajahi Produk
document.getElementById('tombolKatalog')
    .addEventListener('click', function(event) {

        event.preventDefault();

        bukaHalaman('katalog');
    });

// Pencarian dan filter katalog
function aktifkanFilterProduk() {

    const cari = document.getElementById('cariProduk');
    const filter = document.getElementById('filterKategori');

    const kartu = document.querySelectorAll(
        '#daftarProduk .produk-card'
    );

    const hasilKosong = document.getElementById('hasilKosong');

    if (!cari || !filter) return;

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

            if (tampil) jumlahTampil++;
        });

        if (hasilKosong) {
            hasilKosong.hidden = jumlahTampil > 0;
        }
    }

    cari.addEventListener('input', filterProduk);
    filter.addEventListener('change', filterProduk);
}

// Proses form katalog, keranjang, dan checkout
areaKonten.addEventListener('submit', async function(event) {

    const form = event.target.closest(
        '.produk-form, .form-keranjang, .form-checkout'
    );

    if (!form) return;

    event.preventDefault();

    const tombol = form.querySelector('button[type="submit"]');

    if (tombol) {
        tombol.disabled = true;
        tombol.textContent = 'Memproses...';
    }

    try {

        // Proses checkout
        if (form.classList.contains('form-checkout')) {

            const response = await fetch(
                'partials/proses_checkout.php',
                {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const hasil = await response.json();

            if (!response.ok || !hasil.success) {
                throw new Error(
                    hasil.message || 'Checkout gagal diproses.'
                );
            }

            areaKonten.innerHTML = `
                <div style="
                    background:white;
                    padding:45px 25px;
                    border-radius:18px;
                    text-align:center;
                    box-shadow:0 3px 15px #0000000d;
                ">

                    <div style="font-size:65px;">🎉</div>

                    <h2 style="color:#795548;margin:15px 0;">
                        Pesanan Berhasil!
                    </h2>

                    <p style="color:#806b5d;margin-bottom:10px;">
                        Pesanan kamu berhasil dibuat.
                    </p>

                    <p style="margin-bottom:25px;">
                        Nomor Pesanan:
                        <strong>#${hasil.id_transaksi}</strong>
                    </p>

                    <button
                        type="button"
                        onclick="bukaHalaman('katalog')"
                        style="
                            background:#9b5c38;
                            color:white;
                            border:none;
                            padding:12px 25px;
                            border-radius:8px;
                            cursor:pointer;
                        "
                    >
                        Kembali Belanja
                    </button>

                </div>
            `;

            halamanAktif = 'checkout';
            return;
        }

        // Proses tambah produk dan perubahan keranjang
        const response = await fetch(
            'dashboard_pembeli.php?ajax=1&halaman=' + halamanAktif,
            {
                method: 'POST',
                body: new FormData(form)
            }
        );

        if (!response.ok) {
            throw new Error('Gagal memproses permintaan.');
        }

        const html = await response.text();

        areaKonten.innerHTML = html;

        if (halamanAktif === 'katalog') {
            aktifkanFilterProduk();
        }

    } catch (error) {

        alert(error.message || 'Terjadi kesalahan. Silakan coba lagi.');

        if (tombol) {
            tombol.disabled = false;
            tombol.textContent = 'Konfirmasi Pesanan';
        }

        console.error(error);
    }
});

// Tombol kembali dan Checkout
areaKonten.addEventListener('click', function(event) {

    const kembali = event.target.closest('#kembaliKatalog');

    if (kembali) {
        event.preventDefault();
        bukaHalaman('katalog');
        return;
    }

    const checkout = event.target.closest('#tombolCheckout');

    if (checkout) {
        event.preventDefault();
        bukaHalaman('checkout');
    }
});
</script>

</body>
</html>
