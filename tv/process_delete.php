<?php
// Lokasi: cubg/tv/process_delete.php

require_once '../middleware/check_auth.php';
// Hanya admin_tv atau superadmin yang boleh menghapus
authorize(['superadmin', 'admin_tv']);

require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header("location: manage.php?error=ID slide tidak ditemukan.");
    exit;
}

$slide_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_branch_id = $_SESSION['branch_id'];

$pdo->beginTransaction();
try {
    // 1. Ambil data slide yang akan dihapus
    $stmt_get = $pdo->prepare("SELECT media_path, type, branch_id FROM tv_slides WHERE id = ?");
    $stmt_get->execute([$slide_id]);
    $slide = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$slide) {
        throw new Exception("Slide tidak ditemukan di database.");
    }

    // 2. Cek Otorisasi (Apakah user ini boleh menghapus slide ini?)
    if ($user_role != 'superadmin' && $slide['branch_id'] != $user_branch_id) {
        // Jika bukan superadmin DAN slide ini bukan milik cabangnya
        throw new Exception("Anda tidak memiliki hak untuk menghapus slide milik cabang lain.");
    }

    // 3. Hapus file fisik dari server (JIKA BUKAN YOUTUBE)
    if ($slide['type'] == 'image' || $slide['type'] == 'video') {
        // Cek jika file ada sebelum mencoba menghapus
        if (file_exists($slide['media_path'])) {
            unlink($slide['media_path']); // Hapus file dari folder /uploads/tv_media/
        }
    }

    // 4. Hapus data dari database
    $stmt_delete = $pdo->prepare("DELETE FROM tv_slides WHERE id = ?");
    $stmt_delete->execute([$slide_id]);

    // 5. Jika semua sukses, commit
    $pdo->commit();
    header("location: manage.php?success=Slide ID $slide_id berhasil dihapus.");

} catch (Exception $e) {
    // 6. Jika ada error, batalkan semua
    $pdo->rollBack();
    header("location: manage.php?error=" . urlencode($e->getMessage()));
}
?>