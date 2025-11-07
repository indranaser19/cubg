<?php
// Cek jika sesi belum aktif, baru mulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php'; 

// Cek jika user belum login, tendang ke halaman login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Perbarui ini
    header("location: " . BASE_URL . "/auth/login.php");
    exit;
}

/**
 * Fungsi untuk membatasi akses halaman berdasarkan role.
 * @param array $allowed_roles Role yang diizinkan (e.g., ['superadmin', 'credit_officer'])
 */
function authorize($allowed_roles) {
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Jika role tidak diizinkan, bisa tampilkan error 403 atau redirect
        http_response_code(403);
        echo "<h1>403 Forbidden</h1>";
        echo "Anda tidak memiliki hak akses untuk halaman ini.";
        exit;
    }
}

/**
 * Fungsi untuk membatasi data hanya untuk cabang user sendiri.
 * @return string Kriteria SQL untuk filter branch_id
 */
function getBranchQueryFilter() {
    if ($_SESSION['role'] == 'superadmin') {
        return "1"; // Superadmin bisa lihat semua
    } else {
        $branch_id = (int)$_SESSION['branch_id'];
        return "branch_id = $branch_id";
    }
}
?>