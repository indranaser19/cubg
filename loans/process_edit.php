<?php
// Lokasi: cubg/loans/process_edit.php

require_once '../middleware/check_auth.php';
// Hanya pembuat atau superadmin yang boleh edit
authorize(['superadmin', 'credit_officer', 'branch_user']); 

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $loan_id = (int)$_POST['loan_id'];
    
    try {
        $pdo->beginTransaction();

        // --- 1. Ambil Data Lama & Cek Otorisasi (Tetap) ---
        $stmt_check = $pdo->prepare("SELECT created_by_user_id, branch_id FROM loan_applications WHERE id = ?");
        $stmt_check->execute([$loan_id]);
        $loan_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$loan_data) {
            throw new Exception("Data pinjaman tidak ditemukan.");
        }
        if (!($_SESSION['role'] == 'superadmin' || $_SESSION['user_id'] == $loan_data['created_by_user_id'])) {
            throw new Exception("Anda tidak memiliki hak akses untuk mengedit data ini.");
        }

        // --- PERBAIKAN 1: Fungsi Helper untuk membersihkan format Rupiah ---
        $applicant_birth_date = empty($_POST['applicant_birth_date']) ? NULL : $_POST['applicant_birth_date'];

        // Fungsi helper untuk membersihkan nilai numerik (Rupiah)
        // Ini akan menghapus titik, koma, Rp, spasi, dll.
        $clean_numeric = function($value) {
            // 1. Hapus semua karakter selain angka
            $cleaned = preg_replace('/[^0-9]/', '', $value);
            // 2. Jika hasilnya string kosong (setelah dibersihkan), kembalikan 0.
            return (empty($cleaned) && $cleaned !== '0') ? 0 : (int)$cleaned;
        };

        // --- 2. Update loan_applications (Tabel 1) ---
        // Query SQL Anda sudah benar (tidak perlu diubah)
        $sql_app = "UPDATE loan_applications SET
            branch_id = ?, application_date = ?, 
            applicant_name = ?, applicant_ba_number = ?, applicant_occupation = ?, applicant_position = ?, 
            applicant_phone = ?, applicant_birth_place = ?, applicant_birth_date = ?, applicant_gender = ?, 
            applicant_ktp_address = ?, applicant_current_address = ?, applicant_marital_status = ?, 
            spouse_name = ?, spouse_ba_number = ?, spouse_occupation = ?, spouse_position = ?, 
            spouse_work_phone = ?, spouse_work_address = ?, 
            emergency_contact_name = ?, emergency_contact_phone = ?, emergency_contact_relation = ?, emergency_contact_address = ?, 
            financial_saving_saham = ?, financial_saving_megapolitan = ?, financial_saving_padanan = ?, 
            financial_remaining_loan = ?, financial_other_loan = ?, financial_other_savings = ?, 
            loan_amount_requested = ?, loan_term_months = ?, loan_type = ?, loan_purpose = ?, 
            loan_collateral_type = ?, loan_collateral_owner = ?, loan_collateral_status = ?, 
            loan_collateral_value = ?, loan_collateral_location = ?, loan_monthly_payment_capacity = ?
            WHERE id = ?";

        $stmt_app = $pdo->prepare($sql_app);
        
        // Urutan parameter harus sama dengan urutan di SQL di atas
        $stmt_app->execute([
            (int)$_POST['branch_id'], 
            $_POST['application_date'], // Kolom ini wajib diisi
            $_POST['applicant_name'], 
            $_POST['applicant_ba_number'], 
            $_POST['applicant_occupation'], 
            $_POST['applicant_position'],
            $_POST['applicant_phone'], 
            $_POST['applicant_birth_place'], 
            $applicant_birth_date, // MENGGUNAKAN NILAI YANG SUDAH DIBERSIHKAN (NULL jika kosong)
            $_POST['applicant_gender'], 
            $_POST['applicant_ktp_address'], 
            $_POST['applicant_current_address'], 
            $_POST['applicant_marital_status'], 
            $_POST['spouse_name'], 
            $_POST['spouse_ba_number'], 
            $_POST['spouse_occupation'], 
            $_POST['spouse_position'], 
            $_POST['spouse_work_phone'], 
            $_POST['spouse_work_address'], 
            $_POST['emergency_contact_name'], 
            $_POST['emergency_contact_phone'], 
            $_POST['emergency_contact_relation'], 
            $_POST['emergency_contact_address'], 
            // Data Keuangan (SEMUA MENGGUNAKAN FUNGSI BARU $clean_numeric)
            $clean_numeric($_POST['financial_saving_saham']), 
            $clean_numeric($_POST['financial_saving_megapolitan']), 
            $clean_numeric($_POST['financial_saving_padanan']), 
            $clean_numeric($_POST['financial_remaining_loan']), 
            $clean_numeric($_POST['financial_other_loan']), 
            // --- PERBAIKAN 2: Terapkan $clean_numeric di sini ---
            $clean_numeric($_POST['financial_other_savings']),
            $clean_numeric($_POST['loan_amount_requested']), 
            $clean_numeric($_POST['loan_term_months']), 
            $_POST['loan_type'], 
            $_POST['loan_purpose'], 
            $_POST['loan_collateral_type'], 
            $_POST['loan_collateral_owner'], 
            $_POST['loan_collateral_status'], 
            $clean_numeric($_POST['loan_collateral_value']), 
            $_POST['loan_collateral_location'], 
            $clean_numeric($_POST['loan_monthly_payment_capacity']),
            $loan_id
        ]);

        // --- 3. Update loan_application_details (Tabel 2) ---
        // Bagian ini sudah menggunakan $clean_numeric, jadi seharusnya aman.
        
        $stmt_detail_check = $pdo->prepare("SELECT id FROM loan_application_details WHERE loan_app_id = ?");
        $stmt_detail_check->execute([$loan_id]);
        $detail_id_exists = $stmt_detail_check->fetchColumn();

        // Data untuk loan_application_details (di-clean untuk memastikan numerik)
        $detail_data = [
            // Laporan Usaha (18 fields)
            $clean_numeric($_POST['sales_monthly']), $clean_numeric($_POST['sales_total']), $clean_numeric($_POST['cogs_monthly']), $clean_numeric($_POST['cogs_total']), 
            $clean_numeric($_POST['op_payroll_monthly']), $clean_numeric($_POST['op_payroll_total']), $clean_numeric($_POST['op_rent_monthly']), $clean_numeric($_POST['op_rent_total']),
            $clean_numeric($_POST['op_utilities_monthly']), $clean_numeric($_POST['op_utilities_total']), $clean_numeric($_POST['op_transport_monthly']), $clean_numeric($_POST['op_transport_total']),
            $clean_numeric($_POST['op_admin_monthly']), $clean_numeric($_POST['op_admin_total']), $clean_numeric($_POST['op_maintenance_monthly']), $clean_numeric($_POST['op_maintenance_total']),
            $clean_numeric($_POST['op_promotion_monthly']), $clean_numeric($_POST['op_promotion_total']),
            // Modal Usaha (4 fields)
            $clean_numeric($_POST['modal_cubg_loan']), $clean_numeric($_POST['modal_equity']), $_POST['modal_other_source'], $clean_numeric($_POST['modal_other_amount']),
            // Aset (13 fields)
            $clean_numeric($_POST['asset_cash']), $clean_numeric($_POST['asset_bank_savings']), $clean_numeric($_POST['asset_cu_daily_savings']), $clean_numeric($_POST['asset_current_other']),
            $clean_numeric($_POST['invest_megapolitan_savings']), $clean_numeric($_POST['invest_other_cu_savings']), $clean_numeric($_POST['invest_business_assets']),
            $clean_numeric($_POST['invest_commercial_property']), $clean_numeric($_POST['invest_other']), 
            $clean_numeric($_POST['asset_home_value']), $clean_numeric($_POST['asset_home_contents_value']), $clean_numeric($_POST['asset_vehicle_value']), $clean_numeric($_POST['asset_jewelry_value']), $clean_numeric($_POST['asset_personal_other']),
            // Kewajiban (7 fields)
            $clean_numeric($_POST['liability_cu_short_term']), $clean_numeric($_POST['liability_credit_card_kta']), $clean_numeric($_POST['liability_short_term_other']),
            $clean_numeric($_POST['liability_housing_loan']), $clean_numeric($_POST['liability_vehicle_loan']), 
            $clean_numeric($_POST['liability_consumptive_loan']), $clean_numeric($_POST['liability_productive_loan']),

            $loan_id // loan_app_id di akhir
        ];
        
        // Daftar Kolom untuk INSERT/UPDATE (43 fields)
        $detail_cols = "sales_monthly, sales_total, cogs_monthly, cogs_total, op_payroll_monthly, op_payroll_total, op_rent_monthly, op_rent_total, op_utilities_monthly, op_utilities_total, op_transport_monthly, op_transport_total, op_admin_monthly, op_admin_total, op_maintenance_monthly, op_maintenance_total, op_promotion_monthly, op_promotion_total, modal_cubg_loan, modal_equity, modal_other_source, modal_other_amount, asset_cash, asset_bank_savings, asset_cu_daily_savings, asset_current_other, invest_megapolitan_savings, invest_other_cu_savings, invest_business_assets, invest_commercial_property, invest_other, asset_home_value, asset_home_contents_value, asset_vehicle_value, asset_jewelry_value, asset_personal_other, liability_cu_short_term, liability_credit_card_kta, liability_short_term_other, liability_housing_loan, liability_vehicle_loan, liability_consumptive_loan, liability_productive_loan";
        
        // String placeholder untuk values
        $num_detail_fields = count($detail_data) - 1; // Kurangi 1 karena $loan_id ditambahkan di akhir array
        
        // Buat array kolom detail tanpa loan_app_id di akhir (untuk UPDATE)
        $detail_cols_array = array_map('trim', explode(',', $detail_cols));
        $sql_detail_set = implode('=?, ', $detail_cols_array) . '=?';


        if ($detail_id_exists) {
            // Jika baris sudah ada, lakukan UPDATE 
            $sql_detail = "UPDATE loan_application_details SET {$sql_detail_set} WHERE loan_app_id = ?";
            
            // Urutan eksekusi harus sama dengan urutan di SQL: Data fields (43) + loan_app_id
            $stmt_detail = $pdo->prepare($sql_detail);
            $stmt_detail->execute($detail_data);

        } else {
            // Jika baris belum ada, lakukan INSERT 
            // Tambahkan 'loan_app_id' ke daftar kolom untuk INSERT
            $detail_cols_insert = $detail_cols . ", loan_app_id";

            // Tambahkan placeholder '?' untuk loan_app_id
            $sql_detail_placeholders_insert = implode(', ', array_fill(0, $num_detail_fields, '?')) . ", ?";

            $sql_detail = "INSERT INTO loan_application_details ({$detail_cols_insert}) VALUES ({$sql_detail_placeholders_insert})";
            
            $stmt_detail = $pdo->prepare($sql_detail);
            $stmt_detail->execute($detail_data);
        }

        $pdo->commit();

        header("location: view.php?id=$loan_id&success=Permohonan berhasil diperbarui.");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("location: edit.php?id=$loan_id&error=" . urlencode("Gagal menyimpan: " . $e->getMessage()));
        exit;
    }

} else {
    header("location: index.php");
    exit;
}
?>