
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

$keranjang = $_SESSION['keranjang'] ?? [];

if (empty($keranjang)) {
    echo '<div class="empty-cart">
        <h2>Keranjang masih kosong 🛒</h2>
        <p>Silakan pilih produk terlebih dahulu.</p>
    </div>';
    exit;
}

$total = 0;
$items = [];

foreach ($keranjang as $id_produk => $item) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_produk, nama_produk, harga, stok, status
         FROM produk
         WHERE id_produk = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id_produk);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$produk || $produk['status'] !== 'tersedia') {
        continue;
    }

    $jumlah = (int) $item['jumlah'];

    if ($jumlah < 1 || $jumlah > (int) $produk['stok']) {
        continue;
    }

    $subtotal = $jumlah * (float) $produk['harga'];
    $total += $subtotal;

    $items[] = [
        'nama' => $produk['nama_produk'],
        'jumlah' => $jumlah,
        'harga' => $produk['harga'],
        'subtotal' => $subtotal
    ];
}

if (empty($items)) {
    echo '<div class="empty-cart">
        <h2>Produk tidak tersedia</h2>
        <p>Silakan periksa kembali keranjangmu.</p>
    </div>';
    exit;
}
?>

<style>
.checkout-wrapper {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 22px;
    align-items: start;
}

.checkout-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 18px;
    box-shadow: 0 3px 15px #00000008;
}

.checkout-card h3 {
    margin-bottom: 20px;
}

.checkout-item {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 14px 0;
    border-bottom: 1px solid #eee2d9;
}

.checkout-item:last-child {
    border-bottom: none;
}

.checkout-item small {
    display: block;
    color: #806b5d;
    margin-top: 5px;
}

.checkout-total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    font-size: 19px;
    margin: 20px 0;
    color: #9b5c38;
}

.payment-option {
    display: block;
    padding: 16px;
    border: 1px solid #e5d5ca;
    border-radius: 9px;
    margin-bottom: 12px;
    cursor: pointer;
}

.payment-option input {
    margin-right: 8px;
    accent-color: #9b5c38;
}

.payment-option small {
    display: block;
    color: #806b5d;
    margin: 7px 0 0 25px;
}

.btn-confirm {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    background: #9b5c38;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.btn-confirm:hover {
    background: #795548;
}

.btn-back {
    display: inline-block;
    color: #9b5c38;
    text-decoration: none;
    margin-bottom: 20px;
}

@media(max-width: 750px) {
    .checkout-wrapper {
        grid-template-columns: 1fr;
    }
}
</style>

<div>
    <a href="#" id="kembaliKatalog" class="btn-back">
        ← Kembali ke Katalog
    </a>

    <h2>Checkout Pesanan 🧾</h2>
    <p style="color:#806b5d; margin:8px 0 25px;">
        Periksa kembali pesananmu sebelum dikonfirmasi.
    </p>

    <div class="checkout-wrapper">

        <section class="checkout-card">
            <h3>Detail Pesanan</h3>

            <?php foreach ($items as $item): ?>
                <div class="checkout-item">
                    <div>
                        <strong>
                            <?= htmlspecialchars($item['nama']) ?>
                        </strong>
                        <small>
                            <?= $item['jumlah'] ?> × Rp
                            <?= number_format($item['harga'], 0, ',', '.') ?>
                        </small>
                    </div>

                    <strong>
                        Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                    </strong>
                </div>
            <?php endforeach; ?>

            <div class="checkout-total">
                <span>Total Pembayaran</span>
                <span>
                    Rp <?= number_format($total, 0, ',', '.') ?>
                </span>
            </div>
        </section>

        <section class="checkout-card">
            <h3>Metode Pembayaran</h3>

            <form class="form-checkout">

                <label class="payment-option">
                    <input
                        type="radio"
                        name="metode_pembayaran"
                        value="tunai"
                        required
                    >
                    <strong>💵 Tunai</strong>
                    <small>Bayar tunai saat mengambil pesanan.</small>
                </label>

                <label class="payment-option">
                    <input
                        type="radio"
                        name="metode_pembayaran"
                        value="transfer"
                        required
                    >
                    <strong>🏦 Transfer</strong>
                    <small>Bayar melalui transfer saat pengambilan pesanan.</small>
                </label>

                <button type="submit" class="btn-confirm">
                    Konfirmasi Pesanan
                </button>

            </form>
        </section>

    </div>
</div>
