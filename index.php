<?php
/**
 * Dashboard Utama - Backend Integrated
 * RSUD Maju Jaya
 */
require_once 'config.php';

// Hitung Statistik Real-time dari Database
$total_masuk    = $conn->query("SELECT id FROM referrals")->num_rows;
$total_pending  = $conn->query("SELECT id FROM referrals WHERE status_flow = 'ENTRY'")->num_rows;
$total_verif    = $conn->query("SELECT id FROM referrals WHERE status_flow = 'VERIFY'")->num_rows;
$total_selesai  = $conn->query("SELECT id FROM referrals WHERE status_flow = 'REPLIED'")->num_rows;

// Ambil 5 Aktivitas Terbaru dari Log
$sql_logs = "SELECT * FROM referral_logs ORDER BY id DESC LIMIT 5";
$result_logs = $conn->query($sql_logs);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RSUD Maju Jaya DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Top Navbar -->
        <header class="navbar">
            <div class="navbar-left">
                <button class="menu-toggle"><i class="ph ph-list"></i></button>
                <div class="logo">
                    <div class="logo-icon"><i class="ph ph-hospital"></i></div>
                    <span style="font-size: 14px; font-weight: 700;">RSUD Maju Jaya</span>
                </div>
            </div>
            <div class="navbar-right">
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=Admin+DMS&background=6C5CE7&color=fff&rounded=true">
                    <div class="user-info">
                        <span class="user-name">Administrator Utama</span>
                        <span class="user-role">Super Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="main-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-content">
                    <button class="new-doc-btn" onclick="window.location.href='upload.php'"><i class="ph ph-plus"></i><span>Upload Dokumen</span></button>
                    <nav class="sidebar-menu">
                        <div class="menu-group">
                            <h3 class="menu-title">Utama</h3>
                            <ul>
                                <li class="active"><a href="index.php"><i class="ph ph-squares-four"></i><span>Dashboard</span></a></li>
                                <li><a href="approval.php"><i class="ph ph-check-square-offset"></i><span>Verifikasi Berkas Masuk</span></a></li>
                                <li><a href="explorer.php"><i class="ph ph-files"></i><span>Document Explorer</span></a></li>
                                <li><a href="poli.php"><i class="ph ph-stethoscope"></i><span>Layanan Poli Sp.</span></a></li>
                                <li><a href="rujukan.php"><i class="ph ph-first-aid"></i><span>Basis Data Pasien</span></a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </aside>

            <!-- Content Area -->
            <main class="content-area">
                <div class="page-header-container">
                    <h1 class="page-title">Panda DMS Dashboard</h1>
                    <p class="page-description">Selamat datang kembali! Berikut ringkasan status rujukan dokumen di rumah sakit hari ini.</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
                    <div class="stat-card" style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
                        <span style="color:#666; font-size:12px; font-weight:700; text-transform:uppercase;">Total Rujukan</span>
                        <div style="font-size:32px; font-weight:800; color:var(--text-main); margin-top:8px;"><?= $total_masuk ?></div>
                        <div style="font-size:11px; color:#10b981; margin-top:4px;">Berhasil Terarsip</div>
                    </div>
                    <div class="stat-card" style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
                        <span style="color:#666; font-size:12px; font-weight:700; text-transform:uppercase;">Verifikasi Berlaku</span>
                        <div style="font-size:32px; font-weight:800; color:#f59e0b; margin-top:8px;"><?= $total_pending ?></div>
                        <div style="font-size:11px; color:#f59e0b; margin-top:4px;">Butuh Validasi</div>
                    </div>
                    <div class="stat-card" style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
                        <span style="color:#666; font-size:12px; font-weight:700; text-transform:uppercase;">Antrean Poli Sp.</span>
                        <div style="font-size:32px; font-weight:800; color:#3b82f6; margin-top:8px;"><?= $total_verif ?></div>
                        <div style="font-size:11px; color:#3b82f6; margin-top:4px;">Menunggu Diagnosa</div>
                    </div>
                    <div class="stat-card" style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
                        <span style="color:#666; font-size:12px; font-weight:700; text-transform:uppercase;">Balasan Terkirim</span>
                        <div style="font-size:32px; font-weight:800; color:#6366f1; margin-top:8px;"><?= $total_selesai ?></div>
                        <div style="font-size:11px; color:#6366f1; margin-top:4px;">Via WhatsApp</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                    <div class="card" style="padding: 24px;">
                        <h3 style="margin-bottom:20px;">Aktivitas Alur Terkini</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="text-align: left; background: #f8f9fa;">
                                    <th style="padding: 12px;">ID Rujukan</th>
                                    <th style="padding: 12px;">Tahapan</th>
                                    <th style="padding: 12px;">Keterangan Aktivitas</th>
                                    <th style="padding: 12px;">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_logs->num_rows > 0): ?>
                                    <?php while($log = $result_logs->fetch_assoc()): ?>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding: 12px; font-weight:700;"><?= $log['referral_id'] ?></td>
                                            <td style="padding: 12px;"><span class="status-badge" style="font-size:10px;"><?= $log['stage'] ?></span></td>
                                            <td style="padding: 12px; font-size:12px; color:#666;"><?= $log['action_text'] ?></td>
                                            <td style="padding: 12px; font-size:11px; color:#999;"><?= $log['action_time'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="padding: 20px; text-align:center;">Belum ada aktivitas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card" style="padding: 24px;">
                        <h3 style="margin-bottom:20px;">Quick Actions</h3>
                        <div style="display: grid; gap: 12px;">
                            <button onclick="window.location.href='upload.php'" style="padding: 16px; border-radius: 12px; background: #f0f7ff; border: 1px solid #cfe2ff; color: #004da0; text-align: left; cursor: pointer; font-weight: 600;">
                                <i class="ph ph-plus-circle"></i> Daftar Rujukan Baru
                            </button>
                            <button onclick="window.location.href='approval.php'" style="padding: 16px; border-radius: 12px; background: #fff4e6; border: 1px solid #ffe8cc; color: #d9480f; text-align: left; cursor: pointer; font-weight: 600;">
                                <i class="ph ph-shield-check"></i> Menu Verifikator
                            </button>
                            <button onclick="window.location.href='rujukan.php'" style="padding: 16px; border-radius: 12px; background: #f3f0ff; border: 1px solid #e5dbff; color: #5f3dc4; text-align: left; cursor: pointer; font-weight: 600;">
                                <i class="ph ph-database"></i> Lihat Basis Pasien
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
