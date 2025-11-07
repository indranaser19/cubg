<?php
require_once '../middleware/check_auth.php';
// Hanya superadmin atau credit_officer yang bisa akses
authorize(['superadmin', 'credit_officer']);

require_once '../config/db.php';

// Ambil data yang di soft-delete
$where_clauses = [getBranchQueryFilter(), "la.is_deleted = 1"];
$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$sql = "SELECT la.id, la.application_date, la.applicant_name, la.status, b.name AS branch_name
        FROM loan_applications la
        JOIN branches b ON la.branch_id = b.id
        $where_sql
        ORDER BY la.updated_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$deleted_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Bin - Pinjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-recycle"></i> Recycle Bin (Data Pinjaman Terhapus)</h4>
            </div>
            <div class="card-body">
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tgl Pengajuan</th>
                                <th>Cabang</th>
                                <th>Nama Pemohon</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deleted_loans)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Recycle bin kosong.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deleted_loans as $loan): ?>
                                    <tr>
                                        <td><?php echo $loan['id']; ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($loan['application_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($loan['branch_name']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['applicant_name']); ?></td>
                                        <td>
                                            <a href="process_restore.php?id=<?php echo $loan['id']; ?>" 
                                               class="btn btn-success btn-sm" 
                                               title="Restore Data"
                                               onclick="return confirm('Anda yakin ingin mengembalikan data ini?');">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </a>
                                            
                                            <a href="process_delete_permanent.php?id=<?php echo $loan['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               title="Hapus Permanen"
                                               onclick="return confirm('PERINGATAN: Data ini akan dihapus permanen dan tidak bisa dikembalikan. Lanjutkan?');">
                                                <i class="bi bi-trash-fill"></i> Hapus Permanen
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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