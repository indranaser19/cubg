<?php
session_start();
// Tambahkan ini
require_once '../config/db.php'; 

$_SESSION = array();
session_destroy();

// Perbarui ini
header("location: " . BASE_URL . "/auth/login.php");
exit;
?>