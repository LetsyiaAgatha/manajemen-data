<?php
/**
 * Layanan Poli Spesialis - Referral System (Final Layout Fix)
 * RSUD Maju Jaya
 */
require_once 'config.php';

$notif_status = "";
$debug_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['referral_id'])) {
    $id = $conn->real_escape_string($_POST['referral_id']);
    $spec_diagnosis = $conn->real_escape_string($_POST['spec_diagnosis']);
    $follow_up = $conn->real_escape_string($_POST['follow_up']);
    
    $check_sql = "SELECT patient_name, faskes_wa, origin_faskes FROM referrals WHERE referral_id = '$id'";
    $data = $conn->query($check_sql)->fetch_assoc();
    $p_name = $data['patient_name'];
    $f_name = $data['origin_faskes'];
    $f_wa   = preg_replace('/[^0-9]/', '', $data['faskes_wa']); 

    $conn->query("UPDATE referrals SET status_flow = 'REPLIED', spec_diagnosis = '$spec_diagnosis', follow_up_plan = '$follow_up', replied_at = DATETIME('now', 'localtime') WHERE referral_id = '$id'");
    $conn->query("INSERT INTO referral_logs (referral_id, stage, action_text, user_name, created_at) VALUES ('$id', 'REPLIED', 'Selesai didiagnosa spesialis', 'dr. Jatmiko', DATETIME('now', 'localtime'))");
    
    $message = "*BALASAN RUJUKAN RSUD MAJU JAYA*\n\n"
             . "Yth. Rekan Medis di *$f_name*,\n"
             . "Berikut rujukan balasan pasien:\n\n"
             . "Nama: *$p_name*\n"
             . "Diagnosa: $spec_diagnosis\n"
             . "Rencana: $follow_up\n\n"
             . "Tks.";
    
    $notif_status = sendWhatsApp($f_wa, $message);
    $notif_obj = json_decode($notif_status, true);
    if (!$notif_obj['status']) {
        $debug_msg = $notif_obj['reason'] ?? 'HP Disconnected';
    }
}

$sql_poli = "SELECT * FROM referrals WHERE status_flow = 'VERIFY' ORDER BY created_at DESC";
$result = $conn->query($sql_poli);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Layanan Poli Spesialis - RSUD Maju Jaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.3s; z-index: 9999; }
        .modal.active { opacity: 1; visibility: visible; }
        .modal-container { background: white; border-radius: 24px; width: 500px; padding: 40px; position: relative; box-shadow: 0 30px 100px rgba(0,0,0,0.3); text-align: center; }
        .btn-modern { cursor: pointer; border: none; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s; font-family: 'Outfit'; }
        .btn-primary { background: #6366f1; color: white; padding: 14px 28px; width: 100%; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2); }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header Identik Dashboard -->
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
                    <img src="https://ui-avatars.com/api/?name=Dr+Jatmiko&background=6C5CE7&color=fff&rounded=true">
                    <div class="user-info">
                        <span class="user-name">dr. Jatmiko, Sp.JP</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="main-layout">
            <!-- Sidebar Identik Dashboard -->
            <aside class="sidebar">
                <div class="sidebar-content">
                    <button class="new-doc-btn" onclick="window.location.href='referral_registration.php'"><i class="ph ph-plus"></i><span>Upload Dokumen</span></button>
                    <nav class="sidebar-menu">
                        <div class="menu-group">
                            <h3 class="menu-title">Utama</h3>
                            <ul>
                                <li><a href="referral_dashboard.php"><i class="ph ph-squares-four"></i><span>Dashboard</span></a></li>
                                <li><a href="referral_verification.php"><i class="ph ph-check-square-offset"></i><span>Verifikasi Berkas Masuk</span></a></li>
                                <li><a href="referral_explorer.php"><i class="ph ph-files"></i><span>Arsip Rujukan Digital</span></a></li>
                                <li class="active"><a href="referral_specialist.php"><i class="ph ph-stethoscope"></i><span>Layanan Poli Spesialis</span></a></li>
                                <li><a href="referral_patients.php"><i class="ph ph-users"></i><span>Basis Data Pasien</span></a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </aside>

            <main class="content-area">
                <h1 class="page-title">Pemeriksaan Poli Spesialis</h1>
                <div class="card" style="border-radius: 20px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: 12px; color: #64748b;">
                                <th style="padding: 20px; text-align: left;">PASIEN</th>
                                <th style="padding: 20px; text-align: left;">DIAGNOSA FASKES</th>
                                <th style="padding: 20px; text-align: right;">INSTRUMEN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 20px;">
                                            <div style="font-weight: 800;"><?= $row['patient_name'] ?></div>
                                            <div style="font-size: 11px; color: #94a3b8;"><?= $row['referral_id'] ?></div>
                                        </td>
                                        <td style="padding: 20px; font-size: 14px; color: #475569;"><?= $row['diagnosis_initial'] ?></td>
                                        <td style="padding: 20px; text-align: right;">
                                            <button onclick="openForm('<?= $row['referral_id'] ?>', '<?= $row['patient_name'] ?>')" style="padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight:700;">Buat Balasan</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="padding: 60px; text-align: center; color: #cbd5e1;">Antrean poli sedang kosong.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal" id="modalForm" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal-container" style="text-align: left;">
            <h2 style="font-weight: 800; margin-bottom: 8px;">Diagnosa Akhir</h2>
            <p style="margin-bottom: 24px; color: #64748b;">Pasien: <strong id="dispName" style="color:#6366f1;">-</strong></p>
            <form action="referral_specialist.php" method="POST">
                <input type="hidden" name="referral_id" id="valId">
                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-weight:700; font-size:12px; margin-bottom:8px; color:#475569;">HASIL PEMERIKSAAN</label>
                    <textarea name="spec_diagnosis" required style="width:100%; border:1.5px solid #e2e8f0; border-radius:12px; padding:15px; font-family:inherit; font-size:14px;" rows="5"></textarea>
                </div>
                <div style="margin-bottom: 30px;">
                    <label style="display:block; font-weight:700; font-size:12px; margin-bottom:8px; color:#475569;">REKOMENDASI</label>
                    <select name="follow_up" style="width:100%; padding:14px; border-radius:12px; border:1.5px solid #e2e8f0; background:#fff;">
                        <option value="KONSUL_BALIK">Konsul Balik (PRB)</option>
                        <option value="KONTROL_RUTIN">Kontrol Rutin di RSUD</option>
                    </select>
                </div>
                <button type="submit" class="btn-modern btn-primary">Simpan & Beritahu Faskes</button>
            </form>
        </div>
    </div>

    <!-- Status Modal (Beautiful Center) -->
    <div class="modal <?= (!empty($notif_status) || !empty($debug_msg)) ? 'active' : '' ?>" id="statusModal" onclick="this.classList.remove('active')">
        <div class="modal-container">
            <?php if (empty($debug_msg)): ?>
                <div style="width:70px; height:70px; background:#f0fdf4; color:#10b981; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:35px;"><i class="ph ph-check-circle"></i></div>
                <h2 style="font-weight:800; margin-bottom:12px;">Selesai!</h2>
                <p style="color:#64748b; font-size:14px; line-height:1.6; margin-bottom:24px;">Diagnosa tersimpan dan rujukan balasan terkirim otomatis.</p>
                <button onclick="document.getElementById('statusModal').classList.remove('active')" class="btn-modern btn-primary" style="width:auto; padding:12px 40px; background:#1e293b;">Kembali ke Antrean</button>
            <?php else: ?>
                <div style="width:70px; height:70px; background:#fef2f2; color:#ef4444; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:35px;"><i class="ph ph-warning"></i></div>
                <h2 style="font-weight:800; margin-bottom:12px;">Data Tersimpan</h2>
                <p style="color:#64748b; font-size:14px; line-height:1.6;">Laporan medis Aman, namun WhatsApp gagal terkirim: <br><strong style="color:#ef4444;"><?= $debug_msg ?></strong></p>
                <p style="font-size:11px; color:#999; margin-top:10px;">Pastikan HP di Fonnte "Connected".</p>
                <button onclick="document.getElementById('statusModal').classList.remove('active')" class="btn-modern btn-primary" style="width:auto; padding:12px 40px; background:#ef4444;">Tutup</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openForm(id, name) {
            document.getElementById('valId').value = id;
            document.getElementById('dispName').textContent = name;
            document.getElementById('modalForm').classList.add('active');
        }
    </script>
</body>
</html>
