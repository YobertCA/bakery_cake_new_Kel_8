
<?php
session_start();

// Jika sudah login, arahkan sesuai role
if (isset($_SESSION['id_user'])) {
    $role = $_SESSION['role'] ?? '';

    if ($role === 'pembeli') {
        header("Location: pages/dashboard_pembeli.php");
        exit;
    } elseif ($role === 'kasir') {
        header("Location: pages/kasir/dashboard_kasir.php");
        exit;
    } elseif ($role === 'pemilik') {
        header("Location: pages/pemilik/dashboard_pemilik.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bakery & Cake | Freshly Baked Happiness</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    background: #fffaf5;
    color: #4e342e;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 8%;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
}

.logo {
    font-size: 23px;
    font-weight: bold;
    color: #795548;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 25px;
}

.nav-links a {
    text-decoration: none;
    color: #5d4037;
    font-size: 14px;
    font-weight: 600;
}

.nav-links a:hover {
    color: #bc8058;
}

.btn-login {
    background: #795548;
    color: white !important;
    padding: 11px 22px;
    border-radius: 7px;
}

.hero {
    min-height: 520px;
    padding: 70px 8%;
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    align-items: center;
    gap: 40px;
    background: #f8eadc;
}

.hero-content .tagline {
    color: #a66c48;
    font-weight: bold;
    font-size: 14px;
    letter-spacing: 2px;
    margin-bottom: 20px;
}

.hero h1 {
    font-size: 48px;
    line-height: 1.2;
    margin-bottom: 20px;
    color: #4e342e;
}

.hero p {
    color: #795548;
    line-height: 1.8;
    margin-bottom: 30px;
    max-width: 500px;
}

.btn-primary {
    display: inline-block;
    padding: 14px 25px;
    background: #795548;
    color: white;
    border-radius: 7px;
    text-decoration: none;
    font-weight: 600;
}

.btn-primary:hover,
.btn-login:hover {
    background: #5d4037;
}

.hero-image img {
    width: 100%;
    height: 370px;
    object-fit: cover;
    border-radius: 18px;
}

.section {
    padding: 70px 8%;
    text-align: center;
}

.section h2 {
    font-size: 30px;
    margin-bottom: 12px;
}

.section-desc {
    color: #8d6e63;
    margin-bottom: 35px;
}

.category-list {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}

.category-card {
    background: white;
    padding: 35px 20px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
}

.category-card .emoji {
    font-size: 42px;
    margin-bottom: 15px;
}

.category-card h3 {
    margin-bottom: 10px;
}

.category-card p {
    color: #8d6e63;
    font-size: 14px;
    line-height: 1.6;
}

.about {
    background: #f8eadc;
    padding: 65px 8%;
    text-align: center;
}

.about h2 {
    margin-bottom: 18px;
}

.about p {
    max-width: 750px;
    margin: auto;
    line-height: 1.8;
    color: #795548;
}

.footer {
    background: #4e342e;
    color: #fff8f2;
    padding: 25px;
    text-align: center;
    font-size: 13px;
}

@media(max-width: 768px) {
    .navbar {
        padding: 18px 5%;
        flex-wrap: wrap;
        gap: 15px;
    }

    .nav-links {
        gap: 13px;
        flex-wrap: wrap;
    }

    .hero {
        grid-template-columns: 1fr;
        padding: 50px 6%;
    }

    .hero h1 {
        font-size: 36px;
    }

    .hero-image img {
        height: 280px;
    }

    .category-list {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo">🍰 Bakery & Cake</div>

    <div class="nav-links">
        <a href="#beranda">Beranda</a>
        <a href="#kategori">Kategori</a>
        <a href="#tentang">Tentang Kami</a>
        <a href="auth/login.php" class="btn-login">Login</a>
        <a href="auth/register.php">Daftar</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero" id="beranda">
    <div class="hero-content">
        <div class="tagline">FRESHLY BAKED EVERYDAY</div>

        <h1>Manisnya Setiap Momen Bersama Bakery & Cake</h1>

        <p>
            Nikmati berbagai pilihan cake, roti, dan pastry
            dengan rasa istimewa dan kualitas terbaik.
            Temukan camilan favoritmu untuk menemani setiap momen.
        </p>

        <a href="auth/register.php" class="btn-primary">
            Mulai Belanja →
        </a>
    </div>

    <div class="hero-image">
        <img
            src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=900"
            alt="Chocolate Cake">
    </div>
</section>

<!-- KATEGORI -->
<section class="section" id="kategori">
    <h2>Produk Kami</h2>
    <p class="section-desc">
        Berbagai pilihan produk untuk menemani harimu.
    </p>

    <div class="category-list">
        <div class="category-card">
            <div class="emoji">🎂</div>
            <h3>Cake</h3>
            <p>Aneka kue dengan rasa lembut dan tampilan menarik.</p>
        </div>

        <div class="category-card">
            <div class="emoji">🥐</div>
            <h3>Pastry</h3>
            <p>Pastry renyah dengan rasa yang istimewa.</p>
        </div>

        <div class="category-card">
            <div class="emoji">🍞</div>
            <h3>Roti</h3>
            <p>Roti lembut dengan berbagai pilihan rasa.</p>
        </div>
    </div>
</section>

<!-- TENTANG -->
<section class="about" id="tentang">
    <h2>Tentang Bakery & Cake</h2>

    <p>
        Bakery & Cake merupakan toko roti dan kue yang menyediakan
        berbagai pilihan produk seperti cake, roti, dan pastry.
        Kami berkomitmen memberikan produk berkualitas dengan
        rasa yang lezat untuk menemani setiap momen pelanggan.
    </p>
</section>

<!-- FOOTER -->
<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Bakery & Cake.
       All Rights Reserved.</p>
</footer>

</body>
</html>
