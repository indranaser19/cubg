<?php
require_once '../middleware/check_auth.php';
authorize(['superadmin', 'credit_officer', 'teller']); // Izinkan role yg relevan

require_once '../config/db.php';

// --- Logika Filter ---
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_branch_id = $_GET['branch_id'] ?? $_SESSION['branch_id'];

$params = [':report_date' => $filter_date];
$sql_where = "WHERE created_at_date = :report_date";

if ($_SESSION['role'] == 'superadmin') {
    if (!empty($filter_branch_id)) {
        $sql_where .= " AND branch_id = :branch_id";
        $params[':branch_id'] = $filter_branch_id;
    }
} else {
    // Jika bukan superadmin, paksa filter cabangnya sendiri
    $sql_where .= " AND branch_id = :branch_id";
    $params[':branch_id'] = $_SESSION['branch_id'];
}

// Query Laporan
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

// Ambil data cabang (untuk filter superadmin)
$branches = [];
if ($_SESSION['role'] == 'superadmin') {
    $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Antrian Harian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0">Laporan Antrian Harian</h4>
            </div>
            <div class="card-body">
                <form method="GET" action="queue.php" class="mb-4 p-3 bg-light rounded border">
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
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                                <?php if ($_SESSION['role'] == 'superadmin' && empty($filter_branch_id) && count($reports) > 1): ?>
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
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>