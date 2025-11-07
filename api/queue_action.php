<?php
// Lokasi: cubg/api/queue_action.php

require_once '../middleware/check_auth.php';
// Hanya teller atau superadmin yang boleh melakukan aksi
authorize(['superadmin', 'teller']);

// config/db.php sudah di-include via check_auth.php
header('Content-Type: application/json');

$branch_id = $_SESSION['branch_id'];
$teller_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;

try {
    $pdo->beginTransaction();
    $response = ['success' => false, 'message' => 'Aksi tidak diketahui'];

    switch ($action) {
        case 'call_next':
            // 1. Cek apakah teller ini sedang memanggil nomor lain
            $stmt_check = $pdo->prepare("SELECT id FROM queue_numbers WHERE teller_id = ? AND status = 'called' AND created_at_date = ?");
            $stmt_check->execute([$teller_id, $today]);
            if ($stmt_check->fetch()) {
                throw new Exception("Anda masih melayani antrian. Selesaikan antrian saat ini terlebih dahulu.");
            }

            // 2. Ambil antrian 'waiting' berikutnya
            $stmt_next = $pdo->prepare("SELECT id, queue_number FROM queue_numbers WHERE branch_id = ? AND created_at_date = ? AND status = 'waiting' ORDER BY queue_number ASC LIMIT 1");
            $stmt_next->execute([$branch_id, $today]);
            $next_queue = $stmt_next->fetch();

            if ($next_queue) {
                // 3. Update statusnya menjadi 'called'
                $sql_update = "UPDATE queue_numbers SET status = 'called', called_at = NOW(), teller_id = ? WHERE id = ?";
                $pdo->prepare($sql_update)->execute([$teller_id, $next_queue['id']]);
                $response = ['success' => true, 'called_number' => $next_queue['queue_number']];
            } else {
                throw new Exception("Tidak ada antrian lagi.");
            }
            break;

        case 'recall':
        case 'skip':
        case 'finish':
            if (!$id) throw new Exception("ID Antrian tidak ada.");
            
            $new_status = '';
            $sql = "";
            
            if ($action == 'recall') {
                // 'recall' hanya mengupdate timestamp panggilan (agar TV berkedip lagi)
                $sql = "UPDATE queue_numbers SET called_at = NOW() WHERE id = ? AND branch_id = ?";
                $pdo->prepare($sql)->execute([$id, $branch_id]);
                $response = ['success' => true];
            } else {
                if ($action == 'skip') $new_status = 'skipped';
                if ($action == 'finish') $new_status = 'finished';
                
                $sql = "UPDATE queue_numbers SET status = ?, " . ($action == 'finish' ? "served_at = NOW()" : "served_at = NULL") . " WHERE id = ? AND branch_id = ?";
                $pdo->prepare($sql)->execute([$new_status, $id, $branch_id]);
                $response = ['success' => true];
            }
            break;
    }

    $pdo->commit();
    echo json_encode($response);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>