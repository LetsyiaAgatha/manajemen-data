# Panda DMS - Hospital Referral Management System 🏥
**RSUD Maju Jaya Prototype**

Panda DMS adalah sistem manajemen rujukan digital (Document Management System) yang dirancang untuk mempercepat alur rujukan antar fasilitas kesehatan (Faskes) dan Rumah Sakit. Sistem ini menggantikan proses manual dengan alur digital yang terintegrasi, transparan, dan akuntabel.

## ✨ Fitur Utama
- **Digital Registration**: Pendaftaran rujukan baru dengan formulir standar medis resmi.
- **Verification Workflow**: Antrean verifikasi berkas untuk memvalidasi kelengkapan dokumen.
- **Specialist Dashboard**: Layanan khusus Dokter Spesialis untuk memberikan diagnosa dan rencana tindak lanjut secara real-time.
- **Audit Trail & Traceability**: Pelacakan jejak digital dokumen (kapan, di mana, dan siapa yang menangani).
- **Automated WhatsApp Notifications**: Integrasi Fonnte API untuk notifikasi otomatis ke pasien dan Faskes pengirim.
- **Digital Archive**: Penyimpanan rujukan yang sudah selesai dalam basis data digital yang rapi.
- **SQLite Database**: Sistem database serverless yang ringan, portabel, dan cepat.

## 🛠️ Teknologi
- **Backend**: PHP 8.x
- **Database**: SQLite (dms_hospital.sqlite)
- **Frontend**: Vanilla CSS & JavaScript (Premium UI with Outfit & Inter Fonts)
- **Icons**: Phosphor Icons
- **API**: Fonnte WhatsApp Gateway

## 🚀 Cara Menjalankan
1. Clone repositori ini.
2. Jalankan server lokal PHP:
   ```bash
   php -S localhost:8001
   ```
3. Akses aplikasi melalui browser: `http://localhost:8001/referral_dashboard.php`

---
*Dibuat untuk tugas Manajemen Data - RSUD Maju Jaya.*
