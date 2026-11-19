
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Hanya Kasir yang boleh memproses pesanan
if (
    !isset($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'kasir'
) {
    header("Location: ../../auth/login.php");
    exit;
}

// Pastikan menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard_kasir.php");
    exit;
}

$id_transaksi = (int)($_POST['id_transaksi'] ?? 0);
$id_user = (int)$_SESSION['id_user'];

if ($id_transaksi <= 0) {
    $_SESSION['error_proses'] = "ID pesanan tidak valid.";
    header("Location: ../dashboard_kasir.php?halaman=transaksi");
    exit;
}

try {

    mysqli_begin_transaction($conn);

    // Kunci transaksi agar tidak diproses dua kali
    $stmt = mysqli_prepare($conn, "
        SELECT id_transaksi, status
        FROM transaksi
        WHERE id_transaksi = ?
        FOR UPDATE
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
    mysqli_stmt_execute($stmt);

    $transaksi = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$transaksi) {
        throw new Exception("Pesanan tidak ditemukan.");
    }

    if ($transaksi['status'] !== 'pending') {
        throw new Exception("Pesanan sudah pernah diproses.");
    }

    // Ambil detail produk pesanan
    $stmt = mysqli_prepare($conn, "
        SELECT id_produk, jumlah
        FROM detail_transaksi
        WHERE id_transaksi = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $detail = [];

    while ($item = mysqli_fetch_assoc($hasil)) {
        $detail[] = $item;
    }

    mysqli_stmt_close($stmt);

    if (empty($detail)) {
        throw new Exception("Detail pesanan tidak ditemukan.");
    }

    // Periksa dan kurangi stok
    foreach ($detail as $item) {

        $id_produk = (int)$item['id_produk'];
        $jumlah = (int)$item['jumlah'];

        if ($jumlah <= 0) {
            throw new Exception("Jumlah produk tidak valid.");
        }

        $stmt = mysqli_prepare($conn, "
            SELECT nama_produk, stok, status
            FROM produk
            WHERE id_produk = ?
            FOR UPDATE
        ");

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);

        $produk = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

        mysqli_stmt_close($stmt);

        if (!$produk) {
            throw new Exception("Produk tidak ditemukan.");
        }

        if ($produk['status'] !== 'tersedia') {
            throw new Exception(
                "Produk " . $produk['nama_produk'] .
                " sudah tidak tersedia."
            );
        }

        if ((int)$produk['stok'] < $jumlah) {
            throw new Exception(
                "Stok " . $produk['nama_produk'] .
                " tidak mencukupi."
            );
        }

        // Kurangi stok
        $stmt = mysqli_prepare($conn, "
            UPDATE produk
            SET stok = stok - ?
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

        // Catat stok keluar
        $keterangan = "Pesanan #" . $id_transaksi;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO stok_keluar
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
    }

    // Ubah status menjadi selesai
    $status = 'selesai';

    $stmt = mysqli_prepare($conn, "
        UPDATE transaksi
        SET status = ?
        WHERE id_transaksi = ?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $status,
        $id_transaksi
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    $_SESSION['pesan_sukses'] =
        "Pesanan #" . $id_transaksi . " berhasil diselesaikan.";

    // Kembali ke dashboard Kasir
    header("Location: ../dashboard_kasir.php?pesan=berhasil");
    exit;

} catch (Throwable $e) {

    mysqli_rollback($conn);

    $_SESSION['error_proses'] = $e->getMessage();

    header("Location: ../dashboard_kasir.php?pesan=gagal");
    exit;
}
