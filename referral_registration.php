<?php
/**
 * Registrasi Rujukan Baru - Referral System (Final Template Sync)
 * RSUD Maju Jaya
 */
require_once 'config.php';

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate ID
    $ref_id = "RUJ-" . rand(1000, 9999);
    
    // Data Faskes
    $origin_faskes = $conn->real_escape_string($_POST['origin_faskes']);
    $faskes_kab_kota = $conn->real_escape_string($_POST['faskes_kab_kota']);
    $faskes_tingkat = $conn->real_escape_string($_POST['faskes_tingkat']);
    $faskes_wa = $conn->real_escape_string($_POST['faskes_wa']);
    
    // Tujuan
    $target_poli = $conn->real_escape_string($_POST['target_poli']);
    $target_kota = $conn->real_escape_string($_POST['target_kota']);
    
    // Data Pasien
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
        target_poli, target_kota, insurance_type, card_number, birth_date, gender, patient_status_peserta,
        diagnosis_initial, icd10, medical_notes, therapy_initial, doctor_name, letter_date, expiry_date, received_date, status_flow
    ) VALUES (
        '$ref_id', '$p_name', '$p_wa', '$faskes_wa', '$origin_faskes', '$faskes_kab_kota', '$faskes_tingkat',
        '$target_poli', '$target_kota', 'BPJS', '$card_num', '$p_birth', '$p_gender', '$p_status',
        '$diag_init', '$icd10', '$med_notes', '$therapy', '$doc_name', '$letter_date', '$exp_date', '$received_date', 'ENTRY'
    )";

    if ($conn->query($sql)) {
        $msg = "success";
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
                <div class="logo">
                    <div class="logo-icon"><i class="ph ph-hospital"></i></div>
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
                                <li><a href="referral_specialist.php"><i class="ph ph-stethoscope"></i><span>Layanan Poli Spesialis</span></a></li>
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
    </script>
</body>
</html>
