<?php
require_once 'middleware/check_auth.php';
// Tidak perlu 'authorize()' spesifik, semua yang login bisa lihat dashboard
// Konten di dalamnya yang akan difilter
require_once 'config/db.php';

// Ambil info user dari session
$user_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
$today = date('Y-m-d');

// Inisialisasi statistik
$stats = [
    'new_loans_today' => 0,
    'processing_loans' => 0,
    'queue_waiting' => 0,
    'active_slides' => 0,
    'fma_new_today' => 0,      // <-- STAT BARU
    'fma_processing' => 0     // <-- STAT BARU
];

try {
    // === 1. STATISTIK KREDIT (Pinjaman) ===
    if (in_array($role, ['superadmin', 'credit_officer', 'branch_user'])) {
        $loan_sql_where = ($role == 'superadmin') ? "is_deleted = 0" : "is_deleted = 0 AND branch_id = :branch_id";
        $loan_params = ($role == 'superadmin') ? [] : [':branch_id' => $branch_id];

        // Pengajuan Baru Hari Ini
        $sql_new = "SELECT COUNT(id) FROM loan_applications WHERE $loan_sql_where AND DATE(application_date) = :today";
        $stmt_new = $pdo->prepare($sql_new);
        $stmt_new->execute(array_merge($loan_params, [':today' => $today]));
        $stats['new_loans_today'] = $stmt_new->fetchColumn();

        // Pengajuan Sedang Diproses (status_id 2, 6, 7, 8)
        $sql_proc = "SELECT COUNT(id) FROM loan_applications WHERE $loan_sql_where AND status_id IN (2, 6, 7, 8)";
        $stmt_proc = $pdo->prepare($sql_proc);
        $stmt_proc->execute($loan_params);
        $stats['processing_loans'] = $stmt_proc->fetchColumn();
    }

    // === 2. STATISTIK TELLER (Antrian) ===
    if (in_array($role, ['superadmin', 'credit_officer', 'branch_user', 'teller'])) {
        $queue_sql_where = ($role == 'superadmin') ? "created_at_date = :today" : "created_at_date = :today AND branch_id = :branch_id";
        $queue_params = ($role == 'superadmin') ? [':today' => $today] : [':today' => $today, ':branch_id' => $branch_id];
        
        $sql_q = "SELECT COUNT(id) FROM queue_numbers WHERE $queue_sql_where AND status = 'waiting'";
        $stmt_q = $pdo->prepare($sql_q);
        $stmt_q->execute($queue_params);
        $stats['queue_waiting'] = $stmt_q->fetchColumn();
    }
    
    // === 3. STATISTIK TV (Organisasi) ===
    if (in_array($role, ['superadmin', 'admin_tv'])) {
        $sql_s = "SELECT COUNT(id) FROM tv_slides WHERE is_active = 1";
        $stmt_s = $pdo->query($sql_s);
        $stats['active_slides'] = $stmt_s->fetchColumn();
    }
    
    // === 4. STATISTIK DIKLAT (FMA) === (BARU)
    if (in_array($role, ['superadmin', 'user_diklat'])) {
        $fma_sql_where = ($role == 'superadmin') ? "1=1" : "branch_id = :branch_id";
        $fma_params = ($role == 'superadmin') ? [] : [':branch_id' => $branch_id];

        // Permohonan Baru Hari Ini
        $sql_fma_new = "SELECT COUNT(id) FROM fma_applications WHERE $fma_sql_where AND status_permohonan = 'Baru' AND DATE(created_at) = :today";
        $stmt_fma_new = $pdo->prepare($sql_fma_new);
        $stmt_fma_new->execute(array_merge($fma_params, [':today' => $today]));
        $stats['fma_new_today'] = $stmt_fma_new->fetchColumn();

        // Permohonan Sedang Diproses (Status 'Diproses' atau 'Perlu Lengkapi')
        $sql_fma_proc = "SELECT COUNT(id) FROM fma_applications WHERE $fma_sql_where AND status_permohonan IN ('Diproses', 'Perlu Lengkapi')";
        $stmt_fma_proc = $pdo->prepare($sql_fma_proc);
        $stmt_fma_proc->execute($fma_params);
        $stats['fma_processing'] = $stmt_fma_proc->fetchColumn();
    }


} catch (Exception $e) {
    // Tangani error jika ada
    $dashboard_error = "Gagal memuat statistik: " . $e->getMessage();
}

// Map role ke nama yang lebih ramah pengguna
$role_map = [
    'superadmin' => 'Super Admin',
    'credit_officer' => 'Credit Officer',
    'branch_user' => 'Staf Cabang',
    'teller' => 'Teller',
    'admin_tv' => 'Admin TV',
    'user_diklat' => 'Staf Diklat' // <-- ROLE BARU
];
$display_role = $role_map[$role] ?? $role;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CU Bererod Gratia</title>
    <link href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            cursor: pointer;
            border: none;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.25) !important;
        }
        .stats-icon {
            opacity: 0.2;
            font-size: 4rem;
        }
        .stats-content h5 {
            font-size: 1rem;
            opacity: 0.8;
        }
        .stats-content .display-4 {
            font-size: 2.5rem;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; // Sertakan navbar ?>

    <div class="container-fluid mt-4">
        
        <header class="mb-5 p-4 bg-white shadow-sm rounded">
            <h1 class="h2 mb-1">👋 Selamat Datang, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <p class="text-muted mb-0">Posisi Anda: <span class="badge bg-secondary"><?php echo $display_role; ?></span> | Ringkasan Data Hari Ini, <?php echo date('d F Y'); ?></p>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($dashboard_error)): ?>
            <div class="alert alert-danger"><?php echo $dashboard_error; ?></div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            
            <?php if (in_array($role, ['superadmin', 'credit_officer', 'branch_user'])): ?>
            <div class="col-xl-3 col-md-6">
                <a href="loans/index.php?status_group=baru" class="text-decoration-none">
                    <div class="card stats-card bg-primary text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="stats-content">
                                    <h5 class="card-title mb-1">PENGAJUAN PINJAMAN BARU</h5>
                                    <div class="display-4 fw-bold"><?php echo $stats['new_loans_today']; ?></div>
                                </div>
                                <i class="bi bi-file-earmark-plus-fill stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="loans/index.php?status_group=diproses" class="text-decoration-none">
                    <div class="card stats-card bg-warning text-dark shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="stats-content">
                                    <h5 class="card-title mb-1">PINJAMAN DIPROSES</h5>
                                    <div class="display-4 fw-bold"><?php echo $stats['processing_loans']; ?></div>
                                </div>
                                <i class="bi bi-hourglass-split stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if (in_array($role, ['superadmin', 'user_diklat'])): ?>
            <div class="col-xl-3 col-md-6">
                <a href="diklat/index.php?status=Baru" class="text-decoration-none">
                    <div class="card stats-card bg-info text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="stats-content">
                                    <h5 class="card-title mb-1">ANGGOTA BARU HARI INI</h5>
                                    <div class="display-4 fw-bold"><?php echo $stats['fma_new_today']; ?></div>
                                </div>
                                <i class="bi bi-person-plus-fill stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="diklat/index.php?status=Diproses" class="text-decoration-none">
                    <div class="card stats-card" style="background-color: #6610f2; color: white;">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="stats-content">
                                    <h5 class="card-title mb-1">SPMA DIPROSES</h5>
                                    <div class="display-4 fw-bold"><?php echo $stats['fma_processing']; ?></div>
                                </div>
                                <i class="bi bi-person-check-fill stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if (in_array($role, ['superadmin', 'credit_officer', 'branch_user', 'teller'])): ?>
            <div class="col-xl-3 col-md-6">
                <a href="<?php echo (in_array($role, ['superadmin', 'teller'])) ? 'teller/manage.php' : '#'; ?>" class="text-decoration-none <?php echo (in_array($role, ['superadmin', 'teller'])) ? '' : 'disabled'; ?>">
                    <div class="card stats-card bg-danger text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="stats-content">
                                    <h5 class="card-title mb-1">ANTRIAN MENUNGGU</h5>
                                    <div class="display-4 fw-bold"><?php echo $stats['queue_waiting']; ?></div>
                                </div>
                                <i class="bi bi-people-fill stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (in_array($role, ['superadmin', 'admin_tv'])): ?>
            <div class="col-xl-3 col-md-6">
                <a href="tv/manage.php" class="text-decoration-none">
                    <div class="card stats-card bg-success text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="stats-content">
                                    <h5 class="card-title mb-1">SLIDE TV AKTIF</h5>
                                    <div class="display-4 fw-bold"><?php echo $stats['active_slides']; ?></div>
                                </div>
                                <i class="bi bi-tv-fill stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-speedometer2 me-2"></i> Aksi Cepat</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        
                        <?php if (in_array($role, ['superadmin', 'user_diklat'])): ?>
                            <a href="diklat/index.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-file-earmark-person-fill fs-4 me-3 text-info"></i>
                                <div>
                                    <strong>Proses Permohonan Anggota</strong>
                                    <div class="text-muted small">Validasi, edit, dan ubah status permohonan anggota baru</div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($role, ['superadmin', 'credit_officer'])): ?>
                            <a href="loans/create.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-pencil-square fs-4 me-3 text-primary"></i>
                                <div>
                                    <strong>Buat Permohonan Pinjaman Baru</strong>
                                    <div class="text-muted small">Input data pemohon (Formulir 3 halaman)</div>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($role, ['superadmin', 'credit_officer', 'branch_user'])): ?>
                            <a href="loans/index.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-binoculars-fill fs-4 me-3 text-warning"></i>
                                <div>
                                    <strong>Tracking Pengajuan Pinjaman</strong>
                                    <div class="text-muted small">Lihat status, update proses, dan detail pemohon</div>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($role, ['teller'])): ?>
                            <a href="teller/manage.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-person-video3 fs-4 me-3 text-danger"></i>
                                <div>
                                    <strong>Buka Panel Teller</strong>
                                    <div class="text-muted small">Panggil, lewati, atau selesaikan nomor antrian</div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($role, ['superadmin', 'admin_tv'])): ?>
                            <a href="tv/manage.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-images fs-4 me-3 text-success"></i>
                                <div>
                                    <strong>Kelola Slide TV Informasi</strong>
                                    <div class="text-muted small">Upload gambar, video, atau URL YouTube per cabang</div>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-folder-open me-2"></i> Laporan & Administrasi</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (in_array($role, ['superadmin', 'credit_officer', 'teller'])): ?>
                            <a href="reports/queue.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-bar-chart-line-fill fs-4 me-3 text-secondary"></i>
                                <div>
                                    <strong>Laporan Antrian Harian</strong>
                                    <div class="text-muted small">Lihat rekapitulasi jumlah antrian per hari</div>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($role, ['superadmin', 'credit_officer'])): ?>
                            <a href="loans/recycle_bin.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-recycle fs-4 me-3 text-warning"></i>
                                <div>
                                    <strong>Recycle Bin (Pinjaman)</strong>
                                    <div class="text-muted small">Pulihkan data pinjaman yang terhapus</div>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($role == 'superadmin'): ?>
                            <a href="admin/users_manage.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-person-gear fs-4 me-3 text-dark"></i>
                                <div>
                                    <strong>Manajemen Pengguna & Akses</strong>
                                    <div class="text-muted small">Kelola akun pengguna, cabang, dan hak akses</div>
                                </div>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>