<?php
// Lokasi: /cubg/tv/process_slide_reorder.php

require_once '../middleware/check_auth.php';
// Pastikan hanya user yang berhak yang bisa re-order
authorize(['superadmin', 'admin_tv']);

require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$ordered_ids = $_POST['order'];

// Pastikan data adalah array
if (!is_array($ordered_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data format.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Siapkan statement UPDATE
    $sql = "UPDATE tv_slides SET slide_order = :order WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    // Loop melalui array ID dan update 'slide_order' berdasarkan indeks array
    foreach ($ordered_ids as $order_index => $slide_id) {
        $stmt->execute([
            ':order' => $order_index, // $order_index akan 0, 1, 2, ...
            ':id' => (int)$slide_id
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Urutan berhasil disimpan.']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>