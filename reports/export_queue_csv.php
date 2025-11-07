<?php
// Lokasi: cubg/reports/export_queue_csv.php

require_once '../middleware/check_auth.php';
// Pastikan role yang sama bisa mengakses
authorize(['superadmin', 'credit_officer', 'teller']); 

// config/db.php sudah di-include via check_auth.php

// --- Logika Filter (Sama seperti queue.php) ---
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_branch_id = $_GET['branch_id'] ?? '';

// Jika user bukan superadmin, paksa filter berdasarkan cabang mereka
if ($_SESSION['role'] != 'superadmin') {
    $filter_branch_id = $_SESSION['branch_id'];
}

$params = [':report_date' => $filter_date];
$sql_where = "WHERE created_at_date = :report_date";

if (!empty($filter_branch_id)) {
    $sql_where .= " AND branch_id = :branch_id";
    $params[':branch_id'] = $filter_branch_id;
}

// --- Query Laporan (Sama seperti queue.php, TAPI TANPA LIMIT/OFFSET) ---
$sql = "SELECT 
            b.name as branch_name,
            COUNT(q.id) as total_antrian,
            SUM(CASE WHEN q.status = 'finished' THEN 1 ELSE 0 END) as terlayani,
            SUM(CASE WHEN q.status = 'skipped' THEN 1 ELSE 0 END) as tidak_hadir,
            SUM(CASE WHEN q.status = 'waiting' THEN 1 ELSE 0 END) as menunggu
        FROM queue_numbers q
        JOIN branches b ON q.branch_id = b.id
        $sql_where
        GROUP BY b.name
        ORDER BY b.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Logika Ekspor CSV ---
$filename = "laporan_antrian_CUBG_" . $filter_date . ".csv";

// Set header agar browser men-download file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream PHP
$output = fopen('php://output', 'w');

// Tulis header kolom CSV
fputcsv($output, [
    'Cabang', 
    'Total Antrian', 
    'Terlayani', 
    'Tidak Hadir (Skipped)', 
    'Masih Menunggu'
]);

$totals = ['total' => 0, 'served' => 0, 'skipped' => 0, 'waiting' => 0];

// Tulis data baris
foreach ($reports as $report) {
    fputcsv($output, [
        $report['branch_name'],
        $report['total_antrian'],
        $report['terlayani'],
        $report['tidak_hadir'],
        $report['menunggu']
    ]);
    
    // Hitung total untuk baris terakhir
    $totals['total'] += $report['total_antrian'];
    $totals['served'] += $report['terlayani'];
    $totals['skipped'] += $report['tidak_hadir'];
    $totals['waiting'] += $report['menunggu'];
}

// Tulis baris TOTAL (jika Superadmin & Semua Cabang & lebih dari 1 baris data)
if ($_SESSION['role'] == 'superadmin' && empty($filter_branch_id) && count($reports) > 1) {
    fputcsv($output, [
        'TOTAL SEMUA CABANG',
        $totals['total'],
        $totals['served'],
        $totals['skipped'],
        $totals['waiting']
    ]);
}

// Tutup output stream
fclose($output);
exit;
?>