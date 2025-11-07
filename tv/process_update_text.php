<?php
require_once '../middleware/check_auth.php';
// Hanya admin_tv atau superadmin yang boleh update global
authorize(['superadmin', 'admin_tv']);

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage.php?error=Akses tidak sah.');
    exit;
}

// Ambil teks dari form
// Kita tidak lagi mengambil 'branch_id'
$running_text = $_POST['running_text'] ?? '';

// Karena ini adalah update global, kita tidak perlu validasi branch_id
// Kita juga tidak perlu cek role, karena 'authorize' sudah menangani

try {
    // PERUBAHAN: Hapus 'WHERE' clause untuk update semua cabang
    $sql = "UPDATE branches SET running_text = :running_text";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':running_text' => $running_text]);

    // PERUBAHAN: Redirect dengan pesan sukses global
    header("Location: manage.php?success=Teks berjalan global berhasil diperbarui untuk SEMUA cabang.");
    exit;

} catch (PDOException $e) {
    header('Location: manage.php?error=Gagal update teks: ' . $e->getMessage());
    exit;
}
?>