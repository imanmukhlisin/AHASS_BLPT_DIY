<?php
include 'config.php';
checkAuth('Super Admin');

// Ringkasan jumlah user per role
$stmt = $pdo->query("SELECT role, COUNT(*) as jumlah FROM pengguna GROUP BY role");
$role_stats = $stmt->fetchAll();

// Jumlah user aktif vs nonaktif
$stmt = $pdo->query("SELECT status, COUNT(*) as jumlah FROM pengguna GROUP BY status");
$status_stats = $stmt->fetchAll();

// Log aktivitas terakhir
$stmt = $pdo->query("SELECT l.*, u.username FROM log_aktivitas l JOIN pengguna u ON l.user_id = u.id_user ORDER BY l.waktu DESC LIMIT 10");
$logs = $stmt->fetchAll();

$total_pengguna = $pdo->query("SELECT COUNT(*) FROM pengguna")->fetchColumn();
$failed_login = $pdo->query("SELECT COUNT(*) FROM log_aktivitas WHERE aktivitas LIKE '%gagal login%' AND DATE(waktu)=CURDATE()")->fetchColumn();
$aktivitas_hari_ini = $pdo->query("SELECT COUNT(*) FROM log_aktivitas WHERE DATE(waktu)=CURDATE()")->fetchColumn();
$role_labels = [];
$role_values = [];
foreach ($role_stats as $r) {
    $role_labels[] = $r['role'];
    $role_values[] = $r['jumlah'];
}

// Data aktivitas harian 7 hari terakhir
$stmt = $pdo->query("
    SELECT DATE(waktu) as tanggal, COUNT(*) as jumlah
    FROM log_aktivitas
    WHERE waktu >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(waktu)
    ORDER BY tanggal ASC
");
$aktivitas_harian = $stmt->fetchAll();
$aktivitas_labels = [];
$aktivitas_values = [];
foreach ($aktivitas_harian as $a) {
    $aktivitas_labels[] = $a['tanggal'];
    $aktivitas_values[] = $a['jumlah'];
}

// Data aktivitas per jam hari ini (langsung waktu server)
$stmt = $pdo->query("
    SELECT LPAD(HOUR(waktu),2,'0') as jam, COUNT(*) as jumlah
    FROM log_aktivitas
    WHERE DATE(waktu) = CURDATE()
    GROUP BY jam
    ORDER BY jam ASC
");
$aktivitas_jam = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$aktivitas_labels = [];
$aktivitas_values = [];
for ($i = 0; $i < 24; $i++) {
    $jam = sprintf('%02d:00', $i);
    $aktivitas_labels[] = $jam;
    $aktivitas_values[] = isset($aktivitas_jam[sprintf('%02d', $i)]) ? (int)$aktivitas_jam[sprintf('%02d', $i)] : 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                            <a class="nav-link active" href="dashboard_superadmin.php">
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
                    <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Super Admin</h4>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-pill px-4 py-2 d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>
                        </div>
                    </div>
                </header>
                <div class="main-content">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-2">
                            <div class="stat-card bg-primary text-center h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="stat-icon mb-2"><i class="fas fa-users"></i></div>
                                <div class="stat-number"><?= $total_pengguna ?></div>
                                <div class="fw-semibold mt-1">Total Pengguna</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="stat-card bg-info text-center h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="stat-icon mb-2"><i class="fas fa-server"></i></div>
                                <div class="stat-number">Online</div>
                                <div class="fw-semibold mt-1">Status Sistem</div>
                                <div style="font-size:0.9em;">Server OK<br>DB OK</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="stat-card bg-warning text-center h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="stat-icon mb-2"><i class="fas fa-bolt"></i></div>
                                <div class="stat-number"><?= $aktivitas_hari_ini ?></div>
                                <div class="fw-semibold mt-1">Aktivitas Terkini</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card bg-success text-center h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="stat-icon mb-2"><i class="fas fa-chart-pie"></i></div>
                                <div style="width:100px; margin:auto;">
                                    <canvas id="rolePieChart" width="100" height="100"></canvas>
                                </div>
                                <div class="fw-semibold mt-1">Distribusi Role</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="stat-card bg-danger text-center h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="stat-icon mb-2"><i class="fas fa-exclamation-triangle"></i></div>
                                <div class="stat-number"><?= $failed_login ?></div>
                                <div class="fw-semibold mt-1">Security Alert</div>
                                <div style="font-size:0.9em;">Gagal Login Hari Ini</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mb-2">
                        <div class="col-md-6">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-primary text-white fw-semibold">Jumlah User per Role</div>
                                <div class="card-body p-2">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($role_stats as $r): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= $r['role'] ?>
                                                <span class="badge bg-primary rounded-pill"><?= $r['jumlah'] ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-success text-white fw-semibold">Status User</div>
                                <div class="card-body p-2">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($status_stats as $s): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= $s['status'] ?>
                                                <span class="badge bg-success rounded-pill"><?= $s['jumlah'] ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mb-2">
                        <div class="col-12">
                            <div class="card shadow-sm h-100" style="width:100%;max-width:900px;margin:auto;">
                                <div class="card-header bg-info text-white fw-semibold">Grafik Aktivitas Hari Ini (Per Jam)</div>
                                <div class="card-body p-3" style="height:320px;">
                                    <canvas id="aktivitasChart" style="max-width:100%;height:220px;"></canvas>
                                    <div class="mt-3" style="max-height:80px;overflow-y:auto;">
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('rolePieChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($role_labels) ?>,
            datasets: [{
                data: <?= json_encode($role_values) ?>,
                backgroundColor: [
                    '#1976d2', '#5f0afdff', '#ffa726', '#29b6f6', '#ab47bc', '#ef5350'
                ],
            }]
        },
        options: {
            plugins: { legend: { display: true, position: 'bottom' } },
            responsive: false,
            maintainAspectRatio: false
        }
    });

    // Grafik aktivitas harian per jam
    const ctxAktivitas = document.getElementById('aktivitasChart').getContext('2d');
    new Chart(ctxAktivitas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($aktivitas_labels) ?>,
            datasets: [{
                label: 'Jumlah Aktivitas per Jam',
                data: <?= json_encode($aktivitas_values) ?>,
                backgroundColor: '#42a5f5',
                borderRadius: 4,
                maxBarThickness: 18
            }]
        },
        options: {
            scales: {
                x: {
                    title: { display: true, text: 'Jam' },
                    ticks: { autoSkip: false, maxRotation: 0, minRotation: 0, font: { size: 10 } },
                    grid: { display: false }
                },
                y: {
                    title: { display: true, text: 'Jumlah Aktivitas' },
                    beginAtZero: true,
                    stepSize: 1,
                    grid: { color: "#e3f2fd" }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.parsed.y + ' aktivitas';
                        }
                    }
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
    </script>
</body>
</html>