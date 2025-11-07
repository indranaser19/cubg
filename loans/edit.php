<?php
// Mulai session di baris paling atas
session_start(); 

require_once '../config/db.php';

// --- Ambil ID & Data Pinjaman (Diperlukan untuk Otorisasi) ---
if (!isset($_GET['id'])) {
    header("location: index.php?error=ID permohonan tidak ditemukan.");
    exit;
}
$loan_id = (int)$_GET['id'];

// Helper function
function e($value) {
    return htmlspecialchars($value ?? '');
}

// Helper function format
function formatRupiah($value) {
    if (empty($value)) return '';
    return number_format($value, 0, '', '.');
}

// Ambil data pinjaman (termasuk tracking_code)
$sql = "SELECT 
            la.*, 
            ld.* FROM 
            loan_applications la
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

// --- LOGIKA OTORISASI BARU ---
$is_authorized = false;
$auth_error = '';

// 1. Cek otorisasi via session (jika sudah lolos verifikasi tracking)
if (isset($_SESSION['public_edit_access'][$loan_id]) && $_SESSION['public_edit_access'][$loan_id] === true) {
    $is_authorized = true;
}

// 2. Cek otorisasi via POST (jika baru memasukkan kode tracking)
if (!$is_authorized && isset($_POST['tracking_code'])) {
    if (!empty($loan['tracking_code']) && $_POST['tracking_code'] === $loan['tracking_code']) {
        $_SESSION['public_edit_access'][$loan_id] = true;
        $is_authorized = true;
    } else {
        $auth_error = "Kode tracking salah atau tidak ditemukan.";
    }
}

// 3. Cek otorisasi staf (login internal)
if (!$is_authorized && isset($_SESSION['user_id'])) {
    // Jalankan otorisasi internal
    require_once '../middleware/check_auth.php';
    authorize(['superadmin', 'credit_officer', 'branch_user']); // Pastikan role-nya boleh
    
    // Cek kepemilikan
    if ($_SESSION['role'] == 'superadmin' || $_SESSION['user_id'] == $loan['created_by_user_id']) {
        $is_authorized = true;
    }
}

// --- Akhir Logika Otorisasi ---

// Jika staf, ambil data cabang. Jika publik, data ini tidak diperlukan (atau bisa ditambahkan)
$branches = [];
if (isset($_SESSION['user_id'])) {
    $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} else if ($is_authorized) {
    // Jika publik yang lolos, kita tetap perlu data cabang
    $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Permohonan #<?php echo $loan_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
</head>
<body>
    
    <?php 
    // Hanya tampilkan navbar jika user adalah staf yang login ATAU publik yang sudah lolos
    // (Kita asumsikan navbar bisa menangani jika user tidak login)
    if ($is_authorized || isset($_SESSION['user_id'])) {
        include '../includes/navbar.php'; 
    } else {
        // Tampilkan navbar minimalis untuk publik
        echo '<nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="#">CUBG</a></div></nav>';
    }
    ?>

    <div class="container mt-4">

        <?php if (!$is_authorized): ?>
            
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning">
                            <h4 class="mb-0"><i class="bi bi-shield-lock"></i> Verifikasi Diperlukan</h4>
                        </div>
                        <div class="card-body">
                            <p>Untuk mengedit permohonan #<?php echo $loan_id; ?>, silakan masukkan kode tracking Anda.</p>
                            
                            <?php if ($auth_error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($auth_error); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="edit.php?id=<?php echo $loan_id; ?>">
                                <div class="mb-3">
                                    <label for="tracking_code" class="form-label">Kode Tracking</label>
                                    <input type="text" class="form-control" id="tracking_code" name="tracking_code" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle"></i> Verifikasi & Edit
                                </button>
                            </form>
                            
                            <?php if(!isset($_SESSION['user_id'])): // Tampilkan link login jika belum login ?>
                                <hr>
                                <p class="text-center text-muted mb-0">Atau, <a href="../auth/login.php">login</a> sebagai staf.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Permohonan #<?php echo e($loan['id']); ?></h3>
                <div>
                    <button class="btn btn-outline-info me-2" data-bs-toggle="modal" data-bs-target="#shareModal">
                        <i class="bi bi-share"></i> Bagikan Link Edit
                    </button>
                    <a href="view.php?id=<?php echo $loan_id; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Detail
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <form action="process_edit.php" method="POST" id="loanForm">
                <?php if (!isset($_SESSION['user_id'])): // Jika ini adalah user publik ?>
                <input type="hidden" name="tracking_code" value="<?php echo e($loan['tracking_code']); ?>">
                <?php endif; ?>
                
                <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">
                
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="tab-1" role="tabpanel" tabindex="0">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                
                                <h5 class="card-title text-muted">Informasi Umum</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label for="branch_id" class="form-label">Kantor Pelayanan</label>
                                        <select name="branch_id" id="branch_id" class="form-select" required <?php echo (!isset($_SESSION['user_id'])) ? 'disabled' : ''; ?>>
                                            <option value="">-- Pilih Cabang --</option>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?php echo $branch['id']; ?>" <?php echo ($loan['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                                    <?php echo e($branch['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (!isset($_SESSION['user_id'])): // Kirim data branch_id tersembunyi jika publik ?>
                                            <input type="hidden" name="branch_id" value="<?php echo e($loan['branch_id']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="application_date" class="form-label">Tanggal Permohonan</label>
                                        <input type="date" class="form-control" id="application_date" name="application_date" value="<?php echo e($loan['application_date']); ?>" required>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h5 class="card-title text-primary">A. Data Diri Anggota</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6"><label for="applicant_name" class="form-label">1. Nama (sesuai KTP)</label><input type="text" class="form-control" id="applicant_name" name="applicant_name" value="<?php echo e($loan['applicant_name']); ?>" required></div>
                                    <div class="col-md-6"><label for="applicant_ba_number" class="form-label">No. Buku Anggota</label><input type="text" class="form-control" id="applicant_ba_number" name="applicant_ba_number" value="<?php echo e($loan['applicant_ba_number']); ?>"></div>
                                    <div class="col-md-4"><label for="applicant_occupation" class="form-label">2. Pekerjaan</label><input type="text" class="form-control" id="applicant_occupation" name="applicant_occupation" value="<?php echo e($loan['applicant_occupation']); ?>"></div>
                                    <div class="col-md-4"><label for="applicant_position" class="form-label">Jabatan Sekarang</label><input type="text" class="form-control" id="applicant_position" name="applicant_position" value="<?php echo e($loan['applicant_position']); ?>"></div>
                                    <div class="col-md-4"><label for="applicant_phone" class="form-label">6. No. Telepon / HP</label><input type="tel" class="form-control" id="applicant_phone" name="applicant_phone" value="<?php echo e($loan['applicant_phone']); ?>"></div>
                                    <div class="col-md-4"><label for="applicant_birth_place" class="form-label">3. Tempat Lahir</label><input type="text" class="form-control" id="applicant_birth_place" name="applicant_birth_place" value="<?php echo e($loan['applicant_birth_place']); ?>"></div>
                                    <div class="col-md-4"><label for="applicant_birth_date" class="form-label">Tgl Lahir</label><input type="date" class="form-control" id="applicant_birth_date" name="applicant_birth_date" value="<?php echo e($loan['applicant_birth_date']); ?>"></div>
                                    <div class="col-md-4">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <select class="form-select" name="applicant_gender">
                                            <option value="Laki-laki" <?php echo (e($loan['applicant_gender']) == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                            <option value="Perempuan" <?php echo (e($loan['applicant_gender']) == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6"><label for="applicant_ktp_address" class="form-label">4. Alamat Rumah (KTP)</label><textarea class="form-control" id="applicant_ktp_address" name="applicant_ktp_address" rows="2"><?php echo e($loan['applicant_ktp_address']); ?></textarea></div>
                                    <div class="col-md-6"><label for="applicant_current_address" class="form-label">5. Alamat Tinggal saat ini</label><textarea class="form-control" id="applicant_current_address" name="applicant_current_address" rows="2"><?php echo e($loan['applicant_current_address']); ?></textarea></div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="applicant_marital_status">
                                            <option value="Tidak Kawin" <?php echo (e($loan['applicant_marital_status']) == 'Tidak Kawin') ? 'selected' : ''; ?>>Tidak Kawin</option>
                                            <option value="Kawin" <?php echo (e($loan['applicant_marital_status']) == 'Kawin') ? 'selected' : ''; ?>>Kawin</option>
                                        </select>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h5 class="card-title text-primary">B. Data Suami / Istri</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6"><label for="spouse_name" class="form-label">7. Nama</label><input type="text" class="form-control" id="spouse_name" name="spouse_name" value="<?php echo e($loan['spouse_name']); ?>"></div>
                                    <div class="col-md-6"><label for="spouse_ba_number" class="form-label">No. Buku Anggota</label><input type="text" class="form-control" id="spouse_ba_number" name="spouse_ba_number" value="<?php echo e($loan['spouse_ba_number']); ?>"></div>
                                    <div class="col-md-4"><label for="spouse_occupation" class="form-label">8. Pekerjaan</label><input type="text" class="form-control" id="spouse_occupation" name="spouse_occupation" value="<?php echo e($loan['spouse_occupation']); ?>"></div>
                                    <div class="col-md-4"><label for="spouse_position" class="form-label">Jabatan Sekarang</label><input type="text" class="form-control" id="spouse_position" name="spouse_position" value="<?php echo e($loan['spouse_position']); ?>"></div>
                                    <div class="col-md-4"><label for="spouse_work_phone" class="form-label">No. Telp. Perusahaan</label><input type="tel" class="form-control" id="spouse_work_phone" name="spouse_work_phone" value="<?php echo e($loan['spouse_work_phone']); ?>"></div>
                                    <div class="col-md-12"><label for="spouse_work_address" class="form-label">9. Alamat tempat kerja</label><textarea class="form-control" id="spouse_work_address" name="spouse_work_address" rows="2" maxlength="45"><?php echo e($loan['spouse_work_address']); ?></textarea></div>
                                </div>

                                <hr class="my-4">
                                <h5 class="card-title text-primary">C. Data Keluarga Terdekat</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4"><label for="emergency_contact_name" class="form-label">10. Nama</label><input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo e($loan['emergency_contact_name']); ?>"></div>
                                    <div class="col-md-4"><label for="emergency_contact_phone" class="form-label">No. Telp.</label><input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo e($loan['emergency_contact_phone']); ?>"></div>
                                    <div class="col-md-4"><label for="emergency_contact_relation" class="form-label">12. Hubungan dgn Pemohon</label><input type="text" class="form-control" id="emergency_contact_relation" name="emergency_contact_relation" value="<?php echo e($loan['emergency_contact_relation']); ?>"></div>
                                    <div class="col-md-12"><label for="emergency_contact_address" class="form-label">11. Alamat</label><textarea class="form-control" id="emergency_contact_address" name="emergency_contact_address" rows="2"><?php echo e($loan['emergency_contact_address']); ?></textarea></div>
                                </div>

                                <hr class="my-4">
                                <h5 class="card-title text-primary">D. Data Keuangan Anggota</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6"><label for="financial_saving_saham" class="form-label">13. Saldo Simpanan Saham (Rp)</label><input type="text" class="form-control rupiah" id="financial_saving_saham" name="financial_saving_saham" value="<?php echo formatRupiah($loan['financial_saving_saham']); ?>"></div>
                                    <div class="col-md-6"><label for="financial_saving_megapolitan" class="form-label">14. Saldo Simpanan Megapolitan (Rp)</label><input type="text" class="form-control rupiah" id="financial_saving_megapolitan" name="financial_saving_megapolitan" value="<?php echo formatRupiah($loan['financial_saving_megapolitan']); ?>"></div>
                                    <div class="col-md-6"><label for="financial_saving_padanan" class="form-label">15. Saldo Simpanan Padanan (Rp)</label><input type="text" class="form-control rupiah" id="financial_saving_padanan" name="financial_saving_padanan" value="<?php echo formatRupiah($loan['financial_saving_padanan']); ?>"></div>
                                    <div class="col-md-6"><label for="financial_remaining_loan" class="form-label">16. Sisa Pinjaman (Rp)</label><input type="text" class="form-control rupiah" id="financial_remaining_loan" name="financial_remaining_loan" value="<?php echo formatRupiah($loan['financial_remaining_loan']); ?>"></div>
                                    <div class="col-md-6"><label for="financial_other_loan" class="form-label">17. Pinjaman ditempat Lain (Rp)</label><input type="text" class="form-control rupiah" id="financial_other_loan" name="financial_other_loan" value="<?php echo formatRupiah($loan['financial_other_loan']); ?>"></div>
                                    <div class="col-md-6"><label for="financial_other_savings" class="form-label">18. Simpanan lainnya (Rp)</label><input type="text" class="form-control rupiah" id="financial_other_savings" name="financial_other_savings" value="<?php echo formatRupiah($loan['financial_other_savings']); ?>" placeholder="mis: 100.000"></div>
                                </div>

                                <hr class="my-4">
                                <h5 class="card-title text-primary">E. Pinjaman Yang Dimohon</h5>
                                <div class="row g-3">
                                    <div class="col-md-4"><label for="loan_amount_requested" class="form-label">19. Jumlah Permohonan Pinjaman (Rp)</label><input type="text" class="form-control rupiah" id="loan_amount_requested" name="loan_amount_requested" value="<?php echo formatRupiah($loan['loan_amount_requested']); ?>" required></div>
                                    <div class="col-md-4"><label for="loan_term_months" class="form-label">Jangka Waktu (Bulan)</label><input type="number" class="form-control" id="loan_term_months" name="loan_term_months" value="<?php echo e($loan['loan_term_months']); ?>" required></div>
                                    <div class="col-md-4">
                                        <label for="loan_type" class="form-label">20. Jenis Pinjaman</label>
                                        <select name="loan_type" id="loan_type" class="form-select" required>
                                            <option value="">-- Pilih Produk --</option>
                                            <option value="Pinjaman Produktif" <?php echo (e($loan['loan_type']) == 'Pinjaman Produktif') ? 'selected' : ''; ?>>Pinjaman Produktif</option>
                                            <option value="Pinjaman Rumah Tangga" <?php echo (e($loan['loan_type']) == 'Pinjaman Rumah Tangga') ? 'selected' : ''; ?>>Pinjaman Rumah Tangga</option>
                                            <option value="Pinjaman Kendaraan - Motor" <?php echo (e($loan['loan_type']) == 'Pinjaman Kendaraan - Motor') ? 'selected' : ''; ?>>Pinjaman Kendaraan - Motor</option>
                                            <option value="Pinjaman Kendaraan - Mobil" <?php echo (e($loan['loan_type']) == 'Pinjaman Kendaraan - Mobil') ? 'selected' : ''; ?>>Pinjaman Kendaraan - Mobil</option>
                                            <option value="Pinjaman Rumah" <?php echo (e($loan['loan_type']) == 'Pinjaman Rumah') ? 'selected' : ''; ?>>Pinjaman Rumah</option>
                                            <option value="Pinjaman Kavling Tanah" <?php echo (e($loan['loan_type']) == 'Pinjaman Kavling Tanah') ? 'selected' : ''; ?>>Pinjaman Kavling Tanah</option>
                                            <option value="Pinjaman Pendidikan" <?php echo (e($loan['loan_type']) == 'Pinjaman Pendidikan') ? 'selected' : ''; ?>>Pinjaman Pendidikan</option>
                                            <option value="Pinjaman Hari Raya" <?php echo (e($loan['loan_type']) == 'Pinjaman Hari Raya') ? 'selected' : ''; ?>>Pinjaman Hari Raya</option>
                                            <option value="Pinjaman Wisata" <?php echo (e($loan['loan_type']) == 'Pinjaman Wisata') ? 'selected' : ''; ?>>Pinjaman Wisata</option>
                                            <option value="Pinjaman Menambah Aset" <?php echo (e($loan['loan_type']) == 'Pinjaman Menambah Aset') ? 'selected' : ''; ?>>Pinjaman Menambah Aset</option>
                                            <option value="Pinjaman Mikro Gratia" <?php echo (e($loan['loan_type']) == 'Pinjaman Mikro Gratia') ? 'selected' : ''; ?>>Pinjaman Mikro Gratia</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12"><label for="loan_purpose" class="form-label">21. Tujuan Pinjaman</label><textarea class="form-control" id="loan_purpose" name="loan_purpose" rows="2"><?php echo e($loan['loan_purpose']); ?></textarea></div>
                                    <div class="col-md-12"><label for="loan_collateral_type" class="form-label">22. Jaminan yang akan diserahkan</label><input type="text" class="form-control" id="loan_collateral_type" name="loan_collateral_type" value="<?php echo e($loan['loan_collateral_type']); ?>"></div>
                                    <div class="col-md-4"><label for="loan_collateral_owner" class="form-label">23. Pemilik jaminan</label><input type="text" class="form-control" id="loan_collateral_owner" name="loan_collateral_owner" value="<?php echo e($loan['loan_collateral_owner']); ?>"></div>
                                    <div class="col-md-4"><label for="loan_collateral_status" class="form-label">Status Jaminan</label><input type="text" class="form-control" id="loan_collateral_status" name="loan_collateral_status" value="<?php echo e($loan['loan_collateral_status']); ?>"></div>
                                    <div class="col-md-4"><label for="loan_collateral_value" class="form-label">Harga Jaminan (Rp)</label><input type="text" class="form-control rupiah" id="loan_collateral_value" name="loan_collateral_value" value="<?php echo formatRupiah($loan['loan_collateral_value']); ?>"></div>
                                    <div class="col-md-6"><label for="loan_collateral_location" class="form-label">24. Lokasi/kondisi</label><input type="text" class="form-control" id="loan_collateral_location" name="loan_collateral_location" value="<?php echo e($loan['loan_collateral_location']); ?>"></div>
                                    <div class="col-md-6"><label for="loan_monthly_payment_capacity" class="form-label">25. Kemampuan Bayar Bulanan (Rp)</label><input type="text" class="form-control rupiah" id="loan_monthly_payment_capacity" name="loan_monthly_payment_capacity" value="<?php echo formatRupiah($loan['loan_monthly_payment_capacity']); ?>"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display:none;">
                        <input type="hidden" name="sales_monthly" value="<?php echo e($loan['sales_monthly']); ?>">
                        <input type="hidden" name="sales_total" value="<?php echo e($loan['sales_total']); ?>">
                        <input type="hidden" name="cogs_monthly" value="<?php echo e($loan['cogs_monthly']); ?>">
                        <input type="hidden" name="cogs_total" value="<?php echo e($loan['cogs_total']); ?>">
                        <input type="hidden" name="op_payroll_monthly" value="<?php echo e($loan['op_payroll_monthly']); ?>">
                        <input type="hidden" name="op_payroll_total" value="<?php echo e($loan['op_payroll_total']); ?>">
                        <input type="hidden" name="op_rent_monthly" value="<?php echo e($loan['op_rent_monthly']); ?>">
                        <input type="hidden" name="op_rent_total" value="<?php echo e($loan['op_rent_total']); ?>">
                        <input type="hidden" name="op_utilities_monthly" value="<?php echo e($loan['op_utilities_monthly']); ?>">
                        <input type="hidden" name="op_utilities_total" value="<?php echo e($loan['op_utilities_total']); ?>">
                        <input type="hidden" name="op_transport_monthly" value="<?php echo e($loan['op_transport_monthly']); ?>">
                        <input type="hidden" name="op_transport_total" value="<?php echo e($loan['op_transport_total']); ?>">
                        <input type="hidden" name="op_admin_monthly" value="<?php echo e($loan['op_admin_monthly']); ?>">
                        <input type="hidden" name="op_admin_total" value="<?php echo e($loan['op_admin_total']); ?>">
                        <input type="hidden" name="op_maintenance_monthly" value="<?php echo e($loan['op_maintenance_monthly']); ?>">
                        <input type="hidden" name="op_maintenance_total" value="<?php echo e($loan['op_maintenance_total']); ?>">
                        <input type="hidden" name="op_promotion_monthly" value="<?php echo e($loan['op_promotion_monthly']); ?>">
                        <input type="hidden" name="op_promotion_total" value="<?php echo e($loan['op_promotion_total']); ?>">
                        <input type="hidden" name="modal_cubg_loan" value="<?php echo e($loan['modal_cubg_loan']); ?>">
                        <input type="hidden" name="modal_equity" value="<?php echo e($loan['modal_equity']); ?>">
                        <input type="hidden" name="modal_other_source" value="<?php echo e($loan['modal_other_source']); ?>">
                        <input type="hidden" name="modal_other_amount" value="<?php echo e($loan['modal_other_amount']); ?>">
                        <input type="hidden" name="asset_cash" value="<?php echo e($loan['asset_cash']); ?>">
                        <input type="hidden" name="asset_bank_savings" value="<?php echo e($loan['asset_bank_savings']); ?>">
                        <input type="hidden" name="asset_cu_daily_savings" value="<?php echo e($loan['asset_cu_daily_savings']); ?>">
                        <input type="hidden" name="asset_current_other" value="<?php echo e($loan['asset_current_other']); ?>">
                        <input type="hidden" name="invest_megapolitan_savings" value="<?php echo e($loan['invest_megapolitan_savings']); ?>">
                        <input type="hidden" name="invest_other_cu_savings" value="<?php echo e($loan['invest_other_cu_savings']); ?>">
                        <input type="hidden" name="invest_business_assets" value="<?php echo e($loan['invest_business_assets']); ?>">
                        <input type="hidden" name="invest_commercial_property" value="<?php echo e($loan['invest_commercial_property']); ?>">
                        <input type="hidden" name="invest_other" value="<?php echo e($loan['invest_other']); ?>">
                        <input type="hidden" name="asset_home_value" value="<?php echo e($loan['asset_home_value']); ?>">
                        <input type="hidden" name="asset_home_contents_value" value="<?php echo e($loan['asset_home_contents_value']); ?>">
                        <input type="hidden" name="asset_vehicle_value" value="<?php echo e($loan['asset_vehicle_value']); ?>">
                        <input type="hidden" name="asset_jewelry_value" value="<?php echo e($loan['asset_jewelry_value']); ?>">
                        <input type="hidden" name="asset_personal_other" value="<?php echo e($loan['asset_personal_other']); ?>">
                        <input type="hidden" name="liability_cu_short_term" value="<?php echo e($loan['liability_cu_short_term']); ?>">
                        <input type="hidden" name="liability_credit_card_kta" value="<?php echo e($loan['liability_credit_card_kta']); ?>">
                        <input type="hidden" name="liability_short_term_other" value="<?php echo e($loan['liability_short_term_other']); ?>">
                        <input type="hidden" name="liability_housing_loan" value="<?php echo e($loan['liability_housing_loan']); ?>">
                        <input type="hidden" name="liability_vehicle_loan" value="<?php echo e($loan['liability_vehicle_loan']); ?>">
                        <input type="hidden" name="liability_consumptive_loan" value="<?php echo e($loan['liability_consumptive_loan']); ?>">
                        <input type="hidden" name="liability_productive_loan" value="<?php echo e($loan['liability_productive_loan']); ?>">
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mb-5 mt-3">
                     <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>

        <?php endif; ?>
    </div>

    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel"><i class="bi bi-share"></i> Bagikan Link Edit Publik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Siapapun yang memiliki link ini dan <strong>Kode Tracking</strong> dapat mengedit data permohonan.</p>
                    <label for="publicLink" class="form-label">Link Halaman Edit</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="publicLink" value="<?php echo "http://$_SERVER[HTTP_HOST]/cubg/loans/edit.php?id=$loan_id"; ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyButton">
                            <i class="bi bi-clipboard"></i> Salin
                        </button>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Kode Tracking</label>
                        <input type="text" class="form-control" value="<?php echo e($loan['tracking_code']); ?>" readonly>
                    </div>
                    <div id="copyAlert" class="alert alert-success mt-3" style="display:none;" role="alert">
                        Link berhasil disalin!
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        
        // --- Logika Tombol Bagikan ---
        $('#copyButton').on('click', function() {
            var copyText = document.getElementById("publicLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // Untuk HP
            navigator.clipboard.writeText(copyText.value);
            
            $('#copyAlert').fadeIn().delay(2000).fadeOut();
        });

        // --- Logika Format Rupiah ---
        function formatNumber(n) {
            n = n.replace(/[^0-9-]/g, ''); 
            return n.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function cleanNumber(n) {
            return n.replace(/[^0-9]/g, '');
        }

        $('.rupiah').on('keyup', function() {
            var inputVal = $(this).val();
            var cursorPosition = this.selectionStart;
            var originalDots = (inputVal.substring(0, cursorPosition).match(/\./g) || []).length;
            var formattedVal = formatNumber(inputVal);
            $(this).val(formattedVal);
            var newDots = (formattedVal.substring(0, formattedVal.length).substring(0, cursorPosition + (formattedVal.length - inputVal.length)).match(/\./g) || []).length;
            var newCursorPosition = cursorPosition + (newDots - originalDots);
            this.setSelectionRange(newCursorPosition, newCursorPosition);
        });

        $('.rupiah').trigger('keyup');

        // Saat submit, bersihkan nilai rupiah
        $('#loanForm').on('submit', function() {
            // Nonaktifkan field yang di-disable agar terkirim (jika user publik)
            <?php if (!isset($_SESSION['user_id'])): ?>
                $('#branch_id').prop('disabled', false);
            <?php endif; ?>

            $('.rupiah').each(function() {
                var cleanedValue = cleanNumber($(this).val());
                $(this).val(cleanedValue);
            });
            return true;
        });
    });
    </script>
</body>
</html>