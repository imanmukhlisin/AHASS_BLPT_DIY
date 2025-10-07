<?php
    include 'config.php';
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Kepala Kasi Layanan') {
        header('Location: login.php');
        exit();
    }
    $selected_month = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
    // Summary
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_unit, SUM(jasa_rp + jasa_lain_rp) as total_jasa, SUM(sperpart_rp) as total_sperpart, SUM(oli_rp) as total_oli, SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp ELSE 0 END) as total_tunai, SUM(CASE WHEN metode_pembayaran != 'Tunai' THEN jasa_rp + jasa_lain_rp + sperpart_rp + oli_rp ELSE 0 END) as total_non_tunai FROM transaksi WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))");
    $stmt->execute([$selected_month]);
    $summary = $stmt->fetch();
    $total_pendapatan = ($summary['total_jasa'] ?? 0) + ($summary['total_sperpart'] ?? 0) + ($summary['total_oli'] ?? 0);
    // Detail transaksi
    $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah')) ORDER BY created_at ASC");
    $stmt->execute([$selected_month]);
    $transaksi_bulanan = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laporan Bulanan Kepala BLPT</title>
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
                <h5 class="fw-bold" >UPJ AHASS BLPT DIY</h5>
                <small><?= $_SESSION['role'] ?></small>
            </div>
             <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard_kepala_kasi_layanan.php">
                                    <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="laporan_harian_kepala_kasi_layanan.php">
                                    <i class="fas fa-file-alt fa-fw"></i> Laporan Harian
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="laporan_bulanan_kepala_kasi_layanan.php">
                                    <i class="fas fa-chart-bar fa-fw"></i> Laporan Bulanan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="laporan_kinerja_karyawan.php">
                                    <i class="fas fa-users fa-fw"></i>Kinerja Karyawan
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
                <main class="col-md-9 ms-sm-auto col-lg-10">
                    <header class="header-bar" style="position: sticky; top: 0; z-index: 1020; background-color: #fff;">
                        <h4 class="mb-0">Laporan Bulanan Kepala Kasi Layanan</h4>
                        <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                    </header>
                    <div class="main-content" style="height: calc(100vh - 74px); overflow-y: auto;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <form method="GET" action="" class="d-flex align-items-center">
                                <label for="bulan" class="form-label me-2 mb-0">Bulan:</label>
                                <input type="month" id="bulan" name="bulan" value="<?= $selected_month ?>" class="form-control me-2" style="width: 200px;">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Terapkan Filter</button>
                            </form>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card bg-success">
                                    <h6>Total Pendapatan</h6>
                                    <div class="stat-number">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
                                    <i class="fas fa-wallet stat-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card bg-info">
                                    <h6>Pembayaran Tunai</h6>
                                    <div class="stat-number">Rp <?= number_format($summary['total_tunai'] ?? 0, 0, ',', '.') ?></div>
                                    <i class="fas fa-money-bill-wave stat-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card bg-warning text-dark">
                                    <h6>Pembayaran Non-Tunai</h6>
                                    <div class="stat-number">Rp <?= number_format($summary['total_non_tunai'] ?? 0, 0, ',', '.') ?></div>
                                    <i class="fas fa-credit-card stat-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card bg-secondary text-white">
                                    <h6>Total Unit</h6>
                                    <div class="stat-number"><?= $summary['total_unit'] ?? 0 ?></div>
                                    <i class="fas fa-car stat-icon"></i>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Jam</th>
                                                <th>Nota</th>
                                                <th>Mekanik</th> <!-- Tambahkan ini -->
                                                <th>Jasa</th>
                                                <th>Sperpart</th>
                                                <th>Oli</th>
                                                <th>Metode</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($transaksi_bulanan)): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center py-5">
                                                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted">Tidak ada transaksi pada bulan ini.</p>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php 
                                                $grand_total = 0;
                                                foreach ($transaksi_bulanan as $trx): 
                                                    $total_trx = $trx['jasa_rp'] + $trx['jasa_lain_rp'] + $trx['sperpart_rp'] + $trx['oli_rp'];
                                                    $grand_total += $total_trx;
                                                ?>
                                                    <tr>
                                                        <td><?= date('d/m/Y', strtotime($trx['tanggal'])) ?></td>
                                                        <td><?= date('H:i', strtotime($trx['created_at'])) ?></td>
                                                        <td><?= htmlspecialchars($trx['no_nota']) ?></td>
                                                        <td><?= htmlspecialchars($trx['mekanik'] ?? '-') ?></td> <!-- Tambahkan ini -->
                                                        <td>Rp <?= number_format($trx['jasa_rp'] + $trx['jasa_lain_rp'], 0, ',', '.') ?></td>
                                                        <td>Rp <?= number_format($trx['sperpart_rp'], 0, ',', '.') ?></td>
                                                        <td>Rp <?= number_format($trx['oli_rp'], 0, ',', '.') ?></td>
                                                        <td>
                                                            <span class="badge <?= $trx['metode_pembayaran'] == 'Tunai' ? 'bg-success-subtle text-success-emphasis' : 'bg-primary-subtle text-primary-emphasis' ?>">
                                                                <?= htmlspecialchars($trx['metode_pembayaran']) ?>
                                                            </span>
                                                        </td>
                                                        <td><strong>Rp <?= number_format($total_trx, 0, ',', '.') ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <?php if (!empty($transaksi_bulanan)): ?>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="8" class="text-end">Total</td>
                                                <td>Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                                            </tr>
                                        </tfoot>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.getElementById('bulan').addEventListener('change', function() {
                this.form.submit();
            });
        </script>
    </body>
    </html>
