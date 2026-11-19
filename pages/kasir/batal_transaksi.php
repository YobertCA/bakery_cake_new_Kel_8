<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Hanya Kasir yang boleh membatalkan pesanan.
if (
    !isset($_SESSION['id_user']) ||
    ($_SESSION['role'] ?? '') !== 'kasir'
) {
    header("Location: ../../auth/login.php");
    exit;
}

// Hanya menerima POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard_kasir.php?halaman=transaksi");
    exit;
}

$id_transaksi = (int)($_POST['id_transaksi'] ?? 0);
$redirectUrl = 'dashboard_kasir.php?halaman=transaksi';

if ($id_transaksi <= 0) {
    $_SESSION['error_proses'] = "ID pesanan tidak valid.";
    header("Location: " . $redirectUrl);
    exit;
}

try {
    mysqli_begin_transaction($conn);

    // Kunci baris transaksi agar tidak dibatalkan bersamaan dengan proses kasir.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT status FROM transaksi WHERE id_transaksi = ? FOR UPDATE"
    );

    mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $transaksi = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$transaksi) {
        throw new Exception("Pesanan tidak ditemukan.");
    }

    // Pembatalan hanya diizinkan sebelum pesanan diproses.
    if ($transaksi['status'] !== 'pending') {
        throw new Exception(
            "Pesanan tidak dapat dibatalkan karena sudah diproses atau telah dibatalkan."
        );
    }

    $status = 'dibatalkan';

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE transaksi SET status = ? WHERE id_transaksi = ?"
    );

    mysqli_stmt_bind_param($stmt, "si", $status, $id_transaksi);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    $_SESSION['pesan_sukses'] =
        "Pesanan #" . $id_transaksi . " berhasil dibatalkan.";

} catch (Throwable $e) {
    try {
        mysqli_rollback($conn);
    } catch (Throwable $rollbackError) {
        // Abaikan error rollback agar pesan utama tetap dapat ditampilkan.
    }

    $_SESSION['error_proses'] = $e->getMessage();
}

header("Location: " . $redirectUrl);
exit;
