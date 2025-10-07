<?php
        include 'config.php';
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Koperasi') {
            header('Location: login.php');
            exit();
        }
        $filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
        $filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
        // Summary Bulanan
        $stmt = $pdo->prepare("SELECT 
            SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_tunai,
            SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN sperpart_rp ELSE 0 END) as sperpart_non_tunai,
            SUM(CASE WHEN metode_pembayaran = 'Tunai' THEN oli_rp ELSE 0 END) as oli_tunai,
            SUM(CASE WHEN metode_pembayaran = 'Non Tunai' THEN oli_rp ELSE 0 END) as oli_non_tunai
            FROM transaksi 
            WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
            AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah'))");
        $stmt->execute([$filter_bulan]);
        $summary_bulanan = $stmt->fetch();
        // Akumulasi Non Tunai Sperpart & Oli
        $stmt = $pdo->prepare("SELECT 
            SUM(sperpart_rp) as total_sperpart_non_tunai,
            SUM(oli_rp) as total_oli_non_tunai
            FROM transaksi 
            WHERE metode_pembayaran = 'Non Tunai' 
            AND status_verifikasi_bendahara = 'Sudah'
            AND DATE_FORMAT(tanggal, '%Y-%m') = ?");
        $stmt->execute([$filter_bulan]);
        $akumulasi_non_tunai = $stmt->fetch();
        // Data transaksi bulanan
        $stmt = $pdo->prepare("SELECT tanggal, no_nota, sperpart_rp, oli_rp, metode_pembayaran FROM transaksi WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? AND (metode_pembayaran = 'Tunai' OR (metode_pembayaran != 'Tunai' AND status_verifikasi_bendahara = 'Sudah')) ORDER BY tanggal ASC");
        $stmt->execute([$filter_bulan]);
        $transaksi = $stmt->fetchAll();
        // Ambil data transaksi sperpart non tunai selama 1 bulan YANG SUDAH DIVERIFIKASI BENDAHARA
        $stmt = $pdo->prepare("SELECT tanggal, no_nota, sperpart_rp, oli_rp, metode_pembayaran 
            FROM transaksi 
            WHERE metode_pembayaran = 'Non Tunai' 
            AND status_verifikasi_bendahara = 'Sudah'
            AND DATE_FORMAT(tanggal, '%Y-%m') = ? 
            ORDER BY tanggal ASC");
        $stmt->execute([$filter_bulan]);
        $transaksi_non_tunai = $stmt->fetchAll();
        ?>
    <!DOCTYPE html>
      <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Laporan Penjualan Bulanan Koperasi - Sistem UPJ AHHAS</title>
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
                        <a class="nav-link" href="dashboard_koperasi.php">
                            <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan_penjualan_harian_sperpart_koperasi.php">
                            <i class="fas fa-calendar-day fa-fw"></i> Laporan Harian
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="laporan_penjualan_bulanan_sperpart_koperasi.php">
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
                            <h4 class="mb-0">Laporan Penjualan Bulanan Koperasi</h4>
                            <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                        </header>
                        <div class="main-content">
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="bulan" class="form-label">Bulan</label>
                                <input type="month" id="bulan" name="bulan" class="form-control" value="<?= $filter_bulan ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="tahun" class="form-label">Tahun</label>
                                <input type="number" id="tahun" name="tahun" class="form-control" min="2020" max="<?= date('Y') ?>" value="<?= $filter_tahun ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            </div>
                        </form>
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
                        <!-- Akumulasi Non Tunai -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white"><i class="fas fa-credit-card"></i> Akumulasi Sperpart Non Tunai Bulanan</div>
                                    <div class="card-body">
                                        <h5 class="mb-0">Rp <?= number_format($akumulasi_non_tunai['total_sperpart_non_tunai'] ?? 0, 0, ',', '.') ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark"><i class="fas fa-credit-card"></i> Akumulasi Oli Non Tunai Bulanan</div>
                                    <div class="card-body">
                                        <h5 class="mb-0">Rp <?= number_format($akumulasi_non_tunai['total_oli_non_tunai'] ?? 0, 0, ',', '.') ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2 text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPenandaTangan">
                                Cetak PDF
                            </button>
                        </div>
                        <!-- Tabel Transaksi Bulanan -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white"><i class="fas fa-table"></i> Detail Transaksi Bulanan</div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>No Nota</th>
                                                <th>Sperpart (Rp)</th>
                                                <th>Oli (Rp)</th>
                                                <th>Metode Pembayaran</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($transaksi as $row): ?>
                                            <tr>
                                                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                                <td><?= htmlspecialchars($row['no_nota'] ?? '-') ?></td>
                                                <td>Rp <?= number_format($row['sperpart_rp'] ?? 0, 0, ',', '.') ?></td>
                                                <td>Rp <?= number_format($row['oli_rp'] ?? 0, 0, ',', '.') ?></td>
                                                <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($transaksi)): ?>
                                            <tr><td colspan="5" class="text-center">Tidak ada data transaksi bulan ini.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="modalPenandaTangan" tabindex="-1" aria-labelledby="modalPenandaTanganLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form id="formPenandaTangan" class="modal-content shadow-lg rounded-3" onsubmit="return submitPenandaTangan();">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="modalPenandaTanganLabel">📄 Pilih Penanda Tangan</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-3 text-muted">Silakan pilih untuk menjadi penanda tangan laporan ini:</p>
                                    <div class="list-group">
                                    <?php
                                    $stmt_users = $pdo->query("SELECT id, nama_lengkap, jabatan FROM penandatangan ORDER BY urutan ASC");
                                    $users = $stmt_users->fetchAll();
                                    foreach ($users as $u): ?>
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
                    </main>
                </div>
            </div>
            <!-- PRINT FORMAT -->
            <div class="print-format" style="padding:32px; text-align:center;">
                <h4 style="margin-bottom:0;">LAPORAN PENJUALAN SPERPART NON TUNAI BULANAN KOPERASI</h4>
                <h5 style="margin-bottom:10px;">BULAN <?= strtoupper(date('F Y', strtotime($filter_bulan.'-01'))) ?></h5>
                <div style="width:100%; display:flex; justify-content:center;">
                    <div style="max-width:700px; width:90%; margin:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:10pt; margin-bottom:18px;">
                            <thead>
                                <tr style="background:#e3f2fd;">
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>No Nota</th>
                                    <th>Sperpart (Rp)</th>
                                    <th>Oli (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                    <?php
                    $no = 1;
                    $total_sperpart = $total_oli = 0;
                    foreach ($transaksi_non_tunai as $row):
                        $total_sperpart += ($row['sperpart_rp'] ?? 0);
                        $total_oli += ($row['oli_rp'] ?? 0);
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['no_nota'] ?? '-') ?></td>
                        <td><?= number_format($row['sperpart_rp'] ?? 0, 0, ',', '.') ?></td>
                        <td><?= number_format($row['oli_rp'] ?? 0, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($no == 1): ?>
                    <tr><td colspan="5" class="text-center">Tidak ada data sperpart non tunai bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot style="font-weight:bold; background:#f5f5f5;">
                    <tr>
                        <td colspan="3">Total</td>
                        <td><?= number_format($total_sperpart, 0, ',', '.') ?></td>
                        <td><?= number_format($total_oli, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <!-- Tanda tangan dinamis sejajar -->
            <div id="signatureSectionPrint" style="margin-top:30px; width:100%;">
                <div id="ttd-jabatan-row" style="display:flex; justify-content:center; gap:60px; margin-bottom:70px;"></div>
                <div id="ttd-nama-row" style="display:flex; justify-content:center; gap:30px;"></div>
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
    </body>
</html>
