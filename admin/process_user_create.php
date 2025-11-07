<?php
require_once '../middleware/check_auth.php';
// HANYA Superadmin yang boleh memproses ini
authorize(['superadmin']);

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil data dari form
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    // $email = trim($_POST['email']); // DIHAPUS
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $branch_id = $_POST['branch_id'];

    // 2. Validasi
    if (empty($full_name) || empty($username) || empty($password) || empty($role) || empty($branch_id)) {
        header("location: users_manage.php?error=Semua field wajib diisi.");
        exit;
    }

    if ($password !== $confirm_password) {
        header("location: users_manage.php?error=Password dan Konfirmasi Password tidak cocok.");
        exit;
    }

    try {
        // 3. Cek apakah username sudah ada (pengecekan email dihapus)
        $sql_check = "SELECT id FROM users WHERE username = :username";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':username' => $username]);
        
        if ($stmt_check->rowCount() > 0) {
            header("location: users_manage.php?error=Username sudah terdaftar.");
            exit;
        }

        // 4. Hash Password (KRUSIAL UNTUK KEAMANAN)
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // 5. Masukkan ke database (query INSERT diperbarui)
        $sql_insert = "INSERT INTO users (branch_id, username, password_hash, full_name, role, is_active)
                       VALUES (:branch_id, :username, :password_hash, :full_name, :role, 1)";
        
        $stmt_insert = $pdo->prepare($sql_insert);
        
        // Parameter execute diperbarui (tanpa email)
        $stmt_insert->execute([
            ':branch_id' => $branch_id,
            ':username' => $username,
            ':password_hash' => $password_hash,
            ':full_name' => $full_name,
            ':role' => $role
        ]);

        // 6. Redirect sukses
        header("location: users_manage.php?success=Pengguna baru '$full_name' berhasil dibuat.");
        exit;

    } catch (Exception $e) {
        header("location: users_manage.php?error=Gagal membuat pengguna: " . $e->getMessage());
        exit;
    }

} else {
    // Jika bukan method POST, tendang
    header("location: users_manage.php");
    exit;
}
?>