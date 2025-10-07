<?php
include 'config.php';
checkAuth('Front Office');


// Ambil tanggal yang dipilih (default hari ini)
$selected_date = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
// Total transaksi tanggal terpilih
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_unit,
        SUM(jasa_rp + jasa_lain_rp) as total_jasa,
        SUM(sperpart_rp) as total_sperpart,
        SUM(oli_rp) as total_oli,
        SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp ELSE 0 END) as total_tunai,
        SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp ELSE 0 END) as total_non_tunai,
        SUM(jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp) as total_pendapatan
    FROM transaksi 
    WHERE tanggal = ?
    AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))
");
$stmt->execute([$selected_date]);
$summary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM transaksi WHERE tanggal = ? AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah')) ORDER BY created_at DESC");
$stmt->execute([$selected_date]);
$transaksi_hari_ini = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Front Office - Sistem UPJ AHHAS</title>
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
                        <h5 class="fw-bold" style=>UPJ AHASS BLPT DIY</h5>
                        <small><?= $_SESSION['role'] ?></small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard_frontoffice.php">
                                <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="input_transaksi.php">
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
                    <h4 class="mb-0">Dashboard</h4>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                </header>

                <div class="main-content" style="height: calc(100vh - 74px); overflow-y: auto;">
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-info-circle me-2"></i>
                            Menampilkan ringkasan transaksi untuk tanggal <strong><?= date('d F Y', strtotime($selected_date)) ?></strong>.
                        </div>
                        <form method="GET" id="filter-tanggal-form" class="d-flex align-items-center">
                            <label for="tanggal" class="form-label me-2 mb-0">Pilih Tanggal:</label>
                            <input type="date" id="tanggal" name="tanggal" value="<?= $selected_date ?>" class="form-control" style="width: 180px;">
                        </form>
                    </div>
                    <!-- Summary Cards -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card bg-secondary text-white">
                                <h6>Total Unit</h6>
                                <div class="stat-number"><?= $summary['total_unit'] ?? 0 ?></div>
                                <i class="fas fa-car stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                                <h6>Total Jasa</h6>
                                   <div class="stat-number">Rp <?= number_format($summary['total_jasa'] ?? 0, 0, ',', '.') ?></div>
                                <i class="fas fa-tools stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                                <h6>Total Sparepart & Oli</h6>
                                   <div class="stat-number">Rp <?= number_format(($summary['total_sperpart'] ?? 0) + ($summary['total_oli'] ?? 0), 0, ',', '.') ?></div>
                                <i class="fas fa-cogs stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-success text-white">
                                <h6>Total Pendapatan</h6>
                                   <div class="stat-number">Rp <?= number_format($summary['total_pendapatan'] ?? 0, 0, ',', '.') ?></div>
                                <i class="fas fa-wallet stat-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">Pembayaran Tunai</h5>
                                           <p class="card-text fs-4 fw-bold">Rp <?= number_format($summary['total_tunai'] ?? 0, 0, ',', '.') ?></p>
                                    </div>
                                    <i class="fas fa-money-bill-wave fa-3x text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">Pembayaran Non-Tunai</h5>
                                           <p class="card-text fs-4 fw-bold">Rp <?= number_format($summary['total_non_tunai'] ?? 0, 0, ',', '.') ?></p>
                                    </div>
                                    <i class="fas fa-credit-card fa-3x text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Transaksi Terbaru (<?= date('d/m/Y', strtotime($selected_date)) ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Nota</th>
                                <th>Mekanik</th> <!-- Tambahkan ini -->
                                <th>Total Transaksi</th>
                                <th>Metode</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
        <?php if (empty($transaksi_hari_ini)): ?>
            <tr>
                <td colspan="6" class="text-center py-4">Belum ada transaksi hari ini.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($transaksi_hari_ini as $trx): 
                $total = $trx['jasa_rp'] + $trx['jasa_lain_rp'] + $trx['sperpart_rp'] + $trx['oli_rp'];
            ?>
            <tr>
                <td><?= date('H:i', strtotime($trx['created_at'])) ?></td>
                <td><?= htmlspecialchars($trx['no_nota']) ?></td>
                <td><?= htmlspecialchars($trx['mekanik']) ?></td> <!-- Tambahkan ini -->
                <td>Rp <?= number_format($total, 0, ',', '.') ?></td>
                <td>
                    <span class="badge <?= $trx['metode_pembayaran'] == 'Tunai' ? 'bg-success-subtle text-success-emphasis' : 'bg-primary-subtle text-primary-emphasis' ?>">
                        <i class="fas fa-<?= $trx['metode_pembayaran'] == 'Tunai' ? 'money-bill' : 'credit-card' ?> me-1"></i>
                        <?= htmlspecialchars($trx['metode_pembayaran']) ?>
                    </span>
                </td>
                <td>
                    <?php if ($trx['metode_pembayaran'] == 'Tunai'): ?>
                        <span class="badge bg-light text-dark">N/A</span>
                    <?php elseif ($trx['status_verifikasi_bendahara'] == 'Sudah'): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Terverifikasi
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-hourglass-half me-1"></i>Pandding
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Submit form on date change
        document.getElementById('tanggal').addEventListener('change', function() {
            document.getElementById('filter-tanggal-form').submit();
        });

        // Auto refresh every 30 seconds to update real-time, preserving the selected date
        setTimeout(() => {
            // Construct the current URL with query parameters to preserve state
            const url = new URL(window.location);
            // Reload the page
            window.location.href = url.href;
        }, 30000);
    </script>
</body>
</html>