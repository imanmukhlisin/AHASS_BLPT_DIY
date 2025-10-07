<?php
include 'config.php';
checkAuth('Super Admin');

// Proses tambah penandatangan
if (isset($_POST['tambah_penandatangan'])) {
    $nama_lengkap = $_POST['nama_lengkap'];
    $jabatan = $_POST['jabatan'];
    $urutan = intval($_POST['urutan']);
    $stmt = $pdo->prepare("INSERT INTO penandatangan (nama_lengkap, jabatan, urutan) VALUES (?, ?, ?)");
    $stmt->execute([$nama_lengkap, $jabatan, $urutan]);
    header("Location: penandatangan_list.php");
    exit();
}

// Proses hapus penandatangan
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM penandatangan WHERE id=?");
    $stmt->execute([$id]);
    header("Location: penandatangan_list.php");
    exit();
}

// Proses edit penandatangan
if (isset($_POST['edit_penandatangan'])) {
    $id = $_POST['id'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $jabatan = $_POST['jabatan'];
    $urutan = intval($_POST['urutan']);
    $stmt = $pdo->prepare("UPDATE penandatangan SET nama_lengkap=?, jabatan=?, urutan=? WHERE id=?");
    $stmt->execute([$nama_lengkap, $jabatan, $urutan, $id]);
    header("Location: penandatangan_list.php");
    exit();
}

// Ambil data penandatangan
$stmt = $pdo->query("SELECT * FROM penandatangan ORDER BY urutan ASC, id ASC");
$penandatangan = $stmt->fetchAll();

// Ambil data untuk edit jika ada
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM penandatangan WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Penandatangan</title>
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
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: url('gambar_blpt.png'); /* ganti path sesuai file */
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.20;
            pointer-events: none;
            z-index: 0;
            border-radius: inherit;
        }
        .sidebar > * {
            position: relative;
            z-index: 1;
        }
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
        .sidebar .nav-link .fa-fw {
            margin-right: 10px;
        }
        .header-bar {
            background: #ffffffff;
            padding: 15px 10px;
            border-bottom: 2px solid #000000ff;
            display: flex;
            justify-content: space-between;
            align-items: left;
            box-shadow: 0 2px 8px rgba(33,150,243,0.07);
        }
        .main-content {
            padding: 15px;
        }
        .card {
            border-radius: 0.8rem;
            box-shadow: 0 4px 16px rgba(33,150,243,0.09);
            border: none;
            margin-bottom: 22px;
        }
        .stat-card {
            color: white;
            border-radius: 0.8rem;
            padding: 22px 18px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #42a5f5 70%, #1976d2 100%);
            box-shadow: 0 2px 12px rgba(33,150,243,0.10);
        }
        .stat-card.bg-success { background: linear-gradient(135deg, #66bb6a 60%, #43a047 100%);}
        .stat-card.bg-info { background: linear-gradient(135deg, #29b6f6 60%, #0288d1 100%);}
        .stat-card.bg-warning { background: linear-gradient(135deg, #ffca28 70%, #ffa726 100%);}
        .stat-card.bg-primary { background: linear-gradient(135deg, #1976d2 70%, #64b5f6 100%);}
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(33,150,243,0.09);
        }
        .stat-card .stat-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 4rem;
            opacity: 0.18;
        }
        @media (max-width: 1200px) {
            .main-content { padding: 12px; }
            .sidebar { min-width: 220px; }
        }
        @media (max-width: 992px) {
            .sidebar {
                position: relative !important;
                min-width: 100vw;
                height: auto !important;
                box-shadow: none;
                border-radius: 0;
            }
            .main-content {
                padding: 10px;
            }
            .card, .stat-card {
                margin-bottom: 0px;
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                min-width: 100vw;
                position: static !important;
                height: auto !important;
                box-shadow: none;
                border-radius: 0;
                padding-bottom: 12px;
            }
            .main-content {
                padding: 8px;
            }
            .card, .stat-card {
                margin-bottom: 14px;
            }
            .table-responsive {
                font-size: 0.98rem;
            }
            .header-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
        @media (max-width: 400px) {
            .sidebar {
                min-width: 100vw;
                position: static !important;
                height: auto !important;
                box-shadow: none;
                border-radius: 0;
                padding-bottom: 10px;
            }
            .main-content {
                padding: 6px;
            }
            .card, .stat-card {
                margin-bottom: 10px;
            }
            .table-responsive {
                font-size: 0.95rem;
            }
            .header-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .row > [class^="col-"] {
                width: 100%;
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        /* Print Styles */
        @media print {
            body * {
                visibility: hidden !important;
            }
            .print-format, .print-format * {
                visibility: visible !important;
            }
            .print-format {
                position: absolute !important;
                left: 0; right: 0;
                top: 0px;
                margin-left: auto !important;
                margin-right: auto !important;
                width: 95vw !important;
                max-width: 95vw !important;
                background: #fff !important;
                font-size: 10pt !important;
                z-index: 9999 !important;
                display: block !important;
            }
            .print-format table {
                width: 100%;
                border-collapse: collapse;
                font-size: 5pt;
                margin-bottom: 5px;
            }
            .print-format th, .print-format td {
                border: 1px solid #000;
                padding: 2px 4px;
                text-align: center;
                font-size: 10pt;
            }
            .print-format .ttd-section {
                margin-top: 40px;
                width: 100%;
                display: flex;
                justify-content: space-between;
                font-size: 10pt;
            }
            .print-format .ttd-box {
                width: 30%;
                text-align: center;
            }
            .print-format .ttd-box .ttd-line {
                margin: 80px 0 5px 0;
                border-bottom: 1px solid #f60a0aff;
                height: 2px;
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }
        }
        .print-format {
            display: none; /* hanya tampil saat print */
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
                            <a class="nav-link active" href="penandatangan_list.php">
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
                    <h4 class="mb-0"><i class="fas fa-signature me-2"></i>Manajemen Penandatangan Laporan</h4>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                </header>
                <div class="main-content">
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <span>Daftar Penandatangan</span>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahPenandatangan"><i class="fas fa-plus"></i> Tambah Penandatangan</button>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <th>Jabatan</th>
                                        <th>Urutan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($penandatangan as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($p['jabatan']) ?></td>
                                        <td><?= $p['urutan'] ?></td>
                                        <td>
                                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus penandatangan ini?')">Hapus</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($penandatangan)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data penandatangan.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Modal Tambah Penandatangan -->
                    <div class="modal fade" id="modalTambahPenandatangan" tabindex="-1" aria-labelledby="modalTambahPenandatanganLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title" id="modalTambahPenandatanganLabel">Tambah Penandatangan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input name="nama_lengkap" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jabatan</label>
                                        <input name="jabatan" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Urutan Tanda Tangan</label>
                                        <input name="urutan" type="number" class="form-control" value="1" min="1" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="tambah_penandatangan" class="btn btn-success">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Modal Edit Penandatangan -->
                    <?php if ($edit_data): ?>
                    <div class="modal fade show" id="modalEditPenandatangan" tabindex="-1" aria-labelledby="modalEditPenandatanganLabel" aria-modal="true" style="display:block; background:rgba(0,0,0,0.3);">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="modalEditPenandatanganLabel">Edit Penandatangan</h5>
                                    <a href="penandatangan_list.php" class="btn-close"></a>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($edit_data['nama_lengkap']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jabatan</label>
                                        <input name="jabatan" class="form-control" value="<?= htmlspecialchars($edit_data['jabatan']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Urutan Tanda Tangan</label>
                                        <input name="urutan" type="number" class="form-control" value="<?= $edit_data['urutan'] ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="penandatangan_list.php" class="btn btn-secondary">Batal</a>
                                    <button type="submit" name="edit_penandatangan" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        document.body.classList.add('modal-open');
                        document.body.style.overflow = 'hidden';
                    </script>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>