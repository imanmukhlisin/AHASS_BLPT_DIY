<?php
// dashboard_bendahara.php - Halaman Verifikasi
include 'config.php';
checkAuth('Bendahara');

// Ambil tanggal yang dipilih, default ke hari ini
$selected_date = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Proses verifikasi transaksi non-tunai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verifikasi_transaksi'])) {
    $no_nota = $_POST['no_nota'];
    try {
        $stmt = $pdo->prepare("UPDATE transaksi SET status_verifikasi_bendahara = 'Sudah' WHERE no_nota = ? AND metode_pembayaran != 'Tunai'");
        $stmt->execute([$no_nota]);
        $_SESSION['success'] = "Transaksi dengan No. Nota $no_nota berhasil diverifikasi.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal memverifikasi transaksi: " . $e->getMessage();
    }
    header('Location: dashboard_bendahara.php?tanggal=' . $selected_date);
    exit();
}

// Proses Edit Transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_transaksi'])) {
    $no_nota = $_POST['no_nota'];
    $mekanik = $_POST['mekanik'];
    $jasa_rp = str_replace('.', '', $_POST['jasa_rp']);
    $jasa_lain_rp = str_replace('.', '', $_POST['jasa_lain_rp']);
    $sperpart_rp = str_replace('.', '', $_POST['sperpart_rp']);
    $oli_rp = str_replace('.', '', $_POST['oli_rp']);
    $nama_pengirim = $_POST['nama_pengirim'];

    try {
        $stmt = $pdo->prepare("UPDATE transaksi SET mekanik=?, jasa_rp=?, jasa_lain_rp=?, sperpart_rp=?, oli_rp=?, nama_pengirim=? WHERE no_nota=?");
        $stmt->execute([$mekanik, $jasa_rp, $jasa_lain_rp, $sperpart_rp, $oli_rp, $nama_pengirim, $no_nota]);
        $_SESSION['success'] = "Transaksi dengan No. Nota $no_nota berhasil diubah.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal mengubah transaksi: " . $e->getMessage();
    }
    header('Location: dashboard_bendahara.php?tanggal=' . $selected_date);
    exit();
}

// Ambil data transaksi sesuai tanggal
$stmt = $pdo->prepare("
    SELECT *, (jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp) as total_transaksi 
    FROM transaksi 
    WHERE tanggal = ?
    AND (
        metode_pembayaran = 'Tunai'
        OR (metode_pembayaran != 'Tunai')
    )
    ORDER BY created_at DESC
");
$stmt->execute([$selected_date]);
$transaksi_harian = $stmt->fetchAll();

// Ambil statistik untuk summary card
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN (jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp) ELSE 0 END) as total_tunai,
        SUM(CASE WHEN metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah' THEN (jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp) ELSE 0 END) as non_tunai_terverifikasi,
        SUM(CASE WHEN metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Belum' THEN (jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp) ELSE 0 END) as non_tunai_belum_verifikasi
    FROM transaksi
    WHERE tanggal = ?
");
$stmt_stats->execute([$selected_date]);
$stats = $stmt_stats->fetch();

// Ambil daftar penandatangan dari database
$stmt_users = $pdo->query("SELECT nama_lengkap, jabatan FROM penandatangan ORDER BY urutan ASC");
$users = $stmt_users->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Bendahara</title>
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
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard_bendahara.php">
                                <i class="fas fa-check-circle fa-fw"></i> Verifikasi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="laporan_harian_bendahara.php">
                                <i class="fas fa-file-alt fa-fw"></i> Laporan Harian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="laporan_bulanan_bendahara.php">
                                <i class="fas fa-chart-bar fa-fw"></i> Laporan Bulanan
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

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10">
                <header class="header-bar" style="position: sticky; top: 0; z-index: 1020; background-color: #fff;">
                    <h4 class="mb-0">Transaksi Harian</h4>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                </header>

                <div class="main-content" style="height: calc(100vh - 74px); overflow-y: auto;">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card bg-info">
                                <h6>Total Transaksi (<?= date('d/m/Y', strtotime($selected_date)) ?>)</h6>
                                <div class="stat-number"><?= $stats['total_transaksi'] ?? 0 ?></div>
                                <i class="fas fa-receipt stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-success">
                                <h6>Total Tunai</h6>
                                <div class="stat-number">Rp <?= number_format($stats['total_tunai'] ?? 0, 0, ',', '.') ?></div>
                                <i class="fas fa-money-bill-wave stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-primary">
                                <h6>Non-Tunai Terverifikasi</h6>
                                <div class="stat-number">Rp <?= number_format($stats['non_tunai_terverifikasi'] ?? 0, 0, ',', '.') ?></div>
                                <i class="fas fa-check-double stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-warning text-dark">
                                <h6>Non-Tunai Belum Verifikasi</h6>
                                <div class="stat-number">Rp <?= number_format($stats['non_tunai_belum_verifikasi'] ?? 0, 0, ',', '.') ?></div>
                                <i class="fas fa-hourglass-half stat-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Transaksi -->
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i> Daftar Transaksi</h5>
                            <form method="GET" class="d-flex align-items-center" id="filter-tanggal-form">
                                <label for="tanggal" class="form-label me-2 mb-0">Pilih Tanggal:</label>
                                <input type="date" id="tanggal" name="tanggal" value="<?= $selected_date ?>" class="form-control" style="width: 180px;">
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Jam</th>
                                            <th>Nota</th>
                                            <th>Mekanik</th> <!-- Tambahkan ini -->
                                            <th>Jasa</th>
                                            <th>Sperpart</th>
                                            <th>Oli</th>
                                            <th>Total</th>
                                            <th>Metode</th>
                                            <th>Pengirim</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($transaksi_harian)): ?>
                                            <tr><td colspan="11" class="text-center py-5">
                                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Belum ada transaksi pada tanggal ini.</p>
                                            </td></tr>
                                        <?php else: ?>
                                            <?php foreach ($transaksi_harian as $trx): 
                                                $total = $trx['jasa_rp'] + $trx['jasa_lain_rp'] + $trx['sperpart_rp'] + $trx['oli_rp'];
                                            ?>
                                                <tr>
                                                    <td><?= date('H:i', strtotime($trx['created_at'])) ?></td>
                                                    <td><?= htmlspecialchars($trx['no_nota']) ?></td>
                                                    <td><?= htmlspecialchars($trx['mekanik'] ?? '-') ?></td> <!-- Tambahkan ini -->
                                                    <td>Rp <?= number_format($trx['jasa_rp'] + $trx['jasa_lain_rp'], 0, ',', '.') ?></td>
                                                    <td>Rp <?= number_format($trx['sperpart_rp'], 0, ',', '.') ?></td>
                                                    <td>Rp <?= number_format($trx['oli_rp'], 0, ',', '.') ?></td>
                                                    <td><strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></td>
                                                    <td>
                                                        <span class="badge <?= $trx['metode_pembayaran'] == 'Tunai' ? 'bg-success-subtle text-success-emphasis' : 'bg-primary-subtle text-primary-emphasis' ?>">
                                                            <i class="fas fa-<?= $trx['metode_pembayaran'] == 'Tunai' ? 'money-bill' : 'credit-card' ?> me-1"></i>
                                                            <?= htmlspecialchars($trx['metode_pembayaran']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $trx['metode_pembayaran'] != 'Tunai' ? htmlspecialchars($trx['nama_pengirim']) : '-' ?></td>
                                                    <td>
                                                        <?php if ($trx['metode_pembayaran'] == 'Tunai'): ?>
                                                            <span class="badge bg-light text-dark">Otomatis </span>
                                                        <?php elseif ($trx['status_verifikasi_bendahara'] == 'Sudah'): ?>
                                                            <span class="badge bg-success">Terverifikasi</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Belum</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($trx['metode_pembayaran'] != 'Tunai' && $trx['status_verifikasi_bendahara'] == 'Belum'): ?>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin memverifikasi transaksi ini?')">
                                                                <input type="hidden" name="no_nota" value="<?= $trx['no_nota'] ?>">
                                                                <button type="submit" name="verifikasi_transaksi" class="btn btn-sm btn-success me-1">
                                                                    <i class="fas fa-check"></i> Verifikasi
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <!-- Tombol Edit -->
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $trx['no_nota'] ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <!-- Modal Edit -->
                                                        <div class="modal fade" id="editModal<?= $trx['no_nota'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $trx['no_nota'] ?>" aria-hidden="true">
                                                          <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                              <form method="POST">
                                                                <div class="modal-header bg-primary text-white">
                                                                  <h5 class="modal-title" id="editModalLabel<?= $trx['no_nota'] ?>"><i class="fas fa-edit me-2"></i>Edit Transaksi <?= htmlspecialchars($trx['no_nota']) ?></h5>
                                                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="no_nota" value="<?= $trx['no_nota'] ?>">
                                                                    <div class="row g-2">
                                                                        <div class="col-md-6 mb-2">
                                                                            <label class="form-label">Jasa Servis (Rp)</label>
                                                                            <input type="text" class="form-control rupiah-input" name="jasa_rp" value="<?= number_format($trx['jasa_rp'], 0, ',', '.') ?>">
                                                                        </div>
                                                                        <div class="col-md-6 mb-2">
                                                                            <label class="form-label">Jasa Lain (Rp)</label>
                                                                            <input type="text" class="form-control rupiah-input" name="jasa_lain_rp" value="<?= number_format($trx['jasa_lain_rp'], 0, ',', '.') ?>">
                                                                        </div>
                                                                        <div class="col-md-6 mb-2">
                                                                            <label class="form-label">Sperpart (Rp)</label>
                                                                            <input type="text" class="form-control rupiah-input" name="sperpart_rp" value="<?= number_format($trx['sperpart_rp'], 0, ',', '.') ?>">
                                                                        </div>
                                                                        <div class="col-md-6 mb-2">
                                                                            <label class="form-label">Oli (Rp)</label>
                                                                            <input type="text" class="form-control rupiah-input" name="oli_rp" value="<?= number_format($trx['oli_rp'], 0, ',', '.') ?>">
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
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-secondary mt-3" 
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Catatan:</strong> Klik tombol "Verifikasi" pada transaksi non-tunai setelah pembayaran masuk.
                            </div>
                        </div>
                    </div>

                    <!-- Print View -->
                    <div class="print-view" style="display:none;">
                        <div class="text-center mb-3">
                            <h4>LAPORAN TRANSAKSI HARIAN</h4>
                            <h5>UPJ AHHAS BLPT DIY</h5>
                            <p>Tanggal: <?= date('d F Y', strtotime($selected_date)) ?></p>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Jam</th>
                                    <th>No. Nota</th>
                                    <th>Jasa</th>
                                    <th>Sperpart</th>
                                    <th>Oli</th>
                                    <th>Total</th>
                                    <th>Metode</th>
                                    <th>Nama Pengirim</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transaksi_harian)): ?>
                                    <tr><td colspan="9" class="text-center">Belum ada transaksi pada tanggal ini.</td></tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($transaksi_harian as $trx): 
                                        $total = $trx['jasa_rp'] + $trx['jasa_lain_rp'] + $trx['sperpart_rp'] + $trx['oli_rp'];
                                    ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= date('H:i', strtotime($trx['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($trx['no_nota']) ?></td>
                                            <td>Rp <?= number_format($trx['jasa_rp'] + $trx['jasa_lain_rp'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($trx['sperpart_rp'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($trx['oli_rp'], 0, ',', '.') ?></td>
                                            <td><strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></td>
                                            <td><?= htmlspecialchars($trx['metode_pembayaran']) ?></td>
                                            <td><?= $trx['metode_pembayaran'] != 'Tunai' ? htmlspecialchars($trx['nama_pengirim']) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="signature-section mt-5 text-center">
                            <?php foreach ($users as $user): ?>
                                <div class="signature-box">
                                    <p class="mb-0"><?= htmlspecialchars($user['jabatan']) ?>,</p>
                                    <div class="signature-line"></div>
                                    <div class="signature-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('tanggal').addEventListener('change', function() {
            document.getElementById('filter-tanggal-form').submit();
        });
        // Print view trigger (example: add a button to show print view and call window.print())
        // document.getElementById('btnPrint').addEventListener('click', function() {
        //     document.querySelector('.print-view').style.display = 'block';
        //     window.print();
        //     document.querySelector('.print-view').style.display = 'none';
        // });
    </script>
    <script>
        // Format input angka dengan titik ribuan (untuk semua .rupiah-input termasuk di modal edit)
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