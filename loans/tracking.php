<?php
// FILE INI DAPAT DIAKSES PUBLIK (Tidak memerlukan check_auth.php)
require_once '../config/db.php';

$tracking_code = $_GET['code'] ?? '';
$loan = null;
$history = [];

function e($value) {
    return htmlspecialchars($value ?? '');
}

if (!empty($tracking_code)) {
    // --- Logika Pembersihan Kode Tracking (Dipertahankan dari perbaikan sebelumnya) ---
    $cleaned_code = urldecode($tracking_code);
    
    if (strpos($cleaned_code, 'code=') !== false) {
        $parts = explode('code=', $cleaned_code);
        $final_code = end($parts);
    } else {
        $final_code = $cleaned_code;
    }
    
    $tracking_code = $final_code;
    // --------------------------------------------------------------------------

    // 1. Ambil Data Pinjaman berdasarkan Kode Tracking
    $sql_loan = "SELECT 
                    la.id, la.applicant_name, la.applicant_ba_number, la.application_date, la.tracking_code,
                    b.name as branch_name,
                    ls.status_name, ls.badge_class, ls.id as status_id
                FROM 
                    loan_applications la
                JOIN 
                    branches b ON la.branch_id = b.id
                JOIN 
                    loan_statuses ls ON la.status_id = ls.id
                WHERE 
                    la.tracking_code = :code";
    
    $stmt_loan = $pdo->prepare($sql_loan);
    $stmt_loan->execute([':code' => $tracking_code]);
    $loan = $stmt_loan->fetch(PDO::FETCH_ASSOC);

    if ($loan) {
        // Tentukan warna utama status untuk kartu ringkasan
        $status_color = 'primary';
        $status_icon = 'bi-clock-history';
        if (str_contains($loan['badge_class'], 'success')) {
            $status_color = 'success';
            $status_icon = 'bi-check-circle-fill';
        } elseif (str_contains($loan['badge_class'], 'danger')) {
            $status_color = 'danger';
            $status_icon = 'bi-x-octagon-fill';
        } elseif (str_contains($loan['badge_class'], 'warning')) {
            $status_color = 'warning';
            $status_icon = 'bi-hourglass-split';
        }
        $loan['color'] = $status_color;
        $loan['icon'] = $status_icon;
        
        // 2. Ambil Riwayat Status (Untuk tampilan timeline)
        $sql_history = "SELECT 
                            lsh.*, 
                            ls.status_name, ls.id as status_id
                        FROM 
                            loan_status_history lsh
                        JOIN 
                            loan_statuses ls ON lsh.status_id = ls.id
                        WHERE 
                            lsh.loan_app_id = :loan_id
                        ORDER BY 
                            lsh.changed_at ASC"; // ASC untuk urutan timeline yang benar (dari awal ke akhir)

        $stmt_history = $pdo->prepare($sql_history);
        $stmt_history->execute([':loan_id' => $loan['id']]);
        $history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

        // Menentukan status terakhir yang lebih deskriptif (jika diperlukan)
        // Logika ini diasumsikan tidak terlalu penting untuk tampilan final yang sudah menggunakan status_name
        $loan['detailed_status'] = $loan['status_name'];
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Status Pengajuan Pinjaman - CU Bererod Gratia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .timeline {
            position: relative;
            padding-left: 20px;
        }
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 3px;
            background-color: #dee2e6; /* Warna abu-abu garis timeline */
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
            padding-left: 20px;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-badge {
            position: absolute;
            top: 6px;
            left: -8px; /* Sesuaikan agar tepat di tengah garis */
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid white; /* Cincin putih agar terlihat menonjol */
            z-index: 10;
        }
        /* Style untuk badge status aktif/terakhir */
        .timeline-item.active .timeline-badge {
            transform: scale(1.2); /* Sedikit membesar */
            border-color: #0d6efd; /* Warna cincin sesuai primary */
            background-color: var(--bs-primary);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4 rounded-top">
                        <i class="bi bi-geo-alt-fill" style="font-size: 2.5rem;"></i>
                        <h4 class="mb-0 mt-2">Sistem Pelacakan Pengajuan Pinjaman</h4>
                        <p class="mb-0 small">KSP Credit Union Bererod Gratia</p>
                    </div>
                    
                    <div class="card-body p-4 p-md-5">
                        <h5 class="text-center mb-4 text-primary">Cari Status Anda</h5>
                        <form method="GET" action="tracking.php" class="mb-5">
                            <div class="input-group input-group-lg shadow-sm">
                                <input type="text" name="code" class="form-control" 
                                        placeholder="Masukkan Kode Tracking (Contoh: CUBG-001-251031-1)" 
                                        value="<?php echo e($tracking_code); ?>" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i> Lacak
                                </button>
                            </div>
                        </form>

                        <?php if (!empty($tracking_code) && !$loan): ?>
                            <div class="alert alert-danger text-center mt-4 border-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                                Kode Tracking **<?php echo e($tracking_code); ?>** tidak ditemukan atau belum aktif.
                            </div>
                        <?php endif; ?>

                        <?php if ($loan): ?>
                            
                            <div class="card border-<?php echo e($loan['color']); ?> bg-<?php echo e($loan['color']); ?>-subtle text-center mb-5 shadow-sm">
                                <div class="card-body py-4">
                                    <i class="bi <?php echo e($loan['icon']); ?>" style="font-size: 2rem;"></i>
                                    <h5 class="mt-2 mb-1">Status Saat Ini:</h5>
                                    <span class="badge bg-<?php echo e($loan['color']); ?> fs-5">
                                        <?php echo e($loan['detailed_status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <h6 class="border-bottom pb-2 mb-3 text-secondary"><i class="bi bi-file-earmark-text me-1"></i> Detail Permohonan</h6>
                            <dl class="row small mb-5">
                                <dt class="col-sm-6 col-lg-4 text-muted">Kode Tracking</dt>
                                <dd class="col-sm-6 col-lg-8 fw-bold text-break"><?php echo e($loan['tracking_code']); ?></dd>
                                
                                <dt class="col-sm-6 col-lg-4 text-muted">Nama Anggota</dt>
                                <dd class="col-sm-6 col-lg-8"><?php echo e($loan['applicant_name']); ?></dd>
                                
                                <dt class="col-sm-6 col-lg-4 text-muted">No. Buku Anggota</dt>
                                <dd class="col-sm-6 col-lg-8"><?php echo e($loan['applicant_ba_number']); ?></dd>
                                
                                <dt class="col-sm-6 col-lg-4 text-muted">Tanggal Pengajuan</dt>
                                <dd class="col-sm-6 col-lg-8"><?php echo (new DateTime($loan['application_date']))->format('d F Y'); ?></dd>
                                
                                <dt class="col-sm-6 col-lg-4 text-muted">Kantor Pelayanan</dt>
                                <dd class="col-sm-6 col-lg-8"><?php echo e($loan['branch_name']); ?></dd>
                            </dl>

                            <h6 class="border-bottom pb-2 mb-4 text-secondary"><i class="bi bi-list-columns-reverse me-1"></i> Riwayat Proses</h6>
                            <div class="timeline">
                                <?php foreach ($history as $item): ?>
                                    <?php 
                                        $is_current = $item['status_id'] == $loan['status_id'];
                                        $badge_class = $is_current ? 'bg-' . $loan['color'] : 'bg-secondary';
                                    ?>
                                    <div class="timeline-item <?php echo $is_current ? 'active' : ''; ?>">
                                        <div class="timeline-badge <?php echo $badge_class; ?>"></div>
                                        <div class="timeline-panel bg-white p-3 border rounded shadow-sm mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 <?php echo $is_current ? 'text-' . $loan['color'] : 'text-dark'; ?>">
                                                    <?php echo e($item['status_name']); ?>
                                                </h6>
                                                <span class="text-muted small">
                                                    <?php echo (new DateTime($item['changed_at']))->format('d M Y H:i'); ?> WIB
                                                </span>
                                            </div>
                                            <?php if (!empty($item['notes'])): ?>
                                                <p class="mb-0 text-muted small fst-italic">Catatan: <?php echo e($item['notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>