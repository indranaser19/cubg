<?php
require_once '../middleware/check_auth.php';
authorize(['superadmin', 'credit_officer']);

require_once '../config/db.php';

if (isset($_GET['id'])) {
    $loan_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Cek dulu apakah user (selain superadmin) boleh hapus data dari cabang ini
    if ($_SESSION['role'] != 'superadmin') {
        $stmt_check = $pdo->prepare("SELECT branch_id FROM loan_applications WHERE id = :id");
        $stmt_check->execute([':id' => $loan_id]);
        $loan = $stmt_check->fetch();
        
        if (!$loan || $loan['branch_id'] != $_SESSION['branch_id']) {
            header("location: recycle_bin.php?error=Anda tidak punya akses untuk menghapus data ini.");
            exit;
        }
    }

    $pdo->beginTransaction();
    try {
        // 1. Hapus file fisik (Opsional tapi direkomendasikan)
        $stmt_docs = $pdo->prepare("SELECT file_path FROM loan_documents WHERE loan_application_id = :id");
        $stmt_docs->execute([':id' => $loan_id]);
        $documents = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($documents as $doc) {
            if (file_exists($doc['file_path'])) {
                unlink($doc['file_path']);
            }
        }

        // 2. Hapus data dari tabel anak (Child Tables)
        // Note: Jika Anda set ON DELETE CASCADE di database, 4 query ini tidak perlu.
        // Tapi untuk keamanan, kita jalankan manual.
        $pdo->prepare("DELETE FROM loan_application_logs WHERE loan_application_id = :id")->execute([':id' => $loan_id]);
        $pdo->prepare("DELETE FROM loan_documents WHERE loan_application_id = :id")->execute([':id' => $loan_id]);
        $pdo->prepare("DELETE FROM loan_business_finances WHERE loan_application_id = :id")->execute([':id' => $loan_id]);
        $pdo->prepare("DELETE FROM loan_net_worth WHERE loan_application_id = :id")->execute([':id' => $loan_id]);
        
        // 3. Hapus data dari tabel utama (Parent Table)
        $stmt_delete = $pdo->prepare("DELETE FROM loan_applications WHERE id = :id");
        $stmt_delete->execute([':id' => $loan_id]);

        $pdo->commit();
        header("location: recycle_bin.php?success=Data pinjaman ID $loan_id berhasil dihapus permanen.");

    } catch (Exception $e) {
        $pdo->rollBack();
        header("location: recycle_bin.php?error=Gagal menghapus data permanen: " . $e->getMessage());
    }
} else {
    header("location: recycle_bin.php");
    exit;
}
?>