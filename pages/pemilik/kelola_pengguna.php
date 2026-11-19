<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pemilik') {
    exit('Akses ditolak.');
}

require_once __DIR__ . '/../../config/database.php';

$error = '';

// PROSES TAMBAH, EDIT, DAN HAPUS PENGGUNA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !in_array($role, ['pemilik', 'kasir', 'pembeli'], true)) {
            $error = 'Data pengguna tidak valid.';
        } elseif ($aksi === 'tambah' && $password === '') {
            $error = 'Password wajib diisi untuk pengguna baru.';
        } else {
            if ($aksi === 'tambah') {
                $cek = mysqli_prepare($conn, "SELECT id_user FROM users WHERE email = ?");
                mysqli_stmt_bind_param($cek, "s", $email);
                mysqli_stmt_execute($cek);
                $hasil = mysqli_stmt_get_result($cek);

                if (mysqli_num_rows($hasil) > 0) {
                    $error = 'Email sudah digunakan.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "ssss", $nama, $email, $hash, $role);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    mysqli_stmt_close($cek);
                    header("Location: dashboard_pemilik.php");
                    exit;
                }
                mysqli_stmt_close($cek);
            } else {
                $id = (int)($_POST['id_user'] ?? 0);

                if ($id <= 0) {
                    $error = 'Pengguna tidak ditemukan.';
                } elseif ($id === (int)$_SESSION['id_user'] && $role !== 'pemilik') {
                    $error = 'Role akun yang sedang digunakan tidak dapat diubah.';
                } else {
                    $cek = mysqli_prepare($conn, "SELECT id_user FROM users WHERE email = ? AND id_user != ?");
                    mysqli_stmt_bind_param($cek, "si", $email, $id);
                    mysqli_stmt_execute($cek);
                    $hasil = mysqli_stmt_get_result($cek);

                    if (mysqli_num_rows($hasil) > 0) {
                        $error = 'Email sudah digunakan pengguna lain.';
                    } else {
                        if ($password !== '') {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id_user = ?");
                            mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $hash, $role, $id);
                        } else {
                            $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, email = ?, role = ? WHERE id_user = ?");
                            mysqli_stmt_bind_param($stmt, "sssi", $nama, $email, $role, $id);
                        }

                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        mysqli_stmt_close($cek);
                        header("Location: dashboard_pemilik.php");
                        exit;
                    }
                    mysqli_stmt_close($cek);
                }
            }
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id_user'] ?? 0);

        if ($id === (int)$_SESSION['id_user']) {
            $error = 'Akun yang sedang digunakan tidak dapat dihapus.';
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);

            try {
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                header("Location: dashboard_pemilik.php");
                exit;
            } catch (mysqli_sql_exception $e) {
                $error = 'Pengguna tidak dapat dihapus karena masih memiliki data transaksi.';
                mysqli_stmt_close($stmt);
            }
        }
    }
}

$query = mysqli_query($conn, "SELECT id_user, nama, email, role FROM users ORDER BY id_user DESC");
if (!$query) {
    die('Gagal mengambil data pengguna: ' . mysqli_error($conn));
}
?>

<style>
.user-form{background:#fff8f2;padding:20px;border-radius:10px;margin-bottom:25px}
.user-form h3{margin-bottom:17px}
.user-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:15px}
.user-grid label{display:block;font-size:13px;font-weight:bold;margin-bottom:7px}
.user-grid input,.user-grid select{width:100%;padding:11px;border:1px solid #ddc9bb;border-radius:7px;background:white}
.btn-user{margin-top:15px;padding:11px 18px;border:none;border-radius:7px;background:#795548;color:white;cursor:pointer}
.btn-batal-user{background:#ddd;color:#333}
.tabel-user-wrapper{overflow-x:auto}
.tabel-user{width:100%;border-collapse:collapse;font-size:13px}
.tabel-user th{background:#795548;color:white;padding:13px;text-align:left}
.tabel-user td{padding:13px;border-bottom:1px solid #eee2da}
.tabel-user tr:hover{background:#fff8f2}
.role-label{padding:6px 10px;border-radius:15px;background:#eee0d4;color:#795548;font-size:11px;font-weight:bold}
.btn-edit-user,.btn-hapus-user{padding:7px 10px;border:none;border-radius:5px;cursor:pointer;font-size:12px}
.btn-edit-user{background:#e9d3bd;color:#5d4037}
.btn-hapus-user{background:#ffe0e0;color:#a52a2a}
.pesan-error{padding:12px;background:#ffe0e0;color:#a52a2a;border-radius:7px;margin-bottom:15px}
@media(max-width:650px){.user-grid{grid-template-columns:1fr}}
</style>

<?php if ($error !== ''): ?>
<div class="pesan-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="user-form">
    <h3 id="judulUser">Tambah Pengguna Baru</h3>

    <form method="POST" action="kelola_pengguna.php" id="formUser">
        <input type="hidden" name="aksi" id="aksiUser" value="tambah">
        <input type="hidden" name="id_user" id="id_user">

        <div class="user-grid">
            <div>
                <label>Nama Pengguna</label>
                <input type="text" name="nama" id="namaUser" required>
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" id="emailUser" required>
            </div>
            <div>
                <label>Role</label>
                <select name="role" id="roleUser" required>
                    <option value="">Pilih Role</option>
                    <option value="pemilik">Pemilik</option>
                    <option value="kasir">Kasir</option>
                    <option value="pembeli">Pembeli</option>
                </select>
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" id="passwordUser" placeholder="Isi untuk password baru">
            </div>
        </div>

        <button type="submit" class="btn-user" id="btnUser">Simpan Pengguna</button>
        <button type="button" class="btn-user btn-batal-user" id="btnBatalUser" onclick="resetUser()" hidden>Batal Edit</button>
    </form>
</div>

<h3 style="margin-bottom:15px;">Daftar Pengguna</h3>

<div class="tabel-user-wrapper">
<table class="tabel-user">
    <thead>
        <tr><th>No.</th><th>Nama</th><th>Email</th><th>Role</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if (mysqli_num_rows($query) > 0): ?>
        <?php $no = 1; ?>
        <?php while ($user = mysqli_fetch_assoc($query)): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="role-label"><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td>
                <button
                    type="button"
                    class="btn-edit-user"
                    onclick='editUser(<?= json_encode([
                        "id" => (int)$user["id_user"],
                        "nama" => $user["nama"],
                        "email" => $user["email"],
                        "role" => $user["role"]
                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'
                >Edit</button>

                <?php if ((int)$user['id_user'] !== (int)$_SESSION['id_user']): ?>
                <form method="POST" action="kelola_pengguna.php" style="display:inline"
                      onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id_user" value="<?= (int)$user['id_user'] ?>">
                    <button type="submit" class="btn-hapus-user">Hapus</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5" style="text-align:center;padding:25px;">Belum ada pengguna.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
