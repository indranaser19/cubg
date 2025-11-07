<?php
// Lokasi: /cubg/loans/process_create.php

require_once '../middleware/check_auth.php';
authorize(['superadmin', 'credit_officer']);
require_once '../config/db.php';

// Pastikan ini adalah method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari session
    $created_by_user_id = $_SESSION['user_id'];
    
    // Tentukan branch_id berdasarkan role
    if ($_SESSION['role'] == 'superadmin') {
        $branch_id = $_POST['branch_id']; // Superadmin bisa memilih
    } else {
        $branch_id = $_SESSION['branch_id']; // Role lain otomatis
    }

    // Fungsi untuk membersihkan input nominal (menghapus titik/koma)
    // Walaupun JS sudah membersihkan, ini adalah pengaman di sisi server
    function clean_decimal($val) {
        if (empty($val)) return null;
        return preg_replace('/[^\d.]/', '', $val);
    }
    
    // Mulai transaksi database
    $pdo->beginTransaction();

    try {
        // ================== LOGIKA UPLOAD FILE TELAH DIHAPUS ==================
        // Tidak ada lagi $uploaded_files_json
        // ======================================================================

        // 6. Simpan data utama ke tabel 'loan_applications'
        $sql = "INSERT INTO loan_applications (
                    branch_id, created_by_user_id, application_date, applicant_name, 
                    applicant_ba_number, applicant_occupation, applicant_position, applicant_phone, 
                    applicant_birth_place, applicant_birth_date, applicant_gender, 
                    applicant_ktp_address, applicant_current_address, applicant_marital_status, 
                    spouse_name, spouse_ba_number, spouse_occupation, spouse_position, 
                    spouse_work_phone, spouse_work_address, emergency_contact_name, 
                    emergency_contact_phone, emergency_contact_relation, emergency_contact_address, 
                    financial_saving_saham, financial_saving_megapolitan, financial_saving_padanan, 
                    financial_remaining_loan, financial_other_loan, financial_other_savings, 
                    loan_amount_requested, loan_term_months, loan_type, loan_purpose, 
                    loan_collateral_type, loan_collateral_owner, loan_collateral_status, 
                    loan_collateral_value, loan_collateral_location, loan_monthly_payment_capacity,
                    status_id 
                ) VALUES (
                    :branch_id, :created_by_user_id, :application_date, :applicant_name, 
                    :applicant_ba_number, :applicant_occupation, :applicant_position, :applicant_phone, 
                    :applicant_birth_place, :applicant_birth_date, :applicant_gender, 
                    :applicant_ktp_address, :applicant_current_address, :applicant_marital_status, 
                    :spouse_name, :spouse_ba_number, :spouse_occupation, :spouse_position, 
                    :spouse_work_phone, :spouse_work_address, :emergency_contact_name, 
                    :emergency_contact_phone, :emergency_contact_relation, :emergency_contact_address, 
                    :financial_saving_saham, :financial_saving_megapolitan, :financial_saving_padanan, 
                    :financial_remaining_loan, :financial_other_loan, :financial_other_savings, 
                    :loan_amount_requested, :loan_term_months, :loan_type, :loan_purpose, 
                    :loan_collateral_type, :loan_collateral_owner, :loan_collateral_status, 
                    :loan_collateral_value, :loan_collateral_location, :loan_monthly_payment_capacity,
                    1 -- Status '1' = Baru Dibuat
                )";

        $stmt_main = $pdo->prepare($sql);
        
        // Bind parameter untuk tabel utama
        $params = [
            ':branch_id' => $branch_id,
            ':created_by_user_id' => $created_by_user_id,
            ':application_date' => $_POST['application_date'],
            ':applicant_name' => $_POST['applicant_name'],
            ':applicant_ba_number' => $_POST['applicant_ba_number'] ?? null,
            ':applicant_occupation' => $_POST['applicant_occupation'] ?? null,
            ':applicant_position' => $_POST['applicant_position'] ?? null,
            ':applicant_phone' => $_POST['applicant_phone'] ?? null,
            ':applicant_birth_place' => $_POST['applicant_birth_place'] ?? null,
            ':applicant_birth_date' => !empty($_POST['applicant_birth_date']) ? $_POST['applicant_birth_date'] : null,
            ':applicant_gender' => $_POST['applicant_gender'] ?? null,
            ':applicant_ktp_address' => $_POST['applicant_ktp_address'] ?? null,
            ':applicant_current_address' => $_POST['applicant_current_address'] ?? null,
            ':applicant_marital_status' => $_POST['applicant_marital_status'] ?? null,
            ':spouse_name' => $_POST['spouse_name'] ?? null,
            ':spouse_ba_number' => $_POST['spouse_ba_number'] ?? null,
            ':spouse_occupation' => $_POST['spouse_occupation'] ?? null,
            ':spouse_position' => $_POST['spouse_position'] ?? null,
            ':spouse_work_phone' => $_POST['spouse_work_phone'] ?? null,
            ':spouse_work_address' => $_POST['spouse_work_address'] ?? null,
            ':emergency_contact_name' => $_POST['emergency_contact_name'] ?? null,
            ':emergency_contact_phone' => $_POST['emergency_contact_phone'] ?? null,
            ':emergency_contact_relation' => $_POST['emergency_contact_relation'] ?? null,
            ':emergency_contact_address' => $_POST['emergency_contact_address'] ?? null,
            ':financial_saving_saham' => clean_decimal($_POST['financial_saving_saham']),
            ':financial_saving_megapolitan' => clean_decimal($_POST['financial_saving_megapolitan']),
            ':financial_saving_padanan' => clean_decimal($_POST['financial_saving_padanan']),
            ':financial_remaining_loan' => clean_decimal($_POST['financial_remaining_loan']),
            ':financial_other_loan' => clean_decimal($_POST['financial_other_loan']),
            ':financial_other_savings' => $_POST['financial_other_savings'] ?? null,
            ':loan_amount_requested' => clean_decimal($_POST['loan_amount_requested']),
            ':loan_term_months' => $_POST['loan_term_months'],
            ':loan_type' => $_POST['loan_type'] ?? null,
            ':loan_purpose' => $_POST['loan_purpose'] ?? null,
            ':loan_collateral_type' => $_POST['loan_collateral_type'] ?? null,
            ':loan_collateral_owner' => $_POST['loan_collateral_owner'] ?? null,
            ':loan_collateral_status' => $_POST['loan_collateral_status'] ?? null,
            ':loan_collateral_value' => clean_decimal($_POST['loan_collateral_value']),
            ':loan_collateral_location' => $_POST['loan_collateral_location'] ?? null,
            ':loan_monthly_payment_capacity' => clean_decimal($_POST['loan_monthly_payment_capacity'])
            // ':supporting_documents_json' => $uploaded_files_json, // <-- DIHAPUS
        ];
        
        $stmt_main->execute($params);
        $loan_app_id = $pdo->lastInsertId(); // Dapatkan ID dari data yang baru saja disimpan

        // 7. Simpan data opsional (Halaman 2 & 3)
        // (Logika ini tetap sama)
        $sql_details = "INSERT INTO loan_application_details (
                            loan_app_id, sales_monthly, sales_total, cogs_monthly, cogs_total, 
                            op_payroll_monthly, op_payroll_total, op_rent_monthly, op_rent_total, 
                            op_utilities_monthly, op_utilities_total, op_transport_monthly, op_transport_total, 
                            op_admin_monthly, op_admin_total, op_maintenance_monthly, op_maintenance_total, 
                            op_promotion_monthly, op_promotion_total, modal_cubg_loan, modal_equity, 
                            modal_other_source, modal_other_amount, asset_cash, asset_bank_savings, 
                            asset_cu_daily_savings, asset_current_other, invest_megapolitan_savings, 
                            invest_other_cu_savings, invest_business_assets, invest_commercial_property, 
                            invest_other, asset_home_value, asset_home_contents_value, asset_vehicle_value, 
                            asset_jewelry_value, asset_personal_other, liability_cu_short_term, 
                            liability_credit_card_kta, liability_short_term_other, liability_housing_loan, 
                            liability_vehicle_loan, liability_consumptive_loan, liability_productive_loan
                        ) VALUES (
                            :loan_app_id, :sales_monthly, :sales_total, :cogs_monthly, :cogs_total, 
                            :op_payroll_monthly, :op_payroll_total, :op_rent_monthly, :op_rent_total, 
                            :op_utilities_monthly, :op_utilities_total, :op_transport_monthly, :op_transport_total, 
                            :op_admin_monthly, :op_admin_total, :op_maintenance_monthly, :op_maintenance_total, 
                            :op_promotion_monthly, :op_promotion_total, :modal_cubg_loan, :modal_equity, 
                            :modal_other_source, :modal_other_amount, :asset_cash, :asset_bank_savings, 
                            :asset_cu_daily_savings, :asset_current_other, :invest_megapolitan_savings, 
                            :invest_other_cu_savings, :invest_business_assets, :invest_commercial_property, 
                            :invest_other, :asset_home_value, :asset_home_contents_value, :asset_vehicle_value, 
                            :asset_jewelry_value, :asset_personal_other, :liability_cu_short_term, 
                            :liability_credit_card_kta, :liability_short_term_other, :liability_housing_loan, 
                            :liability_vehicle_loan, :liability_consumptive_loan, :liability_productive_loan
                        )";
        
        $stmt_details = $pdo->prepare($sql_details);
        
        $details_params = [
            ':loan_app_id' => $loan_app_id,
            ':sales_monthly' => clean_decimal($_POST['sales_monthly']),
            ':sales_total' => clean_decimal($_POST['sales_total']),
            ':cogs_monthly' => clean_decimal($_POST['cogs_monthly']),
            ':cogs_total' => clean_decimal($_POST['cogs_total']),
            ':op_payroll_monthly' => clean_decimal($_POST['op_payroll_monthly']),
            ':op_payroll_total' => clean_decimal($_POST['op_payroll_total']),
            ':op_rent_monthly' => clean_decimal($_POST['op_rent_monthly']),
            ':op_rent_total' => clean_decimal($_POST['op_rent_total']),
            ':op_utilities_monthly' => clean_decimal($_POST['op_utilities_monthly']),
            ':op_utilities_total' => clean_decimal($_POST['op_utilities_total']),
            ':op_transport_monthly' => clean_decimal($_POST['op_transport_monthly']),
            ':op_transport_total' => clean_decimal($_POST['op_transport_total']),
            ':op_admin_monthly' => clean_decimal($_POST['op_admin_monthly']),
            ':op_admin_total' => clean_decimal($_POST['op_admin_total']),
            ':op_maintenance_monthly' => clean_decimal($_POST['op_maintenance_monthly']),
            ':op_maintenance_total' => clean_decimal($_POST['op_maintenance_total']),
            ':op_promotion_monthly' => clean_decimal($_POST['op_promotion_monthly']),
            ':op_promotion_total' => clean_decimal($_POST['op_promotion_total']),
            ':modal_cubg_loan' => clean_decimal($_POST['modal_cubg_loan']),
            ':modal_equity' => clean_decimal($_POST['modal_equity']),
            ':modal_other_source' => $_POST['modal_other_source'] ?? null,
            ':modal_other_amount' => clean_decimal($_POST['modal_other_amount']),
            ':asset_cash' => clean_decimal($_POST['asset_cash']),
            ':asset_bank_savings' => clean_decimal($_POST['asset_bank_savings']),
            ':asset_cu_daily_savings' => clean_decimal($_POST['asset_cu_daily_savings']),
            ':asset_current_other' => clean_decimal($_POST['asset_current_other']),
            ':invest_megapolitan_savings' => clean_decimal($_POST['invest_megapolitan_savings']),
            ':invest_other_cu_savings' => clean_decimal($_POST['invest_other_cu_savings']),
            ':invest_business_assets' => clean_decimal($_POST['invest_business_assets']),
            ':invest_commercial_property' => clean_decimal($_POST['invest_commercial_property']),
            ':invest_other' => clean_decimal($_POST['invest_other']),
            ':asset_home_value' => clean_decimal($_POST['asset_home_value']),
            ':asset_home_contents_value' => clean_decimal($_POST['asset_home_contents_value']),
            ':asset_vehicle_value' => clean_decimal($_POST['asset_vehicle_value']),
            ':asset_jewelry_value' => clean_decimal($_POST['asset_jewelry_value']),
            ':asset_personal_other' => clean_decimal($_POST['asset_personal_other']),
            ':liability_cu_short_term' => clean_decimal($_POST['liability_cu_short_term']),
            ':liability_credit_card_kta' => clean_decimal($_POST['liability_credit_card_kta']),
            ':liability_short_term_other' => clean_decimal($_POST['liability_short_term_other']),
            ':liability_housing_loan' => clean_decimal($_POST['liability_housing_loan']),
            ':liability_vehicle_loan' => clean_decimal($_POST['liability_vehicle_loan']),
            ':liability_consumptive_loan' => clean_decimal($_POST['liability_consumptive_loan']),
            ':liability_productive_loan' => clean_decimal($_POST['liability_productive_loan'])
        ];
        
        $stmt_details->execute($details_params);

        // 8. Commit transaksi
        $pdo->commit();

        // 9. Redirect ke halaman tracking (index)
        header("location: index.php?success=Permohonan pinjaman baru berhasil dibuat.");

    } catch (Exception $e) {
        // Jika ada error, rollback
        $pdo->rollBack();
        // Redirect kembali ke form dengan pesan error
        header("location: create.php?error=" . urlencode("Terjadi kesalahan: " . $e->getMessage()));
    }

} else {
    // Jika bukan POST, tendang
    header("location: create.php");
    exit;
}
?>