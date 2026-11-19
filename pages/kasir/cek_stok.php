<?php
require_once __DIR__ . '/../../config/database.php';

$query = mysqli_query(
    $conn,
    "SELECT id_produk, nama_produk, kategori, harga, stok, status
     FROM produk
     ORDER BY nama_produk ASC"
);
?>

<style>
.stok-header {
    margin-bottom: 25px;
}

.stok-header p {
    color: #806b5d;
    font-size: 14px;
    margin-top: 7px;
}

.stok-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stok-summary-card {
    background: white;
    border: 1px solid #f0e4dc;
    padding: 22px;
    border-radius: 13px;
}

.stok-summary-card p {
    color: #806b5d;
    font-size: 13px;
    margin-bottom: 12px;
}

.stok-summary-card h3 {
    color: #9b5c38;
    font-size: 25px;
}

.stok-container {
    background: white;
    padding: 25px;
    border: 1px solid #f0e4dc;
    border-radius: 14px;
}

.stok-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.stok-toolbar input {
    padding: 11px 14px;
    border: 1px solid #e5d5ca;
    border-radius: 8px;
    outline: none;
    width: 280px;
}

.stok-table {
    width: 100%;
    border-collapse: collapse;
}

.stok-table th,
.stok-table td {
    padding: 15px 12px;
    text-align: left;
    border-bottom: 1px solid #f0e4dc;
    font-size: 14px;
}

.stok-table th {
    background: #fff8f2;
    color: #795548;
}

.badge-stok {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.stok-aman {
    background: #e3f5e8;
    color: #26834a;
}

.stok-menipis {
    background: #fff0d8;
    color: #a66a12;
}

.stok-habis {
    background: #fde8e7;
    color: #b33a32;
}

.tabel-kosong {
    text-align: center !important;
    color: #806b5d;
    padding: 30px !important;
}

@media(max-width: 750px) {
    .stok-summary {
        grid-template-columns: 1fr;
    }

    .stok-container {
        overflow-x: auto;
    }

    .stok-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .stok-toolbar input {
        width: 100%;
    }
}
</style>

<?php
$totalProduk = 0;
$totalStok = 0;
$stokMenipis = 0;
$stokHabis = 0;

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $totalProduk++;
        $totalStok += (int)$row['stok'];

        if ((int)$row['stok'] === 0) {
            $stokHabis++;
        } elseif ((int)$row['stok'] <= 5) {
            $stokMenipis++;
        }
    }

    mysqli_data_seek($query, 0);
}
?>


<div class="stok-summary">

    <div class="stok-summary-card">
        <p>Total Produk</p>
        <h3><?= $totalProduk ?></h3>
    </div>

    <div class="stok-summary-card">
        <p>Total Stok Produk</p>
        <h3><?= $totalStok ?></h3>
    </div>

    <div class="stok-summary-card">
        <p>Produk Stok Menipis / Habis</p>
        <h3><?= $stokMenipis + $stokHabis ?></h3>
    </div>

</div>

<div class="stok-container">

    <div class="stok-toolbar">
        <h3>Daftar Stok Produk</h3>

        <input
            type="text"
            id="cariStok"
            placeholder="Cari nama produk..."
        >
    </div>

    <table class="stok-table">

        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Status Stok</th>
            </tr>
        </thead>

        <tbody id="daftarStok">

            <?php if ($query && mysqli_num_rows($query) > 0): ?>

                <?php $no = 1; ?>

                <?php while ($produk = mysqli_fetch_assoc($query)): ?>

                    <?php
                    $stok = (int)$produk['stok'];

                    if ($stok === 0) {
                        $statusStok = 'Habis';
                        $kelasStok = 'stok-habis';
                    } elseif ($stok <= 5) {
                        $statusStok = 'Menipis';
                        $kelasStok = 'stok-menipis';
                    } else {
                        $statusStok = 'Aman';
                        $kelasStok = 'stok-aman';
                    }
                    ?>

                    <tr>
                        <td><?= $no++ ?></td>

                        <td>
                            <strong>
                                <?= htmlspecialchars($produk['nama_produk']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($produk['kategori']) ?>
                        </td>

                        <td>
                            Rp <?= number_format(
                                $produk['harga'], 0, ',', '.'
                            ) ?>
                        </td>

                        <td>
                            <?= $stok ?>
                        </td>

                        <td>
                            <span class="badge-stok <?= $kelasStok ?>">
                                <?= $statusStok ?>
                            </span>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6" class="tabel-kosong">
                        Belum ada data produk.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<script>
(function () {
    const pencarian = document.getElementById('cariStok');

    if (pencarian) {
        pencarian.addEventListener('input', function () {
            const kata = this.value.toLowerCase();

            document.querySelectorAll('#daftarStok tr').forEach(row => {
                row.style.display =
                    row.textContent.toLowerCase().includes(kata)
                    ? ''
                    : 'none';
            });
        });
    }
})();
</script>