<?php
// Pastikan file config/db.php sudah di-include SEBELUM navbar ini dipanggil
// (File seperti dashboard.php, loans/index.php, dll, harus memanggil config/db.php)

// Anda harus mendefinisikan BASE_URL di file config/config.php atau sejenisnya
if (!defined('BASE_URL')) {
    // Asumsi default jika BASE_URL belum didefinisikan (Hanya untuk keamanan)
    define('BASE_URL', '/cubg'); 
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_admin_area = ($current_dir == 'admin' || $current_dir == 'users'); // Tambahkan direktori lain jika perlu
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand text-primary fw-bold" href="<?php echo BASE_URL; ?>/dashboard.php">
            <i class="bi bi-bank2 me-1"></i> CUBIZ
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                
                <?php
                // --- Helper function untuk menentukan kelas aktif ---
                function get_active_class($page_name, $dir_name = null) {
                    global $current_page, $current_dir;
                    
                    if ($dir_name) {
                        return ($current_dir == $dir_name) ? 'active bg-light rounded-pill px-3' : '';
                    }
                    return ($current_page == $page_name) ? 'active bg-light rounded-pill px-3' : '';
                }
                ?>
                
                <li class="nav-item">
                    <a class="nav-link text-dark <?php echo get_active_class('dashboard.php'); ?>" href="<?php echo BASE_URL; ?>/dashboard.php">
                        <i class="bi bi-house-door-fill me-1"></i> Dashboard
                    </a>
                </li>
                
                <?php if (in_array($_SESSION['role'], ['superadmin', 'credit_officer', 'branch_user'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-dark <?php echo get_active_class(null, 'loans'); ?>" href="#" id="navbarDropdownLoans" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-journal-text me-1"></i> Kredit
                        </a>
                        <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarDropdownLoans">
                            <?php if (in_array($_SESSION['role'], ['superadmin', 'credit_officer'])): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/loans/create.php">
                                    <i class="bi bi-file-earmark-plus"></i> Buat Pengajuan Baru
                                </a></li>
                            <?php endif; ?>
                            
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/loans/index.php">
                                <i class="bi bi-binoculars"></i> Tracking Pinjaman
                            </a></li>
                            
                            <?php if (in_array($_SESSION['role'], ['superadmin', 'credit_officer'])): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-muted" href="<?php echo BASE_URL; ?>/loans/recycle_bin.php">
                                    <i class="bi bi-recycle"></i> Recycle Bin
                                </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['role'], ['superadmin', 'user_diklat'])): ?>
                <li class="nav-item">
                    <a class="nav-link text-dark <?php echo get_active_class(null, 'diklat'); ?>" href="<?php echo BASE_URL; ?>/diklat/index.php">
                        <i class="bi bi-file-earmark-person me-1"></i> Diklat
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array($_SESSION['role'], ['teller'])): ?>
                <li class="nav-item">
                    <a class="nav-link text-dark <?php echo get_active_class(null, 'teller'); ?>" href="<?php echo BASE_URL; ?>/teller/manage.php">
                        <i class="bi bi-person-video3 me-1"></i> Panel Teller
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['role'], ['superadmin', 'admin_tv'])): ?>
                <li class="nav-item">
                    <a class="nav-link text-dark <?php echo get_active_class(null, 'tv'); ?>" href="<?php echo BASE_URL; ?>/tv/manage.php">
                        <i class="bi bi-tv-fill me-1"></i> Manajemen TV
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['role'], ['superadmin', 'credit_officer', 'teller'])): ?>
                <li class="nav-item">
                    <a class="nav-link text-dark <?php echo get_active_class(null, 'reports'); ?>" href="<?php echo BASE_URL; ?>/reports/queue.php">
                        <i class="bi bi-bar-chart-line-fill me-1"></i> Laporan
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-dark <?php echo get_active_class(null, 'admin') || get_active_class(null, 'users') ? 'active bg-light rounded-pill px-3' : ''; ?>" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear-fill me-1"></i> Administrasi
                    </a>
                    <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarDropdownAdmin">
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users_manage.php">
                                <i class="bi bi-people-fill"></i> Manajemen User
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle btn btn-outline-secondary rounded-pill ps-3 pe-4 py-2 d-flex align-items-center" href="#" id="navbarDropdownProfile" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-2 text-primary"></i>
                        <span class="d-lg-inline d-none fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="navbarDropdownProfile">
                        <li>
                            <span class="dropdown-item-text text-wrap">
                                **<?php echo htmlspecialchars($_SESSION['full_name']); ?>** <br> 
                                <span class="badge bg-primary"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a href="<?php echo BASE_URL; ?>/auth/logout.php" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>