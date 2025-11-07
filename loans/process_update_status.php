<?php
require_once '../middleware/check_auth.php';
// Hanya user yang berhak yang boleh mengubah status (disesuaikan dengan kebijakan Anda)
authorize(['superadmin', 'branch_user', 'credit_officer']); 

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $loan_id = (int)$_POST['loan_id'];
    $new_status_id = (int)$_POST['new_status_id'];
    $notes = $_POST['notes'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    try {
        $pdo->beginTransaction();

        // 1. UPDATE status di tabel utama
        $sql_update = "UPDATE loan_applications SET status_id = :status_id WHERE id = :loan_id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            ':status_id' => $new_status_id,
            ':loan_id' => $loan_id
        ]);

        // 2. INSERT catatan riwayat ke tabel history
        $sql_history = "INSERT INTO loan_status_history (
            loan_app_id, status_id, notes, changed_by_user_id
        ) VALUES (
            :loan_id, :status_id, :notes, :user_id
        )";
        $stmt_history = $pdo->prepare($sql_history);
        $stmt_history->execute([
            ':loan_id' => $loan_id,
            ':status_id' => $new_status_id,
            ':notes' => $notes,
            ':user_id' => $user_id
        ]);

        $pdo->commit();

        header("location: view.php?id=$loan_id&success=Status permohonan berhasil diperbarui.");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("location: view.php?id=$loan_id&error=" . urlencode("Gagal menyimpan status: " . $e->getMessage()));
        exit;
    }

} else {
    header("location: index.php");
    exit;
}
?>