<?php
session_start();
// Tambahkan ini
require_once 'config/db.php'; 

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Perbarui ini
    header("location: " . BASE_URL . "/dashboard.php");
    exit;
} else {
    // Perbarui ini
    header("location: " . BASE_URL . "/auth/login.php");
    exit;
}
?>