<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// === Fungsi Helper ===
function getPostValue($key) {
    return isset($_POST[$key]) && !empty($_POST[$key]) ? trim($_POST[$key]) : null;
}

function generateTrackingCode() {
    $prefix = "FMA-" . date('Ymd');
    $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    return $prefix . '-' . $suffix;
}

// === Ambil Data dari POST ===
$data = [
    'branch_id' => getPostValue('branch_id'),
    'nama_lengkap' => getPostValue('nama_lengkap'),
    'nama_panggilan' => getPostValue('nama_panggilan'),
    'no_ktp' => getPostValue('no_ktp'),
    'tempat_lahir' => getPostValue('tempat_lahir'),
    'tanggal_lahir' => getPostValue('tanggal_lahir'),
    'jenis_kelamin' => getPostValue('jenis_kelamin'),
    'status_perkawinan' => getPostValue('status_perkawinan'),
    'nama_pasangan' => getPostValue('nama_pasangan'),
    'nama_gadis_ibu_kandung' => getPostValue('nama_gadis_ibu_kandung'),
    'agama' => getPostValue('agama'),
    'pendidikan_terakhir' => getPostValue('pendidikan_terakhir'),
    'pekerjaan' => getPostValue('pekerjaan'),
    'alamat_tempat_kerja' => getPostValue('alamat_tempat_kerja'),
    'pendapatan_bulanan' => getPostValue('pendapatan_bulanan'),
    'no_telepon' => getPostValue('no_telepon'),
    'email' => getPostValue('email'),
    'alamat_ktp' => getPostValue('alamat_ktp'),
    'alamat_domisili' => getPostValue('alamat_domisili'),
    'anggota_cu_lain' => getPostValue('anggota_cu_lain'),
    'sumber_informasi' => getPostValue('sumber_informasi'),
    'nama_perekomendasi' => getPostValue('nama_perekomendasi'),
    'nama_ahli_waris' => getPostValue('nama_ahli_waris'),
    'hubungan_ahli_waris' => getPostValue('hubungan_ahli_waris'),
    'kode_tracking' => generateTrackingCode() // Buat kode tracking unik
];

// === Validasi Server-Side ===
$required_fields = [
    'branch_id', 'nama_lengkap', 'no_ktp', 'tempat_lahir', 'tanggal_lahir', 
    'jenis_kelamin', 'status_perkawinan', 'nama_gadis_ibu_kandung', 
    'no_telepon', 'alamat_ktp', 'alamat_domisili', 'nama_ahli_waris', 'hubungan_ahli_waris', 'anggota_cu_lain'
];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        header("Location: index.php?error=Data tidak lengkap. Field '" . str_replace('_', ' ', $field) . "' wajib diisi.");
        exit;
    }
}

if (!preg_match('/^\d{16}$/', $data['no_ktp'])) {
    header("Location: index.php?error=Format No. KTP tidak valid. Harus 16 digit angka.");
    exit;
}

if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
     header("Location: index.php?error=Format email tidak valid.");
    exit;
}

// Cek duplikat KTP
try {
    $stmt = $pdo->prepare("SELECT id FROM fma_applications WHERE no_ktp = ?");
    $stmt->execute([$data['no_ktp']]);
    if ($stmt->fetch()) {
        header("Location: index.php?error=No. KTP ini sudah pernah didaftarkan.");
        exit;
    }
} catch (PDOException $e) {
    header("Location: index.php?error=Database error: " . $e->getMessage());
    exit;
}

// === Simpan ke Database ===
try {
    $sql = "INSERT INTO fma_applications (
                branch_id, kode_tracking, nama_lengkap, nama_panggilan, no_ktp, tempat_lahir, tanggal_lahir, 
                jenis_kelamin, status_perkawinan, nama_pasangan, nama_gadis_ibu_kandung, agama, pendidikan_terakhir, 
                pekerjaan, alamat_tempat_kerja, pendapatan_bulanan, no_telepon, email, alamat_ktp, 
                alamat_domisili, anggota_cu_lain, sumber_informasi, nama_perekomendasi, 
                nama_ahli_waris, hubungan_ahli_waris, status_permohonan
            ) VALUES (
                :branch_id, :kode_tracking, :nama_lengkap, :nama_panggilan, :no_ktp, :tempat_lahir, :tanggal_lahir, 
                :jenis_kelamin, :status_perkawinan, :nama_pasangan, :nama_gadis_ibu_kandung, :agama, :pendidikan_terakhir, 
                :pekerjaan, :alamat_tempat_kerja, :pendapatan_bulanan, :no_telepon, :email, :alamat_ktp, 
                :alamat_domisili, :anggota_cu_lain, :sumber_informasi, :nama_perekomendasi, 
                :nama_ahli_waris, :hubungan_ahli_waris, 'Baru'
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);

    // Sukses
    $success_message = "Data Anda telah terkirim. Kode pelacakan Anda adalah: " . $data['kode_tracking'];
    header("Location: index.php?success=" . urlencode($success_message));
    exit;

} catch (PDOException $e) {
    header("Location: index.php?error=Gagal menyimpan data: " . $e->getMessage());
    exit;
}
?>