<?php
// Lokasi: cubg/api/tv_events.php

// Set header khusus untuk Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../config/db.php'; // Hubungkan ke DB

if (!isset($_GET['branch'])) {
    die("branch not set");
}
$branch_id = (int)$_GET['branch'];
$today = date('Y-m-d'); // <-- Diambil dari kode LAMA Anda

// Variabel untuk menyimpan status terakhir
$last_sent_number = null; // <-- Diambil dari kode LAMA Anda
$last_running_text = null; // <-- Logika BARU
$iterations = 0; // <-- Logika BARU

// Ini adalah loop tak terbatas
while (true) {
    
    // Objek data yang akan dikirim
    $data_to_send = [];

    // --- 1. Cek PANGGILAN ANTRIAN (Logika dari kode LAMA Anda) ---
    $sql_queue = "SELECT q.queue_number, u.full_name as teller_name
                  FROM queue_numbers q
                  JOIN users u ON q.teller_id = u.id
                  WHERE q.branch_id = :branch_id 
                    AND q.created_at_date = :today 
                    AND q.status = 'called'
                  ORDER BY q.called_at DESC
                  LIMIT 1";
                  
    $stmt_queue = $pdo->prepare($sql_queue);
    $stmt_queue->execute([':branch_id' => $branch_id, ':today' => $today]);
    $call = $stmt_queue->fetch(PDO::FETCH_ASSOC);

    // Cek perubahan nomor antrian
    $current_number = $call ? $call['queue_number'] : null;

    if ($current_number != $last_sent_number) {
        $teller_name = $call ? explode(' ', $call['teller_name'])[0] : ''; // Ambil nama depan
        
        // Format data sesuai dengan display.php (key 'queue')
        $data_to_send['queue'] = [
            'number' => $current_number,
            'teller' => $teller_name
        ];
        $last_sent_number = $current_number; // Update status terakhir
    }


    // --- 2. Cek PERUBAHAN RUNNING TEXT (Logika BARU) ---
    // Kita cek database setiap 5 iterasi (sekitar 10 detik) agar tidak terlalu berat
    if ($iterations % 5 == 0 || $last_running_text === null) {
        $stmt_text = $pdo->prepare("SELECT running_text FROM branches WHERE id = ?");
        $stmt_text->execute([$branch_id]);
        $current_running_text = $stmt_text->fetchColumn();

        if ($current_running_text !== $last_running_text) {
            // Ada teks baru (atau teks pertama kali dimuat)
            $data_to_send['running_text'] = $current_running_text;
            $last_running_text = $current_running_text;
        }
    }

    // --- 3. Kirim data HANYA JIKA ADA PERUBAHAN ---
    if (!empty($data_to_send)) {
        echo "data: " . json_encode($data_to_send) . "\n\n";
        ob_flush();
        flush();
    }

    // --- 4. Jeda dan iterasi ---
    sleep(2);
    $iterations++;
    
    // Cek jika koneksi client ditutup
    if (connection_aborted()) {
        break;
    }
}
?>