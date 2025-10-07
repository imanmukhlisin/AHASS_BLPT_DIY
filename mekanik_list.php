<?php
include 'config.php';
checkAuth('Super Admin');

// Handle tambah mekanik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_mekanik']);
    if ($nama !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO mekanik (nama_mekanik) VALUES (?)");
        $stmt->execute([$nama]);
        header('Location: mekanik_list.php?msg=added');
        exit();
    }
}

// Handle hapus mekanik
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $pdo->prepare("DELETE FROM mekanik WHERE id_mekanik = ?");
    $stmt->execute([$id]);
    header('Location: mekanik_list.php?msg=deleted');
    exit();
}

// Ambil semua mekanik
$stmt = $pdo->query("SELECT * FROM mekanik ORDER BY nama_mekanik ASC");
$mekanik = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Mekanik - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd 30%, #bbdefb 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, #183149ff 80%, #6eabdcff 200%);
            color: #fff;
            box-shadow: 0 4px 24px rgba(33,150,243,0.08);
        }
        .sidebar::before {
            content: "";
            position: absolute;
            top: 0; left: 0; bottom: 0; right: 0;
            background-image: url('gambar_blpt.png');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.20;
            pointer-events: none;
            z-index: 0;
            border-radius: inherit;
        }
        .sidebar > * { position: relative; z-index: 1; }
        .sidebar .nav-link {
            color: #e3f2fd;
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 4px;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #1565c0;
            color: #fff;
            border-left: 4px solid #ffca28;
        }
        .sidebar .nav-link .fa-fw { margin-right: 10px; }
        .header-bar {
            background: #fff;
            padding: 15px 10px;
            border-bottom: 2px solid #000;
            display: flex;
            justify-content: space-between;
            align-items: left;
            box-shadow: 0 2px 8px rgba(33,150,243,0.07);
        }
        .main-content { padding: 15px; }
        .card {
            border-radius: 0.8rem;
            box-shadow: 0 4px 16px rgba(33,150,243,0.09);
            border: none;
            margin-bottom: 22px;
        }
        @media (max-width: 992px) {
            .main-content { padding: 10px; }
            .card { margin-bottom: 0px; }
        }
        @media (max-width: 768px) {
            .main-content { padding: 8px; }
            .card { margin-bottom: 14px; }
            .header-bar { flex-direction: column; align-items: flex-start; gap: 8px; }
        }
        @media (max-width: 400px) {
            .main-content { padding: 6px; }
            .card { margin-bottom: 10px; }
            .header-bar { flex-direction: column; align-items: flex-start; gap: 6px; }
            .row > [class^="col-"] {
                width: 100%;
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar vh-100" style="position: fixed; height: 100vh; overflow-y: auto;">
                <div class="position-sticky pt-3 d-flex flex-column h-100">
                    <div class="text-center my-4">
                        <img src="logo_ahass.png" alt="Logo AHASS" style="width:70px; height:70px; object-fit:contain; border-radius:50%; box-shadow:0 2px 8px rgba(33,150,243,0.10); margin-bottom:10px; border:2px solid #90caf9;">
                        <h5 class="fw-bold">UPJ AHASS BLPT DIY</h5>
                        <small><?= $_SESSION['role'] ?></small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard_superadmin.php">
                                <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user_list.php">
                                <i class="fas fa-users fa-fw"></i> Manajemen User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="penandatangan_list.php">
                                <i class="fas fa-signature fa-fw"></i> Penandatangan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="mekanik_list.php">
                                <i class="fas fa-tools fa-fw"></i> Mekanik
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="log_aktivitas.php">
                                <i class="fas fa-history fa-fw"></i> Log Aktivitas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog fa-fw"></i> Pengaturan Sistem
                            </a>
                        </li>
                    </ul>
                    <div class="mt-auto p-3">
                         <a class="btn btn-danger w-100" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10">
                <header class="header-bar" style="position: sticky; top: 0; z-index: 1020;">
                    <h4 class="mb-0"><i class="fas fa-tools me-2"></i>Manajemen Mekanik</h4>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                </header>
                <div class="main-content">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-plus"></i> Tambah Mekanik
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <div class="col-md-8">
                                    <input type="text" name="nama_mekanik" class="form-control" placeholder="Nama Mekanik" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="tambah" class="btn btn-success w-100">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
                        <div class="alert alert-success">Mekanik berhasil ditambahkan.</div>
                    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                        <div class="alert alert-warning">Mekanik berhasil dihapus.</div>
                    <?php endif; ?>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-list"></i> Daftar Mekanik
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">No</th>
                                        <th>Nama Mekanik</th>
                                        <th style="width:120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mekanik as $i => $row): ?>
                                    <tr>
                                        <td><?= $i+1 ?></td>
                                        <td><?= htmlspecialchars($row['nama_mekanik']) ?></td>
                                        <td>
                                            <a href="mekanik_list.php?hapus=<?= $row['id_mekanik'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus mekanik ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($mekanik)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada data mekanik.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>