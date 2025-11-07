<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['branch_id'])) {
        die("Cabang tidak dipilih.");
    }

    $branch_id = (int)$_POST['branch_id'];
    $today = date('Y-m-d');

    try {
        // 1. Cari nama cabang
        $stmt_branch = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
        $stmt_branch->execute([$branch_id]);
        $branch = $stmt_branch->fetch();
        if (!$branch) die("Cabang tidak valid.");
        $branch_name = $branch['name'];

        // 2. Cari nomor antrian terakhir HARI INI di cabang tsb
        $stmt_max = $pdo->prepare("SELECT MAX(queue_number) as max_num FROM queue_numbers WHERE branch_id = ? AND created_at_date = ?");
        $stmt_max->execute([$branch_id, $today]);
        $result = $stmt_max->fetch();
        
        $new_number = $result['max_num'] ? $result['max_num'] + 1 : 1;

        // 3. Masukkan nomor baru
        $sql_insert = "INSERT INTO queue_numbers (branch_id, queue_number, status, created_at_date, created_at_time) 
                       VALUES (?, ?, 'waiting', ?, CURTIME())";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$branch_id, $new_number, $today]);
        
        // 4. Redirect kembali ke halaman kiosk dengan pesan sukses
        header("location: get_number.php?success=1&number=$new_number&branch=" . urlencode($branch_name));
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>