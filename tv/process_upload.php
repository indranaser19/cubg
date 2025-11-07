<?php
require_once '../middleware/check_auth.php';
authorize(['superadmin', 'admin_tv']);

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // === 1. LOGIKA KEAMANAN ROLE (TELAH DIPERBAIKI) ===
    $user_id = $_SESSION['user_id'];
    $branch_id = null;

    if ($_SESSION['role'] == 'superadmin') {
        $branch_id = $_POST['branch_id'] ?? null;
        if (empty($branch_id)) {
            header("location: manage.php?error=Superadmin wajib memilih cabang.");
            exit;
        }
        
        // === INI PERBAIKANNYA ===
        // Ubah string "all" dari form menjadi integer 0 (untuk GLOBAL)
        // === INI PERBAIKANNYA ===
        // Ubah string "all" dari form menjadi NULL (untuk GLOBAL)
        if ($branch_id === 'all') {
            $branch_id = null; 
        }
        // ========================

    } else {
        $branch_id = $_SESSION['branch_id'];
    }

    // Ambil data form lainnya
    $type = $_POST['type'];
    $title = $_POST['title'] ?? null;
    $duration = $_POST['duration_seconds'];
    $order = $_POST['slide_order'];
    $media_path = $_POST['media_path'] ?? null;

    try {
        // Jika tipenya bukan youtube, harus ada file upload
        if ($type == 'image' || $type == 'video') {
            if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] != UPLOAD_ERR_OK) {
                throw new Exception("File media wajib di-upload dan tidak boleh error.");
            }
            
            $upload_dir = "../uploads/tv_media/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['media_file']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_file_name = uniqid('slide_', true) . '.' . $file_ext;
            $target_path = $upload_dir . $new_file_name;
            
            if ($type == 'image' && !in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception("Tipe file gambar tidak valid. Harus .jpg, .jpeg, .png, atau .gif. (File Anda: .$file_ext)");
            }
            if ($type == 'video' && $file_ext != 'mp4') {
                throw new Exception("Tipe file video harus MP4. (File Anda: .$file_ext)");
            }

            if (!move_uploaded_file($_FILES['media_file']['tmp_name'], $target_path)) {
                throw new Exception("Gagal memindahkan file upload.");
            }
            
            $media_path = $target_path;

        // ================== LOGIKA YOUTUBE ==================
        } else if ($type == 'youtube') {
            if (empty($media_path)) {
                throw new Exception("URL YouTube wajib diisi.");
            }

            $video_id = null;

            if (preg_match('/(https\:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/(watch\?v=|embed\/|v\/|u\/\w\/|show\/|shorts\/|watch\?v%3D|watch\?v\=)?([\w\-]{11})(.*)?/i', $media_path, $matches)) {
                if (isset($matches[5]) && strlen($matches[5]) == 11) {
                    $video_id = $matches[5];
                }
            }

            if ($video_id) {
                $media_path = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&mute=1&loop=1&playlist=' . $video_id . '&controls=0&showinfo=0&modestbranding=1';
            } else {
                throw new Exception("URL YouTube tidak valid. Pastikan Anda menyalin URL 'watch' atau 'share'.");
            }
        }
        // ================== AKHIR YOUTUBE ==================


        // 3. Simpan ke database
        // $branch_id sekarang adalah 0 jika "Global", atau ID cabang jika spesifik
        $sql = "INSERT INTO tv_slides (branch_id, uploaded_by_user_id, type, title, media_path, duration_seconds, slide_order)
                VALUES (:branch_id, :user_id, :type, :title, :media_path, :duration, :order)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':branch_id' => $branch_id, // Ini sekarang aman (berisi 0 atau angka ID)
            ':user_id' => $user_id,
            ':type' => $type,
            ':title' => $title,
            ':media_path' => $media_path,
            ':duration' => $duration,
            ':order' => $order
        ]);

        header("location: manage.php?success=Slide baru berhasil ditambahkan.");

    } catch (Exception $e) {
        // Redirect kembali dengan pesan error yang jelas
        header("location: manage.php?error=" . urlencode($e->getMessage()));
    }

} else {
    // Jika bukan method POST, tendang
    header("location: manage.php");
    exit;
}
?>