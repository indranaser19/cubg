<?php
require_once '../middleware/check_auth.php';
authorize(['superadmin', 'user_diklat']); 

require_once '../config/db.php';
function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php?error=ID tidak valid');
    exit;
}

// Ambil data permohonan
$stmt = $pdo->prepare("SELECT * FROM fma_applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    header('Location: index.php?error=Data tidak ditemukan');
    exit;
}

// Security: Pastikan user_diklat hanya bisa edit cabang sendiri
if ($_SESSION['role'] == 'user_diklat' && $app['branch_id'] != $_SESSION['branch_id']) {
    header('Location: index.php?error=Akses ditolak');
    exit;
}

// Ambil data cabang (untuk superadmin ganti cabang)
$branches = [];
if ($_SESSION['role'] == 'superadmin') {
    $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}
$status_list = ['Baru', 'Diproses', 'Diterima', 'Ditolak', 'Perlu Lengkapi', 'Sudah Menjadi Anggota'];

// Opsi untuk dropdown, diambil dari fma/index.php
$sumber_info_list = ["Rekomendasi Anggota", "Facebook/IG", "Youtube", "Website", "Brosur", "Sosialisasi Paroki", "Lainnya"];
$pendidikan_list = ["Pra Sekolah", "SD", "SMP", "SMU", "D1", "D2", "D3", "S1", "S2", "S3", "Lainnya"];
$pekerjaan_list = [
    "Pelajar/Mahasiswa", "Karyawan Swasta", "Wiraswasta", "Pedagang", "PNS", "TNI/POLRI", 
    "Guru", "Dosen", "Dokter", "Perawat", "Petani", "Peternak", "Nelayan", "Buruh", 
    "Ibu Rumah Tangga", "Pensiunan", "Rohaniawan", "Lainnya"
];

// === TAMBAHKAN LIST USAHA ===
$usaha_list = [
    "Kuliner", "Kecantikan", "Fashion", "Otomotif", "Agrobisnis", "Kerajinan",
    "Furniture", "Jasa", "Toko Kelontong", "Lainnya"
];

$agama_list = ["Katholik", "Protestan", "Islam", "Hindu", "Budha", "Konghucu", "Lainnya"];
$pendapatan_list = [
    "< 750.000", "> 750.000 - 1.500.000", "> 1.500.000 - 2.000.000", 
    "> 2.000.000 - 3.000.000", "> 3.000.000 - 4.000.000", "> 4.000.000 - 5.000.000", 
    "> 5.000.000 - 6.000.000", "> 6.000.000 - 7.000.000", "> 7.000.000 - 8.000.000", "> 8.000.000"
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Permohonan - <?php echo e($app['nama_lengkap']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-title-section {
            font-size: 1.1rem; font-weight: 600; color: #0a2f5c;
            border-bottom: 2px solid #f0ad4e; padding-bottom: 5px; margin-bottom: 15px;
        }
        .form-control[readonly], .form-select[readonly] {
            background-color: #e9ecef;
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Permohonan (<?php echo e($app['kode_tracking']); ?>)</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        
                        <form action="process_update.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                            
                            <h5 class="card-title-section text-danger">Panel Admin</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="status_permohonan" name="status_permohonan" required>
                                            <?php foreach ($status_list as $status): ?>
                                                <option value="<?php echo $status; ?>" <?php echo ($app['status_permohonan'] == $status) ? 'selected' : ''; ?>>
                                                    <?php echo $status; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="status_permohonan">Status Permohonan *</label>
                                    </div>
                                </div>
                                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="branch_id" name="branch_id" required>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?php echo $branch['id']; ?>" <?php echo ($app['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                                    <?php echo e($branch['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="branch_id">Pindahkan ke Cabang (Superadmin)</label>
                                    </div>
                                </div>
                                <?php else: ?>
                                <input type="hidden" name="branch_id" value="<?php echo $app['branch_id']; ?>">
                                <?php endif; ?>
                                <div class="col-12 mb-3">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="catatan_admin" name="catatan_admin" placeholder="Catatan Admin" style="height: 100px"><?php echo e($app['catatan_admin']); ?></textarea>
                                        <label for="catatan_admin">Catatan Admin (Internal)</label>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="card-title-section mt-4">Data Diri Calon Anggota</h5>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap" value="<?php echo e($app['nama_lengkap']); ?>" required>
                                        <label for="nama_lengkap">Nama Lengkap (Sesuai KTP) *</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_panggilan" name="nama_panggilan" placeholder="Nama Panggilan" value="<?php echo e($app['nama_panggilan']); ?>">
                                        <label for="nama_panggilan">Nama Panggilan</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="sumber_informasi" name="sumber_informasi">
                                            <option value="">-- Pilih Sumber --</option>
                                            <?php foreach ($sumber_info_list as $sumber): ?>
                                            <option value="<?php echo $sumber; ?>" <?php echo ($app['sumber_informasi'] == $sumber) ? 'selected' : ''; ?>>
                                                <?php echo $sumber; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="sumber_informasi">Sumber Informasi CUBG</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_perekomendasi" name="nama_perekomendasi" placeholder="Nama Perekomendasi" value="<?php echo e($app['nama_perekomendasi']); ?>">
                                        <label for="nama_perekomendasi">Nama Perekomendasi (Jika ada)</label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="no_ktp" name="no_ktp" placeholder="Nomor KTP" value="<?php echo e($app['no_ktp']); ?>" required pattern="\d{16}">
                                        <label for="no_ktp">No. KTP (16 Digit)</label>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" placeholder="Tempat Lahir" value="<?php echo e($app['tempat_lahir']); ?>" required>
                                        <label for="tempat_lahir">Tempat Lahir</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" placeholder="Tanggal Lahir" value="<?php echo e($app['tanggal_lahir']); ?>" required>
                                        <label for="tanggal_lahir">Tanggal Lahir</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                            <option value="Laki-laki" <?php echo ($app['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                            <option value="Perempuan" <?php echo ($app['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                        </select>
                                        <label for="jenis_kelamin">Jenis Kelamin *</label>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="card-title-section mt-4">Alamat & Keterangan</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="alamat_ktp" name="alamat_ktp" placeholder="Alamat Sesuai KTP" style="height: 120px" required><?php echo e($app['alamat_ktp']); ?></textarea>
                                        <label for="alamat_ktp">Alamat Sesuai KTP *</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="alamat_domisili" name="alamat_domisili" placeholder="Alamat Domisili Saat Ini" style="height: 120px" required><?php echo e($app['alamat_domisili']); ?></textarea>
                                        <label for="alamat_domisili">Alamat Domisili Saat Ini *</label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="pendidikan_terakhir" name="pendidikan_terakhir">
                                            <option value="">-- Pilih Pendidikan --</option>
                                            <?php foreach ($pendidikan_list as $p): ?>
                                            <option value="<?php echo $p; ?>" <?php echo ($app['pendidikan_terakhir'] == $p) ? 'selected' : ''; ?>>
                                                <?php echo $p; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="pendidikan_terakhir">Pendidikan Terakhir</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="pekerjaan" name="pekerjaan">
                                            <option value="">-- Pilih Pekerjaan --</option>
                                            <?php foreach ($pekerjaan_list as $p): ?>
                                            <option value="<?php echo $p; ?>" <?php echo ($app['pekerjaan'] == $p) ? 'selected' : ''; ?>>
                                                <?php echo $p; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="pekerjaan">Pekerjaan</label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="usaha" name="usaha">
                                            <option value="">-- Pilih Jenis Usaha --</option>
                                            <?php foreach ($usaha_list as $u): ?>
                                            <option value="<?php echo $u; ?>" <?php echo (isset($app['usaha']) && $app['usaha'] == $u) ? 'selected' : ''; ?>>
                                                <?php echo $u; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="usaha">Usaha (Jika Ada)</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="agama" name="agama">
                                            <option value="">-- Pilih Agama --</option>
                                            <?php foreach ($agama_list as $a): ?>
                                            <option value="<?php echo $a; ?>" <?php echo ($app['agama'] == $a) ? 'selected' : ''; ?>>
                                                <?php echo $a; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="agama">Agama</label>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="card-title-section mt-4">Data Keluarga</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_gadis_ibu_kandung" name="nama_gadis_ibu_kandung" placeholder="Nama Gadis Ibu Kandung" value="<?php echo e($app['nama_gadis_ibu_kandung']); ?>" required>
                                        <label for="nama_gadis_ibu_kandung">Nama Ibu Kandung *</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="status_perkawinan" name="status_perkawinan" required>
                                            <option value="Belum Kawin" <?php echo ($app['status_perkawinan'] == 'Belum Kawin') ? 'selected' : ''; ?>>Belum Kawin</option>
                                            <option value="Kawin" <?php echo ($app['status_perkawinan'] == 'Kawin') ? 'selected' : ''; ?>>Kawin</option>
                                            <option value="Cerai Hidup" <?php echo ($app['status_perkawinan'] == 'Cerai Hidup') ? 'selected' : ''; ?>>Cerai Hidup</option>
                                            <option value="Cerai Mati" <?php echo ($app['status_perkawinan'] == 'Cerai Mati') ? 'selected' : ''; ?>>Cerai Mati</option>
                                        </select>
                                        <label for="status_perkawinan">Status Perkawinan *</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_pasangan" name="nama_pasangan" placeholder="Nama Istri/Suami" value="<?php echo e($app['nama_pasangan']); ?>">
                                        <label for="nama_pasangan">Nama Istri/Suami (Jika Kawin)</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_ahli_waris" name="nama_ahli_waris" placeholder="Nama Ahli Waris" value="<?php echo e($app['nama_ahli_waris']); ?>" required>
                                        <label for="nama_ahli_waris">Nama Ahli Waris *</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="hubungan_ahli_waris" name="hubungan_ahli_waris" placeholder="Hubungan Ahli Waris" value="<?php echo e($app['hubungan_ahli_waris']); ?>" required>
                                        <label for="hubungan_ahli_waris">Hubungan Ahli Waris *</label>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="card-title-section mt-4">Kontak & Ekonomi</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="no_telepon" name="no_telepon" placeholder="No. Telepon/HP" value="<?php echo e($app['no_telepon']); ?>" required>
                                        <label for="no_telepon">No. Telepon/HP (Aktif) *</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo e($app['email']); ?>">
                                        <label for="email">Email</label>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="alamat_tempat_kerja" name="alamat_tempat_kerja" placeholder="Alamat Tempat Kerja" value="<?php echo e($app['alamat_tempat_kerja']); ?>">
                                        <label for="alamat_tempat_kerja">Alamat Tempat Kerja</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="pendapatan_bulanan" name="pendapatan_bulanan">
                                            <option value="">-- Pilih Pendapatan --</option>
                                            <?php foreach ($pendapatan_list as $p): ?>
                                            <option value="<?php echo $p; ?>" <?php echo ($app['pendapatan_bulanan'] == $p) ? 'selected' : ''; ?>>
                                                <?php echo $p; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="pendapatan_bulanan">Pendapatan Bulanan</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Anggota Koperasi/CU Lain? *</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="anggota_cu_lain" id="cu_ya" value="Ya" <?php echo ($app['anggota_cu_lain'] == 'Ya') ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="cu_ya">Ya</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="anggota_cu_lain" id="cu_tidak" value="Tidak" <?php echo (empty($app['anggota_cu_lain']) || $app['anggota_cu_lain'] == 'Tidak') ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="cu_tidak">Tidak</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                                </a>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="export_spma.php?id=<?php echo $app['id']; ?>" class="btn btn-success btn-lg" target="_blank">
                                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> Export SPMA (PDF)
                                    </a>
                                    
                                    <button class="btn btn-primary btn-lg" type="submit">
                                        <i class="bi bi-save-fill me-2"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>