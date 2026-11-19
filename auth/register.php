
<?php
session_start();
require_once '../config/database.php';

$pesan = '';
$tipe_pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if ($nama === '' || $email === '' || $password === '' || $konfirmasi === '') {
        $pesan = 'Semua kolom wajib diisi!';
        $tipe_pesan = 'error';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = 'Format email tidak valid!';
        $tipe_pesan = 'error';

    } elseif (strlen($password) < 8) {
        $pesan = 'Password minimal 8 karakter!';
        $tipe_pesan = 'error';

    } elseif ($password !== $konfirmasi) {
        $pesan = 'Konfirmasi password tidak cocok!';
        $tipe_pesan = 'error';

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_user FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $email_ada = mysqli_num_rows($hasil) > 0;

        mysqli_stmt_close($stmt);

        if ($email_ada) {
            $pesan = 'Email sudah terdaftar!';
            $tipe_pesan = 'error';

        } else {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'pembeli';

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (nama, email, password, role)
                 VALUES (?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $nama,
                $email,
                $password_hash,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {
                $pesan = 'Registrasi berhasil! Silakan login.';
                $tipe_pesan = 'success';
            } else {
                $pesan = 'Registrasi gagal. Silakan coba lagi.';
                $tipe_pesan = 'error';
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi - Bakery & Cake</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    min-height: 100vh;
    background: #fff8f2;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    color: #44352d;
}

.register-box {
    background: white;
    width: 100%;
    max-width: 430px;
    padding: 35px;
    border-radius: 15px;
    box-shadow: 0 5px 25px #00000010;
}

.logo {
    text-align: center;
    color: #9b5c38;
    font-size: 25px;
    font-weight: bold;
    margin-bottom: 10px;
}

.subtitle {
    text-align: center;
    color: #806b5d;
    margin-bottom: 25px;
    font-size: 14px;
}

.form-group {
    margin-bottom: 17px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-size: 14px;
    font-weight: 600;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd2c8;
    border-radius: 8px;
    outline: none;
}

input:focus {
    border-color: #9b5c38;
}

button {
    width: 100%;
    padding: 13px;
    background: #9b5c38;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #795548;
}

.alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 14px;
}

.success {
    background: #e5f5e9;
    color: #26723c;
}

.error {
    background: #fde8e7;
    color: #a52a2a;
}

.login-link {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
}

.login-link a {
    color: #9b5c38;
    font-weight: bold;
    text-decoration: none;
}

</style>
</head>

<body>

<div class="register-box">

    <div class="logo">🥐 Bakery & Cake</div>

    <p class="subtitle">Buat akun untuk mulai berbelanja.</p>

    <?php if ($pesan !== ''): ?>
        <div class="alert <?= $tipe_pesan ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama"
                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                   placeholder="Masukkan nama lengkap" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="Masukkan email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password"
                   placeholder="Minimal 8 karakter" required>
        </div>

        <div class="form-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="konfirmasi"
                   placeholder="Ulangi password" required>
        </div>

        <button type="submit">Daftar Sekarang</button>

    </form>

    <div class="login-link">
        Sudah punya akun?
        <a href="login.php">Login di sini</a>
    </div>

</div>

</body>
</html>
