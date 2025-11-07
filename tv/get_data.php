<?php
// Lokasi: cubg/tv/get_data.php
require_once '../config/db.php';

// Validasi input
if (!isset($_GET['branch'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Branch ID not specified']);
    exit;
}
$branch_id = (int)$_GET['branch'];

$response_data = [
    'queue' => null,
    'running_text' => ''
];

try {
    // --- Kueri 1: Ambil Antrian Terakhir yang Dipanggil (SUDAH DIPERBAIKI) ---
    // Menggunakan tabel 'queue_numbers' dan kolom 'called_at'
    $stmt_queue = $pdo->prepare(
        "SELECT queue_number, teller_id 
         FROM queue_numbers 
         WHERE branch_id = :branch_id 
         AND called_at IS NOT NULL
         ORDER BY called_at DESC 
         LIMIT 1"
    );
    $stmt_queue->execute([':branch_id' => $branch_id]);
    $last_call = $stmt_queue->fetch(PDO::FETCH_ASSOC);

    if ($last_call) {
        // 'number' dan 'teller' adalah nama yang diharapkan oleh JavaScript di display.php
        $response_data['queue'] = [
            'number' => $last_call['queue_number'],
            'teller' => $last_call['teller_id'] 
        ];
    }

    // --- Kueri 2: Ambil Running Text Terbaru ---
    $stmt_branch = $pdo->prepare("SELECT running_text FROM branches WHERE id = :branch_id");
    $stmt_branch->execute([':branch_id' => $branch_id]);
    $branch_data = $stmt_branch->fetch(PDO::FETCH_ASSOC);
    
    if ($branch_data) {
        $response_data['running_text'] = $branch_data['running_text'];
    }

} catch (PDOException $e) {
    // Tangani error database
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    // Tampilkan pesan error SQL agar mudah di-debug
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Kembalikan data sebagai JSON
header('Content-Type: application/json');
echo json_encode($response_data);
exit;
?>