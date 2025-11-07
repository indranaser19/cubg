<?php
require_once '../middleware/check_auth.php';
// Hanya teller atau superadmin yang bisa akses
authorize(['superadmin', 'teller']);

require_once '../config/db.php';

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
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-people-fill"></i> Daftar Antrian Hari Ini</h5>
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
            const formData = new FormData();
            formData.append('action', action);
            if (id) {
                formData.append('id', id);
            }

            try {
                const response = await fetch('../api/queue_action.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    updateQueueDisplay(); // Perbarui seluruh UI
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
            try {
                const response = await fetch('../api/queue_get_list.php');
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
                    // Aktifkan tombol aksi
                    btnRecall.disabled = false;
                    btnSkip.disabled = false;
                    btnFinish.disabled = false;
                } else {
                    currentCallDisplay.textContent = '-';
                    currentCallIdInput.value = '0';
                    // Nonaktifkan tombol aksi
                    btnRecall.disabled = true;
                    btnSkip.disabled = true;
                    btnFinish.disabled = true;
                }
                
                // 4. Atur tombol Panggil Berikutnya
                btnCallNext.disabled = (waitingCount === 0);

            } catch (error) {
                console.error('Update error:', error);
            }
        }

        // Event Listeners
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
        
        // Atur polling untuk refresh data (misal setiap 5 detik)
        // Ini agar jika ada teller lain di cabang yg sama, tampilannya update
        setInterval(updateQueueDisplay, 5000); 
    });
    </script>
</body>
</html>