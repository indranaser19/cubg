<?php
require_once '../middleware/check_auth.php';
// HANYA Superadmin yang boleh mengakses halaman ini
authorize(['superadmin']);

require_once '../config/db.php';

// Ambil daftar cabang untuk dropdown
$stmt_branches = $pdo->query("SELECT id, name FROM branches ORDER BY name");
$branches = $stmt_branches->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar pengguna yang ada
$sql_users = "SELECT u.id, u.username, u.full_name, u.email, u.role, u.branch_id, b.name as branch_name
              FROM users u
              JOIN branches b ON u.branch_id = b.id
              ORDER BY u.full_name";
$stmt_users = $pdo->query($sql_users);
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// === PERUBAHAN DI SINI ===
// Tambahkan 'user_diklat' ke dalam array roles
$roles = ['admin_tv', 'branch_user', 'credit_officer', 'teller', 'user_diklat', 'superadmin'];
// === AKHIR PERUBAHAN ===
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna</title>
    <link href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <style>
        .role-badge {
            text-transform: capitalize;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Buat Pengguna Baru</h4>
                    </div>
                    <div class="card-body">
                        <form action="process_user_create.php" method="POST">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username (untuk login)</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role / Hak Akses</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">-- Pilih Role --</option>
                                    <?php foreach ($roles as $role_option): ?>
                                        <option value="<?php echo $role_option; ?>"><?php echo ucfirst(str_replace('_', ' ', $role_option)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="branch_id" class="form-label">Kantor Cabang</label>
                                <select class="form-select" id="branch_id" name="branch_id" required>
                                    <option value="">-- Pilih Cabang --</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Buat Akun</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h4 class="mb-0"><i class="bi bi-people-fill"></i> Daftar Pengguna Aktif</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Cabang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr 
                                        data-user-id="<?php echo $user['id']; ?>"
                                        data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                        data-branch-id="<?php echo htmlspecialchars($user['branch_id']); ?>"
                                    >
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><span class="badge bg-secondary role-badge"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))); ?></span></td>
                                        <td><?php echo htmlspecialchars($user['branch_name']); ?></td>
                                        <td>
                                            <button 
                                                class="btn btn-sm btn-warning btn-edit-user" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal"
                                                title="Edit User">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <button 
                                                class="btn btn-sm btn-info btn-change-password" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#changePasswordModal"
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                title="Ganti Password">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                            
                                            <a href="#" class="btn btn-sm btn-danger" title="Hapus"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="changePasswordModalLabel">Ganti Password untuk <span id="modal-username" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process_user_update_password.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="modal-user-id">
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <div class="form-text">Password minimal 6 karakter.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_new_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="6">
                        </div>
                        <div id="password-match-alert" class="alert alert-danger d-none" role="alert">
                            Password dan Konfirmasi Password tidak cocok!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info" id="submit-change-password">
                            <i class="bi bi-lock-fill"></i> Simpan Password Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editUserModalLabel"><i class="bi bi-pencil-square"></i> Edit User: <span id="edit-user-name" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process_user_update.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit-user-id">
                        
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" readonly> 
                            <div class="form-text">Username tidak bisa diubah setelah dibuat.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role / Hak Akses</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="">-- Pilih Role --</option>
                                <?php foreach ($roles as $role_option): ?>
                                    <option value="<?php echo $role_option; ?>"><?php echo ucfirst(str_replace('_', ' ', $role_option)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_branch_id" class="form-label">Kantor Cabang</label>
                            <select class="form-select" id="edit_branch_id" name="branch_id" required>
                                <option value="">-- Pilih Cabang --</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // --- 1. Logika Modal EDIT USER ---
        $('.btn-edit-user').on('click', function() {
            var row = $(this).closest('tr');
            
            // Ambil data dari atribut data-* di baris tabel
            var userId = row.data('user-id');
            var fullName = row.data('full-name');
            var username = row.data('username');
            var role = row.data('role');
            var branchId = row.data('branch-id');

            // Isi data ke dalam modal edit
            $('#edit-user-id').val(userId);
            $('#edit-user-name').text(fullName);
            $('#edit_full_name').val(fullName);
            $('#edit_username').val(username);
            
            // Select dropdown yang sesuai
            $('#edit_role').val(role);
            $('#edit_branch_id').val(branchId);
        });

        // --- 2. Logika Modal GANTI PASSWORD ---
        
        // 2a. Mengisi data user saat tombol "Ganti Password" diklik
        $('.btn-change-password').on('click', function() {
            var userId = $(this).data('user-id');
            var userName = $(this).data('user-name');
            
            $('#modal-user-id').val(userId);
            $('#modal-username').text(userName);
            
            // Bersihkan field dan alert setiap kali modal dibuka
            $('#new_password').val('');
            $('#confirm_new_password').val('');
            $('#password-match-alert').addClass('d-none');
            $('#submit-change-password').prop('disabled', true);
        });

        // 2b. Validasi Password secara Reaktif
        function checkPasswordMatch() {
            var password = $('#new_password').val();
            var confirmPassword = $('#confirm_new_password').val();
            var matchAlert = $('#password-match-alert');
            var submitButton = $('#submit-change-password');
            
            if (password.length >= 6 && password === confirmPassword) {
                matchAlert.addClass('d-none');
                submitButton.prop('disabled', false);
                return true;
            } else if (password !== '' && confirmPassword !== '') {
                matchAlert.removeClass('d-none');
                submitButton.prop('disabled', true);
                return false;
            } else {
                matchAlert.addClass('d-none');
                submitButton.prop('disabled', true);
                return false;
            }
        }
        
        // Panggil validasi saat input berubah
        $('#new_password, #confirm_new_password').on('keyup', checkPasswordMatch);
        
        // Panggil validasi saat formulir akan disubmit (sebagai fallback)
        $('#changePasswordModal form').on('submit', function(e) {
            if (!checkPasswordMatch()) {
                e.preventDefault();
                alert('Silakan periksa kembali password baru Anda.');
            }
        });
    });
    </script>
</body>
</html>