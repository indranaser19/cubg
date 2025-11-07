<?php
// Mencegah akses langsung ke direktori.
// Mengarahkan pengguna ke halaman utama (login/dashboard).

header('Location: ../index.php'); // Asumsi halaman utama/login berada di ../index.php
exit;
?>