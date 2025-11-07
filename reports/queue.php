<?php
require_once '../middleware/check_auth.php';
// Izinkan role yg relevan untuk melihat laporan
authorize(['superadmin', 'credit_officer', 'teller']); 

// config/db.php sudah di-include via check_auth.php, 
// sehingga $pdo dan BASE_URL sudah tersedia.

// --- Fungsi Helper untuk Pagination ---
function build_pagination_query_string($page, $date, $branch) {
    $params = [
        'page' => $page,
        'date' => $date
    ];
    if (!empty($branch)) $params['branch_id'] = $branch;
    
    return 'queue.php?' . http_build_query($params);
}
// ------------------------------------

// --- START PAGINATION ---
$limit = 25; // Data (cabang) per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
// --- END PAGINATION ---

// --- Logika Filter ---
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

// --- Query Hitung Total Data (untuk Pagination) ---
// Kita menghitung jumlah cabang yang memiliki data pada hari itu
$sql_count = "SELECT COUNT(DISTINCT b.id)
              FROM queue_numbers q
              JOIN branches b ON q.branch_id = b.id
              $sql_where";
              
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_items = $stmt_count->fetchColumn();
$total_pages = ceil($total_items / $limit);
// --- Akhir Hitung Total ---


// Query Laporan (DENGAN LIMIT DAN OFFSET)
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
        ORDER BY b.name
        LIMIT $limit OFFSET $offset"; // <-- PAGINATION DITERAPKAN

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data cabang (hanya untuk filter superadmin)
$branches = [];
if ($_SESSION['role'] == 'superadmin') {
    $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

// Buat URL untuk Ekspor CSV
$export_params = ['date' => $filter_date];
if (!empty($filter_branch_id)) {
    $export_params['branch_id'] = $filter_branch_id;
}
$export_url = 'export_queue_csv.php?' . http_build_query($export_params);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Antrian Harian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-bar-chart-line-fill"></i> Laporan Antrian Harian</h4>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo BASE_URL; ?>/reports/queue.php" class="mb-4 p-3 bg-light rounded border">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="date" class="form-label">Tanggal</label>
                            <input type="date" name="date" id="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
                        </div>
                        
                        <?php if ($_SESSION['role'] == 'superadmin'): ?>
                        <div class="col-md-4">
                            <label for="branch_id" class="form-label">Cabang</label>
                            <select name="branch_id" id="branch_id" class="form-select">
                                <option value="">Semua Cabang</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" <?php echo ($filter_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Cabang</th>
                                <th>Total Antrian</th>
                                <th>Terlayani</th>
                                <th>Tidak Hadir (Skipped)</th>
                                <th>Masih Menunggu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $totals = ['total' => 0, 'served' => 0, 'skipped' => 0, 'waiting' => 0]; ?>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data untuk tanggal dan cabang terpilih.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['branch_name']); ?></td>
                                        <td><?php echo $report['total_antrian']; $totals['total'] += $report['total_antrian']; ?></td>
                                        <td><?php echo $report['terlayani']; $totals['served'] += $report['terlayani']; ?></td>
                                        <td><?php echo $report['tidak_hadir']; $totals['skipped'] += $report['tidak_hadir']; ?></td>
                                        <td><?php echo $report['menunggu']; $totals['waiting'] += $report['menunggu']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php 
                                // Tampilkan baris TOTAL jika Superadmin, melihat "Semua Cabang", 
                                // ada lebih dari 1 cabang, DAN berada di halaman terakhir (atau total < limit)
                                if ($_SESSION['role'] == 'superadmin' && empty($filter_branch_id) && $total_items > 1 && $page == $total_pages): ?>
                                <tr class="table-secondary fw-bold">
                                    <td>TOTAL SEMUA CABANG</td>
                                    <td><?php echo $totals['total']; ?></td>
                                    <td><?php echo $totals['served']; ?></td>
                                    <td><?php echo $totals['skipped']; ?></td>
                                    <td><?php echo $totals['waiting']; ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_query_string($page - 1, $filter_date, $filter_branch_id); ?>">Previous</a>
                            </li>

                            <?php 
                            $window = 2;
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)):
                            ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_pagination_query_string($i, $filter_date, $filter_branch_id); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php 
                                elseif ($i == $page - $window - 1 || $i == $page + $window + 1): 
                                ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_query_string($page + 1, $filter_date, $filter_branch_id); ?>">Next</a>
                            </li>
                            
                        </ul>
                    </nav>
                <?php endif; ?>
                <div class="mt-3 text-end">
                    <a href="<?php echo htmlspecialchars($export_url); ?>" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel-fill"></i> Export ke CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>