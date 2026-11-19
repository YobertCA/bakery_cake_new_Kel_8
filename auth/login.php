
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $pesan = 'Email dan password wajib diisi!';

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_user, nama, email, password, role
             FROM users
             WHERE email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($hasil);

        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Arahkan pengguna berdasarkan role
            switch ($user['role']) {

                case 'pembeli':
                    header("Location: ../pages/dashboard_pembeli.php");
                    exit;

                case 'kasir':
                    header("Location: ../pages/kasir/dashboard_kasir.php");
                    exit;

                case 'pemilik':
                    header("Location: ../pages/pemilik/dashboard_pemilik.php");
                    exit;

                default:
                    session_unset();
                    session_destroy();
                    $pesan = 'Role akun tidak dikenali.';
                    break;
            }

        } else {

            $pesan = 'Email atau password salah!';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - Bakery & Cake</title>

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

.login-box {
    background: white;
    width: 100%;
    max-width: 430px;
    padding: 38px 35px;
    border-radius: 16px;
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
    margin-bottom: 28px;
    font-size: 14px;
}

.form-group {
    margin-bottom: 19px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #ddd2c8;
    border-radius: 8px;
    outline: none;
    font-size: 14px;
}

input:focus {
    border-color: #9b5c38;
    box-shadow: 0 0 0 3px #9b5c3815;
}

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 65px;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #9b5c38;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
}

button.login-button {
    width: 100%;
    padding: 14px;
    background: #9b5c38;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s;
}

button.login-button:hover {
    background: #795548;
    transform: translateY(-1px);
}

.alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 14px;
    background: #fde8e7;
    color: #a52a2a;
}

.register-link {
    text-align: center;
    margin-top: 22px;
    font-size: 14px;
}

.register-link a {
    color: #9b5c38;
    font-weight: bold;
    text-decoration: none;
}

@media(max-width: 480px) {
    .login-box {
        padding: 30px 22px;
    }
}
</style>
</head>

<body>

<div class="login-box">

    <div class="logo">🥐 Bakery & Cake</div>

    <p class="subtitle">
        Selamat datang kembali! Silakan login.
    </p>

    <?php if ($pesan !== ''): ?>
        <div class="alert">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Email</label>

            <input
                type="email"
                name="email"
                placeholder="Masukkan email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label>Password</label>

            <div class="password-wrapper">
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Masukkan password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    onclick="togglePassword()"
                >Lihat</button>
            </div>
        </div>

        <button type="submit" class="login-button">
            Login Sekarang
        </button>

    </form>

    <div class="register-link">
        Belum punya akun?
        <a href="register.php">Daftar di sini</a>
    </div>

</div>

<script>
function togglePassword() {

    const password = document.getElementById('password');
    const tombol = document.querySelector('.toggle-password');

    if (password.type === 'password') {
        password.type = 'text';
        tombol.textContent = 'Sembunyi';
    } else {
        password.type = 'password';
        tombol.textContent = 'Lihat';
    }
}
</script>

</body>
</html>
