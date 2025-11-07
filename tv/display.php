<?php
require_once '../config/db.php';

if (!isset($_GET['branch'])) {
    die("Error: ID Cabang tidak ditentukan. Tambahkan ?branch=ID_CABANG di URL.");
}
$branch_id = (int)$_GET['branch'];


// Helper function untuk URL YouTube
function getYouTubeEmbedUrl($url) {
    // === REGEX DIPERBARUI (MENAMBAHKAN 'shorts/') ===
    preg_match(
        '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=|shorts/)|youtu\.be/)([^"&?/ ]{11})%i',
        $url,
        $match
    );
    $video_id = $match[1] ?? null;

    if ($video_id) {
        return "https://www.youtube.com/embed/" . $video_id . 
               "?autoplay=1&mute=1&controls=0&loop=1&playlist=" . $video_id;
    }
    
    if (strpos($url, 'youtube.com/embed') !== false) {
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        if (strpos($url, 'autoplay=') === false) $url .= $separator . 'autoplay=1';
        if (strpos($url, 'mute=') === false) $url .= '&mute=1';
        if (strpos($url, 'loop=') === false) $url .= '&loop=1';
        return $url;
    }

    return $url;
}

// Ambil data cabang (HANYA DATA AWAL)
$stmt = $pdo->prepare("SELECT name, running_text FROM branches WHERE id = ?");
$stmt->execute([$branch_id]);
$branch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$branch) {
    die("Error: Cabang tidak ditemukan.");
}
$branch_name = $branch['name'];
$running_text = $branch['running_text']; // Ini untuk tampilan awal

// Ambil daftar slide (DATA STATIS, HANYA SEKALI LOAD)
$stmt_slides = $pdo->prepare(
    "SELECT * FROM tv_slides 
     WHERE (branch_id = ? OR branch_id IS NULL) 
     AND is_active = 1 
     ORDER BY slide_order ASC"
);
$stmt_slides->execute([$branch_id]);
$slides = $stmt_slides->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Informasi - <?php echo htmlspecialchars($branch_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        :root { 
            /* Variabel Warna (Tidak Berubah) */
            --sidebar-bg: #0a2f5c; 
            --accent-color: #FFFF00; /* Tetap kuning sesuai kode Anda */
            --danger-color: #d9534f; 
            
            
            /* Tinggi layout (Tidak Berubah) */
            --running-text-height: 6vh; 
            --layout-height: calc(100vh - var(--running-text-height));
            
            /* Lebar sidebar (Tidak Berubah) */
            --sidebar-width: 380px; 
            
            /* Lebar main-content (Tidak Berubah) */
            --main-width: calc(100vw - var(--sidebar-width));
        }
        
        body, html { 
            height: 100%; 
            width: 100%; 
            margin: 0; 
            padding: 0; 
            overflow: hidden; 
            background-color: #000; 
            color: white; 
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
        }

        .tv-layout { 
            display: flex; 
            height: var(--layout-height);
        }
        
        .main-content { 
            width: var(--main-width); 
            height: 100%; 
            flex-shrink: 0; 
            position: relative; 
            background-color: var(--accent-color); /* Latar belakang oranye */
            display: flex;
            flex-direction: column;
            justify-content: center; /* Tetap di tengah (letterbox atas-bawah) */
        }
        
        .welcome-bar-top {
            color: var(--sidebar-bg);
            font-weight: 700;
            font-size: clamp(1.5rem, 3vh, 2.2rem);
            padding: 1.5vh 1vw;
            text-align: center;
            flex-shrink: 0;
            margin-bottom: 2vh; 
        }
        
        #slideshowContainer { 
            position: relative;
            width: 100%;
            height: 0;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background-color: #000;
            flex-shrink: 0;
        }

        #slideshowContainer .carousel-inner {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        #slideshowContainer .carousel-item { 
            height: 100%; 
            background-color: #000; 
        }

        #slideshowContainer img, #slideshowContainer video { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        .video-container-16-9 { 
            position: absolute; 
            top: 0;
            left: 0;
            width: 100%; 
            height: 100%; 
            padding-top: 0;
            overflow: hidden; 
            background-color: #000;
        }
        .video-container-16-9 iframe { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            border: 0; 
        }

        .sidebar { 
            width: var(--sidebar-width); 
            height: 100%; 
            flex-shrink: 0; 
            background-color: var(--sidebar-bg); 
            display: flex; 
            flex-direction: column; 
            padding: 1.5vw;
            border-left: 0.5vw solid var(--accent-color); 
            box-shadow: -10px 0 20px rgba(0,0,0,0.4); 
        }

        .logo-container { 
            text-align: center; 
            padding-bottom: 1vw; 
            border-bottom: none; 
            flex-shrink: 0;
        }
        .logo-container img { 
            max-width: 160px; 
            height: auto; 
        }
        .logo-container h3 { 
            color: var(--accent-color); 
            margin-top: 15px;
            font-size: clamp(1.3rem, 1.8vw, 2rem);
            font-weight: 700;
        }

        .queue-container { 
            text-align: center; 
            margin-top: 1.5vw; 
            background-color: #fff; 
            color: #000; 
            border-radius: 16px; 
            padding: 1.5vw; 
            box-shadow: 0 8px 20px rgba(0,0,0,0.2); 
            transition: all 0.3s ease; 
            flex-shrink: 0;
        }
        .queue-container h2 { 
            font-size: clamp(1.1rem, 1.6vw, 1.5rem); 
            font-weight: 700; 
            color: #333;
            margin-bottom: 10px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        .queue-number { 
            font-size: clamp(4.5rem, 15vh, 9rem); 
            font-weight: 900;
            color: var(--danger-color); 
            line-height: 1; 
            transition: transform 0.2s, opacity 0.2s; 
        }
        .queue-teller { 
            font-size: clamp(1.6rem, 4vh, 2.8rem); 
            font-weight: 700;
            color: var(--sidebar-bg); 
            margin-top: 10px; 
        }

        .clock-container { 
            margin-top: auto; 
            text-align: center; 
            padding-top: 1.5vw; 
            border-top: 2px solid rgba(255,255,255,0.2);
            flex-shrink: 0;
        }
        .clock-time { 
            font-size: clamp(2.5rem, 6vh, 4rem); 
            font-weight: 700; 
            letter-spacing: 2px; 
            color: #fff;
        }
        .clock-date { 
            font-size: clamp(1rem, 1.8vh, 1.4rem);
            opacity: 0.8; 
            font-weight: 400;
        }
        
        .running-text-container { 
            height: var(--running-text-height); 
            width: 100%; 
            background-color: var(--accent-color); 
            color: var(--sidebar-bg); 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            z-index: 1000; 
            display: flex; 
            align-items: center; 
            overflow: hidden; 
            box-shadow: 0 -5px 15px rgba(0,0,0,0.3);
        }
        .marquee { 
            display: inline-block; 
            white-space: nowrap; 
            font-size: clamp(1.4rem, 2.5vh, 2rem); 
            font-weight: 700; 
            padding-left: 100%; 
            animation: marquee 30s linear infinite; 
        }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }
        
        #audio-prompt { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.8); z-index: 9999; display: flex; justify-content: center; align-items: center; text-align: center; color: white; cursor: pointer; }
        #audio-prompt-content { background-color: var(--sidebar-bg); padding: 40px; border-radius: 15px; border: 3px solid var(--accent-color); box-shadow: 0 0 30px rgba(0,0,0,0.5); }
        #audio-prompt-content h1 { font-size: 2.5rem; margin-bottom: 15px; color: var(--accent-color); }
        #audio-prompt-content p { font-size: 1.5rem; }

        .carousel-caption { background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%); border-radius: 0; bottom: 0; left: 0; right: 0; padding: 2rem 1.5rem; text-align: left; }
        .carousel-caption h5 { font-size: clamp(1.2rem, 2.5vw, 1.8rem); font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.7); }
    </style>
</head>
<body>

    <div id="audio-prompt">
        <div id="audio-prompt-content">
            <i class="bi bi-volume-up-fill" style="font-size: 5rem;"></i>
            <h1>Aktifkan Suara</h1>
            <p>Klik di mana saja untuk mengaktifkan notifikasi suara antrian.</p>
        </div>
    </div>

    <div class="tv-layout">
        
        <div class="main-content">
            
            <div class="welcome-bar-top">
                Selamat Datang di Kantor Cabang <?php echo htmlspecialchars($branch_name); ?>
            </div>
            
            <div id="slideshowContainer" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-wrap="true">
                
                <div class="carousel-inner">
                    <?php if (empty($slides)): ?>
                        <div class="carousel-item active" style="background-color: #333; display: flex; align-items: center; justify-content: center; text-align: center; height: 100%;">
                            <div>
                                <h1>Selamat Datang di CU Bererod Gratia</h1>
                                <h3>Cabang <?php echo htmlspecialchars($branch_name); ?></h3>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($slides as $i => $slide): ?>
                            <div class="carousel-item <?php echo ($i == 0) ? 'active' : ''; ?>" 
                                 data-bs-interval="<?php echo $slide['duration_seconds'] * 1000; ?>">
                                
                                <?php if ($slide['type'] == 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($slide['media_path']); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>">
                                
                                <?php elseif ($slide['type'] == 'video'): ?>
                                    <video src="<?php echo htmlspecialchars($slide['media_path']); ?>" autoplay muted loop></video>
                                
                                <?php elseif ($slide['type'] == 'youtube'): 
                                    $embed_url = getYouTubeEmbedUrl($slide['media_path']);
                                ?>
                                    <div class="video-container-16-9">
                                        <iframe src="<?php echo htmlspecialchars($embed_url); ?>" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen></iframe>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($slide['title'])): ?>
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5><?php echo htmlspecialchars($slide['title']); ?></h5>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <div class="sidebar">
            <div class="logo-container">
                <img src="../assets/logo-cubg.png" alt="Logo CU Bererod Gratia"> 
                <h3><?php echo htmlspecialchars($branch_name); ?></h3>
            </div>
            
            <div class="queue-container">
                <h2>Nomor Antrian</h2>
                <div id="q_number" class="queue-number">-</div>
                <div id="q_teller" class="queue-teller">Silakan ke Teller</div>
            </div>

            <div class="clock-container">
                <div id="clock_time" class="clock-time">...</div>
                <div id="clock_date" class="clock-date">...</div>
            </div>
        </div>
    </div>

    <div class="running-text-container">
        <div class="marquee" id="running_text_content">
            <?php echo htmlspecialchars($running_text ?? ''); ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // === 1. INISIALISASI ===
        const timeEl = document.getElementById('clock_time');
        const dateEl = document.getElementById('clock_date');
        const qNumberEl = document.getElementById('q_number');
        const qTellerEl = document.getElementById('q_teller');
        const runningTextEl = document.getElementById('running_text_content');
        const queueContainer = document.querySelector('.queue-container');
        const branchId = <?php echo $branch_id; ?>;

        let lastQueueNumber = '-'; 
        let lastRunningText = '<?php echo htmlspecialchars($running_text ?? ''); ?>';

        // === 2. LOGIKA SUARA (TTS) ===
        const audioPrompt = document.getElementById('audio-prompt');
        let isAudioEnabled = false;
        const synth = window.speechSynthesis;
        let utterance = new SpeechSynthesisUtterance();
        utterance.lang = 'id-ID';
        utterance.rate = 0.9;
        
        function formatQueueNumberForSpeech(number) {
            let parts = number.split('-');
            let formatted = parts[0];
            if (parts[1]) {
                formatted += ". " + parseInt(parts[1], 10); 
            }
            return formatted;
        }

        // === FUNGSI speakNotification DENGAN PERBAIKAN ===
        function speakNotification(number, teller) {
            if (!isAudioEnabled || synth.speaking) {
                return;
            }
            
            const dingdong = new Audio('../assets/audio/dingdong.mp3'); 
            
            // --- PERBAIKAN: Pasang event listener SEBELUM .play() ---

            // 1. Ini yang terjadi JIKA GAGAL (file tidak ada)
            dingdong.onerror = () => {
                console.warn('File dingdong.mp3 tidak ditemukan. Memutar TTS langsung.');
                const formattedNumber = formatQueueNumberForSpeech(number);
                // === PERUBAHAN TEKS SUARA DI SINI ===
                const textToSpeak = `Nomor antrian ${formattedNumber}, silakan ke Teller ${teller}`;
                utterance.text = textToSpeak;
                synth.speak(utterance);
            };
            
            // 2. Ini yang terjadi JIKA SUKSES (file diputar sampai selesai)
            dingdong.onended = () => {
                const formattedNumber = formatQueueNumberForSpeech(number);
                // === PERUBAHAN TEKS SUARA DI SINI ===
                const textToSpeak = `Nomor antrian ${formattedNumber}, silakan ke Teller ${teller}`;
                utterance.text = textToSpeak;
                synth.speak(utterance);
            };
            
            // 3. SEKARANG baru putar filenya.
            dingdong.play();
        }
        // === AKHIR PERBAIKAN JAVASCRIPT ===


        audioPrompt.addEventListener('click', () => {
            isAudioEnabled = true;
            audioPrompt.style.display = 'none';
            if (synth.paused) {
                synth.resume();
            }
            try {
                 const initUtterance = new SpeechSynthesisUtterance(' ');
                 synth.speak(initUtterance);
                 new Audio().play().catch(() => {});
            } catch (e) {
                console.warn("Gagal inisialisasi audio:", e);
            }
            console.log("Audio diaktifkan.");
            fetchData(); 
        }, { once: true });

        // === 3. JAM REAL-TIME ===
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const date = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            
            timeEl.textContent = time.replace(/\./g, ':'); 
            dateEl.textContent = date;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // === 4. FUNGSI PENGAMBIL DATA ===
        async function fetchData() {
            try {
                const response = await fetch(`get_data.php?branch=${branchId}&t=${new Date().getTime()}`);
                if (!response.ok) {
                    console.error("Gagal mengambil data, status:", response.status);
                    qNumberEl.textContent = 'Err';
                    return;
                }
                
                const data = await response.json();

                // --- Logika Update Antrian ---
                // === PERUBAHAN TEKS DEFAULT DI SINI ===
                let newQueueNumber = '-';
                let newTeller = 'Silakan ke Teller'; // Teks default baru

                if (data.queue && data.queue.number) {
                    newQueueNumber = data.queue.number;
                    // === PERUBAHAN TEKS DINAMIS DI SINI ===
                    newTeller = "Silakan ke Teller ";

                    if (newQueueNumber !== lastQueueNumber) {
                        qNumberEl.textContent = newQueueNumber;
                        qTellerEl.textContent = newTeller;
                        lastQueueNumber = newQueueNumber; 

                        speakNotification(newQueueNumber, data.queue.teller);
                        animateQueue();
                    }
                } else {
                    qNumberEl.textContent = '-';
                    // === PERUBAHAN TEKS RESET DI SINI ===
                    qTellerEl.textContent = 'Silakan ke Teller'; // Teks reset baru
                    lastQueueNumber = '-';
                }
                
                // --- Logika Update Teks Berjalan ---
                if (data.running_text && data.running_text !== lastRunningText) {
                    runningTextEl.textContent = data.running_text;
                    lastRunningText = data.running_text;
                }

            } catch (error) {
                console.error("Error saat fetch data:", error);
                qNumberEl.textContent = 'Offline';
            }
        }

        function animateQueue() {
            queueContainer.style.transform = 'scale(1.05)';
            queueContainer.style.boxShadow = '0 0 30px var(--danger-color)';
            qNumberEl.style.transform = 'scale(1.1)';
            
            setTimeout(() => {
                queueContainer.style.transform = 'scale(1)';
                queueContainer.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
                qNumberEl.style.transform = 'scale(1)';
            }, 500);
        }

        // === 5. JALANKAN PENGAMBIL DATA ===
        setInterval(fetchData, 10000);
        
    });
    </script>
</body>
</html>