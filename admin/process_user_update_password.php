<?php
require_once '../middleware/check_auth.php';
// Hanya Superadmin yang boleh mengubah password user lain
authorize(['superadmin']);

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users_manage.php?error=Akses tidak sah.');
    exit;
}

// Ambil data dari POST
$user_id = $_POST['user_id'] ?? null;
$new_password = $_POST['new_password'] ?? null;
$confirm_new_password = $_POST['confirm_new_password'] ?? null;

// === 1. Validasi Server-Side ===
if (empty($user_id) || empty($new_password) || empty($confirm_new_password)) {
    header('Location: users_manage.php?error=Semua field password harus diisi.');
    exit;
}

if ($new_password !== $confirm_new_password) {
    header('Location: users_manage.php?error=Password baru dan konfirmasi password tidak cocok.');
    exit;
}

if (strlen($new_password) < 6) {
    header('Location: users_manage.php?error=Password minimal harus 6 karakter.');
    exit;
}

// === 2. Hash Password ===
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// === 3. Update Database ===
try {
    // 
    // !!! PERHATIAN: GANTI 'NAMA_KOLOM_PASSWORD_ANDA' DI BAWAH INI !!!
    // Ganti dengan nama kolom password Anda yang sebenarnya (misal: password_hash, user_pass, dll)
    //
    $sql = "UPDATE users SET password_hash = :password WHERE id = :user_id";
    //
    // !!! BATAS PERUBAHAN !!!
    //

    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':password' => $hashed_password,
        ':user_id' => $user_id
    ]);

    // Ambil nama pengguna untuk notifikasi
    $stmt_name = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt_name->execute([$user_id]);
    $user_name = $stmt_name->fetchColumn() ?? 'Pengguna';

    header("Location: users_manage.php?success=Password untuk " . htmlspecialchars($user_name) . " berhasil diperbarui!");
    exit;

} catch (PDOException $e) {
    // Tampilkan error yang lebih spesifik jika gagal
    header('Location: users_manage.php?error=Gagal menyimpan password: ' . $e->getMessage());
    exit;
}
?>