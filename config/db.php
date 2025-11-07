<?php
define('BASE_URL', 'http://localhost/cubg');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Ganti dengan password DB Anda
define('DB_NAME', 'cubg_db');
define('TIMEZONE', 'Asia/Jakarta');

date_default_timezone_set(TIMEZONE);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("ERROR: Tidak dapat terhubung ke database. " . $e->getMessage());
}
?>