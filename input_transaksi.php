<?php
include 'config.php';
// Cek session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Front Office') {
    header('Location: login.php');
    exit();
}

// --- Tambahan: Ambil data mekanik dari database ---
$stmt = $pdo->query("SELECT nama_mekanik FROM mekanik ORDER BY nama_mekanik ASC");
$mekanik_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ========== PROSES HAPUS TRANSAKSI ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_transaksi'])) {
    $no_nota = $_POST['delete_transaksi'];
    try {
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE no_nota = ?");
        $stmt->execute([$no_nota]);
        $_SESSION['success'] = "Transaksi berhasil dihapus!";
        header('Location: input_transaksi.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus transaksi: " . $e->getMessage();
        header('Location: input_transaksi.php');
        exit();
    }
}
// ========== END PROSES HAPUS ==========

// Fungsi untuk memformat angka dari input dengan titik
function formatAngkaInput($angka) {
    // Hapus semua karakter non-digit
    $angka = preg_replace('/[^\d]/', '', $angka);
    return floatval($angka);
}

// Proses input transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_transaksi'])) {
    $no_nota = $_POST['no_nota'];
    $tanggal = $_POST['tanggal'];
    $jasa_rp = isset($_POST['jasa_rp']) ? formatAngkaInput($_POST['jasa_rp']) : 0;
    $jasa_lain_rp = isset($_POST['jasa_lain_rp']) ? formatAngkaInput($_POST['jasa_lain_rp']) : 0;
    $sperpart_rp = isset($_POST['sperpart_rp']) ? formatAngkaInput($_POST['sperpart_rp']) : 0;
    $oli_rp = isset($_POST['oli_rp']) ? formatAngkaInput($_POST['oli_rp']) : 0;
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $status_verifikasi = 'Belum';
    $status_verifikasi_bendahara = 'Belum';
    $nama_pengirim = !empty($_POST['nama_pengirim']) ? $_POST['nama_pengirim'] : null;
    $created_by = $_SESSION['user_id'];
    $mekanik = !empty($_POST['mekanik']) ? $_POST['mekanik'] : null;
    $id_mekanik = null; // Jika belum pakai id mekanik

    try {
        $stmt = $pdo->prepare("INSERT INTO transaksi
            (no_nota, tanggal, jasa_rp, jasa_lain_rp, sperpart_rp, oli_rp, metode_pembayaran, status_verifikasi, status_verifikasi_bendahara, nama_pengirim, created_by, mekanik, id_mekanik)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $no_nota, $tanggal, $jasa_rp, $jasa_lain_rp, $sperpart_rp, $oli_rp, $metode_pembayaran,
            $status_verifikasi, $status_verifikasi_bendahara, $nama_pengirim, $created_by, $mekanik, $id_mekanik
        ]);
        $_SESSION['success'] = "Transaksi berhasil ditambahkan!";
        header('Location: input_transaksi.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menambah transaksi: " . $e->getMessage();
        header('Location: input_transaksi.php');
        exit();
    }
}

// Proses Edit Transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_transaksi'])) {
    $no_nota = $_POST['no_nota'];
    $mekanik = !empty($_POST['mekanik']) ? $_POST['mekanik'] : null;
    $jasa_rp = isset($_POST['jasa_rp']) ? formatAngkaInput($_POST['jasa_rp']) : 0;
    $jasa_lain_rp = isset($_POST['jasa_lain_rp']) ? formatAngkaInput($_POST['jasa_lain_rp']) : 0;
    $sperpart_rp = isset($_POST['sperpart_rp']) ? formatAngkaInput($_POST['sperpart_rp']) : 0;
    $oli_rp = isset($_POST['oli_rp']) ? formatAngkaInput($_POST['oli_rp']) : 0;
    $nama_pengirim = !empty($_POST['nama_pengirim']) ? $_POST['nama_pengirim'] : null;
    $metode_pembayaran = $_POST['metode_pembayaran'];

    try {
        $stmt = $pdo->prepare("UPDATE transaksi SET mekanik=?, jasa_rp=?, jasa_lain_rp=?, sperpart_rp=?, oli_rp=?, nama_pengirim=?, metode_pembayaran=? WHERE no_nota=?");
        $stmt->execute([
            $mekanik, $jasa_rp, $jasa_lain_rp, $sperpart_rp, $oli_rp, $nama_pengirim, $metode_pembayaran, $no_nota
        ]);
        $_SESSION['success'] = "Transaksi berhasil diupdate!";
        header('Location: input_transaksi.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal update transaksi: " . $e->getMessage();
        header('Location: input_transaksi.php');
        exit();
    }
}

// Fungsi update laporan harian
function updateLaporanHarian($tanggal, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as jumlah_unit,
            SUM(jasa_rp + jasa_lain_rp) as total_jasa,
            SUM(sperpart_rp + oli_rp) as total_penjualan,
            SUM(CASE WHEN sperpart_rp > 0 THEN 1 ELSE 0 END) as jumlah_sperpart,
            SUM(CASE WHEN oli_rp > 0 THEN 1 ELSE 0 END) as jumlah_oli
        FROM transaksi 
        WHERE tanggal = ?
        AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))
    ");
    $stmt->execute([$tanggal]);
    $data = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT id_laporan FROM laporan_harian WHERE tanggal = ?");
    $stmt->execute([$tanggal]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE laporan_harian SET jumlah_unit = ?, total_jasa = ?, total_penjualan = ?, jumlah_sperpart = ?, jumlah_oli = ? WHERE tanggal = ?");
        $stmt->execute([$data['jumlah_unit'], $data['total_jasa'], $data['total_penjualan'], $data['jumlah_sperpart'], $data['jumlah_oli'], $tanggal]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO laporan_harian (tanggal, jumlah_unit, total_jasa, total_penjualan, jumlah_sperpart, jumlah_oli) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $data['jumlah_unit'], $data['total_jasa'], $data['total_penjualan'], $data['jumlah_sperpart'], $data['jumlah_oli']]);
    }
}

// Ambil data transaksi sesuai tanggal yang dipilih (default hari ini)
$selected_date = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT t.*, p.nama_lengkap as verifikator 
    FROM transaksi t 
    LEFT JOIN pengguna p ON t.verified_by = p.id_user 
    WHERE t.tanggal = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$selected_date]);
$transaksi_hari_ini = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Transaksi - Sistem UPJ AHHAS</title>
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
                        <img src="logo_ahass.png" alt="Logo DIY" style="width:70px; height:70px; object-fit:contain; border-radius:50%; box-shadow:0 2px 8px rgba(33,150,243,0.10); margin-bottom:10px; border:2px solid #90caf9;">
                        <h5 class="fw-bold">UPJ AHASS BLPT DIY</h5>
                        <small><?= $_SESSION['role'] ?></small>
                    </div>
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard_frontoffice.php">
                                <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="input_transaksi.php">
                                <i class="fas fa-plus-circle fa-fw"></i> Input Transaksi
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
                <!-- Header -->
                <header class="header-bar" style="position: sticky; top: 0; z-index: 1020; background-color: #fff;">
                    <h4 class="mb-0">Input Transaksi</h4>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                </header>
                <div class="main-content" style="height: calc(100vh - 74px); overflow-y: auto;">
                    <!-- Notifikasi -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    <!-- Form Input Transaksi Baru -->
                    <div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Form Input Transaksi Baru</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">No Nota *</label>
                    <input type="text" class="form-control" name="no_nota" required placeholder="Contoh: NOTA001">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Mekanik *</label>
                    <select class="form-control" name="mekanik" required>
    <option value="">Pilih Mekanik</option>
    <?php foreach ($mekanik_list as $nama): ?>
        <option value="<?= htmlspecialchars($nama) ?>"><?= htmlspecialchars($nama) ?></option>
    <?php endforeach; ?>
</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tanggal *</label>
                    <input type="date" class="form-control" name="tanggal" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Metode Pembayaran *</label>
                    <select class="form-control" name="metode_pembayaran" required>
                        <option value="Tunai">Tunai</option>
                        <option value="Non Tunai">Non Tunai</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label">Nama Pengirim (Non Tunai)</label>
                    <input type="text" class="form-control" name="nama_pengirim" placeholder="Nama pengirim transfer">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jasa Servis (Rp)</label>
                    <input type="text" class="form-control rupiah-input" name="jasa_rp" placeholder="Nominal jasa servis" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jasa Lain (Rp)</label>
                    <input type="text" class="form-control rupiah-input" name="jasa_lain_rp" placeholder="Nominal jasa lain" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sparepart (Rp)</label>
                    <input type="text" class="form-control rupiah-input" name="sperpart_rp" placeholder="Nominal sparepart" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Oli (Rp)</label>
                    <input type="text" class="form-control rupiah-input" name="oli_rp" placeholder="Nominal oli" autocomplete="off">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" name="tambah_transaksi" class="btn btn-success btn-lg">
                    <i class="fas fa-save me-2"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>
                    <!-- Filter Tanggal Daftar Transaksi -->
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i> Daftar Transaksi</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($transaksi_hari_ini)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada transaksi hari ini</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Jam</th>
                                                <th>Nota</th>
                                                <th>Mekanik</th> 
                                                <th>Jasa</th>
                                                <th>Sparepart</th>
                                                <th>Oli</th>
                                                <th>Total</th>
                                                <th>Metode</th>
                                                <th>Pengirim</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transaksi_hari_ini as $transaksi): 
                                                $total = $transaksi['jasa_rp'] + $transaksi['jasa_lain_rp'] + $transaksi['sperpart_rp'] + $transaksi['oli_rp'];
                                                $is_non_tunai = $transaksi['metode_pembayaran'] == 'Non Tunai';
                                                $can_edit = ($transaksi['created_by'] == $_SESSION['user_id']);
                                            ?>
                                                <tr>
                                                    <td><?= date('H:i', strtotime($transaksi['created_at'])) ?></td>
                                                    <td><strong><?= $transaksi['no_nota'] ?></strong></td>
                                                    <td><?= htmlspecialchars($transaksi['mekanik']) ?></td> <!-- Tambahkan ini -->
                                                    <td>Rp <?= number_format(round($transaksi['jasa_rp'] + $transaksi['jasa_lain_rp']), 0, ',', '.') ?></td>
                                                    <td>Rp <?= number_format(round($transaksi['sperpart_rp']), 0, ',', '.') ?></td>
                                                    <td>Rp <?= number_format(round($transaksi['oli_rp']), 0, ',', '.') ?></td>
                                                    <td><strong>Rp <?= number_format(round($total), 0, ',', '.') ?></strong></td>
                                                    <td>
                                                        <span class="badge <?= $is_non_tunai ? 'bg-primary-subtle text-primary-emphasis' : 'bg-success-subtle text-success-emphasis' ?>">
                                                            <i class="fas fa-<?= $is_non_tunai ? 'credit-card' : 'money-bill' ?> me-1"></i>
                                                            <?= $transaksi['metode_pembayaran'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $is_non_tunai ? htmlspecialchars($transaksi['nama_pengirim']) : '-' ?></td>
                                                    <td>
                                                        <?php if ($is_non_tunai): ?>
                                                            <?php if (isset($transaksi['status_verifikasi_bendahara'])): ?>
                                                                <?php if ($transaksi['status_verifikasi_bendahara'] == 'Sudah'): ?>
                                                                    <span class="badge bg-success">Terverifikasi</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning text-dark">Belum</span>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">-</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-dark">Tunai</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="table-actions">
                                                        <?php if ($can_edit): ?>
                                                            <!-- Tombol Edit -->
                                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $transaksi['no_nota'] ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <!-- Tombol Hapus -->
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus transaksi ini?')">
                                                                <input type="hidden" name="delete_transaksi" value="<?= $transaksi['no_nota'] ?>">
                                                                <input type="hidden" name="tanggal_transaksi" value="<?= $transaksi['tanggal'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-trash"></i> Hapus
                                                                </button>
                                                            </form>
                                                            <!-- Modal Edit -->
                                                            <div class="modal fade" id="editModal<?= $transaksi['no_nota'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $transaksi['no_nota'] ?>" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="editModalLabel<?= $transaksi['no_nota'] ?>"><i class="fas fa-edit me-2"></i>Edit Transaksi <?= htmlspecialchars($transaksi['no_nota']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="no_nota" value="<?= $transaksi['no_nota'] ?>">
          <div class="row g-3">
            <div class="col-md-12 mb-2">
              <label class="form-label fw-bold">Mekanik *</label>
              <select class="form-control" name="mekanik" required>
                <option value="">Pilih Mekanik</option>
                <?php foreach ($mekanik_list as $nama): ?>
                  <option value="<?= htmlspecialchars($nama) ?>" <?= $transaksi['mekanik'] == $nama ? 'selected' : '' ?>><?= htmlspecialchars($nama) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Jasa Servis (Rp)</label>
              <input type="text" class="form-control rupiah-input" name="jasa_rp" value="<?= number_format($transaksi['jasa_rp'], 0, ',', '.') ?>">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Jasa Lain (Rp)</label>
              <input type="text" class="form-control rupiah-input" name="jasa_lain_rp" value="<?= number_format($transaksi['jasa_lain_rp'], 0, ',', '.') ?>">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Sparepart (Rp)</label>
              <input type="text" class="form-control rupiah-input" name="sperpart_rp" value="<?= number_format($transaksi['sperpart_rp'], 0, ',', '.') ?>">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Oli (Rp)</label>
              <input type="text" class="form-control rupiah-input" name="oli_rp" value="<?= number_format($transaksi['oli_rp'], 0, ',', '.') ?>">
            </div>
            <div class="col-md-12 mb-2">
              <label class="form-label">Nama Pengirim (Non Tunai)</label>
              <input type="text" class="form-control" name="nama_pengirim" value="<?= htmlspecialchars($transaksi['nama_pengirim']) ?>">
            </div>
            <div class="col-md-12 mb-2">
              <label class="form-label">Metode Pembayaran</label>
              <select class="form-control" name="metode_pembayaran" required>
                <option value="Tunai" <?= $transaksi['metode_pembayaran'] == 'Tunai' ? 'selected' : '' ?>>Tunai</option>
                <option value="Non Tunai" <?= $transaksi['metode_pembayaran'] == 'Non Tunai' ? 'selected' : '' ?>>Non Tunai</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="edit_transaksi" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format input angka dengan titik ribuan
        function formatRupiah(angka) {
            let number_string = angka.replace(/[^\d]/g, ''),
                sisa = number_string.length % 3,
                rupiah = number_string.substr(0, sisa),
                ribuan = number_string.substr(sisa).match(/\d{3}/g);
            if (ribuan) {
                rupiah += (sisa ? '.' : '') + ribuan.join('.');
            }
            return rupiah;
        }
        document.querySelectorAll('.rupiah-input').forEach(function(input) {
            input.addEventListener('input', function(e) {
                let val = input.value;
                input.value = formatRupiah(val);
            });
            // Format saat modal edit dibuka
            input.value = formatRupiah(input.value);
        });
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);

        // Auto-submit form on date change
        document.getElementById('tanggal').addEventListener('change', function() {
            document.getElementById('filter-tanggal-form').submit();
        });

        // Saat submit, ubah input jadi angka tanpa titik
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                form.querySelectorAll('.rupiah-input').forEach(function(input) {
                    input.value = input.value.replace(/\./g, '');
                });
            });
        });
    </script>
</body>
</html>