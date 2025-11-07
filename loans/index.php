<?php
// Lokasi: cubg/loans/index.php

require_once '../middleware/check_auth.php';
authorize(['superadmin', 'branch_user', 'credit_officer']);

require_once '../config/db.php';

// --- Fungsi Helper untuk Pagination ---
function build_pagination_query_string($page, $search, $branch, $status_group) {
    $params = [
        'page' => $page
    ];
    if (!empty($search)) $params['search'] = $search;
    if (!empty($branch)) $params['branch_id'] = $branch;
    if (!empty($status_group)) $params['status_group'] = $status_group;
    
    return 'index.php?' . http_build_query($params);
}
// ------------------------------------

// --- START PAGINATION ---
$limit = 25; // Data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
// --- END PAGINATION ---

// --- Logika Filter dan Pencarian ---
$search_term = $_GET['search'] ?? '';
$filter_branch_id = $_GET['branch_id'] ?? '';
$filter_status_group = $_GET['status_group'] ?? ''; 

$params = [];

// === PERUBAHAN DI SINI ===
// Filter data yang 'is_deleted = 0' (tidak ada di recycle bin)
$where_clauses = ["la.is_deleted = 0"];
// === AKHIR PERUBAHAN ===


// 1. Filter berdasarkan Role
if ($_SESSION['role'] != 'superadmin') {
    $where_clauses[] = "la.branch_id = :session_branch_id";
    $params[':session_branch_id'] = $_SESSION['branch_id'];
}

// 2. Filter Cabang
if (!empty($filter_branch_id) && ($_SESSION['role'] == 'superadmin' || $_SESSION['role'] == 'branch_user')) {
    $where_clauses[] = "la.branch_id = :filter_branch_id";
    $params[':filter_branch_id'] = $filter_branch_id;
}

// 3. Filter Group Status
if (!empty($filter_status_group)) {
    switch ($filter_status_group) {
        case 'baru':
            $where_clauses[] = "la.status_id = 1";
            break;
        case 'diproses':
            $where_clauses[] = "la.status_id IN (2, 6, 7, 8)";
            break;
        case 'disetujui':
            $where_clauses[] = "la.status_id = 3";
            break;
        case 'ditolak':
            $where_clauses[] = "la.status_id = 4";
            break;
        case 'lunas':
            $where_clauses[] = "la.status_id = 5";
            break;
    }
}

// 4. Filter Pencarian
if (!empty($search_term)) {
    $where_clauses[] = "(la.applicant_name LIKE :search OR la.applicant_ba_number LIKE :search OR la.tracking_code LIKE :search_code)";
    $params[':search'] = '%' . $search_term . '%';
    $params[':search_code'] = '%' . $search_term . '%';
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = "WHERE " . implode(" AND ", $where_clauses);
}

// --- Query Hitung Total Data (untuk Pagination) ---
$sql_count = "SELECT COUNT(la.id) 
              FROM loan_applications la
              JOIN branches b ON la.branch_id = b.id
              JOIN loan_statuses ls ON la.status_id = ls.id
              $sql_where";
              
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_items = $stmt_count->fetchColumn();
$total_pages = ceil($total_items / $limit);
// --- Akhir Hitung Total ---


// --- Query Utama (DIMODIFIKASI DENGAN LIMIT & OFFSET) ---
$sql = "SELECT 
            la.id, la.applicant_name, la.applicant_ba_number, la.application_date,
            la.loan_amount_requested, la.loan_type, la.created_by_user_id,
            la.tracking_code,
            b.name as branch_name,
            ls.status_name, ls.badge_class
        FROM 
            loan_applications la
        JOIN 
            branches b ON la.branch_id = b.id
        JOIN 
            loan_statuses ls ON la.status_id = ls.id
        $sql_where
        ORDER BY 
            la.application_date DESC, la.id DESC
        LIMIT $limit OFFSET $offset"; // <-- PAGINATION DITERAPKAN DI SINI

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Ambil data untuk Dropdown Filter (Tetap) ---
$branches = [];
if ($_SESSION['role'] == 'superadmin' || $_SESSION['role'] == 'branch_user') {
    $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}
$status_groups = [
    'baru' => 'Baru Dibuat',
    'diproses' => 'Diproses (Semua Tahap)',
    'disetujui' => 'Disetujui',
    'ditolak' => 'Ditolak',
    'lunas' => 'Lunas'
];
$show_action_column = in_array($_SESSION['role'], ['superadmin', 'credit_officer']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Permohonan Pinjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <style>
        .card-header-main {
            background-color: #007bff; color: white;
        }
        .table-custom-row td { vertical-align: middle; font-size: 0.95rem; }
        .table-custom-row .nama-anggota { font-weight: 600; }
        .tracking-cell { 
            white-space: normal; 
            min-width: 100px; /* Beri ruang agar tombol 'Copy' tidak terlipat */
        } 
        <?php if (!$show_action_column): ?>
        .hide-for-branch-user { display: none !important; }
        <?php endif; ?>
        @media (max-width: 767.98px) {
            .table-responsive .table { border-collapse: separate; border-spacing: 0 10px; }
            .table-custom-row { display: block; margin-bottom: 10px; border: 1px solid #dee2e6; border-radius: 0.5rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
            .table-custom-row td { display: block; width: 100%; padding: 0.5rem 1rem; text-align: left !important; border: none; }
            .table-custom-row td:before { content: attr(data-label); font-weight: bold; margin-right: 0.5rem; display: inline-block; min-width: 100px; color: #6c757d; }
            .table-custom-row td:last-child { border-bottom: none; }
            .table thead { display: none; }
            .nama-anggota { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; background-color: #f8f9fa; padding-top: 1rem !important; }
            .d-flex.flex-wrap.gap-1 { justify-content: space-around; }
            <?php if (!$show_action_column): ?>
            .table-custom-row td[data-label="Aksi"] { display: none !important; }
            <?php endif; ?>
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        
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

        <div class="card shadow-lg border-0">
            <div class="card-header card-header-main">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-journal-check me-2"></i> Daftar & Tracking Permohonan</h4>
                    <?php if (in_array($_SESSION['role'], ['superadmin', 'credit_officer'])): ?>
                        <a href="create.php" class="btn btn-light btn-sm text-primary fw-bold">
                            <i class="bi bi-plus-circle"></i> Buat Baru
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                
                <form method="GET" action="index.php" class="mb-4 p-3 bg-light rounded border">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-12">
                            <label for="search" class="form-label small text-muted">Cari Nama / No. BA / Kode Tracking</label>
                            <div class="input-group">
                                <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Cari data permohonan...">
                                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                        
                        <?php if ($_SESSION['role'] == 'superadmin' || $_SESSION['role'] == 'branch_user'): ?>
                            <div class="col-md-3 col-6">
                                <label for="branch_id" class="form-label small text-muted">Filter Cabang</label>
                                <select name="branch_id" id="branch_id" class="form-select form-select-sm">
                                    <option value="">Semua Cabang</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>" <?php echo ($filter_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-3 col-6">
                            <label for="status_group" class="form-label small text-muted">Filter Status</label>
                            <select name="status_group" id="status_group" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                <?php foreach ($status_groups as $key => $name): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($filter_status_group == $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-12">
                            <button type="submit" class="btn btn-primary w-100 mt-2 mt-md-0">
                                <i class="bi bi-funnel"></i> Apply
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Nama Anggota</th>
                                <th>No. BA</th> 
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Jumlah</th>
                                <th>Cabang</th>
                                <th>Status</th>
                                <th>Link Tracking</th>
                                <th class="hide-for-branch-user">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($loans)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <i class="bi bi-info-circle me-1"></i> Tidak ada data permohonan yang cocok dengan filter.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($loans as $loan): ?>
                                <tr class="table-custom-row">
                                    <td class="nama-anggota" data-label="Nama Anggota">
                                        <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($loan['applicant_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td data-label="No. BA">
                                        <?php echo htmlspecialchars($loan['applicant_ba_number'] ?? '-'); ?>
                                    </td>
                                    <td data-label="Tanggal">
                                        <?php echo (new DateTime($loan['application_date']))->format('d M Y'); ?>
                                    </td>
                                    <td data-label="Produk">
                                        <?php echo htmlspecialchars($loan['loan_type'] ?? '-'); ?>
                                    </td>
                                    <td data-label="Jumlah">
                                        <span class="fw-bold">Rp <?php echo number_format($loan['loan_amount_requested'], 0, ',', '.'); ?></span>
                                    </td>
                                    <td data-label="Cabang">
                                        <?php echo htmlspecialchars($loan['branch_name'] ?? '-'); ?>
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge <?php echo htmlspecialchars($loan['badge_class'] ?? 'bg-secondary'); ?>">
                                            <?php echo htmlspecialchars($loan['status_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    
                                    <td class="tracking-cell" id="tracking-<?php echo $loan['id']; ?>" data-label="Link Tracking">
                                        <?php if (!empty($loan['tracking_code'])): ?>
                                            <button class="btn btn-sm btn-light copy-btn" 
                                                    data-loan-id="<?php echo $loan['id']; ?>" 
                                                    data-code-link="tracking.php?code=<?php echo htmlspecialchars($loan['tracking_code']); ?>">
                                                <i class="bi bi-link-45deg"></i> Copy Link
                                            </button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-flex flex-wrap gap-1 hide-for-branch-user" data-label="Aksi">
                                        <?php if (isset($loan['created_by_user_id']) && ($_SESSION['user_id'] == $loan['created_by_user_id'] || $_SESSION['role'] == 'superadmin')): ?>
                                            <a href="edit.php?id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-warning" title="Edit Data">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a> 
                                        <?php endif; ?>
                                        <a href="view.php?id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                            <i class="bi bi-search"></i> Detail
                                        </a>
                                        <a href="export_pdf.php?id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-danger" title="Download PDF Formulir" target="_blank">
                                            <i class="bi bi-file-earmark-pdf"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_query_string($page - 1, $search_term, $filter_branch_id, $filter_status_group); ?>">Previous</a>
                            </li>
                            <?php 
                            $window = 2;
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)):
                            ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_pagination_query_string($i, $search_term, $filter_branch_id, $filter_status_group); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php 
                                elseif ($i == $page - $window - 1 || $i == $page + $window + 1): 
                                ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_query_string($page + 1, $search_term, $filter_branch_id, $filter_status_group); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        
        // Event Delegation untuk Copy Link (PENTING!)
        $(document).on('click', '.copy-btn', function() {
            const link = $(this).attr('data-code-link');
            const tempInput = document.createElement('input');
            const baseURL = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            tempInput.value = baseURL + link;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            $(this).html('<i class="bi bi-check2"></i> Copied!');
            setTimeout(() => {
                $(this).html('<i class="bi bi-link-45deg"></i> Copy Link');
            }, 1500);
        });
    });
    </script>
</body>
</html>