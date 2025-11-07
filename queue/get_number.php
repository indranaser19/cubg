<?php
session_start();
require_once '../config/db.php';

// Ambil data cabang untuk dropdown
$branches_stmt = $pdo->query("SELECT id, name FROM branches WHERE id > 1 ORDER BY name"); // Asumsi id 1 = Kantor Pusat
$branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambil Nomor Antrian - CU Bererod Gratia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center" style="min-height: 100vh; align-items: center;">
            <div class="col-md-6">
                <div class="card shadow-lg text-center">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">CU Bererod Gratia</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">
                                <h3>Nomor Antrian Anda:</h3>
                                <h1 class="display-1 fw-bold"><?php echo htmlspecialchars($_GET['number']); ?></h1>
                                <p>Cabang: <?php echo htmlspecialchars($_GET['branch']); ?><br>
                                   Silakan menunggu untuk dipanggil.</p>
                                <hr>
                                <a href="get_number.php" class="btn btn-primary">Ambil Antrian Baru</a>
                            </div>
                        <?php else: ?>
                            <h3 class="mb-3">Ambil Nomor Antrian</h3>
                            <p class="text-muted">Silakan pilih kantor pelayanan (cabang) Anda.</p>
                            <form action="process_get_number.php" method="POST">
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label fs-5">Pilih Cabang</label>
                                    <select name="branch_id" id="branch_id" class="form-select form-select-lg" required>
                                        <option value="">-- Pilih Cabang --</option>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-person-bounding-box"></i> Ambil Nomor
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted">
                        <?php echo date('d F Y, H:i:s'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>