<?php
require_once '../middleware/check_auth.php';
// Hanya superadmin atau user_diklat yang bisa mengakses
authorize(['superadmin', 'user_diklat']); 

require_once '../config/db.php';
function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }

// --- Helper function untuk Pagination ---
function build_pagination_query_string($page, $status, $branch) {
    $params = ['page' => $page];
    if (!empty($status)) $params['status'] = $status;
    if (!empty($branch)) $params['branch_id'] = $branch;
    return 'index.php?' . http_build_query($params);
}
// ------------------------------------

// --- START PAGINATION ---
$limit = 25; // Data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
// --- END PAGINATION ---

// Logika Filter
$filter_status = $_GET['status'] ?? '';
$filter_branch_id = $_GET['branch_id'] ?? '';

$sql_where = "WHERE 1=1";
$params = [];

// Jika user_diklat, paksa filter berdasarkan cabang mereka
if ($_SESSION['role'] == 'user_diklat') {
    $filter_branch_id = $_SESSION['branch_id'];
}

if (!empty($filter_branch_id)) {
    $sql_where .= " AND a.branch_id = :branch_id";
    $params[':branch_id'] = $filter_branch_id;
}
if (!empty($filter_status)) {
    $sql_where .= " AND a.status_permohonan = :status";
    $params[':status'] = $filter_status;
}

// Ambil data cabang (untuk filter & form input)
$branches = [];
$user_branch_name = ''; // Untuk user_diklat
if ($_SESSION['role'] == 'superadmin') {
    $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Ambil nama cabang user_diklat
    $stmt_branch = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
    $stmt_branch->execute([$_SESSION['branch_id']]);
    $user_branch_name = $stmt_branch->fetchColumn();
}

// --- Query Hitung Total Data ---
$sql_count = "SELECT COUNT(a.id) 
              FROM fma_applications a
              $sql_where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_items = $stmt_count->fetchColumn();
$total_pages = ceil($total_items / $limit);
// --- Akhir Hitung Total ---


// Ambil data permohonan (DIMODIFIKASI DENGAN LIMIT & OFFSET)
$sql = "SELECT a.*, b.name as branch_name 
        FROM fma_applications a
        JOIN branches b ON a.branch_id = b.id
        $sql_where
        ORDER BY a.created_at DESC
        LIMIT $limit OFFSET $offset"; // <-- Ditambahkan LIMIT/OFFSET
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_list = ['Baru', 'Diproses', 'Diterima', 'Ditolak', 'Perlu Lengkapi', 'Sudah Menjadi Anggota'];

// === DATA UNTUK FORM MODAL (BARU) ===
$sumber_info_list = ["Rekomendasi Anggota", "Facebook/IG", "Youtube", "Website", "Brosur", "Sosialisasi Paroki", "Lainnya"];
$pendidikan_list = ["Pra Sekolah", "SD", "SMP", "SMU", "D1", "D2", "D3", "S1", "S2", "S3", "Lainnya"];
$pekerjaan_list = [
    "Pelajar/Mahasiswa", "Karyawan Swasta", "Wiraswasta", "Pedagang", "PNS", "TNI/POLRI", 
    "Guru", "Dosen", "Dokter", "Perawat", "Petani", "Peternak", "Nelayan", "Buruh", 
    "Ibu Rumah Tangga", "Pensiunan", "Rohaniawan", "Lainnya"
];
$agama_list = ["Katholik", "Protestan", "Islam", "Hindu", "Budha", "Konghucu", "Lainnya"];
$pendapatan_list = [
    "< 750.000", "> 750.000 - 1.500.000", "> 1.500.000 - 2.000.000", 
    "> 2.000.000 - 3.000.000", "> 3.000.000 - 4.000.000", "> 4.000.000 - 5.000.000", 
    "> 5.000.000 - 6.000.000", "> 6.000.000 - 7.000.000", "> 7.000.000 - 8.000.000", "> 8.000.000"
];
// === AKHIR DATA FORM MODAL ===
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Permohonan Anggota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-title-section {
            font-size: 1.1rem; font-weight: 600; color: #0a2f5c;
            border-bottom: 2px solid #f0ad4e; padding-bottom: 5px; margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo e($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo e($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-file-earmark-text-fill"></i> Daftar Permohonan Anggota Baru</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Permohonan
                </button>
            </div>
            <div class="card-body">
                
                <form method="GET" action="index.php" class="mb-4 p-3 bg-light rounded border">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Semua Status</option>
                                <?php foreach ($status_list as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo ($filter_status == $status) ? 'selected' : ''; ?>>
                                        <?php echo $status; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if ($_SESSION['role'] == 'superadmin'): ?>
                        <div class="col-md-5">
                            <label for="branch_id" class="form-label">Cabang</label>
                            <select name="branch_id" id="branch_id" class="form-select">
                                <option value="">Semua Cabang</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" <?php echo ($filter_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Tanggal Masuk</th>
                                <th>Kode</th>
                                <th>Nama Lengkap</th>
                                <th>No. KTP</th>
                                <th>No. Telepon</th>
                                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                                    <th>Cabang</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="<?php echo ($_SESSION['role'] == 'superadmin') ? '8' : '7'; ?>" class="text-center">Tidak ada data permohonan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td><?php echo e(date('d M Y, H:i', strtotime($app['created_at']))); ?></td>
                                        <td><?php echo e($app['kode_tracking']); ?></td>
                                        <td><?php echo e($app['nama_lengkap']); ?></td>
                                        <td><?php echo e($app['no_ktp']); ?></td>
                                        <td><?php echo e($app['no_telepon']); ?></td>
                                        <?php if ($_SESSION['role'] == 'superadmin'): ?>
                                            <td><?php echo e($app['branch_name']); ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <?php
                                            $badge_class = 'bg-secondary';
                                            if ($app['status_permohonan'] == 'Baru') $badge_class = 'bg-primary';
                                            if ($app['status_permohonan'] == 'Diterima' || $app['status_permohonan'] == 'Sudah Menjadi Anggota') $badge_class = 'bg-success';
                                            if ($app['status_permohonan'] == 'Ditolak') $badge_class = 'bg-danger';
                                            if ($app['status_permohonan'] == 'Diproses' || $app['status_permohonan'] == 'Perlu Lengkapi') $badge_class = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo e($app['status_permohonan']); ?></span>
                                        </td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-warning" title="Edit/Proses">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <?php if ($_SESSION['role'] == 'superadmin'): ?>
                                            <a href="process_delete.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus data ini?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_query_string($page - 1, $filter_status, $filter_branch_id); ?>">Previous</a>
                            </li>
                            
                            <?php 
                            $window = 2; // Jumlah halaman di sekitar halaman aktif
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)):
                            ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_pagination_query_string($i, $filter_status, $filter_branch_id); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php 
                                elseif ($i == $page - $window - 1 || $i == $page + $window + 1): 
                                ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_query_string($page + 1, $filter_status, $filter_branch_id); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                </div>
        </div>
    </div>

    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addMemberModalLabel"><i class="bi bi-person-plus-fill"></i> Formulir Permohonan Anggota Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <form action="process_create.php" method="POST">
                        
                        <h5 class="card-title-section text-primary">Cabang</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="branch_id_modal" class="form-label">Cabang Pilihan *</label>
                                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                                    <select class="form-select" id="branch_id_modal" name="branch_id" required>
                                        <option value="" selected disabled>-- Pilih Cabang --</option>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['id']; ?>"><?php echo e($branch['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="hidden" name="branch_id" value="<?php echo $_SESSION['branch_id']; ?>">
                                    <input type="text" class="form-control" value="<?php echo e($user_branch_name); ?>" readonly disabled>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h5 class="card-title-section mt-4">Data Diri Calon Anggota</h5>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nama_lengkap_modal" name="nama_lengkap" placeholder="Nama Lengkap" required>
                                    <label for="nama_lengkap_modal">Nama Lengkap (Sesuai KTP) *</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nama_panggilan_modal" name="nama_panggilan" placeholder="Nama Panggilan">
                                    <label for="nama_panggilan_modal">Nama Panggilan</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="sumber_informasi_modal" name="sumber_informasi">
                                        <option value="">-- Pilih Sumber --</option>
                                        <?php foreach ($sumber_info_list as $sumber): ?>
                                        <option value="<?php echo $sumber; ?>"><?php echo $sumber; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="sumber_informasi_modal">Sumber Informasi CUBG</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nama_perekomendasi_modal" name="nama_perekomendasi" placeholder="Nama Perekomendasi">
                                    <label for="nama_perekomendasi_modal">Nama Perekomendasi (Jika ada)</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="no_ktp_modal" name="no_ktp" placeholder="Nomor KTP" required pattern="\d{16}" title="KTP harus 16 digit angka">
                                    <label for="no_ktp_modal">No. KTP (16 Digit) *</label>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="tempat_lahir_modal" name="tempat_lahir" placeholder="Tempat Lahir" required>
                                    <label for="tempat_lahir_modal">Tempat Lahir *</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="tanggal_lahir_modal" name="tanggal_lahir" placeholder="Tanggal Lahir" required>
                                    <label for="tanggal_lahir_modal">Tanggal Lahir *</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="jenis_kelamin_modal" name="jenis_kelamin" required>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                    <label for="jenis_kelamin_modal">Jenis Kelamin *</label>
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="card-title-section mt-4">Alamat & Keterangan</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <textarea class="form-control" id="alamat_ktp_modal" name="alamat_ktp" placeholder="Alamat Sesuai KTP" style="height: 120px" required></textarea>
                                    <label for="alamat_ktp_modal">Alamat Sesuai KTP *</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <textarea class="form-control" id="alamat_domisili_modal" name="alamat_domisili" placeholder="Alamat Domisili Saat Ini" style="height: 120px" required></textarea>
                                    <label for="alamat_domisili_modal">Alamat Domisili Saat Ini *</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="pendidikan_terakhir_modal" name="pendidikan_terakhir">
                                        <option value="">-- Pilih Pendidikan --</option>
                                        <?php foreach ($pendidikan_list as $p): ?>
                                        <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="pendidikan_terakhir_modal">Pendidikan Terakhir</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="pekerjaan_modal" name="pekerjaan">
                                        <option value="">-- Pilih Pekerjaan --</option>
                                        <?php foreach ($pekerjaan_list as $p): ?>
                                        <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="pekerjaan_modal">Pekerjaan</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="agama_modal" name="agama">
                                        <option value="">-- Pilih Agama --</option>
                                        <?php foreach ($agama_list as $a): ?>
                                        <option value="<?php echo $a; ?>"><?php echo $a; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="agama_modal">Agama</label>
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="card-title-section mt-4">Data Keluarga</h5>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nama_gadis_ibu_kandung_modal" name="nama_gadis_ibu_kandung" placeholder="Nama Gadis Ibu Kandung" required>
                                    <label for="nama_gadis_ibu_kandung_modal">Nama Ibu Kandung *</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="status_perkawinan_modal" name="status_perkawinan" required>
                                        <option value="Belum Kawin">Belum Kawin</option>
                                        <option value="Kawin">Kawin</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                    <label for="status_perkawinan_modal">Status Perkawinan *</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nama_pasangan_modal" name="nama_pasangan" placeholder="Nama Istri/Suami">
                                    <label for="nama_pasangan_modal">Nama Istri/Suami (Jika Kawin)</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nama_ahli_waris_modal" name="nama_ahli_waris" placeholder="Nama Ahli Waris" required>
                                    <label for="nama_ahli_waris_modal">Nama Ahli Waris *</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="hubungan_ahli_waris_modal" name="hubungan_ahli_waris" placeholder="Hubungan Ahli Waris" required>
                                    <label for="hubungan_ahli_waris_modal">Hubungan Ahli Waris *</label>
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="card-title-section mt-4">Kontak & Ekonomi</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="no_telepon_modal" name="no_telepon" placeholder="No. Telepon/HP" required>
                                    <label for="no_telepon_modal">No. Telepon/HP (Aktif) *</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email_modal" name="email" placeholder="Email">
                                    <label for="email_modal">Email</label>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="alamat_tempat_kerja_modal" name="alamat_tempat_kerja" placeholder="Alamat Tempat Kerja">
                                    <label for="alamat_tempat_kerja_modal">Alamat Tempat Kerja</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="pendapatan_bulanan_modal" name="pendapatan_bulanan">
                                        <option value="">-- Pilih Pendapatan --</option>
                                        <?php foreach ($pendapatan_list as $p): ?>
                                        <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="pendapatan_bulanan_modal">Pendapatan Bulanan</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Anggota Koperasi/CU Lain? *</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="anggota_cu_lain" id="cu_ya_modal" value="Ya" required>
                                        <label class="form-check-label" for="cu_ya_modal">Ya</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="anggota_cu_lain" id="cu_tidak_modal" value="Tidak" required checked>
                                        <label class="form-check-label" for="cu_tidak_modal">Tidak</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Simpan Permohonan</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>