<?php
require_once '../middleware/check_auth.php';
// Hanya teller atau superadmin yang bisa akses
authorize(['superadmin', 'teller']);

// config/db.php sudah di-include via check_auth.php, 
// sehingga $pdo dan BASE_URL sudah tersedia.

// Ambil nama cabang teller
$branch_id = $_SESSION['branch_id'];
$stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
$stmt->execute([$branch_id]);
$branch_name = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Antrian Teller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #currentCall {
            font-size: 5rem;
            font-weight: bold;
        }
        .queue-list {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-person-video3"></i> Panel Panggil Antrian
                            <span class="badge bg-success float-end"><?php echo htmlspecialchars($branch_name); ?></span>
                        </h4>
                    </div>
                    <div class="card-body text-center">
                        <h5 class="text-muted">SEDANG DIPANGGIL</h5>
                        <div id="currentCall" class="text-primary my-3">-</div>
                        <input type="hidden" id="current_call_id" value="0">
                        
                        <div class="d-grid gap-2">
                            <button id="btnCallNext" class="btn btn-primary btn-lg">
                                <i class="bi bi-megaphone-fill"></i> PANGGIL BERIKUTNYA
                            </button>
                            <div class="row">
                                <div class="col">
                                    <button id="btnRecall" class="btn btn-secondary btn-lg w-100">
                                        <i class="bi bi-arrow-clockwise"></i> Panggil Ulang
                                    </button>
                                </div>
                                <div class="col">
                                    <button id="btnSkip" class="btn btn-warning btn-lg w-100">
                                        <i class="bi bi-skip-forward-fill"></i> Lewati
                                    </button>
                                </div>
                                <div class="col">
                                    <button id="btnFinish" class="btn btn-success btn-lg w-100">
                                        <i class="bi bi-check-circle-fill"></i> Selesai
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-people-fill"></i> Daftar Antrian Hari Ini</h5>
                        <button id="btnCreateManual" class="btn btn-sm btn-outline-primary" title="Buat Antrian Baru">
                            <i class="bi bi-plus-circle"></i> Buat Manual
                        </button>
                    </div>
                    <div class="card-body">
                        <h6><i class="bi bi-hourglass-split"></i> Menunggu (<span id="countWaiting">0</span>)</h6>
                        <ul id="listWaiting" class="list-group queue-list mb-3">
                            </ul>
                        
                        <h6><i class="bi bi-check-all"></i> Sudah Dilayani (<span id="countFinished">0</span>)</h6>
                        <ul id="listFinished" class="list-group queue-list">
                            </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tentukan URL API
        const API_ACTION_URL = '<?php echo BASE_URL; ?>/api/queue_action.php';
        const API_LIST_URL = '<?php echo BASE_URL; ?>/api/queue_get_list.php';
        
        // ==================== PERUBAHAN JAVASCRIPT (1) ====================
        const API_CREATE_URL = '<?php echo BASE_URL; ?>/api/queue_create_manual.php';
        const btnCreateManual = document.getElementById('btnCreateManual');
        // ================== AKHIR PERUBAHAN JAVASCRIPT (1) ==================

        const currentCallDisplay = document.getElementById('currentCall');
        const currentCallIdInput = document.getElementById('current_call_id');
        const btnCallNext = document.getElementById('btnCallNext');
        const btnRecall = document.getElementById('btnRecall');
        const btnSkip = document.getElementById('btnSkip');
        const btnFinish = document.getElementById('btnFinish');
        const listWaiting = document.getElementById('listWaiting');
        const listFinished = document.getElementById('listFinished');
        const countWaiting = document.getElementById('countWaiting');
        const countFinished = document.getElementById('countFinished');

        // Fungsi utama untuk memanggil API
        async function queueAction(action, id = null) {
            // ... (Kode fungsi queueAction tetap sama) ...
            const formData = new FormData();
            formData.append('action', action);
            if (id) {
                formData.append('id', id);
            }
            try {
                const response = await fetch(API_ACTION_URL, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    updateQueueDisplay();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('Tidak dapat terhubung ke server.');
            }
        }

        // Fungsi untuk update tampilan
        async function updateQueueDisplay() {
            // ... (Kode fungsi updateQueueDisplay tetap sama) ...
            try {
                const response = await fetch(API_LIST_URL);
                const data = await response.json();
                
                // 1. Update list Menunggu
                listWaiting.innerHTML = '';
                let waitingCount = 0;
                if (data.waiting.length > 0) {
                    data.waiting.forEach(q => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item';
                        li.textContent = `Nomor ${q.queue_number}`;
                        listWaiting.appendChild(li);
                        waitingCount++;
                    });
                } else {
                    listWaiting.innerHTML = '<li class="list-group-item text-muted">Antrian kosong</li>';
                }
                countWaiting.textContent = waitingCount;
                
                // 2. Update list Selesai
                listFinished.innerHTML = '';
                let finishedCount = 0;
                if (data.history.length > 0) {
                    data.history.forEach(q => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between';
                        li.innerHTML = `Nomor ${q.queue_number} <span class="badge bg-${q.status === 'finished' ? 'success' : 'warning'}">${q.status}</span>`;
                        listFinished.appendChild(li);
                        if(q.status === 'finished') finishedCount++;
                    });
                } else {
                    listFinished.innerHTML = '<li class="list-group-item text-muted">Belum ada riwayat</li>';
                }
                countFinished.textContent = finishedCount;

                // 3. Update Panggilan Saat Ini
                if (data.currently_called) {
                    currentCallDisplay.textContent = data.currently_called.queue_number;
                    currentCallIdInput.value = data.currently_called.id;
                    btnRecall.disabled = false;
                    btnSkip.disabled = false;
                    btnFinish.disabled = false;
                } else {
                    currentCallDisplay.textContent = '-';
                    currentCallIdInput.value = '0';
                    btnRecall.disabled = true;
                    btnSkip.disabled = true;
                    btnFinish.disabled = true;
                }
                
                // 4. Atur tombol Panggil Berikutnya
                btnCallNext.disabled = (waitingCount === 0 && !data.currently_called);
                
                // Jika sedang ada yg dipanggil, tombol Panggil Berikutnya juga nonaktif
                if (data.currently_called) {
                    btnCallNext.disabled = true;
                }

            } catch (error) {
                console.error('Update error:', error);
            }
        }
        
        // ==================== PERUBAHAN JAVASCRIPT (2) ====================
        // Fungsi baru untuk membuat antrian manual
        async function createManualQueue() {
            btnCreateManual.disabled = true; // Nonaktifkan tombol sementara
            try {
                const response = await fetch(API_CREATE_URL, {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    // Jika sukses, refresh daftar antrian
                    updateQueueDisplay();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Create error:', error);
                alert('Tidak dapat membuat antrian.');
            }
            btnCreateManual.disabled = false; // Aktifkan kembali tombolnya
        }

        // Tambahkan Event Listener untuk tombol baru
        btnCreateManual.addEventListener('click', createManualQueue);
        // ================== AKHIR PERUBAHAN JAVASCRIPT (2) ==================


        // Event Listeners (yang sudah ada)
        btnCallNext.addEventListener('click', () => queueAction('call_next'));
        btnRecall.addEventListener('click', () => {
            const id = currentCallIdInput.value;
            if (id !== '0') queueAction('recall', id);
        });
        btnSkip.addEventListener('click', () => {
            const id = currentCallIdInput.value;
            if (id !== '0') queueAction('skip', id);
        });
        btnFinish.addEventListener('click', () => {
            const id = currentCallIdInput.value;
            if (id !== '0') queueAction('finish', id);
        });

        // Muat data saat halaman dibuka
        updateQueueDisplay();
        
        // Atur polling untuk refresh data
        setInterval(updateQueueDisplay, 5000); 
    });
    </script>
</body>
</html>