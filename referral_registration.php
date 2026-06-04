<?php
/**
 * Registrasi Rujukan Baru - Referral System (Final Template Sync)
 * RSUD Maju Jaya
 */
require_once 'config.php';

// AJAX NIK Search Handler
if (isset($_GET['action']) && $_GET['action'] === 'search_nik') {
    header('Content-Type: application/json');
    $nik = isset($_GET['nik']) ? $conn->real_escape_string($_GET['nik']) : '';
    
    $patient_query = $conn->query("SELECT * FROM patients WHERE nik = '$nik' AND is_deleted = 0 LIMIT 1");
    if ($patient_query && $patient_query->num_rows > 0) {
        $patient = $patient_query->fetch_assoc();
        
        // Fetch medical/visit history of this patient from referrals table
        $history_query = $conn->query("SELECT referral_id, target_poli, diagnosis_initial, status_flow, created_at FROM referrals WHERE nik = '$nik' AND is_deleted = 0 ORDER BY created_at DESC");
        $history = [];
        if ($history_query) {
            while ($row = $history_query->fetch_assoc()) {
                $history[] = $row;
            }
        }
        
        echo json_encode([
            'found' => true,
            'patient' => $patient,
            'history' => $history
        ]);
    } else {
        echo json_encode([
            'found' => false
        ]);
    }
    exit;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate ID (Opsi B: RUJ-[POLI]-YYMMDD-[XXX])
    $target_poli = $conn->real_escape_string($_POST['target_poli']);
    $poliName = trim(strtoupper($target_poli));
    if (strpos($poliName, 'POLI ') === 0) {
        $poliName = substr($poliName, 5);
    }
    $poliCode = substr($poliName, 0, 3);
    if (empty($poliCode)) { $poliCode = 'GEN'; }
    $dateStr = date('ymd');
    
    while (true) {
        $randNum = rand(100, 999);
        $ref_id = "RUJ-" . $poliCode . "-" . $dateStr . "-" . $randNum;
        $dup_check = $conn->query("SELECT id FROM referrals WHERE referral_id = '$ref_id'");
        if ($dup_check->num_rows == 0) {
            break;
        }
    }
    
    // Data Faskes
    $origin_faskes = $conn->real_escape_string($_POST['origin_faskes']);
    $faskes_kab_kota = $conn->real_escape_string($_POST['faskes_kab_kota']);
    $faskes_tingkat = $conn->real_escape_string($_POST['faskes_tingkat']);
    $faskes_wa = $conn->real_escape_string($_POST['faskes_wa']);
    $faskes_alamat = $conn->real_escape_string($_POST['faskes_alamat']);
    $faskes_telp = $conn->real_escape_string($_POST['faskes_telp']);
    $faskes_email = $conn->real_escape_string($_POST['faskes_email']);
    
    // Tujuan
    $target_poli = $conn->real_escape_string($_POST['target_poli']);
    $target_kota = $conn->real_escape_string($_POST['target_kota']);
    
    // Data Pasien
    $p_nik = $conn->real_escape_string($_POST['nik']);
    $p_name = $conn->real_escape_string($_POST['patient_name']);
    $p_wa = $conn->real_escape_string($_POST['patient_wa']);
    $card_num = $conn->real_escape_string($_POST['card_number']);
    $p_birth = $conn->real_escape_string($_POST['birth_date']);
    $p_gender = $conn->real_escape_string($_POST['gender']);
    $p_status = $conn->real_escape_string($_POST['patient_status_peserta']);
    
    // Informasi Medis
    $diag_init = $conn->real_escape_string($_POST['diagnosis_initial']);
    $icd10 = $conn->real_escape_string($_POST['icd10']);
    $med_notes = $conn->real_escape_string($_POST['medical_notes']);
    $therapy = $conn->real_escape_string($_POST['therapy_initial']);
    $doc_name = $conn->real_escape_string($_POST['doctor_name']);
    
    // Dates (Input User)
    $letter_date = $_POST['letter_date'] ?? date('Y-m-d');
    $exp_date = $_POST['expiry_date'] ?? date('Y-m-d', strtotime('+90 days'));
    $received_date = $_POST['received_date'] ?? date('Y-m-d');

    $sql = "INSERT INTO referrals (
        referral_id, patient_name, patient_wa, faskes_wa, origin_faskes, faskes_kab_kota, faskes_tingkat,
        faskes_alamat, faskes_telp, faskes_email,
        target_poli, target_kota, insurance_type, card_number, birth_date, gender, patient_status_peserta,
        diagnosis_initial, icd10, medical_notes, therapy_initial, doctor_name, letter_date, expiry_date, received_date, status_flow, nik
    ) VALUES (
        '$ref_id', '$p_name', '$p_wa', '$faskes_wa', '$origin_faskes', '$faskes_kab_kota', '$faskes_tingkat',
        '$faskes_alamat', '$faskes_telp', '$faskes_email',
        '$target_poli', '$target_kota', 'BPJS', '$card_num', '$p_birth', '$p_gender', '$p_status',
        '$diag_init', '$icd10', '$med_notes', '$therapy', '$doc_name', '$letter_date', '$exp_date', '$received_date', 'ENTRY', '$p_nik'
    )";

    if ($conn->query($sql)) {
        $msg = "success";
        
        // Simpan ke master data patients jika belum ada
        $check_patient = $conn->query("SELECT id FROM patients WHERE nik = '$p_nik' AND is_deleted = 0 LIMIT 1");
        if ($check_patient && $check_patient->num_rows === 0) {
            $genderLetter = (trim($p_gender) === 'Laki-laki') ? 'L' : 'P';
            $birthYear = !empty($p_birth) ? date('Y', strtotime($p_birth)) : '0000';
            while (true) {
                $randNum = rand(1000, 9999);
                $patient_id = "PSN-" . $genderLetter . "-" . $birthYear . "-" . $randNum;
                $dup_check = $conn->query("SELECT id FROM patients WHERE patient_id = '$patient_id'");
                if ($dup_check->num_rows == 0) {
                    break;
                }
            }
            $conn->query("INSERT INTO patients (patient_id, name, birth_date, gender, phone, insurance_type, card_number, nik) VALUES ('$patient_id', '$p_name', '$p_birth', '$p_gender', '$p_wa', 'BPJS', '$card_num', '$p_nik')");
        }
        
        $conn->query("INSERT INTO referral_logs (referral_id, stage, action_text, user_name, created_at) VALUES ('$ref_id', 'ENTRY', 'Dokumen rujukan baru dibuat', 'Admin Faskes', DATETIME('now', 'localtime'))");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran Rujukan - RSUD Maju Jaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-section { background: white; border-radius: 20px; padding: 32px; margin-bottom: 24px; border: 1px solid #f1f5f9; }
        .section-title { font-size: 16px; font-weight: 800; color: #1e293b; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: #6366f1; font-size: 20px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        label { display: block; font-size: 13px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
        input, select, textarea { width: 100%; padding: 14px; border-radius: 12px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px; transition: 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .btn-submit { background: #6366f1; color: white; border: none; padding: 18px 40px; border-radius: 14px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="app-container">
        <header class="navbar">
            <div class="navbar-left">
                <div class="logo" style="display: flex; align-items: center; gap: 10px;">
                    <img src="img/logo.png" alt="Logo" style="height: 38px; width: auto; object-fit: contain;">
                    <span style="font-weight: 800;">RSUD Maju Jaya</span>
                </div>
            </div>
            <div class="navbar-right">
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=Faskes+User&background=6C5CE7&color=fff&rounded=true">
                    <div class="user-info"><span class="user-name">Admin Faskes</span></div>
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
                                <li><a href="referral_explorer.php"><i class="ph ph-files"></i><span>Arsip Rujukan Digital</span></a></li>
                                <li><a href="referral_specialist.php"><i class="ph ph-stethoscope"></i><span>Penerimaan Rujukan Akhir</span></a></li>
                                <li><a href="referral_patients.php"><i class="ph ph-users"></i><span>Basis Data Pasien</span></a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </aside>

            <main class="content-area">
                <h1 class="page-title">Pendaftaran Rujukan Baru</h1>
                
                <?php if($msg == "success"): ?>
                    <div style="background: #f0fdf4; color: #166534; padding: 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700;">
                        Berhasil! Data rujukan telah masuk ke antrean.
                    </div>
                <?php endif; ?>

                <form action="referral_registration.php" method="POST">
                    <div class="form-section">
                        <h3 class="section-title"><i class="ph ph-hospital"></i> 1. Data Faskes & Tujuan</h3>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Nama Faskes Pengirim</label>
                                <input type="text" name="origin_faskes" required>
                            </div>
                            <div class="form-group">
                                <label>Kabupaten/Kota Faskes</label>
                                <input type="text" name="faskes_kab_kota" required>
                            </div>
                        </div>
                        <div class="grid-2" style="margin-top:20px;">
                            <div class="form-group">
                                <label>Tingkat Faskes</label>
                                <input type="text" name="faskes_tingkat" value="Puskesmas">
                            </div>
                            <div class="form-group">
                                <label>No. WA Faskes</label>
                                <input type="text" name="faskes_wa" required>
                            </div>
                        </div>
                        <div style="margin-top:20px;">
                            <label>Alamat Faskes Pengirim</label>
                            <input type="text" name="faskes_alamat" required placeholder="Contoh: Jl. Terusan Cikampek No. 10">
                        </div>
                        <div class="grid-2" style="margin-top:20px;">
                            <div class="form-group">
                                <label>No. Telp Faskes</label>
                                <input type="text" name="faskes_telp" required placeholder="Contoh: (0341) 551070">
                            </div>
                            <div class="form-group">
                                <label>Email Faskes</label>
                                <input type="email" name="faskes_email" required placeholder="Contoh: pusk.dinoyo@malangkota.go.id">
                            </div>
                        </div>
                        <div class="grid-2" style="margin-top:20px;">
                            <div class="form-group">
                                <label>Poli Spesialis Tujuan</label>
                                <input type="text" name="target_poli" value="Poli Jantung">
                            </div>
                            <div class="form-group">
                                <label>Kota RS Tujuan</label>
                                <input type="text" name="target_kota" value="Kota Malang">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="ph ph-user"></i> 2. Data Pasien</h3>
                        
                        <!-- Pencarian NIK -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
                            <label>NIK Pasien (Master Data)</label>
                            <div style="display: flex; gap: 12px;">
                                <input type="text" id="search_nik" name="nik" placeholder="Masukkan 16 digit NIK..." required style="flex: 1;">
                                <button type="button" onclick="performNikSearch()" style="background: #4f46e5; color: white; border: none; padding: 0 24px; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                    <i class="ph ph-magnifying-glass"></i> Cari NIK
                                </button>
                            </div>
                            <div id="search_status" style="margin-top: 10px; font-weight: 700; font-size: 13px; display: none;"></div>
                        </div>

                        <!-- Riwayat Kunjungan Pasien (Akan tampil dinamis) -->
                        <div id="patient_history_section" style="background: #fafafa; border-radius: 12px; border: 1px dashed #cbd5e1; padding: 20px; margin-bottom: 24px; display: none;">
                            <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 800; color: #334155; text-transform: uppercase; display: flex; align-items: center; gap: 8px;">
                                <i class="ph ph-clock-counter-clockwise" style="color: #6366f1;"></i> Riwayat Medis Pasien di RSUD
                            </h4>
                            <div id="history_list" style="display: flex; flex-direction: column; gap: 10px;"></div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>Nama Lengkap Pasien</label>
                                <input type="text" name="patient_name" required>
                            </div>
                            <div class="form-group">
                                <label>No. Kartu BPJS</label>
                                <input type="text" name="card_number" required>
                            </div>
                        </div>
                        <div class="grid-2" style="margin-top:20px;">
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" name="birth_date" required>
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="gender">
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-2" style="margin-top:20px;">
                            <div class="form-group">
                                <label>Status Peserta BPJS</label>
                                <input type="text" name="patient_status_peserta" value="PBI">
                            </div>
                            <div class="form-group">
                                <label>No. WA Pasien</label>
                                <input type="text" name="patient_wa">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="ph ph-first-aid"></i> 3. Informasi Medis & Tanggal</h3>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Diagnosa Awal</label>
                                <input type="text" name="diagnosis_initial" required>
                            </div>
                            <div class="form-group">
                                <label>Kode ICD-10</label>
                                <input type="text" name="icd10" required>
                            </div>
                        </div>
                        <div style="margin-top:20px;">
                            <label>Catatan Medis</label>
                            <textarea name="medical_notes" rows="2"></textarea>
                        </div>
                        <div style="margin-top:20px;">
                            <label>Terapi Diberikan</label>
                            <textarea name="therapy_initial" rows="2"></textarea>
                        </div>
                        <div class="grid-2" style="margin-top:20px;">
                            <div class="form-group">
                                <label>Dokter Pemeriksa</label>
                                <input type="text" name="doctor_name" required>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Masuk RS</label>
                                <input type="date" name="received_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="grid-2" style="margin-top:20px;">
                            <div class="form-group">
                                <label>Tanggal Surat Dibuat</label>
                                <input type="date" name="letter_date" id="lDate" value="<?= date('Y-m-d') ?>" required onchange="calcExp()">
                            </div>
                            <div class="form-group">
                                <label>Masa Berlaku (Expired)</label>
                                <input type="date" name="expiry_date" id="eDate" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Simpan Data Rujukan</button>
                </form>
            </main>
        </div>
    </div>
    <script>
        function calcExp() {
            const lDate = new Date(document.getElementById('lDate').value);
            if(!isNaN(lDate)) {
                lDate.setDate(lDate.getDate() + 90);
                document.getElementById('eDate').value = lDate.toISOString().split('T')[0];
            }
        }
        window.onload = calcExp;

        function performNikSearch() {
            const nik = document.getElementById('search_nik').value.trim();
            if (!nik) {
                alert('Silakan masukkan NIK terlebih dahulu.');
                return;
            }
            
            const statusDiv = document.getElementById('search_status');
            statusDiv.style.display = 'block';
            statusDiv.style.color = '#64748b';
            statusDiv.textContent = 'Mencari data...';
            
            fetch(`referral_registration.php?action=search_nik&nik=${encodeURIComponent(nik)}`)
                .then(res => res.json())
                .then(data => {
                    const historySection = document.getElementById('patient_history_section');
                    const historyList = document.getElementById('history_list');
                    
                    if (data.found) {
                        statusDiv.style.color = '#16a34a';
                        statusDiv.textContent = '✓ Pasien Terdaftar (Data terisi otomatis)';
                        
                        // Populate fields
                        document.querySelector('input[name="patient_name"]').value = data.patient.name || '';
                        document.querySelector('input[name="card_number"]').value = data.patient.card_number || '';
                        document.querySelector('input[name="birth_date"]').value = data.patient.birth_date || '';
                        document.querySelector('select[name="gender"]').value = data.patient.gender || 'Laki-laki';
                        document.querySelector('input[name="patient_status_peserta"]').value = data.patient.insurance_type === 'BPJS' ? 'PBI' : (data.patient.insurance_type || '');
                        document.querySelector('input[name="patient_wa"]').value = data.patient.phone || '';
                        
                        // Populate History
                        historyList.innerHTML = '';
                        if (data.history && data.history.length > 0) {
                            data.history.forEach(item => {
                                const div = document.createElement('div');
                                div.style.background = 'white';
                                div.style.padding = '12px';
                                div.style.borderRadius = '8px';
                                div.style.border = '1px solid #e2e8f0';
                                div.style.fontSize = '13px';
                                
                                let statusBadge = '';
                                if (item.status_flow === 'ENTRY') statusBadge = '<span style="background:#fef3c7; color:#d97706; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px;">MASUK</span>';
                                else if (item.status_flow === 'VERIFY') statusBadge = '<span style="background:#e0e7ff; color:#4f46e5; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px;">VERIFIKASI</span>';
                                else if (item.status_flow === 'REPLIED') statusBadge = '<span style="background:#d1fae5; color:#059669; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px;">DIJAWAB</span>';
                                
                                div.innerHTML = `
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                        <strong>ID: ${item.referral_id}</strong>
                                        ${statusBadge}
                                    </div>
                                    <div style="color:#475569;">Poli Tujuan: <strong>${item.target_poli}</strong></div>
                                    <div style="color:#64748b; font-size:12px; margin-top:2px;">Diagnosa Awal: ${item.diagnosis_initial}</div>
                                    <div style="color:#94a3b8; font-size:11px; margin-top:4px;">Tanggal: ${item.created_at}</div>
                                `;
                                historyList.appendChild(div);
                            });
                            historySection.style.display = 'block';
                        } else {
                            historyList.innerHTML = '<p style="color:#64748b; font-size:13px; margin:0;">Tidak ada riwayat rujukan/pemeriksaan sebelumnya.</p>';
                            historySection.style.display = 'block';
                        }
                    } else {
                        statusDiv.style.color = '#2563eb';
                        statusDiv.textContent = 'ℹ Pasien Baru (Silakan masukkan data baru)';
                        
                        // Clear fields for new input
                        document.querySelector('input[name="patient_name"]').value = '';
                        document.querySelector('input[name="card_number"]').value = '';
                        document.querySelector('input[name="birth_date"]').value = '';
                        document.querySelector('select[name="gender"]').value = 'Laki-laki';
                        document.querySelector('input[name="patient_status_peserta"]').value = 'PBI';
                        document.querySelector('input[name="patient_wa"]').value = '';
                        
                        historyList.innerHTML = '';
                        historySection.style.display = 'none';
                    }
                })
                .catch(err => {
                    console.error(err);
                    statusDiv.style.color = '#dc2626';
                    statusDiv.textContent = '⚠️ Gagal memuat data.';
                });
        }
    </script>
</body>
</html>
