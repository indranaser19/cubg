<?php
// Lokasi: cubg/api/queue_get_list.php

require_once '../middleware/check_auth.php';
// Hanya teller atau superadmin yang boleh melihat daftar
authorize(['superadmin', 'teller']);

// config/db.php sudah di-include via check_auth.php
header('Content-Type: application/json');

$branch_id = $_SESSION['branch_id'];
$teller_id = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    $response = [
        'currently_called' => null, // Antrian yg sedang dipanggil teller INI
        'waiting' => [],            // Semua yg menunggu di cabang ini
        'history' => []             // (finished, skipped) di cabang ini
    ];

    // 1. Cek antrian yang SEDANG DIPANGGIL oleh teller INI
    $stmt_current = $pdo->prepare("SELECT id, queue_number FROM queue_numbers WHERE teller_id = ? AND status = 'called' AND created_at_date = ? ORDER BY called_at DESC LIMIT 1");
    $stmt_current->execute([$teller_id, $today]);
    $response['currently_called'] = $stmt_current->fetch(PDO::FETCH_ASSOC);

    // 2. Ambil semua yang 'waiting'
    $stmt_waiting = $pdo->prepare("SELECT id, queue_number FROM queue_numbers WHERE branch_id = ? AND status = 'waiting' AND created_at_date = ? ORDER BY queue_number ASC");
    $stmt_waiting->execute([$branch_id, $today]);
    $response['waiting'] = $stmt_waiting->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ambil riwayat hari ini (selesai atau diskip)
    $stmt_history = $pdo->prepare("SELECT id, queue_number, status FROM queue_numbers WHERE branch_id = ? AND status IN ('finished', 'skipped') AND created_at_date = ? ORDER BY served_at DESC, called_at DESC");
    $stmt_history->execute([$branch_id, $today]);
    $response['history'] = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>