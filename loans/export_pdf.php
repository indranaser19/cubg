<?php
// Lokasi: cubg/loans/export_pdf.php

require_once '../config/db.php';
require_once '../middleware/check_auth.php';

// TCPDF harus dimuat terlebih dahulu.
require_once('../tcpdf/tcpdf.php');

// FPDI Autoload (Asumsi path ini sudah benar)
require_once('../fpdi/src/autoload.php'); 

// Kita menggunakan kelas FPDI dengan namespace penuhnya
use setasign\Fpdi\Tcpdf\Fpdi;

authorize(['superadmin', 'credit_officer', 'branch_user']);

if (!isset($_GET['id'])) {
    die("ID Permohonan tidak ditemukan.");
}

$loan_id = (int)$_GET['id'];

// --- Fungsi Pembantu ---
function e($value) {
    return $value ?? '';
}
function formatRupiah($amount) {
    // Digunakan untuk kolom yang memiliki label Rp. di formulir
    if (empty($amount) || $amount == 0) return '0'; 
    return number_format($amount, 0, ',', '.');
}

// FUNGSI BARU UNTUK FORMAT TANGGAL INDONESIA
function formatTanggalID($dateStr) {
    if (empty($dateStr)) return '';
    
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    try {
        $dt = new DateTime($dateStr);
        $tanggal = $dt->format('d'); // 'd' (01-31)
        $bulan_angka = (int)$dt->format('m');
        $tahun = $dt->format('Y');
        
        return $tanggal . ' ' . $bulan[$bulan_angka] . ' ' . $tahun;
    } catch (Exception $e) {
        return ''; // Handle jika format tanggal salah
    }
}


// --- Ambil Data Pinjaman (Gabungan la dan ld) ---
// PERBAIKAN SQL: Tambahkan la.loan_term_months dan la.applicant_birth_date SETELAH ld.*
$sql = "SELECT 
            la.*, 
            ld.*, 
            b.name as branch_name, 
            u.full_name as created_by_user,
            la.loan_term_months,
            la.applicant_birth_date
        FROM 
            loan_applications la
        LEFT JOIN 
            loan_application_details ld ON la.id = ld.loan_app_id
        JOIN 
            branches b ON la.branch_id = b.id
        JOIN 
            users u ON la.created_by_user_id = u.id
        WHERE 
            la.id = :loan_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':loan_id' => $loan_id]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    die("Data permohonan tidak ditemukan.");
}


// --- Implementasi FPDI dengan TCPDF ---
$pdf = new Fpdi(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

// Set metadata
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Formulir Pinjaman #' . $loan_id);
$pdf->SetAutoPageBreak(FALSE, 0); 
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// --- Path ke Template PDF Asli ---
$template_path = '../templates/01. FORM_PERMOHONAN_PINJAMAN_CUBG.pdf'; 

// Penanganan Error Kompresi PDF yang sering terjadi
try {
    $pdf->setSourceFile($template_path); 
} catch (\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException $e) {
    die("Error Fatal: Gagal memuat template PDF. Solusi: Harap buka file template PDF ('01...') di editor PDF (mis. Adobe Acrobat) dan simpan ulang (*Save As*) sebagai PDF versi lama (mis. 1.4 atau PDF/A-1b) untuk menghapus kompresi yang tidak didukung FPDI.");
}


// --- Kode Ekspor KHUSUS Halaman 1 ---

// 1. Impor hanya halaman 1
$tplId = $pdf->importPage(1);

// 2. Tambahkan halaman baru (Page 1)
$pdf->AddPage();

// 3. Gunakan halaman template sebagai latar belakang (sesuaikan ukuran)
$pdf->useTemplate($tplId, 0, 0, 210, 297); 

// Set font dan warna untuk data
$pdf->SetTextColor(0, 0, 0); 
$pdf->SetFont('helvetica', '', 8);

// --- PENEMPATAN DATA (Kritis: Koordinat X, Y) ---

$date_formatted = (new DateTime(e($loan['application_date'])))->format('d/m/Y');

// HEADER
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(84, 39.5); $pdf->Write(0, $date_formatted);
$pdf->SetXY(158, 39.5); $pdf->Write(0, e($loan['branch_name']));

$pdf->SetFont('helvetica', '', 8);

// A. DATA DIRI ANGGOTA
$pdf->SetXY(47, 54); $pdf->Write(0, e($loan['applicant_name']));
$pdf->SetXY(158, 54); $pdf->Write(0, e($loan['applicant_ba_number']));

$pdf->SetXY(47, 60); $pdf->Write(0, e($loan['applicant_occupation']));
$pdf->SetXY(158, 60); $pdf->Write(0, e($loan['applicant_position']));

// Penulisan Tempat/Tgl Lahir
// PERUBAHAN DI SINI: Gunakan fungsi formatTanggalID
$birth_date_formatted = formatTanggalID(e($loan['applicant_birth_date']));
$pdf->SetXY(47, 65); $pdf->Write(0, e($loan['applicant_birth_place']) . ', ' . $birth_date_formatted);

$gender = (e($loan['applicant_gender']) == 'Laki-laki') ? 'Laki-laki' : 'Perempuan';
$pdf->SetXY(158, 65); $pdf->Write(0, $gender);
$pdf->SetXY(158, 90.5); $pdf->Write(0, e($loan['applicant_marital_status']));

// Alamat Rumah (KTP) - MultiCell
$pdf->SetXY(47, 71); $pdf->MultiCell(140, 4, e($loan['applicant_ktp_address']), 0, 'L', 0, 1, 47, 71, true, 0, false, true, 0, 'T', false);

// Alamat Tinggal Saat Ini - MultiCell
$pdf->SetXY(47, 81.5); $pdf->MultiCell(140, 4, e($loan['applicant_current_address']), 0, 'L', 0, 1, 47, 81.5, true, 0, false, true, 0, 'T', false);

$pdf->SetXY(47, 91); $pdf->Write(0, e($loan['applicant_phone']));

// B. DATA SUAMI/ISTRI
$pdf->SetXY(47, 102); $pdf->Write(0, e($loan['spouse_name']));
$pdf->SetXY(158, 102); $pdf->Write(0, e($loan['spouse_ba_number']));

$pdf->SetXY(47, 107); $pdf->Write(0, e($loan['spouse_occupation']));
$pdf->SetXY(134.5, 107); $pdf->Write(0, e($loan['spouse_position']));

$pdf->SetXY(47, 112); $pdf->Write(0, e($loan['spouse_work_address'])); // Alamat Kerja (diletakkan di baris ke-9)
$pdf->SetXY(134.5, 113); $pdf->Write(0, e($loan['spouse_work_phone'])); // No. Telp. Perusahaan (diletakkan di baris ke-9)

// C. DATA KELUARGA TERDEKAT 
$pdf->SetXY(47, 123); $pdf->Write(0, e($loan['emergency_contact_name'])); 
$pdf->SetXY(47, 128); $pdf->Write(0, e($loan['emergency_contact_address']));
$pdf->SetXY(134.5, 133); $pdf->Write(0, e($loan['emergency_contact_phone']));
$pdf->SetXY(47, 133); $pdf->Write(0, e($loan['emergency_contact_relation']));

// D. DATA KEUANGAN ANGGOTA
$pdf->SetXY(65, 144); $pdf->Write(0, formatRupiah($loan['financial_saving_saham']));
$pdf->SetXY(65, 149); $pdf->Write(0, formatRupiah($loan['financial_saving_megapolitan']));
$pdf->SetXY(65, 154); $pdf->Write(0, formatRupiah($loan['financial_saving_padanan']));
$pdf->SetXY(155, 144); $pdf->Write(0, formatRupiah($loan['financial_remaining_loan']));
$pdf->SetXY(155, 149); $pdf->Write(0, formatRupiah($loan['financial_other_loan']));
$pdf->SetXY(134.5, 154); $pdf->Write(0, formatRupiah($loan['financial_other_savings'])); // Diubah juga ke formatRupiah

// E. PINJAMAN YANG DIMOHON
$pdf->SetXY(66, 164); $pdf->Write(0, formatRupiah($loan['loan_amount_requested']));
$pdf->SetXY(155, 174.5); $pdf->Write(0, e($loan['loan_term_months'])); // Ini akan mengambil 24 (setelah perbaikan SQL)
$pdf->SetXY(61, 174.5); $pdf->Write(0, e($loan['loan_type']));
$pdf->SetXY(61, 180); $pdf->Write(0, e($loan['loan_purpose']));
$pdf->SetXY(61, 185.5); $pdf->Write(0, e($loan['loan_collateral_type']));
$pdf->SetXY(61, 191); $pdf->Write(0, e($loan['loan_collateral_owner']));
$pdf->SetXY(132, 191); $pdf->Write(0, e($loan['loan_collateral_status']));
$pdf->SetXY(132, 196.5); $pdf->Write(0, formatRupiah($loan['loan_collateral_value']));
$pdf->SetXY(61, 196); $pdf->Write(0, e($loan['loan_collateral_location']));
$pdf->SetXY(66, 201.5); $pdf->Write(0, formatRupiah($loan['loan_monthly_payment_capacity']));

// Tanda Tangan
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(19, 231.5); $pdf->Write(0, e($loan['applicant_name']));
$pdf->SetXY(27, 235); $pdf->Write(0, e($loan['applicant_ba_number']));
$pdf->SetXY(67, 231.5); $pdf->Write(0, e($loan['spouse_name']));


// --- Output PDF ---
$pdf->Output('Permohonan_Pinjaman_' . $loan_id . '.pdf', 'I');
?>