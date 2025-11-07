<?php
// Form ini tidak memerlukan login, tapi perlu koneksi DB untuk ambil daftar cabang
require_once '../config/db.php';

// Ambil daftar cabang (FIX: Menghapus "WHERE is_active = 1")
$branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Opsi untuk dropdown, diambil dari SPMA.pdf
$sumber_info_list = ["Rekomendasi Anggota", "Facebook/IG", "Youtube", "Website", "Brosur", "Sosialisasi Paroki", "Lainnya"];
$pendidikan_list = ["Pra Sekolah", "SD", "SMP", "SMU", "D1", "D2", "D3", "S1", "S2", "S3"];
$pekerjaan_list = [
    "Pelajar/Mahasiswa", "Karyawan Swasta", "Wiraswasta", "Pedagang", "PNS", "TNI/POLRI", 
    "Guru", "Dosen", "Dokter", "Perawat", "Petani", "Peternak", "Nelayan", "Buruh", 
    "Ibu Rumah Tangga", "Pensiunan", "Rohaniawan", "Lainnya"
];
$agama_list = ["Katholik", "Protestan", "Islam", "Hindu", "Budha", "Konghucu", "Lainnya"];

// === PERUBAHAN DI SINI: Menambahkan list usaha ===
$usaha_list = [
    "Kuliner", "Kecantikan", "Fashion", "Otomotif", "Agrobisnis", "Kerajinan",
    "Furniture", "Jasa", "Toko Kelontong", "Lainnya"
];
// === AKHIR PERUBAHAN ===

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
    <title>Formulir Permohonan Menjadi Anggota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f4f8; }
        .form-header {
            background-color: #0a2f5c; color: white;
            border-bottom: 5px solid #f0ad4e;
        }
        .form-header img { max-width: 70px; margin-right: 15px; }
        .card-title-section {
            font-size: 1.1rem; font-weight: 600; color: #0a2f5c;
            border-bottom: 2px solid #f0ad4e;
            padding-bottom: 5px; margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container my-4 my-md-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> 
                        <strong>Permohonan Terkirim!</strong> <?php echo e($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> 
                        <strong>Oops!</strong> <?php echo e($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-lg border-0">
                    <div class="card-header form-header p-4 text-center">
                        <div class="d-flex flex-column flex-md-row justify-content-center align-items-center">
                            <img src="../assets/logo-cubg.png" alt="Logo CUBG" class="d-none d-md-block">
                            <div>
                                <h2 class="mb-0 h3">Formulir Permohonan Menjadi Anggota</h2>
                                <h3 class="mb-0 h5 fw-light">KSP Credit Union Bererod Gratia</h3>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <p class="text-muted text-center mb-4">Silakan isi data di bawah ini dengan lengkap dan benar.</p>
                        
                        <form action="process_submit.php" method="POST" class="needs-validation" novalidate>
                            
                            <h5 class="card-title-section">Pilihan Kantor Pelayanan</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="branch_id" name="branch_id" required>
                                            <option value="" selected disabled>-- Pilih Cabang --</option>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?php echo $branch['id']; ?>"><?php echo e($branch['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="branch_id">Kantor Cabang yang Dituju *</label>
                                        <div class="invalid-feedback">Cabang wajib dipilih.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="card-title-section mt-4">Data Diri Calon Anggota</h5>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap" required>
                                        <label for="nama_lengkap">Nama Lengkap (Sesuai KTP) *</label>
                                        <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_panggilan" name="nama_panggilan" placeholder="Nama Panggilan">
                                        <label for="nama_panggilan">Nama Panggilan</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="sumber_informasi" name="sumber_informasi">
                                            <option value="" selected>-- Pilih Sumber --</option>
                                            <?php foreach ($sumber_info_list as $sumber): ?>
                                            <option value="<?php echo $sumber; ?>"><?php echo $sumber; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="sumber_informasi">Sumber Informasi CUBG</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_perekomendasi" name="nama_perekomendasi" placeholder="Nama Perekomendasi (Jika ada)">
                                        <label for="nama_perekomendasi">Nama Perekomendasi (Jika ada)</label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="no_ktp" name="no_ktp" placeholder="Nomor KTP" required pattern="\d{16}">
                                        <label for="no_ktp">No. KTP (16 Digit) *</label>
                                        <div class="invalid-feedback">Nomor KTP wajib diisi (16 digit angka).</div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" placeholder="Tempat Lahir" required>
                                        <label for="tempat_lahir">Tempat Lahir *</label>
                                        <div class="invalid-feedback">Tempat lahir wajib diisi.</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" placeholder="Tanggal Lahir" required>
                                        <label for="tanggal_lahir">Tanggal Lahir *</label>
                                        <div class="invalid-feedback">Tanggal lahir wajib diisi.</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                            <option value="" selected disabled>-- Pilih --</option>
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                        <label for="jenis_kelamin">Jenis Kelamin *</label>
                                        <div class="invalid-feedback">Jenis kelamin wajib dipilih.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="card-title-section mt-4">Alamat & Keterangan</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="alamat_ktp" name="alamat_ktp" placeholder="Alamat Sesuai KTP" style="height: 120px" required></textarea>
                                        <label for="alamat_ktp">Alamat Sesuai KTP *</label>
                                        <div class="invalid-feedback">Alamat KTP wajib diisi.</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="alamat_domisili" name="alamat_domisili" placeholder="Alamat Domisili Saat Ini" style="height: 120px" required></textarea>
                                        <label for="alamat_domisili">Alamat Domisili Saat Ini *</label>
                                        <div class="invalid-feedback">Alamat domisili wajib diisi.</div>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input type="checkbox" class="form-check-input" id="sama_dengan_ktp">
                                        <label class="form-check-label text-muted" for="sama_dengan_ktp">Centang jika sama dengan alamat KTP</label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="pendidikan_terakhir" name="pendidikan_terakhir">
                                            <option value="" selected>-- Pilih Pendidikan --</option>
                                            <?php foreach ($pendidikan_list as $p): ?>
                                            <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                                            <?php endforeach; ?>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                        <label for="pendidikan_terakhir">7. Pendidikan Terakhir</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="pekerjaan" name="pekerjaan">
                                            <option value="" selected>-- Pilih Pekerjaan --</option>
                                            <?php foreach ($pekerjaan_list as $p): ?>
                                            <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="pekerjaan">8. Pekerjaan</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="usaha" name="usaha">
                                            <option value="" selected>-- Pilih Jenis Usaha --</option>
                                            <?php foreach ($usaha_list as $u): ?>
                                            <option value="<?php echo $u; ?>"><?php echo $u; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="usaha">9. Usaha (Jika Ada)</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="agama" name="agama">
                                            <option value="" selected>-- Pilih Agama --</option>
                                            <?php foreach ($agama_list as $a): ?>
                                            <option value="<?php echo $a; ?>"><?php echo $a; ?></option>
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
                                        <input type="text" class="form-control" id="nama_gadis_ibu_kandung" name="nama_gadis_ibu_kandung" placeholder="Nama Gadis Ibu Kandung" required>
                                        <label for="nama_gadis_ibu_kandung">Nama Ibu Kandung *</label>
                                        <div class="invalid-feedback">Nama ibu kandung wajib diisi.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="status_perkawinan" name="status_perkawinan" required>
                                            <option value="" selected disabled>-- Pilih --</option>
                                            <option value="Belum Kawin">Belum Kawin</option>
                                            <option value="Kawin">Kawin</option>
                                            <option value="Cerai Hidup">Cerai Hidup</option>
                                            <option value="Cerai Mati">Cerai Mati</option>
                                        </select>
                                        <label for="status_perkawinan">Status Perkawinan *</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_pasangan" name="nama_pasangan" placeholder="Nama Istri/Suami (Jika Kawin)">
                                        <label for="nama_pasangan">Nama Istri/Suami (Jika Kawin)</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nama_ahli_waris" name="nama_ahli_waris" placeholder="Nama Ahli Waris" required>
                                        <label for="nama_ahli_waris">Nama Ahli Waris *</label>
                                        <div class="invalid-feedback">Nama ahli waris wajib diisi.</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="hubungan_ahli_waris" name="hubungan_ahli_waris" placeholder="Hubungan Ahli Waris" required>
                                        <label for="hubungan_ahli_waris">Hubungan Ahli Waris *</label>
                                        <div class="invalid-feedback">Hubungan wajib diisi.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="card-title-section mt-4">Kontak & Ekonomi</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="no_telepon" name="no_telepon" placeholder="No. Telepon/HP" required>
                                        <label for="no_telepon">No. Telepon/HP (Aktif) *</label>
                                        <div class="invalid-feedback">No. telepon wajib diisi.</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                                        <label for="email">Email</label>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="alamat_tempat_kerja" name="alamat_tempat_kerja" placeholder="Alamat Tempat Kerja">
                                        <label for="alamat_tempat_kerja">Alamat Tempat Kerja</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="pendapatan_bulanan" name="pendapatan_bulanan">
                                            <option value="" selected>-- Pilih Pendapatan --</option>
                                            <?php foreach ($pendapatan_list as $p): ?>
                                            <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="pendapatan_bulanan">Pendapatan Bulanan</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Anggota Koperasi/CU Lain? *</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="anggota_cu_lain" id="cu_ya" value="Ya" required>
                                            <label class="form-check-label" for="cu_ya">Ya</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="anggota_cu_lain" id="cu_tidak" value="Tidak" required>
                                            <label class="form-check-label" for="cu_tidak">Tidak</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            
                            <div class="d-grid">
                                <button class="btn btn-primary btn-lg" type="submit">
                                    <i class="bi bi-send-fill me-2"></i> Kirim Permohonan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script validasi Bootstrap 5
        (function () {
          'use strict'
          var forms = document.querySelectorAll('.needs-validation')
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                  event.preventDefault()
                  event.stopPropagation()
                }
                form.classList.add('was-validated')
              }, false)
            });
            
          // Script untuk checkbox alamat
          const checkboxAlamat = document.getElementById('sama_dengan_ktp');
          const alamatKtp = document.getElementById('alamat_ktp');
          const alamatDomisili = document.getElementById('alamat_domisili');
          
          if(checkboxAlamat) {
              checkboxAlamat.addEventListener('change', function() {
                  if (this.checked) {
                      alamatDomisili.value = alamatKtp.value;
                  } else {
                      alamatDomisili.value = '';
                  }
              });
              
              // Tambahan: jika alamat KTP diubah, update juga domisili JIKA tercentang
              alamatKtp.addEventListener('input', function() {
                  if (checkboxAlamat.checked) {
                      alamatDomisili.value = this.value;
                  }
              });
          }
        })()
    </script>
</body>
</html>