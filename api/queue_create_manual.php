<?php
// Lokasi: cubg/api/queue_create_manual.php

require_once '../middleware/check_auth.php';
// Hanya Teller atau Superadmin yang boleh membuat antrian
authorize(['superadmin', 'teller']); 

require_once '../config/db.php';
header('Content-Type: application/json');

// Ambil cabang dari Teller yang sedang login
$branch_id = $_SESSION['branch_id']; 
$today = date('Y-m-d');

try {
    // 1. Cari nomor antrian terakhir HARI INI di cabang tsb
    $stmt_max = $pdo->prepare("SELECT MAX(queue_number) as max_num FROM queue_numbers WHERE branch_id = ? AND created_at_date = ?");
    $stmt_max->execute([$branch_id, $today]);
    $result = $stmt_max->fetch();
    
    // Tentukan nomor baru (jika belum ada, mulai dari 1)
    $new_number = $result['max_num'] ? $result['max_num'] + 1 : 1;

    // 2. Masukkan nomor baru ke database
    $sql_insert = "INSERT INTO queue_numbers (branch_id, queue_number, status, created_at_date, created_at_time) 
                   VALUES (?, ?, 'waiting', ?, CURTIME())";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([$branch_id, $new_number, $today]);
    
    // 3. Kirim respon sukses
    echo json_encode(['success' => true, 'new_number' => $new_number]);

} catch (Exception $e) {
    // Kirim respon error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>