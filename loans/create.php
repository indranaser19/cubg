<?php
// Lokasi: /cubg/loans/create.php

require_once '../middleware/check_auth.php';
// Hanya role tertentu yang bisa membuat pengajuan
authorize(['superadmin', 'credit_officer']);

require_once '../config/db.php';

// Ambil data cabang untuk dropdown
try {
    $branches_stmt = $pdo->query("SELECT id, name FROM branches ORDER BY name");
    $branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $branches = [];
    $error_message = "Gagal memuat data cabang: " . $e->getMessage();
}

// Helper function (kosong, untuk konsistensi)
function e($value) {
    return htmlspecialchars($value ?? '');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Permohonan Pinjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="mb-0">Formulir Permohonan Pinjaman Baru</h3>
            </div>
            <div class="card-body">
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php elseif (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="process_create.php" method="POST" class="mt-3" id="loanForm">
                    <h5 class="card-title text-muted">Informasi Umum</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="application_date" class="form-label">Tanggal Permohonan</label>
                            <input type="date" class="form-control" id="application_date" name="application_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Kantor Pelayanan</label>
                            <select class="form-select" id="branch_id" name="branch_id" required <?php echo ($_SESSION['role'] != 'superadmin') ? 'disabled' : ''; ?>>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" <?php echo ($_SESSION['role'] != 'superadmin' && $_SESSION['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($_SESSION['role'] != 'superadmin'): ?>
                                <input type="hidden" name="branch_id" value="<?php echo $_SESSION['branch_id']; ?>" />
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="card-title text-primary">A. Data Diri Anggota</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label for="applicant_name" class="form-label">1. Nama (sesuai KTP)</label><input type="text" class="form-control" id="applicant_name" name="applicant_name" required></div>
                        <div class="col-md-6"><label for="applicant_ba_number" class="form-label">No. Buku Anggota</label><input type="text" class="form-control" id="applicant_ba_number" name="applicant_ba_number"></div>
                        <div class="col-md-4"><label for="applicant_occupation" class="form-label">2. Pekerjaan</label><input type="text" class="form-control" id="applicant_occupation" name="applicant_occupation"></div>
                        <div class="col-md-4"><label for="applicant_position" class="form-label">Jabatan Sekarang</label><input type="text" class="form-control" id="applicant_position" name="applicant_position"></div>
                        <div class="col-md-4"><label for="applicant_phone" class="form-label">6. No. Telepon / HP</label><input type="tel" class="form-control" id="applicant_phone" name="applicant_phone"></div>
                        <div class="col-md-4"><label for="applicant_birth_place" class="form-label">3. Tempat Lahir</label><input type="text" class="form-control" id="applicant_birth_place" name="applicant_birth_place"></div>
                        <div class="col-md-4"><label for="applicant_birth_date" class="form-label">Tgl Lahir</label><input type="date" class="form-control" id="applicant_birth_date" name="applicant_birth_date"></div>
                        <div class="col-md-4">
                            <label class="form-label">Jenis Kelamin</label>
                            <select class="form-select" name="applicant_gender">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label for="applicant_ktp_address" class="form-label">4. Alamat Rumah (KTP)</label><textarea class="form-control" id="applicant_ktp_address" name="applicant_ktp_address" rows="2"></textarea></div>
                        
                        <div class="col-md-6">
                            <label for="applicant_current_address" class="form-label">5. Alamat Tinggal saat ini</label>
                            <textarea class="form-control" id="applicant_current_address" name="applicant_current_address" rows="2"></textarea>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="copyAddressCheck">
                                <label class="form-check-label" for="copyAddressCheck" style="font-size: 0.9em;">
                                    Sama dengan Alamat KTP
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="applicant_marital_status">
                                <option value="Tidak Kawin">Tidak Kawin</option>
                                <option value="Kawin">Kawin</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="card-title text-primary">B. Data Suami / Istri</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label for="spouse_name" class="form-label">7. Nama</label><input type="text" class="form-control" id="spouse_name" name="spouse_name"></div>
                        <div class="col-md-6"><label for="spouse_ba_number" class="form-label">No. Buku Anggota</label><input type="text" class="form-control" id="spouse_ba_number" name="spouse_ba_number"></div>
                        <div class="col-md-4"><label for="spouse_occupation" class="form-label">8. Pekerjaan</label><input type="text" class="form-control" id="spouse_occupation" name="spouse_occupation"></div>
                        <div class="col-md-4"><label for="spouse_position" class="form-label">Jabatan Sekarang</label><input type="text" class="form-control" id="spouse_position" name="spouse_position"></div>
                        <div class="col-md-4"><label for="spouse_work_phone" class="form-label">No. Telp. Perusahaan</label><input type="tel" class="form-control" id="spouse_work_phone" name="spouse_work_phone"></div>
                        <div class="col-md-12"><label for="spouse_work_address" class="form-label">9. Alamat tempat kerja</label><textarea class="form-control" id="spouse_work_address" name="spouse_work_address" rows="2" maxlength="37"></textarea></div>
                    </div>

                    <hr class="my-4">
                    <h5 class="card-title text-primary">C. Data Keluarga Terdekat</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label for="emergency_contact_name" class="form-label">10. Nama</label><input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name"></div>
                        <div class="col-md-4"><label for="emergency_contact_phone" class="form-label">No. Telp.</label><input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone"></div>
                        <div class="col-md-4"><label for="emergency_contact_relation" class="form-label">12. Hubungan dgn Pemohon</label><input type="text" class="form-control" id="emergency_contact_relation" name="emergency_contact_relation"></div>
                        <div class="col-md-12"><label for="emergency_contact_address" class="form-label">11. Alamat</label><textarea class="form-control" id="emergency_contact_address" name="emergency_contact_address" rows="2" maxlength="80"></textarea></div>
                    </div>

                    <hr class="my-4">
                    <h5 class="card-title text-primary">D. Data Keuangan Anggota</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label for="financial_saving_saham" class="form-label">13. Saldo Simpanan Saham (Rp)</label><input type="text" class="form-control rupiah" id="financial_saving_saham" name="financial_saving_saham"></div>
                        <div class="col-md-6"><label for="financial_saving_megapolitan" class="form-label">14. Saldo Simpanan Megapolitan (Rp)</label><input type="text" class="form-control rupiah" id="financial_saving_megapolitan" name="financial_saving_megapolitan"></div>
                        <div class="col-md-6"><label for="financial_saving_padanan" class="form-label">15. Saldo Simpanan Padanan (Rp)</label><input type="text" class="form-control rupiah" id="financial_saving_padanan" name="financial_saving_padanan"></div>
                        <div class="col-md-6"><label for="financial_remaining_loan" class="form-label">16. Sisa Pinjaman (Rp)</label><input type="text" class="form-control rupiah" id="financial_remaining_loan" name="financial_remaining_loan"></div>
                        <div class="col-md-6"><label for="financial_other_loan" class="form-label">17. Pinjaman ditempat Lain (Rp)</label><input type="text" class="form-control rupiah" id="financial_other_loan" name="financial_other_loan"></div>
                        <div class="col-md-6"><label for="financial_other_savings" class="form-label">18. Simpanan lainnya (Rp)</label><input type="text" class="form-control rupiah" id="financial_other_savings" name="financial_other_savings"></div>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="card-title text-primary">E. Pinjaman Yang Dimohon</h5>
                    <div class="row g-3">
                        <div class="col-md-4"><label for="loan_amount_requested" class="form-label">19. Jumlah Permohonan Pinjaman (Rp)</label><input type="text" class="form-control rupiah" id="loan_amount_requested" name="loan_amount_requested" required></div>
                        
                        <div class="col-md-4"><label for="loan_term_months" class="form-label">Jangka Waktu (Bulan)</label><input type="number" class="form-control" id="loan_term_months" name="loan_term_months" required></div>
                        
                        <div class="col-md-4">
                            <label for="loan_type" class="form-label">20. Jenis Pinjaman</label>
                            <select name="loan_type" id="loan_type" class="form-select" required>
                                <option value="">-- Pilih Produk --</option>
                                <option value="Pinjaman Produktif">Pinjaman Produktif</option>
                                <option value="Pinjaman Rumah Tangga">Pinjaman Rumah Tangga</option>
                                <option value="Pinjaman Kendaraan - Motor">Pinjaman Kendaraan - Motor</option>
                                <option value="Pinjaman Kendaraan - Mobil">Pinjaman Kendaraan - Mobil</option>
                                <option value="Pinjaman Rumah">Pinjaman Rumah</option>
                                <option value="Pinjaman Kavling Tanah">Pinjaman Kavling Tanah</option>
                                <option value="Pinjaman Pendidikan">Pinjaman Pendidikan</option>
                                <option value="Pinjaman Hari Raya">Pinjaman Hari Raya</option>
                                <option value="Pinjaman Wisata">Pinjaman Wisata</option>
                                <option value="Pinjaman Menambah Aset">Pinjaman Menambah Aset</option>
                                <option value="Pinjaman Mikro Gratia">Pinjaman Mikro Gratia</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12"><label for="loan_purpose" class="form-label">21. Tujuan Pinjaman</label><textarea class="form-control" id="loan_purpose" name="loan_purpose" rows="2"></textarea></div>
                        
                        <div class="col-md-12"><label for="loan_collateral_type" class="form-label">22. Jaminan yang akan diserahkan</label><input type="text" class="form-control" id="loan_collateral_type" name="loan_collateral_type"></div>
                        
                        <div class="col-md-4"><label for="loan_collateral_owner" class="form-label">23. Pemilik jaminan</label><input type="text" class="form-control" id="loan_collateral_owner" name="loan_collateral_owner"></div>
                        
                        <div class="col-md-4"><label for="loan_collateral_status" class="form-label">Status Jaminan</label><input type="text" class="form-control" id="loan_collateral_status" name="loan_collateral_status"></div>
                        
                        <div class="col-md-4"><label for="loan_collateral_value" class="form-label">Harga Jaminan (Rp)</label><input type="text" class="form-control rupiah" id="loan_collateral_value" name="loan_collateral_value"></div>
                        
                        <div class="col-md-6"><label for="loan_collateral_location" class="form-label">24. Lokasi/kondisi</label><input type="text" class="form-control" id="loan_collateral_location" name="loan_collateral_location"></div>
                        
                        <div class="col-md-6"><label for="loan_monthly_payment_capacity" class="form-label">25. Kemampuan Bayar Bulanan (Rp)</label><input type="text" class="form-control rupiah" id="loan_monthly_payment_capacity" name="loan_monthly_payment_capacity"></div>
                    </div>

                    <div style="display:none;">
                        <input type="hidden" name="sales_monthly" value="0">
                        <input type="hidden" name="liability_productive_loan" value="0">
                    </div>
                    
                    <hr class="my-4">
                    <div class="d-flex justify-content-end mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-2"></i> Simpan Permohonan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Fungsi untuk mengkonversi angka menjadi format Rupiah (dengan titik)
        function formatNumber(n) {
            n = n.replace(/[^0-9-]/g, ''); 
            return n.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Fungsi untuk membersihkan angka (menghilangkan titik)
        function cleanNumber(n) {
            return n.replace(/[^0-9]/g, '');
        }

        // Terapkan format saat input diubah (event keyup)
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

        
        // === PERUBAHAN JAVASCRIPT DIMULAI DI SINI ===
        $('#copyAddressCheck').on('change', function() {
            const ktpAddress = $('#applicant_ktp_address').val();
            const currentAddressField = $('#applicant_current_address');

            if ($(this).is(':checked')) {
                // 1. Salin value
                currentAddressField.val(ktpAddress);
                // 2. Buat field read-only
                currentAddressField.prop('readonly', true);
            } else {
                // 1. Kosongkan value
                currentAddressField.val('');
                // 2. Buat field bisa diedit lagi
                currentAddressField.prop('readonly', false);
            }
        });
        
        // Listener tambahan: Jika user mengubah alamat KTP SETELAH mencentang,
        // alamat tinggal saat ini akan ikut ter-update.
        $('#applicant_ktp_address').on('keyup', function() {
            if ($('#copyAddressCheck').is(':checked')) {
                $('#applicant_current_address').val($(this).val());
            }
        });
        // === PERUBAHAN JAVASCRIPT SELESAI ===
        

        // Pastikan nilai yang dikirim ke server adalah angka murni (tanpa titik)
        $('#loanForm').on('submit', function() {
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