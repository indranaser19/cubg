<?php
require_once '../middleware/check_auth.php';
authorize(['superadmin', 'credit_officer', 'branch_user']);

require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header("location: index.php?error=ID Pinjaman tidak ditemukan.");
    exit;
}

$loan_id = $_GET['id'];
$branch_filter_sql = getBranchQueryFilter(); // Ambil filter RBA

// 1. Ambil Data Utama
$sql = "SELECT la.*, b.name as branch_name, u.full_name as created_by
        FROM loan_applications la
        JOIN branches b ON la.branch_id = b.id
        JOIN users u ON la.created_by_user_id = u.id
        WHERE la.id = :id AND ($branch_filter_sql)";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $loan_id]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    header("location: index.php?error=Data tidak ditemukan atau Anda tidak memiliki akses.");
    exit;
}

// 2. Ambil Data Laba Rugi (Hal. 2)
$stmt_biz = $pdo->prepare("SELECT * FROM loan_business_finances WHERE loan_application_id = :id");
$stmt_biz->execute([':id' => $loan_id]);
$biz = $stmt_biz->fetch(PDO::FETCH_ASSOC);

// 3. Ambil Data Kekayaan Bersih (Hal. 3)
$stmt_worth = $pdo->prepare("SELECT * FROM loan_net_worth WHERE loan_application_id = :id");
$stmt_worth->execute([':id' => $loan_id]);
$worth = $stmt_worth->fetch(PDO::FETCH_ASSOC);

// 4. Ambil Log Aktivitas
$sql_logs = "SELECT l.*, u.full_name as user_name 
             FROM loan_application_logs l 
             JOIN users u ON l.user_id = u.id 
             WHERE l.loan_application_id = :id 
             ORDER BY l.timestamp DESC";
$stmt_logs = $pdo->prepare($sql_logs);
$stmt_logs->execute([':id' => $loan_id]);
$logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

// 5. Ambil Dokumen
$stmt_docs = $pdo->prepare("SELECT * FROM loan_documents WHERE loan_application_id = :id");
$stmt_docs->execute([':id' => $loan_id]);
$docs = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

// Fungsi helper untuk menampilkan data
function display_field($label, $value, $format_as_currency = false) {
    $formatted_value = htmlspecialchars($value);
    if (empty($formatted_value)) {
        $formatted_value = '<em class="text-muted">-</em>';
    } else if ($format_as_currency) {
        $formatted_value = 'Rp ' . number_format($value, 0, ',', '.');
    }
    
    echo "<div class='row mb-2'>
            <dt class='col-sm-5'>$label</dt>
            <dd class='col-sm-7'>$formatted_value</dd>
          </div>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pinjaman ID #<?php echo $loan['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
             <h3 class="mb-0">Detail Permohonan Pinjaman ID: #<?php echo $loan['id']; ?></h3>
             <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Tracking
             </a>
        </div>
       

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="detailTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="page1-tab" data-bs-toggle="tab" data-bs-target="#page1" type="button" role="tab">Hal 1: Data Anggota</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="page2-tab" data-bs-toggle="tab" data-bs-target="#page2" type="button" role="tab">Hal 2: Laporan Usaha</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="page3-tab" data-bs-toggle="tab" data-bs-target="#page3" type="button" role="tab">Hal 3: Kekayaan Bersih</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="detailTabsContent">
                            <div class="tab-pane fade show active" id="page1" role="tabpanel">
                                <h4>Data Pinjaman</h4>
                                <dl class="row">
                                    <?php display_field('Jumlah Permohonan', $loan['loan_amount_requested'], true); ?>
                                    <?php display_field('Jangka Waktu', $loan['loan_term_months'] . ' Bulan'); ?>
                                    <?php display_field('Jenis Pinjaman', $loan['loan_type']); ?>
                                    <?php display_field('Tujuan Pinjaman', $loan['loan_purpose']); ?>
                                </dl>
                                <hr>
                                <h4>A. Data Diri Anggota</h4>
                                <dl class="row">
                                    <?php display_field('Nama', $loan['applicant_name']); ?>
                                    <?php display_field('No. BA', $loan['applicant_ba_number']); ?>
                                    <?php display_field('Pekerjaan/Jabatan', $loan['applicant_occupation'] . ' / ' . $loan['applicant_position']); ?>
                                    <?php display_field('Tempat/Tgl Lahir', $loan['applicant_birth_place'] . ', ' . $loan['applicant_birth_date']); ?>
                                    <?php display_field('Alamat KTP', $loan['applicant_ktp_address']); ?>
                                    <?php display_field('Alamat Tinggal', $loan['applicant_current_address']); ?>
                                    <?php display_field('No. HP', $loan['applicant_phone']); ?>
                                </dl>
                                <hr>
                                <h4>B. Data Suami / Istri</h4>
                                <dl class="row">
                                    <?php display_field('Nama', $loan['spouse_name']); ?>
                                    <?php display_field('Pekerjaan/Jabatan', $loan['spouse_occupation'] . ' / ' . $loan['spouse_position']); ?>
                                </dl>
                                <hr>
                                <h4>D. Data Keuangan Anggota</h4>
                                <dl class="row">
                                    <?php display_field('Simp. Saham', $loan['financial_saving_saham'], true); ?>
                                    <?php display_field('Simp. Megapolitan', $loan['financial_saving_megapolitan'], true); ?>
                                    <?php display_field('Sisa Pinjaman', $loan['financial_remaining_loan'], true); ?>
                                </dl>
                            </div>
                            
                            <div class="tab-pane fade" id="page2" role="tabpanel">
                                <?php if ($biz): ?>
                                    <h4>Laporan Laba/Rugi Usaha</h4>
                                    <dl class_row="row">
                                        <?php display_field('Penjualan (Bulanan)', $biz['sales_monthly'], true); ?>
                                        <?php display_field('HPP (Bulanan)', $biz['cogs_monthly'], true); ?>
                                        <?php display_field('Biaya Gaji (Bulanan)', $biz['op_payroll_monthly'], true); ?>
                                        <?php display_field('Biaya Sewa (Bulanan)', $biz['op_rent_monthly'], true); ?>
                                    </dl>
                                    <hr>
                                    <h4>Sumber Modal Usaha</h4>
                                    <dl class="row">
                                        <?php display_field('Pinjaman CUBG', $biz['modal_cubg_loan'], true); ?>
                                        <?php display_field('Modal Sendiri', $biz['modal_equity'], true); ?>
                                    </dl>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada data Laporan Usaha yang diisi untuk permohonan ini.</p>
                                <?php endif; ?>
                            </div>

                            <div class="tab-pane fade" id="page3" role="tabpanel">
                                <?php if ($worth): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h4>Aset</h4>
                                            <dl>
                                                <?php display_field('Uang Tunai', $worth['asset_cash'], true); ?>
                                                <?php display_field('Simpanan Bank', $worth['asset_bank_savings'], true); ?>
                                                <?php display_field('Nilai Rumah', $worth['asset_home_value'], true); ?>
                                                <?php display_field('Nilai Kendaraan', $worth['asset_vehicle_value'], true); ?>
                                            </dl>
                                        </div>
                                        <div class="col-md-6">
                                            <h4>Kewajiban (Utang)</h4>
                                            <dl>
                                                <?php display_field('Utang KTA/Kartu Kredit', $worth['liability_credit_card_kta'], true); ?>
                                                <?php display_field('Sisa Pinjaman Perumahan', $worth['liability_housing_loan'], true); ?>
                                                <?php display_field('Sisa Pinjaman Kendaraan', $worth['liability_vehicle_loan'], true); ?>
                                            </dl>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada data Kekayaan Bersih yang diisi untuk permohonan ini.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Status Permohonan</h5>
                    </div>
                    <div class="card-body">
                        <?php
                            $status = $loan['status'];
                            $badge = 'secondary';
                            if ($status == 'baru') $badge = 'primary';
                            else if ($status == 'diproses') $badge = 'warning';
                            else if ($status == 'disetujui') $badge = 'success';
                            else if ($status == 'ditolak') $badge = 'danger';
                        ?>
                        <h3 class="text-center"><span class="badge bg-<?php echo $badge; ?> p-2"><?php echo ucfirst($status); ?></span></h3>
                        <hr>
                        <?php display_field('Proses Terakhir', $loan['sub_status']); ?>
                        <?php display_field('Diajukan Oleh', $loan['created_by']); ?>
                        <?php display_field('Cabang', $loan['branch_name']); ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-paperclip"></i> Dokumen Terlampir</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($docs)): ?>
                            <p class="text-muted">Tidak ada dokumen yang diupload.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($docs as $doc): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($doc['file_name']); ?>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-task"></i> Lacak Aktivitas</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($logs as $log): ?>
                            <div class="mb-3 border-bottom pb-2">
                                <strong><?php echo htmlspecialchars($log['user_name']); ?></strong> 
                                <span class="text-muted">
                                    (<?php echo date('d-m-Y H:i', strtotime($log['timestamp'])); ?>)
                                </span>
                                <p class="mb-0">
                                    <strong>Aksi:</strong> <?php echo htmlspecialchars($log['action']); ?>
                                    <?php if($log['sub_status']): ?>
                                    (<?php echo htmlspecialchars($log['sub_status']); ?>)
                                    <?php endif; ?>
                                </p>
                                <p class="mb-0 fst-italic">"<?php echo htmlspecialchars($log['notes']); ?>"</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>