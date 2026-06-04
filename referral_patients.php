<?php
/**
 * Basis Data Pasien - Referral System (Soft Delete Version)
 * RSUD Maju Jaya
 */
require_once 'config.php';

// AJAX Endpoint: Get Patient Detail & History
if (isset($_GET['action']) && $_GET['action'] === 'get_patient_detail') {
    $pid = $conn->real_escape_string($_GET['patient_id']);
    $p_res = $conn->query("SELECT * FROM patients WHERE patient_id = '$pid' AND is_deleted = 0");
    if ($p_res->num_rows > 0) {
        $patient = $p_res->fetch_assoc();
        $nik = $patient['nik'];
        $name = $patient['name'];
        
        $history = [];
        if (!empty($nik)) {
            $h_res = $conn->query("SELECT * FROM referrals WHERE (nik = '$nik' OR patient_name = '" . $conn->real_escape_string($name) . "') AND is_deleted = 0 ORDER BY created_at DESC");
        } else {
            $h_res = $conn->query("SELECT * FROM referrals WHERE patient_name = '" . $conn->real_escape_string($name) . "' AND is_deleted = 0 ORDER BY created_at DESC");
        }
        while ($h_row = $h_res->fetch_assoc()) {
            $history[] = $h_row;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'patient' => $patient,
            'history' => $history
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Pasien tidak ditemukan']);
        exit;
    }
}

// 1. Logic Update Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    $pid = $conn->real_escape_string($_POST['patient_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $birth = $conn->real_escape_string($_POST['birth_date']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $ins = $conn->real_escape_string($_POST['insurance_type']);

    $sql_update = "UPDATE patients SET name='$name', birth_date='$birth', gender='$gender', phone='$phone', insurance_type='$ins' WHERE patient_id='$pid'";
    if ($conn->query($sql_update)) { header("Location: referral_patients.php?msg=updated"); exit; }
}

// 2. Logic Soft Delete
if (isset($_GET['delete_id'])) {
    $did = $conn->real_escape_string($_GET['delete_id']);
    $conn->query("UPDATE patients SET is_deleted = 1 WHERE patient_id = '$did'");
    header("Location: referral_patients.php?msg=deleted");
    exit;
}

// 3. Ambil data pasien (Hanya yang is_deleted = 0)
$sql_patients = "SELECT * FROM patients WHERE is_deleted = 0 ORDER BY created_at DESC";
$result = $conn->query($sql_patients);

$patients_js = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { $patients_js[] = $row; }
    $result->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Basis Data Pasien - RSUD Maju Jaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.3s; z-index: 9999; }
        .modal.active { opacity: 1; visibility: visible; }
        .modal-layout { background: white; border-radius: 24px; padding: 40px; width: 550px; position: relative; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 13px; color: #475569; }
        .form-input { width: 100%; padding: 14px; border-radius: 12px; border: 1.5px solid #e2e8f0; font-family: inherit; margin-bottom: 20px; }
        .action-btn { width: 38px; height: 38px; border-radius: 10px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration:none; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .spinner { animation: spin 1s linear infinite; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header Identik -->
        <header class="navbar">
            <div class="navbar-left">
                <button class="menu-toggle"><i class="ph ph-list"></i></button>
                <div class="logo" style="display: flex; align-items: center; gap: 10px;">
                    <img src="img/logo.png" alt="Logo" style="height: 38px; width: auto; object-fit: contain;">
                    <span style="font-size: 14px; font-weight: 800;">RSUD Maju Jaya</span>
                </div>
            </div>
            <div class="navbar-right">
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=Admin+Patient&background=6C5CE7&color=fff&rounded=true">
                    <div class="user-info"><span class="user-name">Administrator Pasien</span></div>
                </div>
            </div>
        </header>

        <div class="main-layout">
            <!-- Sidebar Identik -->
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
                                <li><a href="referral_specialist.php"><i class="ph ph-stethoscope"></i><span>Penerimaan Rujukan Akhir</span></a></li>
                                <li class="active"><a href="referral_patients.php"><i class="ph ph-users"></i><span>Basis Data Pasien</span></a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </aside>

            <main class="content-area">
                <h1 class="page-title">Basis Data Pasien</h1>
                <p class="page-description">Daftar rekam medis pasien yang terverifikasi di RSUD Maju Jaya.</p>

                <div class="card" style="border-radius: 20px; overflow: hidden; margin-top: 32px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: 12px; color: #64748b;">
                                <th style="padding: 16px; text-align: left;">NAMA PASIEN</th>
                                <th style="padding: 16px; text-align: left;">TTL / GENDER</th>
                                <th style="padding: 16px; text-align: left;">WHATSAPP</th>
                                <th style="padding: 16px; text-align: right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 16px;">
                                            <div style="font-weight: 800; color: #1e293b;"><?= $row['name'] ?></div>
                                            <div style="font-size: 11px; color: #94a3b8;"><?= $row['patient_id'] ?></div>
                                        </td>
                                        <td style="padding: 16px; font-size: 13px;">
                                            <div><?= date('d M Y', strtotime($row['birth_date'])) ?></div>
                                            <div style="color: #64748b; font-size: 11px;"><?= $row['gender'] ?></div>
                                        </td>
                                        <td style="padding: 16px; font-size: 13px; color: #10b981; font-weight: 700;"><?= $row['phone'] ?></td>
                                        <td style="padding: 16px; text-align: right;">
                                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                                <button onclick="openDetailModal('<?= $row['patient_id'] ?>')" class="action-btn" style="background:#e0e7ff; color:#4f46e5;" title="Detail & Riwayat Medis"><i class="ph ph-user-list"></i></button>
                                                <button onclick="openEditModal('<?= $row['patient_id'] ?>')" class="action-btn" style="background:#f1f5f9; color:#6366f1;" title="Edit Profil"><i class="ph ph-note-pencil"></i></button>
                                                <a href="referral_patients.php?delete_id=<?= $row['patient_id'] ?>" onclick="return confirm('Arsip pasien ini akan dipindahkan ke folder sampah (Soft Delete). Lanjutkan?')" class="action-btn" style="background:#fef2f2; color:#ef4444;" title="Hapus"><i class="ph ph-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="padding: 60px; text-align: center; color: #cbd5e1;">Belum ada data pasien aktif.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal" id="modalEdit" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal-layout">
            <h2 style="font-weight: 800; margin-bottom: 24px;">Edit Profil Pasien</h2>
            <form action="referral_patients.php" method="POST">
                <input type="hidden" name="update_patient" value="1">
                <input type="hidden" name="patient_id" id="eId">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" id="eName" class="form-input" required>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Tgl Lahir</label>
                        <input type="date" name="birth_date" id="eBirth" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" id="eGender" class="form-input">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>
                <label class="form-label">No. WhatsApp</label>
                <input type="text" name="phone" id="ePhone" class="form-input">
                <label class="form-label">Tipe Jaminan</label>
                <select name="insurance_type" id="eIns" class="form-input">
                    <option value="JKN - PBI">JKN - PBI</option>
                    <option value="JKN - NON PBI">JKN - NON PBI</option>
                    <option value="UMUM">UMUM</option>
                </select>
                <button type="submit" style="width:100%; padding:18px; background:#6366f1; color:white; border:none; border-radius:14px; font-weight:800; cursor:pointer;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- Modal Detail & Riwayat Pasien -->
    <div class="modal" id="modalDetail" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal-layout" style="width: 950px; max-width: 95%; max-height: 90vh; overflow-y: auto; display: flex; flex-direction: column; gap: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 15px;">
                <h2 style="font-weight: 800; font-size: 20px; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-folder-open" style="color: #6366f1;"></i> Rekam Medis & Profil Pasien
                </h2>
                <button onclick="document.getElementById('modalDetail').classList.remove('active')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;"><i class="ph ph-x"></i></button>
            </div>
            
            <div id="detailLoading" style="text-align: center; padding: 40px; color: #64748b;">
                <i class="ph ph-circle-notch spinner" style="font-size: 32px; animation: spin 1s linear infinite; display: inline-block;"></i>
                <p style="margin-top: 10px; font-weight: 600;">Memuat rekam medis...</p>
            </div>
            
            <div id="detailContent" style="display: none; grid-template-columns: 1fr 1.8fr; gap: 24px;">
                <!-- Left: Profil Pasien -->
                <div style="background: #f8fafc; border-radius: 16px; padding: 24px; border: 1.5px solid #e2e8f0; align-self: start;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img id="detAvatar" src="" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid #6366f1; margin-bottom: 10px;">
                        <h3 id="detName" style="font-weight: 800; font-size: 18px; color: #1e293b; margin: 0;">-</h3>
                        <span id="detId" style="font-size: 12px; color: #94a3b8; font-weight: 600;">-</span>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; font-weight: 700; display: block; text-transform: uppercase;">NIK (Nomor Induk Kependudukan)</span>
                            <span id="detNik" style="font-size: 14px; color: #334155; font-weight: 600;">-</span>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; font-weight: 700; display: block; text-transform: uppercase;">No. JKN / BPJS</span>
                            <span id="detBpjs" style="font-size: 14px; color: #334155; font-weight: 600;">-</span>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; font-weight: 700; display: block; text-transform: uppercase;">Tempat / Tanggal Lahir</span>
                            <span id="detTtl" style="font-size: 14px; color: #334155; font-weight: 600;">-</span>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; font-weight: 700; display: block; text-transform: uppercase;">Jenis Kelamin</span>
                            <span id="detGender" style="font-size: 14px; color: #334155; font-weight: 600;">-</span>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; font-weight: 700; display: block; text-transform: uppercase;">No. WhatsApp</span>
                            <span id="detPhone" style="font-size: 14px; color: #10b981; font-weight: 700;">-</span>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; font-weight: 700; display: block; text-transform: uppercase;">Tipe Jaminan</span>
                            <span id="detInsurance" style="font-size: 14px; color: #334155; font-weight: 600;">-</span>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; font-weight: 700; display: block; text-transform: uppercase;">Terdaftar Sejak</span>
                            <span id="detRegDate" style="font-size: 14px; color: #334155; font-weight: 600;">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Riwayat Medis / Rujukan -->
                <div>
                    <h3 style="font-weight: 800; font-size: 16px; color: #1e293b; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-clock-counter-clockwise"></i> Log Riwayat Rujukan & Medis
                    </h3>
                    
                    <div id="detHistoryList" style="display: flex; flex-direction: column; gap: 16px; max-height: 55vh; overflow-y: auto; padding-right: 8px;">
                        <!-- List dynamically generated -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const patients = <?= json_encode($patients_js) ?>;
        function openEditModal(id) {
            const p = patients.find(x => x.patient_id === id);
            if (!p) return;
            document.getElementById('eId').value = p.patient_id;
            document.getElementById('eName').value = p.name;
            document.getElementById('eBirth').value = p.birth_date;
            document.getElementById('eGender').value = p.gender;
            document.getElementById('ePhone').value = p.phone;
            document.getElementById('eIns').value = p.insurance_type;
            document.getElementById('modalEdit').classList.add('active');
        }

        function openDetailModal(id) {
            const modal = document.getElementById('modalDetail');
            const loading = document.getElementById('detailLoading');
            const content = document.getElementById('detailContent');
            
            loading.style.display = 'block';
            content.style.display = 'none';
            modal.classList.add('active');
            
            fetch(`referral_patients.php?action=get_patient_detail&patient_id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        const p = res.patient;
                        const history = res.history;
                        
                        // Populate profile
                        document.getElementById('detAvatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=6C5CE7&color=fff&rounded=true`;
                        document.getElementById('detName').textContent = p.name;
                        document.getElementById('detId').textContent = p.patient_id;
                        document.getElementById('detNik').textContent = p.nik || '-';
                        document.getElementById('detBpjs').textContent = p.card_number || '-';
                        
                        // Age calculation
                        let dobText = '-';
                        if (p.birth_date) {
                            const dob = new Date(p.birth_date);
                            const options = { year: 'numeric', month: 'short', day: 'numeric' };
                            const dobStr = dob.toLocaleDateString('id-ID', options);
                            const ageDiff = Date.now() - dob.getTime();
                            const ageDate = new Date(ageDiff);
                            const age = Math.abs(ageDate.getUTCFullYear() - 1970);
                            dobText = `${dobStr} (${age} Tahun)`;
                        }
                        document.getElementById('detTtl').textContent = dobText;
                        document.getElementById('detGender').textContent = p.gender || '-';
                        document.getElementById('detPhone').textContent = p.phone || '-';
                        document.getElementById('detInsurance').textContent = p.insurance_type || '-';
                        document.getElementById('detRegDate').textContent = p.created_at || '-';
                        
                        // Populate history list
                        const list = document.getElementById('detHistoryList');
                        list.innerHTML = '';
                        
                        if (history && history.length > 0) {
                            history.forEach(item => {
                                const status = item.status_flow;
                                let badgeBg = '#6366f1';
                                let badgeColor = 'white';
                                let statusText = 'PROSES VERIFIKASI';
                                
                                if (status === 'REPLIED') {
                                    badgeBg = '#d1fae5';
                                    badgeColor = '#065f46';
                                    statusText = 'SELESAI (ARSIP)';
                                } else if (status === 'VERIFY') {
                                    badgeBg = '#fef3c7';
                                    badgeColor = '#92400e';
                                    statusText = 'PENERIMAAN RUJUKAN AKHIR';
                                }
                                
                                const finalDest = item.final_destination || item.target_poli || '-';
                                const verifiedBy = item.verified_by || '-';
                                const medNotes = item.medical_notes || '-';
                                const therapyInit = item.therapy_initial || '-';
                                const specDiag = item.spec_diagnosis || '-';
                                const followUp = item.follow_up_plan || '-';
                                
                                const div = document.createElement('div');
                                div.style.background = 'white';
                                div.style.border = '1.5px solid #e2e8f0';
                                div.style.borderRadius = '16px';
                                div.style.padding = '16px';
                                div.style.boxShadow = '0 1px 3px rgba(0,0,0,0.05)';
                                div.style.marginBottom = '12px';
                                
                                div.innerHTML = `
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                        <span style="font-size: 13px; font-weight: 800; color: #4f46e5;">ID: ${item.referral_id}</span>
                                        <span style="font-size: 10px; font-weight: 800; background: ${badgeBg}; color: ${badgeColor}; padding: 4px 8px; border-radius: 6px;">${statusText}</span>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px; color: #475569; margin-bottom: 12px; line-height: 1.5;">
                                        <div><strong>Faskes Asal:</strong> ${item.origin_faskes || '-'}</div>
                                        <div><strong>Poli Rujukan:</strong> ${item.target_poli || '-'}</div>
                                        <div><strong>Tanggal Masuk:</strong> ${item.received_date || '-'}</div>
                                        <div><strong>Diagnosa Awal:</strong> ${item.diagnosis_initial || '-'}</div>
                                    </div>
                                    
                                    <details style="border-top: 1px dashed #e2e8f0; padding-top: 10px; margin-top: 10px; font-size: 12px; color: #475569;">
                                        <summary style="cursor: pointer; font-weight: 700; color: #6366f1; outline: none; list-style: none; display: flex; align-items: center; gap: 6px;">
                                            <i class="ph ph-caret-down"></i> Detail Rekam Medis & Catatan Tindakan
                                        </summary>
                                        <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 12px; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1.5px solid #f1f5f9; line-height: 1.5;">
                                            <div>
                                                <strong style="color: #334155;">Catatan Keluhan / Medis (Faskes):</strong>
                                                <p style="margin: 4px 0 0 0; color: #64748b;">${medNotes}</p>
                                            </div>
                                            <div>
                                                <strong style="color: #334155;">Terapi Awal (Faskes):</strong>
                                                <p style="margin: 4px 0 0 0; color: #64748b;">${therapyInit}</p>
                                            </div>
                                            
                                            <div style="border-top: 1.5px solid #e2e8f0; padding-top: 10px;">
                                                <strong style="color: #334155;">Hasil Verifikasi Nakes General:</strong>
                                                <div style="margin-top: 6px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                    <div>Pemeriksa: <span style="font-weight: 600; color: #1e293b;">${verifiedBy}</span></div>
                                                    <div>Tujuan Akhir: <span style="font-weight: 600; color: #991b1b;">${finalDest}</span></div>
                                                </div>
                                            </div>
                                            
                                            ${status === 'REPLIED' ? `
                                            <div style="border-top: 1.5px solid #e2e8f0; padding-top: 10px;">
                                                <strong style="color: #334155;">Hasil Diagnosa Akhir / Rencana Tindak Lanjut:</strong>
                                                <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 4px;">
                                                    <div>Diagnosa Spesialis: <span style="font-weight: 600; color: #1e293b;">${specDiag}</span></div>
                                                    <div>Rencana Tindakan: <span style="font-weight: 600; color: #1e293b;">${followUp}</span></div>
                                                </div>
                                            </div>
                                            ` : ''}
                                        </div>
                                    </details>
                                `;
                                list.appendChild(div);
                            });
                        } else {
                            list.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 20px;">Belum ada riwayat medis terdaftar.</p>';
                        }
                        
                        loading.style.display = 'none';
                        content.style.display = 'grid';
                    } else {
                        alert(res.message || 'Gagal memuat rekam medis');
                        modal.classList.remove('active');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Gagal memuat data dari server');
                    modal.classList.remove('active');
                });
        }
    </script>
</body>
</html>
