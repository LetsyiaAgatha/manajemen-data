[Mulai]
   ↓
Pasien Datang Membawa Surat Rujukan
   ↓
Petugas Pendaftaran Menerima Dokumen
   ↓
Cek Kelengkapan Dokumen?
   ├── Tidak Lengkap → Kembalikan ke Pasien → [Selesai]
   └── Lengkap
         ↓
Input Data Pasien ke Sistem
         ↓
Verifikasi BPJS / Administrasi
         ↓
Data Valid?
   ├── Tidak → Perbaikan / Ditolak → [Selesai]
   └── Ya
         ↓
Pembuatan SEP (Surat Eligibilitas Peserta)
         ↓
Cetak & Serahkan Dokumen ke Pasien
         ↓
Arahkan ke Poli Tujuan
         ↓
Petugas Poli Menerima Berkas
         ↓
Input Antrian Poli
         ↓
Pemeriksaan oleh Dokter
         ↓
Perlu Tindakan Lanjutan?
   ├── Rawat Jalan → Resep → Apotek → [Selesai]
   ├── Pemeriksaan Penunjang → Lab/Radiologi → Kembali ke Dokter
   └── Rawat Inap → Masuk Ruang Perawatan → [Selesai]