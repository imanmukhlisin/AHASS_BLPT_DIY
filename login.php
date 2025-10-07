<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($user['status'] !== 'Aktif') {
            $error = "Akun Anda sudah dinonaktifkan. Silakan hubungi admin.";
        } elseif (password_verify($password, $user['password'])) {
            login_success($user);
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}

function login_success($user) {
    global $pdo; // gunakan koneksi utama!
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

    // Catat log aktivitas login
    $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, waktu) VALUES (?, 'Login', NOW())");
    $stmt->execute([$_SESSION['user_id']]);

    switch ($user['role']) {
        case 'Front Office':
            header('Location: dashboard_frontOffice.php');
            break;
        case 'Bendahara':
            header('Location: dashboard_bendahara.php');
            break;
        case 'Koperasi':
            header('Location: dashboard_koperasi.php');
            break;
        case 'Kepala Kasi Layanan':
            header('Location: dashboard_kepala_kasi_layanan.php');
            break;
        default:
            header('Location: dashboard_superadmin.php');
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISTEM UPJ AHHASBLPT DIY - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #183149ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        /* Watermark background */
        .bg-watermark {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            pointer-events: none;
            background: url('gambar_blpt.png') center center no-repeat;
            background-size: cover;
            opacity: 0.50;
        }
        .login-container {
            background: #ffffffff;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(18, 76, 124, 1);
            padding: 38px 32px 32px 32px;
            width: 100%;
            max-width: 390px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
            position: relative;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2px;
        }
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1c62c5ff;
            margin-bottom: 1px;
            letter-spacing: 1px;
        }
        .logo-container {
            margin-bottom: 1px;
        }
        .logo-container img {
            width: 200px;
            height: 200px;
            object-fit: contain;
            border-radius: 8%;
            background: #fff;
            
        }
        .login-title {
            font-size: 1.08rem;
            color: #000000ff;
            margin-bottom: 18px;
            text-align: center;
        }
        form {
            width: 100%;
        }
        .input-group {
            margin-bottom: 18px;
        }
        .input-group-text {
            background: #e3f2fd;
            border: none;
            border-radius: 8px 0 0 8px;
            color: #2164f3ff;
            font-size: 1.2rem;
            padding-left: 16px;
        }
        .form-control {
            border-radius: 0 8px 8px 0;
            border: 1px solid #90caf9;
            font-size: 1.08rem;
            padding: 12px 16px;
            background: #f7fbff;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #2196f3;
            box-shadow: 0 0 0 0.15rem rgba(33,150,243,0.12);
        }
        .btn-login {
            background: #1c62c5ff;
            border: none;
            border-radius: 8px;
            color: #fff;
            padding: 13px 0;
            width: 100%;
            font-weight: 700;
            font-size: 1.13rem;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px rgba(255, 255, 255, 1);
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #2f5cb5ff;
        }
        .alert-danger {
            font-size: 0.98rem;
            margin-bottom: 12px;
            border-radius: 8px;
        }
        @media (max-width: 500px) {
            .login-container {
                padding: 18px 8px;
                max-width: 98vw;
            }
            .logo-container img {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-watermark"></div>
    <div class="login-container">
        <div class="login-header">
            <h1>SISTEM UPJ AHASS BLPT DIY</h1>
        </div>
        <div class="logo-container">
            <img src="logo_ahass.png" alt="Logo BLPT DIY">
        </div>
        <div class="login-title">Sign in to your account</div>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-login">Login</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>