<?php
require_once '../middleware/check_auth.php';
authorize(['superadmin', 'user_diklat']); 

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// === 1. Ambil Semua Data dari Form ===
// Kita akan kumpulkan semua data dalam satu array agar rapi
$data = [
    'id' => $_POST['id'] ?? null,
    'status_permohonan' => $_POST['status_permohonan'] ?? 'Baru',
    'catatan_admin' => $_POST['catatan_admin'] ?? null,
    'branch_id' => $_POST['branch_id'] ?? null,
    'nama_lengkap' => $_POST['nama_lengkap'] ?? null,
    'nama_panggilan' => $_POST['nama_panggilan'] ?? null,
    'sumber_informasi' => $_POST['sumber_informasi'] ?? null,
    'nama_perekomendasi' => $_POST['nama_perekomendasi'] ?? null,
    'no_ktp' => $_POST['no_ktp'] ?? null,
    'tempat_lahir' => $_POST['tempat_lahir'] ?? null,
    'tanggal_lahir' => $_POST['tanggal_lahir'] ?? null,
    'jenis_kelamin' => $_POST['jenis_kelamin'] ?? null,
    'alamat_ktp' => $_POST['alamat_ktp'] ?? null,
    'alamat_domisili' => $_POST['alamat_domisili'] ?? null,
    'pendidikan_terakhir' => $_POST['pendidikan_terakhir'] ?? null,
    'pekerjaan' => $_POST['pekerjaan'] ?? null,
    'usaha' => $_POST['usaha'] ?? null, // <-- FIELD BARU DITAMBAHKAN
    'agama' => $_POST['agama'] ?? null,
    'nama_gadis_ibu_kandung' => $_POST['nama_gadis_ibu_kandung'] ?? null,
    'status_perkawinan' => $_POST['status_perkawinan'] ?? null,
    'nama_pasangan' => $_POST['nama_pasangan'] ?? null,
    'nama_ahli_waris' => $_POST['nama_ahli_waris'] ?? null,
    'hubungan_ahli_waris' => $_POST['hubungan_ahli_waris'] ?? null,
    'no_telepon' => $_POST['no_telepon'] ?? null,
    'email' => $_POST['email'] ?? null,
    'alamat_tempat_kerja' => $_POST['alamat_tempat_kerja'] ?? null,
    'pendapatan_bulanan' => $_POST['pendapatan_bulanan'] ?? null,
    'anggota_cu_lain' => $_POST['anggota_cu_lain'] ?? null,
    'updated_by_user_id' => $_SESSION['user_id'],
];


// === 2. Validasi & Keamanan ===
if (empty($data['id'])) {
    header('Location: index.php?error=ID tidak valid');
    exit;
}

// Security: Pastikan user_diklat hanya bisa edit cabang sendiri (jika bukan superadmin)
if ($_SESSION['role'] == 'user_diklat') {
    $stmt = $pdo->prepare("SELECT branch_id FROM fma_applications WHERE id = ?");
    $stmt->execute([$data['id']]);
    $app_branch_id = $stmt->fetchColumn();
    
    if ($app_branch_id != $_SESSION['branch_id']) {
        header('Location: index.php?error=Akses ditolak');
        exit;
    }
    // Paksa branch_id tetap sama
    $data['branch_id'] = $_SESSION['branch_id'];
}

// Validasi minimal
if (empty($data['branch_id']) || empty($data['nama_lengkap']) || empty($data['no_ktp'])) {
    header("Location: edit.php?id=" . $data['id'] . "&error=Data wajib (Nama, KTP, Cabang) tidak boleh kosong.");
    exit;
}


// === 3. Update Database ===
try {
    // Kueri ini sekarang mengupdate SEMUA field dari form edit
    $sql = "UPDATE fma_applications SET 
                status_permohonan = :status_permohonan,
                catatan_admin = :catatan_admin,
                branch_id = :branch_id,
                nama_lengkap = :nama_lengkap,
                nama_panggilan = :nama_panggilan,
                sumber_informasi = :sumber_informasi,
                nama_perekomendasi = :nama_perekomendasi,
                no_ktp = :no_ktp,
                tempat_lahir = :tempat_lahir,
                tanggal_lahir = :tanggal_lahir,
                jenis_kelamin = :jenis_kelamin,
                alamat_ktp = :alamat_ktp,
                alamat_domisili = :alamat_domisili,
                pendidikan_terakhir = :pendidikan_terakhir,
                pekerjaan = :pekerjaan,
                usaha = :usaha,
                agama = :agama,
                nama_gadis_ibu_kandung = :nama_gadis_ibu_kandung,
                status_perkawinan = :status_perkawinan,
                nama_pasangan = :nama_pasangan,
                nama_ahli_waris = :nama_ahli_waris,
                hubungan_ahli_waris = :hubungan_ahli_waris,
                no_telepon = :no_telepon,
                email = :email,
                alamat_tempat_kerja = :alamat_tempat_kerja,
                pendapatan_bulanan = :pendapatan_bulanan,
                anggota_cu_lain = :anggota_cu_lain,
                updated_by_user_id = :updated_by_user_id
            WHERE id = :id";
            
    $stmt = $pdo->prepare($sql);
    
    // Eksekusi query dengan mengirim seluruh array $data
    // PDO akan mencocokkan key di array (cth: 'nama_lengkap') dengan placeholder (cth: ':nama_lengkap')
    $stmt->execute($data);

    header("Location: index.php?success=Data permohonan (ID: " . $data['id'] . ") berhasil diperbarui.");
    exit;

} catch (PDOException $e) {
    // Cek jika error karena KTP duplikat
    if ($e->errorInfo[1] == 1062) { 
        header("Location: edit.php?id=" . $data['id'] . "&error=Gagal: No. KTP " . htmlspecialchars($data['no_ktp']) . " sudah terdaftar.");
    } else {
        header("Location: edit.php?id=" . $data['id'] . "&error=Gagal update: " . $e->getMessage());
    }
    exit;
}
?>