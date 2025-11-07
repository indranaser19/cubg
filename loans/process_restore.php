<?php
require_once '../middleware/check_auth.php';
authorize(['superadmin', 'credit_officer']);

require_once '../config/db.php';

if (isset($_GET['id'])) {
    $loan_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $pdo->beginTransaction();
    try {
        // 1. Set flag is_deleted = 0
        $sql_update = "UPDATE loan_applications SET is_deleted = 0 WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([':id' => $loan_id]);

        // 2. Catat di log
        $sql_log = "INSERT INTO loan_application_logs (loan_application_id, user_id, action, notes) 
                    VALUES (:loan_id, :user_id, 'restore', 'Data dikembalikan dari recycle bin')";
        $stmt_log = $pdo->prepare($sql_log);
        $stmt_log->execute([':loan_id' => $loan_id, ':user_id' => $user_id]);

        $pdo->commit();
        header("location: recycle_bin.php?success=Data pinjaman ID $loan_id berhasil dikembalikan.");

    } catch (Exception $e) {
        $pdo->rollBack();
        header("location: recycle_bin.php?error=Gagal mengembalikan data: " . $e->getMessage());
    }
} else {
    header("location: recycle_bin.php");
    exit;
}
?>