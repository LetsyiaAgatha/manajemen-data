<?php
/**
 * Arsip & Pelacakan Rujukan - Referral System (Full Traceability)
 * RSUD Maju Jaya
 */
require_once 'config.php';

// 1. Logic Soft Delete
if (isset($_GET['delete_id'])) {
    $did = $conn->real_escape_string($_GET['delete_id']);
    
    // Set flag is_deleted = 1
    $conn->query("UPDATE referrals SET is_deleted = 1 WHERE referral_id = '$did'");
    
    // Tulis ke logs
    $conn->query("INSERT INTO referral_logs (referral_id, stage, action_text, user_name, created_at) 
                  VALUES ('$did', 'CANCELLED', 'Rujukan dibatalkan oleh Staff Arsiparis', 'Staff Arsiparis', DATETIME('now', 'localtime'))");
    
    header("Location: referral_explorer.php?msg=deleted");
    exit;
}

// 2. Logic Update Rujukan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_referral') {
    $ref_id = $conn->real_escape_string($_POST['referral_id']);
    $p_name = $conn->real_escape_string($_POST['patient_name']);
    $card_num = $conn->real_escape_string($_POST['card_number']);
    $p_birth = $conn->real_escape_string($_POST['birth_date']);
    $p_gender = $conn->real_escape_string($_POST['gender']);
    $p_status = $conn->real_escape_string($_POST['patient_status_peserta']);
    $p_wa = $conn->real_escape_string($_POST['patient_wa']);
    
    $origin_faskes = $conn->real_escape_string($_POST['origin_faskes']);
    $faskes_alamat = $conn->real_escape_string($_POST['faskes_alamat']);
    $faskes_telp = $conn->real_escape_string($_POST['faskes_telp']);
    $faskes_email = $conn->real_escape_string($_POST['faskes_email']);
    $diag_init = $conn->real_escape_string($_POST['diagnosis_initial']);
    $icd10 = $conn->real_escape_string($_POST['icd10']);
    $med_notes = $conn->real_escape_string($_POST['medical_notes']);
    $therapy = $conn->real_escape_string($_POST['therapy_initial']);
    $doc_name = $conn->real_escape_string($_POST['doctor_name']);

    $sql_update = "UPDATE referrals SET 
        patient_name = '$p_name', 
        card_number = '$card_num', 
        birth_date = '$p_birth', 
        gender = '$p_gender', 
        patient_status_peserta = '$p_status',
        patient_wa = '$p_wa',
        origin_faskes = '$origin_faskes',
        faskes_alamat = '$faskes_alamat',
        faskes_telp = '$faskes_telp',
        faskes_email = '$faskes_email',
        diagnosis_initial = '$diag_init',
        icd10 = '$icd10',
        medical_notes = '$med_notes',
        therapy_initial = '$therapy',
        doctor_name = '$doc_name'
        WHERE referral_id = '$ref_id'";
        
    if ($conn->query($sql_update)) {
        // Tulis ke logs
        $conn->query("INSERT INTO referral_logs (referral_id, stage, action_text, user_name, created_at) 
                      VALUES ('$ref_id', 'EDITED', 'Data rujukan diperbarui oleh Staff Arsiparis', 'Staff Arsiparis', DATETIME('now', 'localtime'))");
        header("Location: referral_explorer.php?msg=updated");
        exit;
    }
}

// 3. Ambil data logs untuk detail tracking
$all_logs = [];
$log_query = $conn->query("SELECT * FROM referral_logs ORDER BY created_at ASC");
while($l = $log_query->fetch_assoc()) {
    $all_logs[$l['referral_id']][] = $l;
}

// 4. Ambil semua rujukan (Hanya yang is_deleted = 0)
$sql_docs = "SELECT * FROM referrals WHERE is_deleted = 0 ORDER BY created_at DESC";
$result = $conn->query($sql_docs);

$docs_js = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if (!empty($row['birth_date'])) {
            $today = new DateTime();
            $bday = new DateTime($row['birth_date']);
            $row['age'] = $today->diff($bday)->y;
        } else {
            $row['age'] = '-';
        }
        $docs_js[] = $row;
    }
    $result->data_seek(0);
}
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

        /* Form styling for Edit Modal */
        .form-label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 12px; color: #475569; text-transform: uppercase; }
        .form-input { width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px; margin-bottom: 16px; }
        .form-input:focus { border-color: #6366f1; outline: none; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        /* Preview A4 Styles */
        .modal-layout-preview { width: 95% !important; height: 95vh !important; display: flex !important; overflow: hidden !important; padding: 0 !important; }
        .preview-container { 
            flex:1; 
            background:#f1f5f9; 
            padding:40px; 
            overflow-y:auto; 
            display:flex; 
            justify-content:center; 
            align-items: flex-start; 
            min-height: 0;
            height: 100%;
        }
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
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .paper-a4::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            width: 320px;
            height: 320px;
            background: url('img/logo.png') no-repeat center;
            background-size: contain;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
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
        .sidebar-action { width:350px; padding:32px; background:white; border-left:1px solid #e2e8f0; display:flex; flex-direction:column; }

        @media print {
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .app-container, .modal, .modal-layout, .sidebar-action, button, a { display: none !important; }
            .modal.active { position: static !important; display: block !important; visibility: visible !important; opacity: 1 !important; background: none !important; backdrop-filter: none !important; height: auto !important; width: auto !important; overflow: visible !important; }
            .modal-layout { display: block !important; border-radius: 0 !important; padding: 0 !important; max-height: none !important; overflow: visible !important; width: 100% !important; height: auto !important; box-shadow: none !important; }
            .preview-container { display: block !important; padding: 0 !important; background: none !important; overflow: visible !important; height: auto !important; }
            .paper-a4 { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; min-height: 0 !important; }
        }
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
                <p class="page-description">Pantau jejak digital, lakukan koreksi administrasi, dan pembatalan rujukan secara real-time.</p>

                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                    <div style="background: #fef2f2; color: #ef4444; padding: 16px; border-radius: 12px; margin-top: 20px; font-weight: 700; font-size: 14px; border: 1px solid #fee2e2;">
                        Dokumen rujukan berhasil dibatalkan dan dipindahkan ke riwayat log pembatalan.
                    </div>
                <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                    <div style="background: #ecfdf5; color: #10b981; padding: 16px; border-radius: 12px; margin-top: 20px; font-weight: 700; font-size: 14px; border: 1px solid #d1fae5;">
                        Perubahan rujukan berhasil disimpan dan dicatat ke audit trail.
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-top: 32px;">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $status = $row['status_flow'];
                            $badge_color = ($status == 'REPLIED') ? '#10b981' : (($status == 'VERIFY') ? '#f59e0b' : '#6366f1');
                            $status_text = ($status == 'REPLIED') ? 'SELESAI (ARSIP)' : (($status == 'VERIFY') ? 'DI POLI SPESIALIS' : 'PROSES VERIFIKASI');
                        ?>
                            <div class="card" style="padding: 24px; border-radius: 20px; display: flex; flex-direction: column; justify-content: space-between; min-height: 250px;">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <span style="font-size: 10px; font-weight: 800; color: white; background: <?= $badge_color ?>; padding: 4px 10px; border-radius: 6px;"><?= $status_text ?></span>
                                        <span style="font-size: 11px; color: #94a3b8; font-family: monospace; font-weight: 700;"><?= $row['referral_id'] ?></span>
                                    </div>
                                    <h3 style="font-weight: 800; font-size: 16px; margin-bottom: 4px; color: #1e293b;"><?= $row['patient_name'] ?></h3>
                                    
                                    <div style="background: #f8fafc; padding: 12px; border-radius: 10px; font-size: 12px; margin-bottom: 20px; margin-top: 10px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span style="color: #64748b;">Faskes:</span>
                                            <span style="font-weight: 700; color: #334155;"><?= $row['origin_faskes'] ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #64748b;">Diagnosa:</span>
                                            <span style="font-weight: 700; color: #334155;"><?= $row['icd10'] ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 8px;">
                                    <button onclick="openPreviewModal('<?= $row['referral_id'] ?>')" style="flex: 1.2; padding: 10px; background: #ecfdf5; color: #10b981; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px;" title="Pratinjau Surat Rujukan">
                                        <i class="ph ph-eye"></i> Preview
                                    </button>
                                    <button onclick="traceDocument('<?= $row['referral_id'] ?>', '<?= $row['patient_name'] ?>')" style="flex: 1; padding: 10px; background: #6366f1; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px;">
                                        <i class="ph ph-magnifying-glass"></i> Lacak
                                    </button>
                                    <button onclick="openEditModal('<?= $row['referral_id'] ?>')" style="padding: 10px; background: #f1f5f9; color: #475569; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Koreksi Data Rujukan">
                                        <i class="ph ph-pencil-simple"></i>
                                    </button>
                                    <a href="referral_explorer.php?delete_id=<?= $row['referral_id'] ?>" onclick="return confirm('Apakah Anda yakin ingin membatalkan rujukan ini? Pembatalan akan dicatat ke dalam audit trail.')" style="padding: 10px; background: #fef2f2; color: #ef4444; border: none; border-radius: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; text-decoration: none;" title="Batalkan Rujukan">
                                        <i class="ph ph-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: #cbd5e1; font-weight: 500;">Belum ada arsip rujukan terdaftar.</div>
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

    <!-- Modal Edit Referral -->
    <div class="modal" id="modalEditReferral" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal-layout" style="width: 650px;">
            <button onclick="document.getElementById('modalEditReferral').classList.remove('active')" style="position: absolute; top: 24px; right: 24px; border:none; background:#f1f5f9; width:44px; height:44px; border-radius:50%; cursor:pointer;"><i class="ph ph-x"></i></button>
            <h2 style="font-weight: 800; margin-bottom: 20px;">Koreksi Dokumen Rujukan</h2>
            
            <form action="referral_explorer.php" method="POST">
                <input type="hidden" name="action" value="edit_referral">
                <input type="hidden" name="referral_id" id="edit_ref_id">
                
                <h3 style="font-size: 13px; font-weight: 800; color: #6366f1; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 16px;">1. DATA PASIEN & DEMOGRAFI</h3>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Pasien</label>
                        <input type="text" name="patient_name" id="edit_patient_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Kartu BPJS</label>
                        <input type="text" name="card_number" id="edit_card_number" class="form-input" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="birth_date" id="edit_birth_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" id="edit_gender" class="form-input" style="background:#fff;">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Status Peserta BPJS</label>
                        <input type="text" name="patient_status_peserta" id="edit_patient_status_peserta" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. WA Pasien</label>
                        <input type="text" name="patient_wa" id="edit_patient_wa" class="form-input">
                    </div>
                </div>

                <h3 style="font-size: 13px; font-weight: 800; color: #6366f1; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 16px; margin-top: 10px;">2. INFORMASI MEDIS</h3>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nama Faskes Pengirim</label>
                        <input type="text" name="origin_faskes" id="edit_origin_faskes" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dokter Pemeriksa</label>
                        <input type="text" name="doctor_name" id="edit_doctor_name" class="form-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Faskes Pengirim</label>
                    <input type="text" name="faskes_alamat" id="edit_faskes_alamat" class="form-input" required>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">No. Telp Faskes</label>
                        <input type="text" name="faskes_telp" id="edit_faskes_telp" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Faskes</label>
                        <input type="email" name="faskes_email" id="edit_faskes_email" class="form-input" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Diagnosa Awal</label>
                        <input type="text" name="diagnosis_initial" id="edit_diagnosis_initial" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode ICD-10</label>
                        <input type="text" name="icd10" id="edit_icd10" class="form-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Medis</label>
                    <textarea name="medical_notes" id="edit_medical_notes" class="form-input" rows="2" style="resize:vertical;"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Terapi Diberikan</label>
                    <textarea name="therapy_initial" id="edit_therapy_initial" class="form-input" rows="2" style="resize:vertical;"></textarea>
                </div>

                <button type="submit" style="width: 100%; padding: 16px; background: #6366f1; color: white; border: none; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;">
                    <i class="ph ph-floppy-disk"></i> Simpan Perubahan & Catat Log
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Preview (Official A4 Read-only Template) -->
    <div class="modal" id="modalPreview" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal-layout modal-layout-preview">
            <div class="preview-container">
                <div class="paper-a4" id="printArea">
                    <!-- KOP SURAT -->
                    <div class="kop-surat">
                        <p style="text-transform: uppercase;">PEMERINTAH KABUPATEN/KOTA <span id="vKabKotaHead">-</span></p>
                        <p style="font-weight: 700;">DINAS KESEHATAN</p>
                        <h2 style="text-transform: uppercase;">[<span id="vFaskesHead">-</span>]</h2>
                        <p>Alamat: <span id="vFaskesAlamat">-</span> | Telp: <span id="vFaskesTelp">-</span> | Email: <span id="vFaskesEmail">-</span></p>
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
                    <table class="data-table" style="margin-bottom: 15px;">
                        <tr><td width="150">Tanggal Surat Dibuat</td><td>: <span id="vLetterDate">-</span></td></tr>
                        <tr><td>Berlaku s.d (Expired)</td><td>: <strong id="vExpDate">-</strong></td></tr>
                    </table>

                    <div style="display: flex; justify-content: flex-end; margin-top: 20px; margin-bottom: 40px;">
                        <div style="text-align: center; width: 250px; font-size: 13px; line-height: 1.5;">
                            <p style="margin: 0;"><span id="vKabKotaSign">-</span>, <span id="vLetterDateSign">-</span></p>
                            <p style="margin: 5px 0 65px 0; font-weight: 500;">Dokter Pemeriksa,</p>
                            <p style="margin: 0; font-weight: 700; text-decoration: underline;">( <span id="vDocName">-</span> )</p>
                            <p style="margin: 3px 0 0 0; font-size: 11px; color: #64748b;">NIP/SIP: .................................</p>
                        </div>
                    </div>

                    <!-- FOOTER HALAMAN -->
                    <div style="margin-top: auto; padding-top: 15px; display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #cbd5e1; font-size: 11px; color: #64748b; font-family: 'Inter', sans-serif;">
                        <span>Dokumen Rujukan Digital JKN/BPJS - DMS Hospital</span>
                        <span style="font-weight: 600;">Halaman 1 dari 1</span>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-action">
                <h3 style="font-weight: 800; margin-bottom: 12px; font-size: 20px;">Pratinjau Surat</h3>
                <p style="font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 40px;">Dokumen ini merupakan arsip resmi rujukan digital pasien yang tersimpan di sistem Panda DMS.</p>
                
                <div style="flex: 1;"></div>

                <button onclick="window.print()" style="display:block; width:100%; padding:18px; background:#10b981; color:white; border:none; border-radius:14px; text-decoration:none; text-align:center; font-weight:800; font-size:15px; cursor:pointer; box-shadow: 0 10px 20px rgba(16,185,129,0.2);"><i class="ph ph-printer" style="vertical-align:middle; margin-right:6px;"></i> Cetak Dokumen</button>
                <button onclick="document.getElementById('modalPreview').classList.remove('active')" style="display:block; width:100%; padding:15px; border:none; background:none; color:#94a3b8; font-weight:700; margin-top:15px; cursor:pointer;">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        const logData = <?= json_encode($all_logs) ?>;
        const allDocs = <?= json_encode($docs_js) ?>;
        
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
                            <div class="timeline-time">${log.created_at ? new Date(log.created_at.replace(' ', 'T')).toLocaleString('id-ID') : '-'}</div>
                            <div style="font-weight: 800; margin: 4px 0; font-size: 14px; color: #1e293b;">${log.action_text}</div>
                            <div class="timeline-author"><i class="ph ph-user-circle"></i> Ditangani Oleh: ${log.user_name}</div>
                        </div>
                    `;
                    timeline.appendChild(item);
                });
            }
            
            document.getElementById('modalTrace').classList.add('active');
        }

        function openEditModal(id) {
            const doc = allDocs.find(x => x.referral_id === id);
            if (!doc) return;
            
            // Populating fields
            document.getElementById('edit_ref_id').value = doc.referral_id;
            document.getElementById('edit_patient_name').value = doc.patient_name;
            document.getElementById('edit_card_number').value = doc.card_number;
            document.getElementById('edit_birth_date').value = doc.birth_date;
            document.getElementById('edit_gender').value = doc.gender;
            document.getElementById('edit_patient_status_peserta').value = doc.patient_status_peserta;
            document.getElementById('edit_patient_wa').value = doc.patient_wa || '';
            document.getElementById('edit_origin_faskes').value = doc.origin_faskes;
            document.getElementById('edit_faskes_alamat').value = doc.faskes_alamat || '';
            document.getElementById('edit_faskes_telp').value = doc.faskes_telp || '';
            document.getElementById('edit_faskes_email').value = doc.faskes_email || '';
            document.getElementById('edit_doctor_name').value = doc.doctor_name;
            document.getElementById('edit_diagnosis_initial').value = doc.diagnosis_initial;
            document.getElementById('edit_icd10').value = doc.icd10;
            document.getElementById('edit_medical_notes').value = doc.medical_notes || '';
            document.getElementById('edit_therapy_initial').value = doc.therapy_initial || '';

            document.getElementById('modalEditReferral').classList.add('active');
        }

        function openPreviewModal(id) {
            const d = allDocs.find(x => x.referral_id === id);
            if(!d) return;
            
            // Header
            document.getElementById('vKabKotaHead').textContent = d.faskes_kab_kota;
            document.getElementById('vFaskesHead').textContent = d.origin_faskes;
            document.getElementById('vFaskesAlamat').textContent = d.faskes_alamat || '[Alamat Faskes]';
            document.getElementById('vFaskesTelp').textContent = d.faskes_telp || '[No. Telp]';
            document.getElementById('vFaskesEmail').textContent = d.faskes_email || '[Email Faskes]';
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

            document.getElementById('modalPreview').classList.add('active');
        }
    </script>
</body>
</html>
