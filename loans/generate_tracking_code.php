<?php
// Lokasi: /cubg/loans/generate_tracking_code.php

require_once '../config/db.php';
require_once '../middleware/check_auth.php'; 
// Hanya izinkan pengguna tertentu yang berhak membuat kode tracking
authorize(['superadmin', 'credit_officer']); 

header('Content-Type: application/json');

// Pastikan permintaan datang melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid loan ID.']);
    exit;
}

$loan_id = (int)$_POST['id'];

// --- Logika Pembuatan Kode Tracking Unik ---
// Contoh format: CUBG-[BranchID]-[YMD]-[LoanID] 
// Contoh: CUBG-001-251031-123
$prefix = 'CUBG';
$branch_id_padded = str_pad($_SESSION['branch_id'], 3, '0', STR_PAD_LEFT);
$date_part = date('ymd');

$new_tracking_code = $prefix . '-' . $branch_id_padded . '-' . $date_part . '-' . $loan_id;

try {
    // Cek apakah kode sudah ada (opsional, untuk memastikan update terjadi)
    $check_stmt = $pdo->prepare("SELECT tracking_code FROM loan_applications WHERE id = ?");
    $check_stmt->execute([$loan_id]);
    $existing_code = $check_stmt->fetchColumn();

    // Update database
    $update_sql = "UPDATE loan_applications SET tracking_code = :code WHERE id = :id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([
        ':code' => $new_tracking_code,
        ':id' => $loan_id
    ]);

    // Kirim respons sukses
    echo json_encode([
        'success' => true, 
        'tracking_code' => $new_tracking_code, 
        'message' => 'Tracking code generated and updated successfully.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>