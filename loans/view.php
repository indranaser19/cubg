<?php
require_once '../middleware/check_auth.php';
// Role yang boleh melihat: superadmin, branch_user (melihat semua), credit_officer (melihat cabang sendiri)
authorize(['superadmin', 'branch_user', 'credit_officer']);

require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header("location: index.php?error=ID permohonan tidak ditemukan.");
    exit;
}

$loan_id = (int)$_GET['id'];

// Helper function untuk menampilkan data
function e($value) {
    return htmlspecialchars($value ?? '');
}
function formatRupiah($amount) {
    if (empty($amount)) return 'Rp 0';
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// --- Ambil Data Pinjaman (Gabungan 2 tabel) ---
$sql = "SELECT 
            la.*, 
            b.name as branch_name,
            u.full_name as created_by_user,
            ls.status_name, ls.badge_class,
            ld.*, -- Ambil semua dari loan_application_details (walaupun tidak ditampilkan, tetap diambil)
            la.loan_term_months
        FROM 
            loan_applications la
        JOIN 
            branches b ON la.branch_id = b.id
        JOIN 
            loan_statuses ls ON la.status_id = ls.id
        JOIN 
            users u ON la.created_by_user_id = u.id
        LEFT JOIN 
            loan_application_details ld ON la.id = ld.loan_app_id
        WHERE 
            la.id = :loan_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':loan_id' => $loan_id]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    header("location: index.php?error=Data permohonan tidak ditemukan.");
    exit;
}

// --- Cek Otorisasi Cabang ---
if ($_SESSION['role'] == 'credit_officer' && $loan['branch_id'] != $_SESSION['branch_id']) {
    header("location: index.php?error=Anda tidak memiliki hak akses untuk melihat permohonan ini.");
    exit;
}

// Ambil semua status untuk dropdown "Ubah Status"
$statuses = $pdo->query("SELECT id, status_name FROM loan_statuses ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// === TAMBAHKAN VARIABEL INI ===
// Cek hak akses untuk Generate Code
$can_generate_code = in_array($_SESSION['role'], ['superadmin', 'credit_officer']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Permohonan Pinjaman #<?php echo $loan_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <style>
        dt { font-weight: 600; }
        dd { margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4 mb-5">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Detail Permohonan #<?php echo e($loan['id']); ?></h3>
                <span class="badge <?php echo e($loan['badge_class']); ?> fs-6"><?php echo e($loan['status_name']); ?></span>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Data Anggota & Pinjaman</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>A. Data Diri Anggota</h6>
                                <dl>
                                    <dt>Nama (KTP)</dt><dd><?php echo e($loan['applicant_name']); ?></dd>
                                    <dt>No. BA</dt><dd><?php echo e($loan['applicant_ba_number']); ?></dd>
                                    <dt>Tgl Lahir</dt><dd><?php echo e($loan['applicant_birth_place']); ?>, <?php echo !empty($loan['applicant_birth_date']) ? (new DateTime(e($loan['applicant_birth_date'])))->format('d M Y') : '-'; ?></dd>
                                    <dt>Pekerjaan/Jabatan</dt><dd><?php echo e($loan['applicant_occupation']); ?> / <?php echo e($loan['applicant_position']); ?></dd>
                                    <dt>Alamat KTP</dt><dd><?php echo e($loan['applicant_ktp_address']); ?></dd>
                                    <dt>Alamat Tinggal</dt><dd><?php echo e($loan['applicant_current_address']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6>E. Pinjaman Yang Dimohon</h6>
                                <dl>
                                    <dt>Jumlah Permohonan</dt><dd><?php echo formatRupiah($loan['loan_amount_requested']); ?></dd>
                                    <dt>Jangka Waktu</dt><dd><?php echo e($loan['loan_term_months']); ?> Bulan</dd>
                                    <dt>Jenis Pinjaman</dt><dd><?php echo e($loan['loan_type']); ?></dd>
                                    <dt>Tujuan Pinjaman</dt><dd><?php echo e($loan['loan_purpose']); ?></dd>
                                    <dt>Jaminan</dt><dd><?php echo e($loan['loan_collateral_type']); ?></dd>
                                    <dt>Kemampuan Bayar</dt><dd><?php echo formatRupiah($loan['loan_monthly_payment_capacity']); ?></dd>
                                </dl>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>B. Data Suami/Istri</h6>
                                <dl>
                                    <dt>Nama/No. BA</dt><dd><?php echo e($loan['spouse_name']); ?> / <?php echo e($loan['spouse_ba_number']); ?></dd>
                                    <dt>Pekerjaan/Jabatan</dt><dd><?php echo e($loan['spouse_occupation']); ?> / <?php echo e($loan['spouse_position']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6>D. Data Keuangan Anggota</h6>
                                <dl>
                                    <dt>Saldo Saham/Megapolitan/Padanan</dt><dd><?php echo formatRupiah($loan['financial_saving_saham']); ?> / <?php echo formatRupiah($loan['financial_saving_megapolitan']); ?> / <?php echo formatRupiah($loan['financial_saving_padanan']); ?></dd>
                                    <dt>Sisa Pinjaman CU / Lain</dt><dd><?php echo formatRupiah($loan['financial_remaining_loan']); ?> / <?php echo formatRupiah($loan['financial_other_loan']); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Tindak Lanjut</h5>
                    </div>
                    <div class="card-body">
                        <form action="process_update_status.php" method="POST">
                            <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">
                            <div class="mb-3">
                                <label for="new_status_id" class="form-label">Ubah Status Permohonan</label>
                                <select name="new_status_id" id="new_status_id" class="form-select" required>
                                    <?php 
                                    $ordered_statuses = [1, 2, 6, 7, 8, 3, 4, 5];
                                    $status_map_view = [
                                        1 => 'Baru Dibuat', 2 => 'Survei', 6 => 'Rapat Kantor Cabang',
                                        7 => 'Rapat Komite Kantor Pusat', 8 => 'Rapat Dewan Pengurus',
                                        3 => 'Disetujui', 4 => 'Ditolak', 5 => 'Lunas',
                                    ];
                                    $current_group = '';
                                    foreach ($ordered_statuses as $id) {
                                        $status = array_filter($statuses, function($s) use ($id) { return $s['id'] == $id; });
                                        if (empty($status)) continue;
                                        $status = reset($status);
                                        $group_name = '';
                                        if ($id == 1) $group_name = 'Pembuatan';
                                        elseif (in_array($id, [2, 6, 7, 8])) $group_name = 'Diproses';
                                        elseif (in_array($id, [3, 4, 5])) $group_name = 'Selesai';
                                        if ($group_name != $current_group) {
                                            if ($current_group != '') echo '</optgroup>';
                                            if ($group_name != '') echo '<optgroup label="' . $group_name . '">';
                                            $current_group = $group_name;
                                        }
                                        echo '<option value="' . $status['id'] . '" ' . ($status['id'] == $loan['status_id'] ? 'selected' : '') . '>';
                                        echo e($status_map_view[$id]);
                                        echo '</option>';
                                    }
                                    if ($current_group != '') echo '</optgroup>';
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Catatan (Opsional)</label>
                                <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Tambahkan catatan..."></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Status
                                </button>
                            </div>
                        </form>

                        <hr>
                        
                        <div class="mb-3" id="tracking-section">
                            <label class="form-label fw-bold">Kode Tracking Publik</label>
                            
                            <?php if (!empty($loan['tracking_code'])): ?>
                                <div id="code-display">
                                    <span class="badge bg-success d-block fs-6 mb-2"><?php echo e($loan['tracking_code']); ?></span>
                                    <button class="btn btn-sm btn-light copy-btn w-100" data-code-link="tracking.php?code=<?php echo e($loan['tracking_code']); ?>">
                                        <i class="bi bi-link-45deg"></i> Copy Link Tracking
                                    </button>
                                </div>
                            <?php elseif ($can_generate_code): ?>
                                <button class="btn btn-sm btn-info btn-generate-tracking w-100" data-id="<?php echo $loan_id; ?>">
                                    <i class="bi bi-magic"></i> Generate Kode
                                </button>
                            <?php else: ?>
                                <p class="text-muted small mb-0">Kode tracking belum dibuat.</p>
                            <?php endif; ?>
                        </div>
                        <a href="tracking.php?code=<?php echo e($loan['tracking_code']); ?>" class="btn btn-outline-primary w-100">
                            <i class="bi bi-clock-history"></i> Lihat Riwayat Tracking
                        </a>
                        
                        <?php if (in_array($_SESSION['role'], ['superadmin', 'credit_officer'])): ?>
                            <a href="edit.php?id=<?php echo $loan_id; ?>" class="btn btn-warning w-100 mt-2">
                                <i class="bi bi-pencil"></i> Edit Data Permohonan
                            </a>
                            <a href="export_pdf.php?id=<?php echo $loan_id; ?>" class="btn btn-danger w-100 mt-2" target="_blank">
                                <i class="bi bi-file-earmark-pdf"></i> Download PDF Formulir
                            </a>

                            <a href="process_soft_delete.php?id=<?php echo $loan_id; ?>" 
                               class="btn btn-outline-danger w-100 mt-2" 
                               onclick="return confirm('Yakin ingin memindahkan data ini ke Recycle Bin?');">
                                <i class="bi bi-trash"></i> Hapus (Recycle Bin)
                            </a>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Event listener untuk tombol generate tracking code
        $(document).on('click', '.btn-generate-tracking', function() {
            var button = $(this);
            var loanId = button.data('id');
            var targetContainer = $('#tracking-section'); // Target container di view.php
            
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...');

            $.ajax({
                url: 'generate_tracking_code.php', // Pastikan file ini ada
                type: 'POST',
                data: { id: loanId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Buat konten baru (termasuk label)
                        var newContent = `
                            <label class="form-label fw-bold">Kode Tracking Publik</label>
                            <div id="code-display">
                                <span class="badge bg-success d-block fs-6 mb-2">${response.tracking_code}</span>
                                <button class="btn btn-sm btn-light copy-btn w-100" data-code-link="tracking.php?code=${response.tracking_code}">
                                    <i class="bi bi-link-45deg"></i> Copy Link Tracking
                                </button>
                            </div>
                        `;
                        // Ganti seluruh isi #tracking-section
                        targetContainer.html(newContent);
                        
                        // Perbarui juga link "Lihat Riwayat"
                        $('a[href*="tracking.php"]').attr('href', 'tracking.php?code=' + response.tracking_code);

                    } else {
                        alert('Gagal membuat kode tracking: ' + response.message);
                        button.prop('disabled', false).html('<i class="bi bi-magic"></i> Generate Kode');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Terjadi kesalahan koneksi atau server: ' + xhr.status + ' ' + error);
                    button.prop('disabled', false).html('<i class="bi bi-magic"></i> Generate Kode');
                }
            });
        });

        // Event Delegation untuk Copy Link
        $(document).on('click', '.copy-btn', function() {
            const link = $(this).attr('data-code-link');
            const tempInput = document.createElement('input');
            // Dapatkan URL dasar yang benar
            const baseURL = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            tempInput.value = baseURL + link;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Ubah teks tombol
            $(this).html('<i class="bi bi-check2"></i> Copied!');
            setTimeout(() => {
                $(this).html('<i class="bi bi-link-45deg"></i> Copy Link Tracking');
            }, 1500);
        });
    });
    </script>
</body>
</html>