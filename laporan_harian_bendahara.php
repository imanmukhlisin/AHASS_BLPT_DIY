<?php
include 'config.php';
checkAuth('Bendahara');

$selected_date = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Ambil data summary untuk tanggal yang dipilih
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_unit,
        SUM(COALESCE(jasa_rp,0) + COALESCE(jasa_lain_rp,0)) as total_jasa,
        SUM(COALESCE(sperpart_rp,0)) as total_sperpart,
        SUM(COALESCE(oli_rp,0)) as total_oli,
        SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN COALESCE(jasa_rp,0) + COALESCE(jasa_lain_rp,0) + COALESCE(sperpart_rp,0) + COALESCE(oli_rp,0) ELSE 0 END) as total_tunai,
        SUM(CASE WHEN metode_pembayaran != 'Tunai' THEN COALESCE(jasa_rp,0) + COALESCE(jasa_lain_rp,0) + COALESCE(sperpart_rp,0) + COALESCE(oli_rp,0) ELSE 0 END) as total_non_tunai
    FROM transaksi 
    WHERE tanggal = ?
    AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))
");
$stmt->execute([$selected_date]);
$summary = $stmt->fetch();

// Tambahkan definisi total_pendapatan agar tidak error
$total_pendapatan = 
    ($summary['total_jasa'] ?? 0) +
    ($summary['total_sperpart'] ?? 0) +
    ($summary['total_oli'] ?? 0);

$stmt = $pdo->prepare("
    SELECT * FROM transaksi
    WHERE tanggal = ?
    AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))
    ORDER BY created_at ASC
");
$stmt->execute([$selected_date]);
$transaksi_harian = $stmt->fetchAll();

// Ambil daftar penandatangan dari database
$stmt_users = $pdo->query("SELECT id, nama_lengkap, jabatan FROM penandatangan ORDER BY urutan ASC");
$users = $stmt_users->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian - Bendahara</title>
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
                        <li class="nav-item"><a class="nav-link" href="dashboard_bendahara.php"><i class="fas fa-check-circle fa-fw"></i> Verifikasi</a></li>
                        <li class="nav-item"><a class="nav-link active" href="laporan_harian_bendahara.php"><i class="fas fa-file-alt fa-fw"></i> Laporan Harian</a></li>
                        <li class="nav-item"><a class="nav-link" href="laporan_bulanan_bendahara.php"><i class="fas fa-chart-bar fa-fw"></i> Laporan Bulanan</a></li>
                    </ul>
                    <div class="mt-auto p-3">
                        <a class="btn btn-danger w-100" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                    </div>
                </div>
            </nav>
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10">
                <header class="header-bar" style="position: sticky; top: 0; z-index: 1020; background-color: #fff;">
                    <h4 class="mb-0">Laporan Harian</h4>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                </header>
                <div class="main-content" style="height: calc(100vh - 74px); overflow-y: auto;">
                    <!-- Filter Tanggal -->
                    <div class="card mb-4 card-filter">
                        <div class="card-body">
                            <form method="GET" action="" class="d-flex align-items-center">
                                <label for="tanggal" class="form-label me-2 mb-0">Pilih Tanggal:</label>
                                <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($selected_date) ?>" class="form-control me-2" style="width: 200px;">
                                <button type="submit" class="btn btn-primary">Tampilkan</button>
                            </form>
                        </div>
                    </div>
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card bg-secondary text-white">
                                <h6>Total Unit</h6>
                                <div class="stat-number"><?= $summary['total_unit'] ?? 0 ?></div>
                                <i class="fas fa-car stat-icon"></i>
                            </div>
                        </div>
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
                    </div>
                    <!-- Tabel Laporan -->
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Detail Transaksi Tanggal <?= date('d F Y', strtotime($selected_date)) ?></h5>
                            <div>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPenandaTangan">
                                    Cetak PDF
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Jam</th>
                                            <th>Nota</th>
                                            <th>Mekanik</th>
                                            <th>Metode</th> <!-- Tambahkan ini -->
                                            <th>Jasa</th>
                                            <th>Sperpart</th>
                                            <th>Oli</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($transaksi_harian)): ?>
                                            <tr><td colspan="8" class="text-center py-5">Tidak ada transaksi pada tanggal ini.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($transaksi_harian as $row): ?>
                                                <tr>
                                                    <td><?= date('H:i', strtotime($row['created_at'])) ?></td>
                                                    <td><?= htmlspecialchars($row['no_nota'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($row['mekanik'] ?? '-') ?></td>
                                                     <td>
                                                        <?php if (($row['metode_pembayaran'] ?? '') == 'Tunai'): ?>
                                                            <span class="badge bg-success-subtle text-success-emphasis">Tunai</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-primary-subtle text-primary-emphasis">Non-Tunai</span>
                                                        <?php endif; ?>
                                                    </td><!-- Tambahkan ini -->
                                                        <td>Rp <?= number_format($row['jasa_rp'] + $row['jasa_lain_rp'], 0, ',', '.') ?></td>
                                                        <td>Rp <?= number_format($row['sperpart_rp'], 0, ',', '.') ?></td>
                                                        <td>Rp <?= number_format($row['oli_rp'], 0, ',', '.') ?></td>
                                                        <td>Rp <?= number_format(($row['jasa_rp'] ?? 0) + ($row['jasa_lain_rp'] ?? 0) + ($row['sperpart_rp'] ?? 0) + ($row['oli_rp'] ?? 0) , 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td>Total</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td>Rp <?= number_format($summary['total_jasa'] ?? 0, 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($summary['total_sperpart'] ?? 0, 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($summary['total_oli'] ?? 0, 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- PRINT FORMAT -->
                    <div class="print-format" style="padding:32px; text-align:center;">
    <h4 style="margin-bottom:0;">LAPORAN HARIAN UPJ AHHAS BLPT DIY</h4>
    <h5 style="margin-bottom:10px;">TANGGAL <?= strtoupper(date('d F Y', strtotime($selected_date))) ?></h5>
    <div style="width:100%; display:flex; justify-content:center;">
        <div style="max-width:400px; width:80%;">
            <div style="display:flex; justify-content:center; gap:1px; width:100%;">
                <!-- JASA -->
                <table style="width:15%; min-width:170px; border-collapse:collapse; margin:0; font-size:8pt;">
                    <tr>
                        <th rowspan="2" style="width:8%;">NO</th>
                        <th rowspan="2" style="width:28%;">NOTA</th>
                        <th colspan="2" style="width:16%;">SERVIS</th>
                        <th colspan="2" style="width:16%;">LAIN LAIN</th>
                        <th rowspan="2" style="width:16%;">JUMLAH</th>
                    </tr>
                    <tr>
                        <th style="width:8%;">CASH</th>
                        <th style="width:8%;">NON CASH</th>
                        <th style="width:8%;">CASH</th>
                        <th style="width:8%;">NON CASH</th>
                    </tr>
                    <?php
                    $no = 1;
                    $total_servis_cash = $total_servis_non_cash = $total_lain_cash = $total_lain_non_cash = $total_jasa_jumlah = 0;
                    foreach ($transaksi_harian as $trx):
                        $servis_cash = ($trx['metode_pembayaran']=='Tunai') ? ($trx['jasa_rp']??0) : 0;
                        $servis_non_cash = ($trx['metode_pembayaran']!='Tunai') ? ($trx['jasa_rp']??0) : 0;
                        $lain_cash = ($trx['metode_pembayaran']=='Tunai') ? ($trx['jasa_lain_rp']??0) : 0;
                        $lain_non_cash = ($trx['metode_pembayaran']!='Tunai') ? ($trx['jasa_lain_rp']??0) : 0;
                        $jumlah = $servis_cash + $servis_non_cash + $lain_cash + $lain_non_cash;
                        $total_servis_cash += $servis_cash;
                        $total_servis_non_cash += $servis_non_cash;
                        $total_lain_cash += $lain_cash;
                        $total_lain_non_cash += $lain_non_cash;
                        $total_jasa_jumlah += $jumlah;
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td style="word-break:break-all;"><?= htmlspecialchars($trx['no_nota']??'-') ?></td>
                        <td><?= number_format($servis_cash,0,',','.') ?></td>
                        <td><?= number_format($servis_non_cash,0,',','.') ?></td>
                        <td><?= number_format($lain_cash,0,',','.') ?></td>
                        <td><?= number_format($lain_non_cash,0,',','.') ?></td>
                        <td><?= number_format($jumlah,0,',','.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight:bold;">
                        <td colspan="2">Total</td>
                        <td><?= number_format($total_servis_cash,0,',','.') ?></td>
                        <td><?= number_format($total_servis_non_cash,0,',','.') ?></td>
                        <td><?= number_format($total_lain_cash,0,',','.') ?></td>
                        <td><?= number_format($total_lain_non_cash,0,',','.') ?></td>
                        <td><?= number_format($total_jasa_jumlah,0,',','.') ?></td>
                    </tr>
                </table>
                <!-- PENJUALAN (tanpa NO dan NOTA) -->
                <table style="width:15%; min-width:170px; border-collapse:collapse; margin:0; font-size:8pt;">
                    <tr>
                         <th colspan="2" style="width:16%;">PART</th>
                        <th colspan="2" style="width:16%;">OLI</th>
                        <th rowspan="2" style="width:16%;">JUMLAH</th>
                    </tr>
                    <tr>
                        <th style="width:8%;">CASH</th>
                        <th style="width:8%;">NON CASH</th>
                        <th style="width:8%;">CASH</th>
                        <th style="width:8%;">NON CASH</th>
                    </tr>
                    <?php
                    $total_part_cash = $total_part_non_cash = $total_oli_cash = $total_oli_non_cash = $total_penjualan_jumlah = 0;
                    foreach ($transaksi_harian as $trx):
                        $part_cash = ($trx['metode_pembayaran']=='Tunai') ? ($trx['sperpart_rp']??0) : 0;
                        $part_non_cash = ($trx['metode_pembayaran']!='Tunai') ? ($trx['sperpart_rp']??0) : 0;
                        $oli_cash = ($trx['metode_pembayaran']=='Tunai') ? ($trx['oli_rp']??0) : 0;
                        $oli_non_cash = ($trx['metode_pembayaran']!='Tunai') ? ($trx['oli_rp']??0) : 0;
                        $jumlah = $part_cash + $part_non_cash + $oli_cash + $oli_non_cash;
                        $total_part_cash += $part_cash;
                        $total_part_non_cash += $part_non_cash;
                        $total_oli_cash += $oli_cash;
                        $total_oli_non_cash += $oli_non_cash;
                        $total_penjualan_jumlah += $jumlah;
                    ?>
                    <tr>
                        <td><?= number_format($part_cash,0,',','.') ?></td>
                        <td><?= number_format($part_non_cash,0,',','.') ?></td>
                        <td><?= number_format($oli_cash,0,',','.') ?></td>
                        <td><?= number_format($oli_non_cash,0,',','.') ?></td>
                        <td><?= number_format($jumlah,0,',','.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight:bold;">
                        <td><?= number_format($total_part_cash,0,',','.') ?></td>
                        <td><?= number_format($total_part_non_cash,0,',','.') ?></td>
                        <td><?= number_format($total_oli_cash,0,',','.') ?></td>
                        <td><?= number_format($total_oli_non_cash,0,',','.') ?></td>
                        <td><?= number_format($total_penjualan_jumlah,0,',','.') ?></td>
                    </tr>
                </table>
                <!-- JUMLAH (tanpa NO dan NOTA) -->
                <table style="width:15%; min-width:100px; border-collapse:collapse; margin:0; font-size:8pt;">
                    <tr>
                        <th colspan="2" style="width:16%;">JUMLAH</th>
                    </tr>
                    <tr>
                        <th style="width:8%;">Cash</th>
                        <th style="width:8%;">QRIS</th>
                    </tr>
                    <?php
                    $total_cash = $total_qris = $total_jumlah = 0;
                    foreach ($transaksi_harian as $trx):
                        $jumlah = ($trx['jasa_rp']??0)+($trx['jasa_lain_rp']??0)+($trx['sperpart_rp']??0)+($trx['oli_rp']??0);
                        $cash = ($trx['metode_pembayaran']=='Tunai') ? $jumlah : 0;
                        $qris = ($trx['metode_pembayaran']!='Tunai') ? $jumlah : 0;
                        $total_cash += $cash;
                        $total_qris += $qris;
                    ?>
                    <tr>
                        <td><?= number_format($cash,0,',','.') ?></td>
                        <td><?= number_format($qris,0,',','.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight:bold;">
                       
                        <td><?= number_format($total_cash,0,',','.') ?></td>
                        <td><?= number_format($total_qris,0,',','.') ?></td>
                    </tr>
                </table>
            </div>
            <!-- Rekap bawah -->
            <table style="margin:18px auto 24px auto; width:70%; border-collapse:collapse; font-size:8pt;">
                <tr>
                    <th></th>
                    <th>Cash</th>
                    <th>QRIS</th>
                    <th>Jumlah</th>
                </tr>
                <tr>
                    <td>BLPT</td>
                    <td><?= number_format($total_servis_cash + $total_lain_cash,0,',','.') ?></td>
                    <td><?= number_format($total_servis_non_cash + $total_lain_non_cash,0,',','.') ?></td>
                    <td><?= number_format($total_jasa_jumlah,0,',','.') ?></td>
                </tr>
                <tr>
                    <td>KOPERASI</td>
                    <td><?= number_format($total_part_cash + $total_oli_cash,0,',','.') ?></td>
                    <td><?= number_format($total_part_non_cash + $total_oli_non_cash,0,',','.') ?></td>
                    <td><?= number_format($total_penjualan_jumlah,0,',','.') ?></td>
                </tr>
                <tr style="font-weight:bold;">
                    <td>Total</td>
                    <td><?= number_format($total_servis_cash + $total_lain_cash + $total_part_cash + $total_oli_cash,0,',','.') ?></td>
                    <td><?= number_format($total_servis_non_cash + $total_lain_non_cash + $total_part_non_cash + $total_oli_non_cash,0,',','.') ?></td>
                    <td><?= number_format($total_jasa_jumlah + $total_penjualan_jumlah,0,',','.') ?></td>
                </tr>
            </table>
            <!-- Tanda tangan dinamis sejajar -->
            <div id="signatureSectionPrint" style="margin-top:30px; width:100%;">
    <div id="ttd-jabatan-row" style="display:flex; justify-content:center; gap:60px; margin-bottom:70px;"></div>
    <div id="ttd-nama-row" style="display:flex; justify-content:center; gap:30px;"></div>
</div>
<script>
window.onbeforeprint = function() {
    let ttd_ids = JSON.parse(localStorage.getItem('penanda_tangan') || '[]');
    let users = <?= json_encode($users) ?>;
    let selected = users.filter(u => ttd_ids.includes(u.id.toString()));
    let jabatanRow = document.getElementById('ttd-jabatan-row');
    let namaRow = document.getElementById('ttd-nama-row');
    jabatanRow.innerHTML = '';
    namaRow.innerHTML = '';
    selected.forEach(function(u) {
        let jabatanBox = document.createElement('div');
        jabatanBox.style.width = '220px';
        jabatanBox.style.textAlign = 'center';
        jabatanBox.innerText = u.jabatan;
        jabatanRow.appendChild(jabatanBox);

        let namaBox = document.createElement('div');
        namaBox.style.width = '230px';
        namaBox.style.textAlign = 'center';
        namaBox.innerHTML = `<span style="font-weight:bold; display:inline-block; min-width:120px; padding-bottom:2px;white-space: nowrap;">${u.nama_lengkap}</span>`;
        namaRow.appendChild(namaBox);
    });
};
window.onafterprint = function() {
    document.getElementById('ttd-jabatan-row').innerHTML = '';
    document.getElementById('ttd-nama-row').innerHTML = '';
};
</script>
                    <!-- Signature Section SELALU DI BAWAH PRINT VIEW -->
                    <div class="signature-section mt-5 text-center" id="signatureSection"></div>
                </div>
            </main>
        </div>
    </div>
    <!-- Modal Pilih Penanda Tangan -->
    <div class="modal fade" id="modalPenandaTangan" tabindex="-1" aria-labelledby="modalPenandaTanganLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formPenandaTangan" class="modal-content shadow-lg rounded-3" onsubmit="return submitPenandaTangan();">
      
      <!-- Header -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalPenandaTanganLabel">📄 Pilih Penanda Tangan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <p class="mb-3 text-muted">Silakan pilih untuk menjadi penanda tangan laporan ini:</p>
        <div class="list-group">
          <?php foreach ($users as $u): ?>
            <label class="list-group-item d-flex align-items-center">
              <input class="form-check-input me-2" type="checkbox" name="penanda_tangan[]" value="<?= $u['id'] ?>">
              <div>
                <strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong><br>
                <small class="text-muted"><?= htmlspecialchars($u['jabatan']) ?></small>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="btnCetakLaporan">
          <i class="bi bi-printer"></i> Cetak Laporan
        </button>
      </div>

    </form>
  </div>
</div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function submitPenandaTangan() {
        let checked = [];
        document.querySelectorAll('input[name="penanda_tangan[]"]:checked').forEach(function(el) {
            checked.push(el.value);
        });
        localStorage.setItem('penanda_tangan', JSON.stringify(checked));
        var modal = bootstrap.Modal.getInstance(document.getElementById('modalPenandaTangan'));
        modal.hide();
        setTimeout(function() {
            window.print();
        }, 300);
        return false;
    }

   
    window.onafterprint = function() {
        for (let i = 0; i < 3; i++) {
            document.getElementById('ttd-jabatan-' + i).innerHTML = '';
            document.getElementById('ttd-nama-' + i).innerHTML = '';
            document.getElementById('ttd-line-' + i).style.width = '120px';
        }
    };
    </script>
</body>
</html>
