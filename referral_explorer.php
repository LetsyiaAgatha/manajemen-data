<?php
/**
 * Arsip & Pelacakan Rujukan - Referral System (Full Traceability)
 * RSUD Maju Jaya
 */
require_once 'config.php';

// 1. Ambil data logs untuk detail tracking (via AJAX atau pre-load)
// Di sini kita pre-load semua logs agar performa cepat
$all_logs = [];
$log_query = $conn->query("SELECT * FROM referral_logs ORDER BY created_at ASC");
while($l = $log_query->fetch_assoc()) {
    $all_logs[$l['referral_id']][] = $l;
}

// 2. Ambil semua rujukan (agar bisa dilacak posisinya)
$sql_docs = "SELECT * FROM referrals ORDER BY created_at DESC";
$result = $conn->query($sql_docs);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tracking & Arsip - RSUD Maju Jaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.3s; z-index: 9999; }
        .modal.active { opacity: 1; visibility: visible; }
        .modal-layout { background: white; border-radius: 24px; padding: 40px; width: 600px; max-height: 85vh; overflow-y: auto; position: relative; }
        
        /* Timeline Styling */
        .timeline { position: relative; padding-left: 40px; margin-top: 20px; }
        .timeline::before { content: ''; position: absolute; left: 15px; top: 0; width: 2px; height: 100%; background: #e2e8f0; }
        .timeline-item { position: relative; margin-bottom: 30px; }
        .timeline-icon { position: absolute; left: -32px; top: 0; width: 16px; height: 16px; border-radius: 50%; background: #6366f1; border: 3px solid white; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .timeline-content { background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .timeline-time { font-size: 11px; color: #94a3b8; font-weight: 600; }
        .timeline-author { font-size: 12px; font-weight: 700; color: #6366f1; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="app-container">
        <header class="navbar">
            <div class="navbar-left">
                <div class="logo">
                    <div class="logo-icon"><i class="ph ph-hospital"></i></div>
                    <span style="font-weight: 800;">RSUD Maju Jaya</span>
                </div>
            </div>
            <div class="navbar-right">
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=Arsiparis&background=6C5CE7&color=fff&rounded=true">
                    <div class="user-info"><span class="user-name">Pusat Pelacakan DMS</span></div>
                </div>
            </div>
        </header>

        <div class="main-layout">
            <aside class="sidebar">
                <div class="sidebar-content">
                    <button class="new-doc-btn" onclick="window.location.href='referral_registration.php'"><i class="ph ph-plus"></i><span>Upload Dokumen</span></button>
                    <nav class="sidebar-menu">
                        <div class="menu-group">
                            <h3 class="menu-title">Utama</h3>
                            <ul>
                                <li><a href="referral_dashboard.php"><i class="ph ph-squares-four"></i><span>Dashboard</span></a></li>
                                <li><a href="referral_verification.php"><i class="ph ph-check-square-offset"></i><span>Verifikasi Berkas Masuk</span></a></li>
                                <li class="active"><a href="referral_explorer.php"><i class="ph ph-files"></i><span>Arsip Rujukan Digital</span></a></li>
                                <li><a href="referral_specialist.php"><i class="ph ph-stethoscope"></i><span>Layanan Poli Spesialis</span></a></li>
                                <li><a href="referral_patients.php"><i class="ph ph-users"></i><span>Basis Data Pasien</span></a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </aside>

            <main class="content-area">
                <h1 class="page-title">Pusat Pelacakan & Arsip Digital</h1>
                <p class="page-description">Pantau jejak digital dan lokasi dokumen rujukan secara real-time.</p>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-top: 32px;">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $status = $row['status_flow'];
                            $badge_color = ($status == 'REPLIED') ? '#10b981' : (($status == 'VERIFY') ? '#f59e0b' : '#6366f1');
                            $status_text = ($status == 'REPLIED') ? 'SELESAI (ARSIP)' : (($status == 'VERIFY') ? 'DI POLI SPESIALIS' : 'PROSES VERIFIKASI');
                        ?>
                            <div class="card" style="padding: 24px; border-radius: 20px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <span style="font-size: 10px; font-weight: 800; color: white; background: <?= $badge_color ?>; padding: 4px 10px; border-radius: 6px;"><?= $status_text ?></span>
                                    <i class="ph ph-info" style="color: #cbd5e1; cursor: pointer;"></i>
                                </div>
                                <h3 style="font-weight: 800; font-size: 16px; margin-bottom: 4px;"><?= $row['patient_name'] ?></h3>
                                <div style="font-size: 11px; color: #94a3b8; font-family: monospace; margin-bottom: 20px;"><?= $row['referral_id'] ?></div>
                                
                                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; font-size: 12px; margin-bottom: 20px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span style="color: #64748b;">Faskes:</span>
                                        <span style="font-weight: 700;"><?= $row['origin_faskes'] ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: #64748b;">Diagnosa:</span>
                                        <span style="font-weight: 700;"><?= $row['icd10'] ?></span>
                                    </div>
                                </div>

                                <button onclick="traceDocument('<?= $row['referral_id'] ?>', '<?= $row['patient_name'] ?>')" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <i class="ph ph-magnifying-glass"></i> Lacak Jejak Digital
                                </button>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Tracking Timeline -->
    <div class="modal" id="modalTrace" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal-layout">
            <button onclick="document.getElementById('modalTrace').classList.remove('active')" style="position: absolute; top: 24px; right: 24px; border:none; background:#f1f5f9; width:44px; height:44px; border-radius:50%; cursor:pointer;"><i class="ph ph-x"></i></button>
            <h2 style="font-weight: 800; margin-bottom: 5px;">Audit Trail Dokumen</h2>
            <p style="color: #64748b; font-size: 14px;">Pasien: <strong id="tPatient" style="color: #6366f1;">-</strong></p>
            
            <div id="timelineContainer" class="timeline">
                <!-- Data Jejak akan Muncul di Sini -->
            </div>
        </div>
    </div>

    <script>
        const logData = <?= json_encode($all_logs) ?>;
        
        function traceDocument(id, name) {
            document.getElementById('tPatient').textContent = name;
            const timeline = document.getElementById('timelineContainer');
            timeline.innerHTML = '';
            
            const logs = logData[id] || [];
            
            if (logs.length === 0) {
                timeline.innerHTML = '<p style="text-align:center; padding:20px; color:#94a3b8;">Belum ada jejak audit terdata.</p>';
            } else {
                logs.forEach(log => {
                    const item = document.createElement('div');
                    item.className = 'timeline-item';
                    item.innerHTML = `
                        <div class="timeline-icon"></div>
                        <div class="timeline-content">
                            <div class="timeline-time">${new Date(log.created_at).toLocaleString('id-ID')}</div>
                            <div style="font-weight: 800; margin: 4px 0; font-size: 14px; color: #1e293b;">${log.action_text}</div>
                            <div class="timeline-author"><i class="ph ph-user-circle"></i> Ditangani Oleh: ${log.user_name}</div>
                        </div>
                    `;
                    timeline.appendChild(item);
                });
            }
            
            document.getElementById('modalTrace').classList.add('active');
        }
    </script>
</body>
</html>
