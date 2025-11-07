<?php
require_once '../middleware/check_auth.php';
// Hanya admin_tv atau superadmin
authorize(['superadmin', 'admin_tv']);

require_once '../config/db.php';

// Ambil data cabang (untuk filter, form, dan teks berjalan)
$stmt_branches = $pdo->query("SELECT id, name, running_text FROM branches ORDER BY name");
$branches = $stmt_branches->fetchAll(PDO::FETCH_ASSOC);

// Logika Filter
$filter_branch_id = $_GET['branch_id'] ?? ''; // <-- INI UNTUK FILTER LIST
$where_clause = "";
$params = [];

// Logika Filter
$filter_branch_id = $_GET['branch_id'] ?? ''; // <-- INI UNTUK FILTER LIST
$where_clause = "";
$params = [];

// Filter list HANYA untuk cabang sendiri jika bukan superadmin
if ($_SESSION['role'] != 'superadmin') {
    $filter_branch_id = $_SESSION['branch_id'];
}

// === PERBAIKAN DI SINI ===
// Kita hanya memfilter jika branch_id TIDAK KOSONG dan BERUPA ANGKA.
// Ini akan mengabaikan nilai non-numerik seperti "all" dan mencegah error.
if (!empty($filter_branch_id) && is_numeric($filter_branch_id)) { 
    $where_clause = "WHERE s.branch_id = :branch_id";
    $params[':branch_id'] = (int)$filter_branch_id; // (int) untuk keamanan
}
// Jika $filter_branch_id = "" (dari "Semua Cabang"), kondisi ini false, dan semua cabang akan ditampilkan.
// Jika $filter_branch_id = "all", kondisi ini juga false, dan semua cabang akan ditampilkan (mencegah error).

// Ambil slides yang ada
$sql = "SELECT s.*, b.name as branch_name, u.full_name as uploader_name
        FROM tv_slides s
        JOIN branches b ON s.branch_id = b.id
        JOIN users u ON s.uploaded_by_user_id = u.id
        $where_clause
        ORDER BY s.branch_id, s.slide_order ASC";
$stmt_slides = $pdo->prepare($sql);
$stmt_slides->execute($params);
$slides = $stmt_slides->fetchAll(PDO::FETCH_ASSOC);

// Ambil nama cabang user (hanya untuk form upload slide)
$user_branch_name = '';
if ($_SESSION['role'] != 'superadmin') {
    foreach ($branches as $branch) {
        if ($branch['id'] == $_SESSION['branch_id']) {
            $user_branch_name = $branch['name'];
            break;
        }
    }
}

// Ambil running text dari cabang pertama sebagai contoh
$current_running_text = '';
if (!empty($branches)) {
    $current_running_text = $branches[0]['running_text']; 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Slide TV Informasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa; /* Latar belakang lebih cerah */
        }
        
        .card {
            /* Shadow lebih jelas */
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
            border: none;
        }
        
        .card-header {
            background-color: #ffffff;
            border-bottom: 2px solid #0d6efd; /* Aksen biru di header */
        }

        /* --- CSS Untuk Drag & Drop --- */
        .ui-state-highlight {
            height: 90px;
            background-color: #e9ecef;
            border: 2px dashed #0d6efd;
        }
        .drag-handle {
            cursor: move;
            vertical-align: middle;
            text-align: center;
            width: 40px;
        }

        /* --- CSS Untuk Tabel Responsif --- */
        @media (max-width: 992px) {
            .responsive-table thead {
                display: none; /* Sembunyikan header tabel di mobile */
            }
            .responsive-table tbody, .responsive-table tr, .responsive-table td {
                display: block;
                width: 100%;
            }
            .responsive-table tr {
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                overflow: hidden; /* Agar preview rapi */
            }
            .responsive-table td {
                padding: 0.75rem 1rem;
                border: none;
                border-bottom: 1px solid #e9ecef;
                text-align: right; /* Ratakan kanan data */
                position: relative;
                min-height: 48px; /* Tinggi minimum agar rapi */
            }
            .responsive-table td:last-child {
                border-bottom: none;
            }
            /* Buat label di kiri menggunakan data-label */
            .responsive-table td[data-label]::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                font-weight: 600;
                color: #212529;
                text-align: left;
            }
            
            /* Penyesuaian khusus untuk sel tertentu */
            .responsive-table .drag-handle {
                text-align: center !important;
                background-color: #f8f9fa;
            }
            .responsive-table .drag-handle::before {
                display: none; /* Sembunyikan label "Pindah" */
            }
            .responsive-table .preview-cell {
                text-align: center !important; /* Tengahkan preview */
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
            .responsive-table .preview-cell::before {
                display: none; /* Sembunyikan label "Preview" */
            }
        }
        
        /* CSS Untuk Preview Upload */
        #file_preview {
            display: none; /* Sembunyi by default */
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
        }
        #file_preview img, #file_preview video, #file_preview iframe {
            max-width: 100%;
            height: auto;
            max-height: 200px;
            display: block;
            margin: 0 auto;
            border-radius: 0.25rem;
        }
        #file_preview p {
            margin-bottom: 0;
            font-style: italic;
            color: #6c757d;
        }

    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4 mb-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="display-6 mb-0">Manajemen TV Informasi</h2>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-lg h-100">
                    <div class="card-header py-3">
                        <h4 class="mb-0 fw-bold"><i class="bi bi-upload me-2"></i>Upload Slide Baru</h4>
                    </div>
                    <div class="card-body">
                        <form id="upload_form" action="process_upload.php" method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="branch_id" class="form-label">Cabang</label>
                                    <?php if ($_SESSION['role'] == 'superadmin'): ?>
                                        <select name="branch_id" id="branch_id" class="form-select" required>
                                            <option value="" selected disabled>-- Pilih Cabang --</option>
                                            <option value="all">GLOBAL (Semua Cabang)</option>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="hidden" name="branch_id" value="<?php echo $_SESSION['branch_id']; ?>">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_branch_name); ?>" readonly disabled>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="type" class="form-label">Tipe Konten</label>
                                    <select name="type" id="type" class="form-select" required>
                                        <option value="image" selected>Gambar (JPG/PNG)</option>
                                        <option value="youtube">Video (YouTube URL)</option>
                                    </select>
                                </div>
                                <div class="col-12" id="field_media_file">
                                    <label for="media_file" class="form-label">File Media</label>
                                    <input type="file" name="media_file" id="media_file" class="form-control" accept="image/jpeg,image/png">
                                </div>
                                <div class="col-12" id="field_media_path" style="display: none;">
                                    <label for="media_path" class="form-label">URL Media (YouTube)</label>
                                    <input type="text" name="media_path" id="media_path" class="form-control" placeholder="cth: https://www.youtube.com/watch?v=XXXXXX">
                                </div>
                                <div class="col-md-8">
                                    <label for="title" class="form-label">Judul / Deskripsi Singkat</label>
                                    <input type="text" name="title" id="title" class="form-control" placeholder="(Opsional)">
                                </div>
                                <div class="col-md-4">
                                    <label for="duration_seconds" class="form-label">Durasi (detik)</label>
                                    <input type="number" name="duration_seconds" id="duration_seconds" class="form-control" value="10" required>
                                </div>
                                
                                <div class="col-12">
                                    <div id="file_preview"></div>
                                </div>
                                
                                <input type="hidden" name="slide_order" value="99"> 
                                <div class="col-12 text-end">
                                    <button type="submit" id="upload_submit_btn" class="btn btn-primary btn-lg">
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        <i class="bi bi-upload me-1"></i> Upload Slide
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php if ($_SESSION['role'] == 'superadmin'): ?>
            <div class="col-lg-6">
                <div class="card shadow-lg h-100">
                    <div class="card-header py-3">
                        <h4 class="mb-0 fw-bold"><i class="bi bi-input-cursor-text me-2"></i>Update Teks Berjalan (Global)</h4>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <form id="text_form" action="process_update_text.php" method="POST" class="d-flex flex-column flex-grow-1">
                            <div class="row g-3 flex-grow-1">
                                <div class="col-12">
                                    <label for="running_text" class="form-label">Teks Berjalan (Akan di-update ke SEMUA cabang)</label>
                                    <textarea name="running_text" id="running_text" class="form-control" rows="5" style="min-height: 150px;"><?php echo htmlspecialchars($current_running_text ?? ''); ?></textarea>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Teks ini akan menggantikan teks berjalan di semua TV cabang.</small>
                                        <small id="char_count" class="text-muted">0 karakter</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 text-end mt-auto pt-3">
                                <button type="submit" id="text_submit_btn" class="btn btn-primary btn-lg">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    <i class="bi bi-check-circle me-1"></i> Update Teks Global
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card shadow-lg">
            <div class="card-header py-3">
                <h4 class="mb-0 fw-bold"><i class="bi bi-images me-2"></i>Daftar Slide Aktif</h4>
            </div>
            <div class="card-body">

                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                <form method="GET" action="manage.php" class="mb-4 p-3 bg-light rounded border-light">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5 col-lg-4">
                            <label for="filter_branch_id" class="form-label fw-bold">Filter per Cabang</label>
                            <select name="branch_id" id="filter_branch_id" class="form-select form-select-lg">
                                <option value="">Semua Cabang</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" <?php echo ($filter_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <a href="manage.php" class="btn btn-outline-secondary btn-lg w-100">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle responsive-table">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" colspan="2">Pindah</th> 
                                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                                    <th scope="col">Cabang</th>
                                <?php endif; ?>
                                <th scope="col">Preview</th>
                                <th scope="col">Tipe</th>
                                <th scope="col">Konten</th>
                                <th scope="col">Durasi</th>
                                <th scope="col">Urutan</th>
                                <th scope="col">Uploader</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="slide-table-body">
                            <?php if (empty($slides)): ?>
                                <tr>
                                    <td colspan="<?php echo ($_SESSION['role'] == 'superadmin') ? '9' : '8'; ?>" class="text-center py-5">
                                        <h4 class="text-muted">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <?php echo !empty($filter_branch_id) ? 'Tidak ada slide untuk cabang ini.' : 'Belum ada slide yang diupload.'; ?>
                                        </h4>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php foreach ($slides as $slide): ?>
                            <tr data-id="<?php echo $slide['id']; ?>">
                                <td class="drag-handle" data-label="Pindah">
                                    <i class="bi bi-grip-vertical fs-4 text-muted"></i>
                                </td>
                                <td></td> <?php if ($_SESSION['role'] == 'superadmin'): ?>
                                    <td data-label="Cabang"><?php echo htmlspecialchars($slide['branch_name']); ?></td>
                                <?php endif; ?>
                                <td class="preview-cell" style="width: 150px;">
                                    <?php
                                    $preview_style = "width: 120px; height: 70px; object-fit: cover; background-color: #222; border-radius: 4px; border: 1px solid #ddd;";
                                    switch ($slide['type']) {
                                        case 'image':
                                            echo '<img src="' . htmlspecialchars($slide['media_path']) . '" alt="' . htmlspecialchars($slide['title']) . '" style="' . $preview_style . '">';
                                            break;
                                        case 'video':
                                            echo '<video src="' . htmlspecialchars($slide['media_path']) . '" style="' . $preview_style . '" muted playsinline></video>';
                                            break;
                                        case 'youtube':
                                            $embed_url = str_replace("watch?v=", "embed/", $slide['media_path']);
                                            echo '<iframe src="' . htmlspecialchars($embed_url) . '" style="' . $preview_style . ' border: none;" allowfullscreen></iframe>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td data-label="Tipe"><span class="badge bg-primary fs-6"><?php echo $slide['type']; ?></span></td>
                                <td data-label="Konten"><?php echo htmlspecialchars($slide['title'] ?: $slide['media_path']); ?></td>
                                <td data-label="Durasi"><?php echo $slide['duration_seconds']; ?> dtk</td>
                                <td data-label="Urutan"><?php echo $slide['slide_order']; ?></td>
                                <td data-label="Uploader"><?php echo htmlspecialchars($slide['uploader_name']); ?></td>
                                <td data-label="Aksi">
                                    <a href="process_delete.php?id=<?php echo $slide['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin hapus slide ini?');">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="reorderToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Urutan slide berhasil disimpan!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const toastEl = document.getElementById('reorderToast');
        const toast = new bootstrap.Toast(toastEl);

        // === 1. Skrip Form Reaktif ===
        
        const typeSelect = document.getElementById('type');
        const fieldMediaFile = document.getElementById('field_media_file');
        const fieldMediaPath = document.getElementById('field_media_path');
        const mediaFileInput = document.getElementById('media_file');
        const mediaPathInput = document.getElementById('media_path');
        const previewContainer = document.getElementById('file_preview');
        const runningTextArea = document.getElementById('running_text');
        const charCountEl = document.getElementById('char_count');

        // Fungsi toggle Tipe Konten
        function toggleFields() {
            previewContainer.style.display = 'none'; 
            previewContainer.innerHTML = '';
            
            if (typeSelect.value === 'youtube') {
                fieldMediaFile.style.display = 'none';
                mediaFileInput.required = false;
                
                fieldMediaPath.style.display = 'block';
                mediaPathInput.required = true;
            } else { 
                fieldMediaFile.style.display = 'block';
                mediaFileInput.required = true;

                fieldMediaPath.style.display = 'none';
                mediaPathInput.required = false;
            }
        }
        
        // Fungsi update preview (REAKTIF)
        function updatePreview() {
            previewContainer.style.display = 'none';
            previewContainer.innerHTML = '';
            
            // --- Preview untuk File Upload (HANYA GAMBAR) ---
            if (mediaFileInput.files && mediaFileInput.files[0]) {
                const file = mediaFileInput.files[0];
                const fileType = file.type;
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    let previewElement = '';
                    if (fileType.startsWith('image/')) {
                        previewElement = `<img src="${e.target.result}" alt="Preview">`;
                    }
                    // === PERUBAHAN DI SINI: Logika preview video dihapus ===
                    previewContainer.innerHTML = previewElement;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
            
            // --- Preview untuk YouTube ---
            else if (mediaPathInput.value.trim() !== '') {
                let url = mediaPathInput.value.trim();
                let videoId = null;
                const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|shorts\/)([^#\&\?]*).*/;
                const match = url.match(regExp);
                if (match && match[2].length == 11) {
                    videoId = match[2];
                }
                
                if(videoId) {
                    const embedUrl = `https://www.youtube.com/embed/${videoId}`;
                    previewContainer.innerHTML = `<iframe src="${embedUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
                    previewContainer.style.display = 'block';
                } else {
                    previewContainer.innerHTML = `<p>URL YouTube tidak valid. Gunakan format 'watch?v=...'</p>`;
                    previewContainer.style.display = 'block';
                }
            }
        }
        
        // Fungsi update character counter
        function updateCharCount() {
            const count = runningTextArea.value.length;
            charCountEl.textContent = `${count} karakter`;
        }

        toggleFields();
        updateCharCount();

        typeSelect.addEventListener('change', toggleFields);
        mediaFileInput.addEventListener('change', updatePreview);
        mediaPathInput.addEventListener('input', updatePreview); 
        runningTextArea.addEventListener('input', updateCharCount);

        // === 2. Skrip Form Loading Spinner ===
        
        const uploadForm = document.getElementById('upload_form');
        const uploadBtn = document.getElementById('upload_submit_btn');
        const textForm = document.getElementById('text_form');
        const textBtn = document.getElementById('text_submit_btn');

        function setButtonLoading(button, isLoading) {
            const spinner = button.querySelector('.spinner-border');
            const icon = button.querySelector('.bi');
            if (isLoading) {
                button.disabled = true;
                spinner.classList.remove('d-none');
                if(icon) icon.classList.add('d-none');
            } else {
                button.disabled = false;
                spinner.classList.add('d-none');
                if(icon) icon.classList.remove('d-none');
            }
        }

        uploadForm.addEventListener('submit', function() {
            setButtonLoading(uploadBtn, true);
        });
        
        textForm.addEventListener('submit', function() {
            setButtonLoading(textBtn, true);
        });

        
        // === 3. Skrip Drag & Drop (Sortable) dengan Toast ===
        
        $("#slide-table-body").sortable({
            handle: ".drag-handle", 
            placeholder: "ui-state-highlight",
            axis: "y", 
            
            update: function(event, ui) {
                var order = [];
                var branchId = "<?php echo $filter_branch_id; ?>";
                
                $(this).find('tr').each(function() {
                    if ($(this).data('id')) {
                        order.push($(this).data('id'));
                    }
                });

                $.ajax({
                    url: 'process_slide_reorder.php',
                    type: 'POST',
                    data: {
                        order: order,
                        branch_id: branchId 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toast.show(); 
                            
                            $('#slide-table-body tr[data-id]').each(function(index) {
                                $(this).find('td[data-label="Urutan"]').text(index + 1);
                            });
                        } else {
                            alert('Gagal menyimpan urutan: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error: Tidak dapat terhubung ke server.');
                    }
                });
            }
        }).disableSelection();
    });
    </script>
    
</body>
</html>