<?php
/**
 * Verifikasi Berkas Masuk - Referral System (Official Template Sync)
 */
require_once 'config.php';

// Logic Approve
if (isset($_GET['approve_id'])) {
    $id = $conn->real_escape_string($_GET['approve_id']);
    $conn->query("UPDATE referrals SET status_flow = 'VERIFY' WHERE referral_id = '$id'");
    $conn->query("INSERT INTO referral_logs (referral_id, stage, action_text, user_name, created_at) VALUES ('$id', 'VERIFIED', 'Dokumen disetujui & diteruskan ke Poli', 'Verifikator', DATETIME('now', 'localtime'))");
    header("Location: referral_verification.php");
    exit;
}

$sql_queue = "SELECT * FROM referrals WHERE status_flow = 'ENTRY' ORDER BY created_at DESC";
$result = $conn->query($sql_queue);
$docs_js = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { 
        // Hitung umur sederhana
        $bday = new DateTime($row['birth_date']);
        $today = new DateTime();
        $row['age'] = $today->diff($bday)->y;
        $docs_js[] = $row; 
    }
    $result->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Berkas - RSUD Maju Jaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        .modal { position: fixed; top: 0; left: 0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(8px); display:flex; align-items:center; justify-content:center; opacity:0; visibility:hidden; transition:0.3s; z-index:9999; }
        .modal.active { opacity:1; visibility:visible; }
        .modal-layout { background:white; border-radius:24px; display:flex; width:95%; height:95vh; overflow:hidden; }
        
        /* A4 Template Styles */
        .preview-container { flex:1; background:#f1f5f9; padding:40px; overflow-y:auto; display:flex; justify-content:center; }
        .paper-a4 { 
            background:white; 
            width:210mm; 
            min-height:297mm; 
            padding:20mm; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            font-family:'Inter', sans-serif;
            color: black;
            line-height: 1.4;
            font-size: 13px;
        }
        .kop-surat { text-align:center; border-bottom: 2px solid black; padding-bottom: 5px; margin-bottom: 15px; }
        .kop-surat h2 { margin:0; font-size: 16px; font-weight: 800; }
        .kop-surat p { margin:2px 0; font-size: 11px; }
        
        .doc-title { text-align:center; margin-bottom: 20px; }
        .doc-title h1 { text-decoration: underline; margin:0; font-size: 18px; font-weight: 800; }
        
        .section-header { font-weight: 800; text-decoration: underline; color: #1e40af; margin-bottom: 10px; font-size: 13px; text-transform: uppercase; }
        .data-table { width:100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table td { padding: 4px 0; vertical-align: top; }
        
        .info-box { border: 1px solid black; padding: 15px; min-height: 80px; margin-bottom: 20px; font-style: italic; color: #333; }
        
        .footer-table { width:100%; margin-top: 40px; }
        .footer-table td { text-align: center; width: 50%; }
        
        .sidebar-action { width:350px; padding:32px; background:white; border-left:1px solid #e2e8f0; display:flex; flex-direction:column; }
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
                    <img src="https://ui-avatars.com/api/?name=Admin+Verif&background=6C5CE7&color=fff&rounded=true">
                    <div class="user-info"><span class="user-name">Staff Verifikator</span></div>
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
                                <li class="active"><a href="referral_verification.php"><i class="ph ph-check-square-offset"></i><span>Verifikasi Berkas Masuk</span></a></li>
                                <li><a href="referral_explorer.php"><i class="ph ph-files"></i><span>Arsip Rujukan Digital</span></a></li>
                                <li><a href="referral_specialist.php"><i class="ph ph-stethoscope"></i><span>Layanan Poli Spesialis</span></a></li>
                                <li><a href="referral_patients.php"><i class="ph ph-users"></i><span>Basis Data Pasien</span></a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </aside>

            <main class="content-area">
                <h1 class="page-title">Verifikasi Berkas Masuk</h1>
                <p class="page-description">Tinjau kesesuaian data input dengan berkas rujukan fisik sebelum diteruskan ke Poli.</p>

                <div class="card" style="border-radius:20px; overflow:hidden;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; font-size:12px; color:#64748b;">
                                <th style="padding:16px; text-align:left;">PASIEN</th>
                                <th style="padding:16px; text-align:left;">FASKES ASAL</th>
                                <th style="padding:16px; text-align:right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr style="border-bottom:1px solid #f1f5f9;">
                                        <td style="padding:16px;">
                                            <div style="font-weight:800; color:#1e293b;"><?= $row['patient_name'] ?></div>
                                            <div style="font-size:11px; color:#94a3b8;"><?= $row['referral_id'] ?></div>
                                        </td>
                                        <td style="padding:16px; font-weight:700; color:#6366f1;"><?= $row['origin_faskes'] ?></td>
                                        <td style="padding:16px; text-align:right;">
                                            <button onclick="openVerif('<?= $row['referral_id'] ?>')" style="padding:10px 20px; background:#6366f1; color:white; border:none; border-radius:10px; cursor:pointer; font-weight:700;">Tinjau Berkas</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="padding:50px; text-align:center; color:#94a3b8;">Antrean bersih.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Review (Official Template) -->
    <div class="modal" id="modalVerif" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal-layout">
            <div class="preview-container">
                <div class="paper-a4">
                    <!-- KOP SURAT -->
                    <div class="kop-surat">
                        <p style="text-transform: uppercase;">PEMERINTAH KABUPATEN/KOTA <span id="vKabKotaHead">-</span></p>
                        <p style="font-weight: 700;">DINAS KESEHATAN</p>
                        <h2 style="text-transform: uppercase;">[<span id="vFaskesHead">-</span>]</h2>
                        <p>Alamat: [Alamat Faskes] | Telp: [No. Telp] | Email: [Email Faskes]</p>
                    </div>

                    <!-- JUDUL SURAT -->
                    <div class="doc-title">
                        <h1>SURAT RUJUKAN PASIEN</h1>
                        <p style="font-size: 12px; margin-top: 5px;">Nomor Rujukan: <strong id="vFullId">-</strong></p>
                    </div>

                    <!-- SECTION 1: HEADER INFO -->
                    <div style="display: flex; gap: 40px; border-bottom: 1px solid black; padding-bottom: 10px; margin-bottom: 20px;">
                        <div style="flex: 1;">
                            <div class="section-header">Dari Fasilitas Kesehatan</div>
                            <table class="data-table">
                                <tr><td width="100">Nama Faskes</td><td>: <strong id="vFaskes">-</strong></td></tr>
                                <tr><td>Kab/Kota</td><td>: <span id="vKabKota">-</span></td></tr>
                                <tr><td>Tingkat</td><td>: <span id="vTingkat">-</span></td></tr>
                            </table>
                        </div>
                        <div style="flex: 1;">
                            <div class="section-header">Ditujukan Kepada</div>
                            <table class="data-table">
                                <tr><td width="100">Nama RS</td><td>: <strong>RSUD Maju Jaya</strong></td></tr>
                                <tr><td>Bagian/Poli</td><td>: <strong id="vPoli">-</strong></td></tr>
                                <tr><td>Kota</td><td>: <span id="vTargetKota">-</span></td></tr>
                            </table>
                        </div>
                    </div>

                    <!-- SECTION 2: DATA PASIEN -->
                    <div class="section-header">DATA PASIEN</div>
                    <table class="data-table">
                        <tr><td width="150">Nama Pasien</td><td>: <strong id="vName">-</strong></td></tr>
                        <tr><td>No. Kartu BPJS</td><td>: <span id="vCard">-</span></td></tr>
                        <tr><td>Tanggal Lahir</td><td>: <span id="vBirth">-</span> (Umur: <span id="vAge">-</span> Tahun)</td></tr>
                        <tr><td>Jenis Kelamin</td><td>: <span id="vGender">-</span></td></tr>
                        <tr><td>Status Peserta</td><td>: <strong id="vStatus">-</strong></td></tr>
                    </table>
                    <div style="border-bottom: 1px solid black; margin-bottom: 20px;"></div>

                    <!-- SECTION 3: INFORMASI MEDIS -->
                    <div class="section-header">INFORMASI MEDIS</div>
                    <table class="data-table">
                        <tr><td width="150">Diagnosa Awal</td><td>: <strong id="vDiag">-</strong></td></tr>
                        <tr><td>Kode ICD-10</td><td>: <span id="vIcd">-</span></td></tr>
                        <tr><td>Asal Diagnosa</td><td>: <span id="vFaskesDiag">-</span></td></tr>
                    </table>

                    <div style="font-weight: 700; margin-bottom: 5px;">Catatan dari Faskes Pengirim:</div>
                    <div class="info-box" id="vNotes">-</div>

                    <div style="font-weight: 700; margin-bottom: 5px;">Terapi / Tindakan yang Telah Diberikan:</div>
                    <div class="info-box" id="vTherapy">-</div>

                    <!-- DATES & SIGNATURE -->
                    <div style="border-bottom: 1px solid black; margin-bottom: 20px;"></div>
                    <table class="data-table">
                        <tr><td width="150">Tanggal Surat Dibuat</td><td>: <span id="vLetterDate">-</span></td></tr>
                        <tr><td>Berlaku s.d (Expired)</td><td>: <strong id="vExpDate">-</strong></td></tr>
                    </table>

                    <div style="margin-top: 30px; text-align: right; padding-right: 50px;">
                        <p><span id="vKabKotaSign">-</span>, <span id="vLetterDateSign">-</span></p>
                        <p style="margin-bottom: 60px;">Dokter Pemeriksa,</p>
                        <p><strong style="text-decoration: underline;">( <span id="vDocName">-</span> )</strong></p>
                        <p style="font-size: 11px; color: #64748b;">NIP/SIP: .................................</p>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-action">
                <h3 style="font-weight: 800; margin-bottom: 12px; font-size: 20px;">Validasi Berkas</h3>
                <p style="font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 40px;">Pastikan data yang muncul di preview sudah sesuai dengan dokumen fisik pasien sebelum diteruskan ke Poli Spesialis.</p>
                
                <div style="flex: 1;"></div>

                <a id="btnApprove" href="#" style="display:block; width:100%; padding:18px; background:#6366f1; color:white; border-radius:14px; text-decoration:none; text-align:center; font-weight:800; box-shadow: 0 10px 20px rgba(99,102,241,0.2);">Setujui & Teruskan</a>
                <button onclick="document.getElementById('modalVerif').classList.remove('active')" style="display:block; width:100%; padding:15px; border:none; background:none; color:#94a3b8; font-weight:700; margin-top:15px; cursor:pointer;">Kembali</button>
            </div>
        </div>
    </div>

    <script>
        const allDocs = <?= json_encode($docs_js) ?>;
        function openVerif(id) {
            const d = allDocs.find(x => x.referral_id === id);
            if(!d) return;
            
            // Header
            document.getElementById('vKabKotaHead').textContent = d.faskes_kab_kota;
            document.getElementById('vFaskesHead').textContent = d.origin_faskes;
            document.getElementById('vFullId').textContent = d.referral_id + "/BPJS/" + new Date().getFullYear();
            
            // Section 1
            document.getElementById('vFaskes').textContent = d.origin_faskes;
            document.getElementById('vKabKota').textContent = d.faskes_kab_kota;
            document.getElementById('vTingkat').textContent = d.faskes_tingkat;
            document.getElementById('vPoli').textContent = d.target_poli;
            document.getElementById('vTargetKota').textContent = d.target_kota;
            
            // Section 2
            document.getElementById('vName').textContent = d.patient_name;
            document.getElementById('vCard').textContent = d.card_number;
            document.getElementById('vBirth').textContent = d.birth_date;
            document.getElementById('vAge').textContent = d.age;
            document.getElementById('vGender').textContent = d.gender;
            document.getElementById('vStatus').textContent = d.patient_status_peserta;
            
            // Section 3
            document.getElementById('vDiag').textContent = d.diagnosis_initial;
            document.getElementById('vIcd').textContent = d.icd10;
            document.getElementById('vFaskesDiag').textContent = d.origin_faskes;
            document.getElementById('vNotes').textContent = d.medical_notes || "(Tanpa catatan)";
            document.getElementById('vTherapy').textContent = d.therapy_initial || "(Tanpa terapi)";
            
            // Footer
            document.getElementById('vLetterDate').textContent = d.letter_date;
            document.getElementById('vExpDate').textContent = d.expiry_date;
            document.getElementById('vKabKotaSign').textContent = d.faskes_kab_kota;
            document.getElementById('vLetterDateSign').textContent = d.letter_date;
            document.getElementById('vDocName').textContent = d.doctor_name;

            document.getElementById('btnApprove').href = "referral_verification.php?approve_id=" + id;
            document.getElementById('modalVerif').classList.add('active');
        }
    </script>
</body>
</html>
