<?php
/**
 * Basis Data Pasien - Referral System (Soft Delete Version)
 * RSUD Maju Jaya
 */
require_once 'config.php';

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
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header Identik -->
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
                                <li><a href="referral_specialist.php"><i class="ph ph-stethoscope"></i><span>Layanan Poli Spesialis</span></a></li>
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
                                                <button onclick="openEditModal('<?= $row['patient_id'] ?>')" class="action-btn" style="background:#f1f5f9; color:#6366f1;"><i class="ph ph-note-pencil"></i></button>
                                                <a href="referral_patients.php?delete_id=<?= $row['patient_id'] ?>" onclick="return confirm('Arsip pasien ini akan dipindahkan ke folder sampah (Soft Delete). Lanjutkan?')" class="action-btn" style="background:#fef2f2; color:#ef4444;"><i class="ph ph-trash"></i></a>
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
    </script>
</body>
</html>
