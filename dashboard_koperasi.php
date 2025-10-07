<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Koperasi') {
    header('Location: login.php');
    exit();
}

// Filter
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Summary Harian
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_non_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN oli_rp ELSE 0 END) as oli_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN oli_rp ELSE 0 END) as oli_non_tunai
    FROM transaksi 
    WHERE tanggal = ?
    AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))
");
$stmt->execute([$filter_tanggal]);
$summary_harian = $stmt->fetch();

// Summary Bulanan
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_non_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN oli_rp ELSE 0 END) as oli_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN oli_rp ELSE 0 END) as oli_non_tunai
    FROM transaksi 
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
    AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))
");
$stmt->execute([$filter_bulan]);
$summary_bulanan = $stmt->fetch();

// Summary Tahunan
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_non_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN oli_rp ELSE 0 END) as oli_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN oli_rp ELSE 0 END) as oli_non_tunai
    FROM transaksi 
    WHERE YEAR(tanggal) = ?
    AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))
");
$stmt->execute([$filter_tahun]);
$summary_tahunan = $stmt->fetch();

// Data grafik harian untuk chart
$stmt = $pdo->prepare("SELECT tanggal,
    SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_non_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN oli_rp ELSE 0 END) as oli_tunai,
    SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN oli_rp ELSE 0 END) as oli_non_tunai
    FROM transaksi
    WHERE tanggal BETWEEN ? AND ?
    AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))
    GROUP BY tanggal ORDER BY tanggal ASC");
$range_start = date('Y-m-01', strtotime($filter_tanggal));
$range_end = $filter_tanggal;
$stmt->execute([$range_start, $range_end]);
$grafik_harian = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Koperasi - Sistem UPJ AHHAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                            <a class="nav-link active" href="dashboard_koperasi.php">
                                <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="laporan_penjualan_harian_sperpart_koperasi.php">
                                <i class="fas fa-calendar-day fa-fw"></i> Laporan Harian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="laporan_penjualan_bulanan_sperpart_koperasi.php">
                                <i class="fas fa-calendar-alt fa-fw"></i> Laporan Bulanan
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

            <main class="col-md-9 ms-sm-auto col-lg-10" style="height: 100vh; overflow-y: auto;">
                <header class="header-bar" style="position: sticky; top: 0; z-index: 1020; background-color: #fff;">
                    <h4 class="mb-0">Dashboard</h4>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                </header>
                <div class="main-content">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="tanggal" class="form-label">Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?= $filter_tanggal ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="bulan" class="form-label">Bulan</label>
                        <input type="month" id="bulan" name="bulan" class="form-control" value="<?= $filter_bulan ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="tahun" class="form-label">Tahun</label>
                        <input type="number" id="tahun" name="tahun" class="form-control" min="2020" max="<?= date('Y') ?>" value="<?= $filter_tahun ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                    </div>
                </form>
                <!-- Summary Cards Harian -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary">
                            <h6>Sperpart Tunai (Harian)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_harian['sperpart_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-cogs stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success">
                            <h6>Sperpart Non Tunai (Harian)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_harian['sperpart_non_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-credit-card stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info">
                            <h6>Oli Tunai (Harian)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_harian['oli_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-oil-can stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-dark">
                            <h6>Oli Non Tunai (Harian)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_harian['oli_non_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-credit-card stat-icon"></i>
                        </div>
                    </div>
                </div>
                <!-- Summary Cards Bulanan -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary">
                            <h6>Sperpart Tunai (Bulanan)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_bulanan['sperpart_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-cogs stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success">
                            <h6>Sperpart Non Tunai (Bulanan)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_bulanan['sperpart_non_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-credit-card stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info">
                            <h6>Oli Tunai (Bulanan)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_bulanan['oli_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-oil-can stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-dark">
                            <h6>Oli Non Tunai (Bulanan)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_bulanan['oli_non_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-credit-card stat-icon"></i>
                        </div>
                    </div>
                </div>
                <!-- Summary Cards Tahunan -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary">
                            <h6>Sperpart Tunai (Tahunan)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_tahunan['sperpart_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-cogs stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success">
                            <h6>Sperpart Non Tunai (Tahunan)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_tahunan['sperpart_non_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-credit-card stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info">
                            <h6>Oli Tunai (Tahunan)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_tahunan['oli_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-oil-can stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-dark">
                            <h6>Oli Non Tunai (Tahunan)</h6>
                            <div class="stat-number">Rp <?= number_format($summary_tahunan['oli_non_tunai'] ?? 0, 0, ',', '.') ?></div>
                            <i class="fas fa-credit-card stat-icon"></i>
                        </div>
                    </div>
                </div>
                <!-- Grafik Line Chart Harian -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Grafik Penjualan Harian (<?= date('M Y', strtotime($filter_tanggal)) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="grafikHarian"></canvas>
                    </div>
                </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
            const ctx = document.getElementById('grafikHarian').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [<?php foreach($grafik_harian as $row) echo "'".date('d M', strtotime($row['tanggal']))."',"; ?>],
                    datasets: [
                        {
                            label: 'Sperpart Tunai',
                            data: [<?php foreach($grafik_harian as $row) echo ($row['sperpart_tunai'] ?: 0).","; ?>],
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75,192,192,0.2)',
                            tension: 0.1
                        },
                        {
                            label: 'Sperpart Non Tunai',
                            data: [<?php foreach($grafik_harian as $row) echo ($row['sperpart_non_tunai'] ?: 0).","; ?>],
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255,99,132,0.2)',
                            tension: 0.1
                        },
                        {
                            label: 'Oli Tunai',
                            data: [<?php foreach($grafik_harian as $row) echo ($row['oli_tunai'] ?: 0).","; ?>],
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54,162,235,0.2)',
                            tension: 0.1
                        },
                        {
                            label: 'Oli Non Tunai',
                            data: [<?php foreach($grafik_harian as $row) echo ($row['oli_non_tunai'] ?: 0).","; ?>],
                            borderColor: 'rgb(255, 205, 86)',
                            backgroundColor: 'rgba(255,205,86,0.2)',
                            tension: 0.1
                        }
                    ]
                },
                options: {responsive:true, scales:{y:{beginAtZero:true}}}
            });
            </script>
    </body>
    </html>