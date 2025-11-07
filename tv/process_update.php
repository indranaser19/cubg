<?php
require_once '../middleware/check_auth.php';
authorize(['superadmin']);

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $branch_id = $_POST['branch_id'];
    $type = $_POST['type'];
    $title = $_POST['title'] ?? null;
    $duration = $_POST['duration_seconds'];
    $order = $_POST['slide_order'];
    $user_id = $_SESSION['user_id'];
    $media_path = $_POST['media_path'] ?? null;

    try {
        // Jika tipenya bukan youtube, harus ada file upload
        if ($type == 'image' || $type == 'video') {
            if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] != UPLOAD_ERR_OK) {
                throw new Exception("File media wajib di-upload untuk tipe $type.");
            }
            
            $upload_dir = "../uploads/tv_media/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['media_file']['name']);
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid('slide_', true) . '.' . $file_ext;
            $target_path = $upload_dir . $new_file_name;
            
            // Validasi tipe file
            if ($type == 'image' && !in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception("Tipe file gambar tidak valid.");
            }
            if ($type == 'video' && $file_ext != 'mp4') {
                throw new Exception("Tipe file video harus MP4.");
            }

            if (!move_uploaded_file($_FILES['media_file']['tmp_name'], $target_path)) {
                throw new Exception("Gagal memindahkan file upload.");
            }
            
            $media_path = $target_path; // Set media_path ke file yg di-upload

        } else if ($type == 'youtube') {
            if (empty($media_path)) {
                throw new Exception("URL YouTube wajib diisi.");
            }
            // Ubah URL youtube standar menjadi URL embed
            if (strpos($media_path, 'watch?v=') !== false) {
                parse_str(parse_url($media_path, PHP_URL_QUERY), $vars);
                $media_path = 'https://www.youtube.com/embed/' . $vars['v'] . '?autoplay=1&mute=1&loop=1&playlist=' . $vars['v'];
            }
        }

        // Simpan ke database
        $sql = "INSERT INTO tv_slides (branch_id, uploaded_by_user_id, type, title, media_path, duration_seconds, slide_order)
                VALUES (:branch_id, :user_id, :type, :title, :media_path, :duration, :order)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':branch_id' => $branch_id,
            ':user_id' => $user_id,
            ':type' => $type,
            ':title' => $title,
            ':media_path' => $media_path,
            ':duration' => $duration,
            ':order' => $order
        ]);

        header("location: manage.php?success=Slide baru berhasil ditambahkan.");

    } catch (Exception $e) {
        header("location: manage.php?error=" . urlencode($e->getMessage()));
    }

}
?>