<?php
/**
 * Dashboard Utama - Referral System (Premium Sync)
 * RSUD Maju Jaya
 */
require_once 'config.php';

// Dashboard Stats
$total_rujukan = $conn->query("SELECT COUNT(*) FROM referrals")->fetch_row()[0];
$antrean_poli = $conn->query("SELECT COUNT(*) FROM referrals WHERE status_flow = 'VERIFY'")->fetch_row()[0];
$butuh_verif = $conn->query("SELECT COUNT(*) FROM referrals WHERE status_flow = 'ENTRY'")->fetch_row()[0];
$terarsip = $conn->query("SELECT COUNT(*) FROM referrals WHERE status_flow = 'REPLIED'")->fetch_row()[0];

// Log Aktivitas
$logs = $conn->query("SELECT * FROM referral_logs ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - RSUD Maju Jaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Header Konsisten -->
        <header class="navbar">
            <div class="navbar-left">
                <button class="menu-toggle"><i class="ph ph-list"></i></button>
                <div class="logo">
                    <div class="logo-icon"><i class="ph ph-hospital"></i></div>
                    <span style="font-size: 14px; font-weight: 800;">RSUD Maju Jaya</span>
                </div>
            </div>
            <div class="navbar-right">
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=Admin+RS&background=6C5CE7&color=fff&rounded=true">
                    <div class="user-info">
                        <span class="user-name">Administrator Utama</span>
                        <span class="user-role">Super Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="main-layout">
            <!-- Sidebar Konsisten -->
            <aside class="sidebar">
                <div class="sidebar-content">
                    <button class="new-doc-btn" onclick="window.location.href='referral_registration.php'"><i class="ph ph-plus"></i><span>Upload Dokumen</span></button>
                    <nav class="sidebar-menu">
                        <div class="menu-group">
                            <h3 class="menu-title">Utama</h3>
                            <ul>
                                <li class="active"><a href="referral_dashboard.php"><i class="ph ph-squares-four"></i><span>Dashboard</span></a></li>
                                <li><a href="referral_verification.php"><i class="ph ph-check-square-offset"></i><span>Verifikasi Berkas Masuk</span></a></li>
                                <li><a href="referral_explorer.php"><i class="ph ph-files"></i><span>Arsip Rujukan Digital</span></a></li>
                                <li><a href="referral_specialist.php"><i class="ph ph-stethoscope"></i><span>Layanan Poli Spesialis</span></a></li>
                                <li><a href="referral_patients.php"><i class="ph ph-users"></i><span>Basis Data Pasien</span></a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </aside>

            <main class="content-area">
                <h1 class="page-title">Referral System Dashboard</h1>
                <p class="page-description">Ringkasan aktivitas rujukan rumah sakit hari ini.</p>

                <!-- Stats Grid -->
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 32px;">
                    <div class="card" style="padding: 24px; border-radius: 20px;">
                        <div style="color: #64748b; font-size: 12px; font-weight: 800; text-transform: uppercase;">Total Rujukan</div>
                        <div style="font-size: 32px; font-weight: 800; margin: 8px 0;"><?= $total_rujukan ?></div>
                        <div style="font-size: 11px; color: #10b981;">+ Data Terbaru</div>
                    </div>
                    <div class="card" style="padding: 24px; border-radius: 20px;">
                        <div style="color: #64748b; font-size: 12px; font-weight: 800; text-transform: uppercase;">Butuh Verifikasi</div>
                        <div style="font-size: 32px; font-weight: 800; margin: 8px 0; color: #f59e0b;"><?= $butuh_verif ?></div>
                        <div style="font-size: 11px; color: #f59e0b;">Menunggu Verifikator</div>
                    </div>
                    <div class="card" style="padding: 24px; border-radius: 20px;">
                        <div style="color: #64748b; font-size: 12px; font-weight: 800; text-transform: uppercase;">Antrean Poli</div>
                        <div style="font-size: 32px; font-weight: 800; margin: 8px 0; color: #6366f1;"><?= $antrean_poli ?></div>
                        <div style="font-size: 11px; color: #6366f1;">Siap Didiagnosa</div>
                    </div>
                    <div class="card" style="padding: 24px; border-radius: 20px;">
                        <div style="color: #64748b; font-size: 12px; font-weight: 800; text-transform: uppercase;">Selesai/Arsip</div>
                        <div style="font-size: 32px; font-weight: 800; margin: 8px 0; color: #10b981;"><?= $terarsip ?></div>
                        <div style="font-size: 11px; color: #10b981;">Sudah Berbalas</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
                    <div class="card" style="padding: 32px; border-radius: 24px;">
                        <h3 style="margin-bottom: 24px; font-weight: 800;">Aktivitas Alur Terkini</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <?php while($log = $logs->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 16px 0;">
                                    <div style="font-weight: 700;"><?= $log['referral_id'] ?></div>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= date('H:i', strtotime($log['created_at'])) ?></div>
                                </td>
                                <td style="padding: 16px 0;">
                                    <span style="font-size: 12px; background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-weight: 600;"><?= $log['stage'] ?></span>
                                </td>
                                <td style="padding: 16px 0; font-size: 13px; color: #475569;">
                                    <?= $log['action_text'] ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                    
                    <div class="card" style="padding: 32px; border-radius: 24px; background: #6366f1; color: white;">
                        <h3 style="margin-bottom: 16px; font-weight: 800;">Quick Actions</h3>
                        <button onclick="window.location.href='referral_registration.php'" style="width:100%; padding:15px; border-radius:12px; border:none; background:rgba(255,255,255,0.2); color:white; font-weight:800; margin-bottom:12px; cursor:pointer; text-align:left;"><i class="ph ph-plus"></i> Daftar Rujukan Baru</button>
                        <button onclick="window.location.href='referral_verification.php'" style="width:100%; padding:15px; border-radius:12px; border:none; background:rgba(255,255,255,0.2); color:white; font-weight:800; cursor:pointer; text-align:left;"><i class="ph ph-check"></i> Menu Verifikator</button>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
