<?php
session_start();
require_once '../config/db.php';

// Logika PHP Anda tidak diubah sama sekali
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password wajib diisi.';
    } else {
        $sql = "SELECT id, username, password_hash, role, branch_id, full_name FROM users WHERE username = :username AND is_active = 1";
        
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $user = $stmt->fetch();
                    if (password_verify($password, $user['password_hash'])) {
                        // Password benar, mulai session
                        session_regenerate_id();
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['branch_id'] = $user['branch_id'];
                        
                        // Arahkan ke dashboard
                        header("location: ../dashboard.php");
                        exit;
                    } else {
                        $error_message = 'Username atau password salah.';
                    }
                } else {
                    $error_message = 'Username atau password salah.';
                }
            } else {
                $error_message = 'Terjadi kesalahan. Coba lagi nanti.';
            }
            unset($stmt);
        }
    }
}
unset($pdo);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CU Bererod Gratia</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #0a2f5c; /* Biru tua dari sidebar */
            --brand-primary-darker: #082548;
            --brand-accent: #f0ad4e; /* Oranye/Kuning dari display.php */
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6; /* Latar belakang abu-abu muda */
        }

        /* Layout utama untuk memusatkan form */
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }

        /* Card login yang modern */
        .login-card {
            max-width: 450px;
            width: 100%;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        }

        /* Tombol login dengan warna brand */
        .btn-brand {
            background-color: var(--brand-primary);
            color: white;
            font-weight: 600;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            transition: background-color 0.2s ease;
        }

        .btn-brand:hover {
            background-color: var(--brand-primary-darker);
            color: white;
        }

        /* Style untuk input group */
        .input-group-text {
            background-color: #fff;
            border-right: 0;
            color: #6c757d;
        }
        
        .form-control {
            border-left: 0;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--brand-primary);
        }
        
        /* Mengatasi border-left pada input setelah icon */
        .form-control:focus, 
        .form-control:not(:focus) {
            border-left: 0;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--brand-primary);
            box-shadow: none;
            color: var(--brand-primary);
        }

        /* Khusus untuk input password agar tombol show/hide tidak aneh */
        #password {
            border-right: 0;
        }
        #togglePassword {
            border-left: 0;
            background-color: #fff;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="card login-card p-4">
            <div class="card-body">
                
                <div class="text-center mb-4">
                    <img src="../assets/logo-cubg.png" alt="Logo CU Bererod Gratia" style="max-height: 75px;">
                </div>

                <h4 class="text-center fw-bold mb-4" style="color: var(--brand-primary);">
                    LOGIN CUBIZ
                </h4>

                <?php if(!empty($error_message)): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label visually-hidden">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label visually-hidden">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Lihat password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-brand btn-lg">Login</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const icon = togglePassword.querySelector('i');

            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    // Toggle tipe atribut
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    
                    // Toggle ikon
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                });
            }
        });
    </script>
</body>
</html>