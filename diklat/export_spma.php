<?php
// Lokasi: cubg/diklat/export_spma.php

require_once '../middleware/check_auth.php';
authorize(['superadmin', 'user_diklat']); 
require_once '../config/db.php';

// --- INCLUDES DARI REFERENSI ANDA ---
// 1. Muat TCPDF
require_once('../tcpdf/tcpdf.php');
// 2. Muat Autoloader FPDI
require_once('../fpdi/src/autoload.php');

// 3. Gunakan namespace FPDI yang kompatibel dengan TCPDF
use setasign\Fpdi\Tcpdf\Fpdi;
// ------------------------------------


// --- Fungsi Helper (diambil dari referensi Anda) ---
function e($value) {
    return $value ?? '';
}

function formatTanggalID($dateStr) {
    if (empty($dateStr)) return '';
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    try {
        $dt = new DateTime($dateStr);
        $tanggal = $dt->format('d');
        $bulan_angka = (int)$dt->format('m');
        $tahun = $dt->format('Y');
        return $tanggal . ' ' . $bulan[$bulan_angka] . ' ' . $tahun;
    } catch (Exception $e) {
        return '';
    }
}
// ------------------------------------


// --- 1. AMBIL DATA APLIKASI ---
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID tidak valid");
}

// Ambil data permohonan (fma_applications)
// Query a.* sudah otomatis mengambil field 'usaha'
$stmt = $pdo->prepare("SELECT a.*, b.name as branch_name 
                      FROM fma_applications a
                      LEFT JOIN branches b ON a.branch_id = b.id
                      WHERE a.id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die("Data tidak ditemukan");
}


// --- 2. INISIALISASI PDF (menggunakan Fpdi dari TCPDF) ---
$pdf = new Fpdi(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('SPMA - ' . e($app['nama_lengkap']));
$pdf->SetAutoPageBreak(FALSE, 0); 
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Path ke template SPMA Anda
$template_path = '../templates/SPMA.pdf';

try {
    $pageCount = $pdf->setSourceFile($template_path); 
} catch (\Exception $e) {
    die("Error Fatal: Gagal memuat template PDF '../templates/SPMA.pdf'. 
         Pastikan file ada dan tidak rusak. 
         Error: " . $e->getMessage());
}


// --- 3. PROSES HALAMAN 1 ---
$tplId1 = $pdf->importPage(1);
$pdf->AddPage();
$pdf->useTemplate($tplId1, 0, 0, 210, 297); // 0,0 = X,Y; 210,297 = Ukuran A4

// Set font
$pdf->SetTextColor(0, 0, 0); // Hitam
$pdf->SetFont('helvetica', '', 9); // Ukuran font 9pt


// Header (diisi oleh CUBG)
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(152, 59); $pdf->Write(0, e($app['branch_name'])); // Perkiraan Kantor Pelayanan

$pdf->SetFont('helvetica', '', 9);

// 1. Nama
$pdf->SetXY(75, 91.5); $pdf->Write(0, e($app['nama_lengkap']));
$pdf->SetXY(163, 92); $pdf->Write(0, e($app['nama_panggilan']));

// 4. Identitas
$pdf->SetXY(75, 109); $pdf->Write(0, e($app['no_ktp']));

// 5. Alamat KTP (Gunakan MultiCell untuk teks panjang)
$pdf->SetXY(75, 113.5); $pdf->MultiCell(120, 4, e($app['alamat_ktp']), 0, 'L');

// 6. Alamat Domisi
$pdf->SetXY(75, 125); $pdf->MultiCell(120, 4, e($app['alamat_domisili']), 0, 'L');

// 7. Tempat/Tgl Lahir
$pdf->SetXY(75, 156); $pdf->Write(0, e($app['tempat_lahir']));
$pdf->SetXY(100, 156); $pdf->Write(0, formatTanggalID(e($app['tanggal_lahir'])));

// 8. Agama (Ini check box, jadi kita tulis di sebelahnya)
$pdf->SetXY(75, 152.5); $pdf->Write(0, e($app['agama']));

// 9. Jenis Kelamin (Check box)
$pdf->SetXY(165, 156); $pdf->Write(0, e($app['jenis_kelamin']));

// 10. Nama Ibu
$pdf->SetXY(75, 160.5); $pdf->Write(0, e($app['nama_gadis_ibu_kandung']));

// 11. Status Kawin
$pdf->SetXY(75, 165.5); $pdf->Write(0, e($app['status_perkawinan']));
$pdf->SetXY(75, 170.5); $pdf->Write(0, e($app['nama_pasangan']));

// 12. Ahli Waris
$pdf->SetXY(30, 193); $pdf->Write(0, e($app['nama_ahli_waris']));
$pdf->SetXY(116, 193); $pdf->Write(0, e($app['hubungan_ahli_waris']));

// 15. Pendidikan (PDF #7)
$pdf->SetXY(75, 136); $pdf->Write(0, e($app['pendidikan_terakhir']));

// 13. Pekerjaan (PDF #8)
$pdf->SetXY(75, 142.5); $pdf->Write(0, e($app['pekerjaan']));


// 9. Usaha (PDF #9)

$pdf->SetXY(75, 147.5); $pdf->Write(0, e($app['usaha']));

// 16. Kontak
$pdf->SetXY(75, 228.5); $pdf->Write(0, e($app['no_telepon']));
$pdf->SetXY(130, 228.5); $pdf->Write(0, e($app['email']));

// 17. Alamat Kerja
$pdf->SetXY(75, 233.2); $pdf->MultiCell(135, 4, e($app['alamat_tempat_kerja']), 0, 'L');

// 18. Pendapatan 
$pdf->SetXY(75, 243); $pdf->Write(0, e($app['pendapatan_bulanan']));

// 19. Anggota CU Lain 
$pdf->SetXY(75, 251.5); $pdf->Write(0, e($app['anggota_cu_lain']));

$pdf->SetXY(75, 97.5); $pdf->Write(0, e($app['sumber_informasi']));

$pdf->SetXY(163, 97.5); $pdf->Write(0, e($app['nama_perekomendasi']));
// --- 4. PROSES HALAMAN 2 ---
$tplId2 = $pdf->importPage(2);
$pdf->AddPage();
$pdf->useTemplate($tplId2, 0, 0, 210, 297); 

$pdf->SetTextColor(0, 0, 0); 
$pdf->SetFont('helvetica', '', 9);



// --- 5. OUTPUT PDF ---
$nama_file = 'SPMA_' . preg_replace('/[^A-Za-z0-9]/', '_', $app['nama_lengkap']) . '.pdf';
$pdf->Output($nama_file, 'I'); // 'I' = Tampilkan di browser
exit;
?>