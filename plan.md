# 📄 **Specific DMS – Sistem Informasi Rujukan Rumah Sakit**

**Fokus: Manajemen Surat Rujukan Faskes (Masuk & Keluar)**

---

## 1. 📌 Spesifikasi DMS

**Document Management System (DMS)** pada Rumah Sakit Tipe B dirancang untuk mengelola seluruh dokumen yang berkaitan dengan **surat rujukan fasilitas kesehatan** secara digital, terpusat, dan riwayat yang senantiasa *up-to-date*. Sistem ini memberantas risiko hilangnya rekam medis rujukan cetak dan menormalisasi komunikasi antar Faskes.

### ✨ Spesifikasi Sistem:

**a. Platform**
Sistem berbasis web (*web-based system*) fungsional penuh di antarmuka komputer pos admisi Rumah Sakit tanpa perlu instalasi pihak ketiga.

**b. Pengguna Sistem (User)**
Sistem ini dirancang dengan pendekatan *single-user architecture*, di mana **Admin Pendaftaran RS (Admisi)** berperan sebagai pengelola tunggal. Admin memegang kendali penuh atas input surat dari puskesmas (Rujukan Masuk) maupun ke faskes rujukan tingkat lanjut (Rujukan Keluar).

**c. Fitur Utama**
* Input pendaftaran rujukan masuk faskes jenjang pertama dan rujukan keluar faskes tersier.
* Upload berkas pindaian (Surat Rujukan BPJS/Umum, Lembar Diagnosa, Hasil Lab) ke folder bersangkutan.
* Memperbarui status rujukan (Aktif, Diproses, Selesai/Diarsipkan) dengan fungsionalitas pencatatan jejak (*Version Control*).

**d. Metadata Dokumen**
* Nama Dokumen (e.g., Surat_Rujukan_Masuk_PoliAnak)
* Nama Lengkap Pasien / ID Pendaftaran
* Tanggal Upload
* Faskes Asal/Tujuan (Puskesmas / RSUP)
* Status Klinis Dokumen (Aktif / Arsip)

**e. Sistem Pencarian**
Pencarian instan berdasar ID Pasien (RUJ-XXXX) atau Nama Pasien.

**f. Keamanan Sistem**
Enkripsi akses *Single-Sign On* standar medis, *backup* basis data cloud otomatis.

---

## 2. 🏥 Latar Belakang Institusi (Healthcare)

Unit Admisi dan Pendaftaran Rumah Sakit merupakan gerbang utama bagi pasien. RS melayani pasien *walk-in* maupun **pasien rujukan** yang diarahkan dari jejaring Fasilitas Kesehatan Tingkat Pertama (Klinik/Puskesmas) ke Poliklinik spesialis bedah, saraf, dan penyakit dalam (Rujukan Masuk).

Selain itu, RS juga menangani eskalasi rawat inap dengan menerbitkan surat kuasa Rujukan Keluar menuju RS Pusat/Nasional. Mengelola ratusan dokumen fisik harian rentan kesalahan dan mereduksi ketepatan klaim asuransi kesehatan BPJS/Pihak Ketiga.

---

## 3. 🖥️ UI Awal DMS (Tampilan Sistem)

### 🔐 Halaman Login
Gerbang masuk staf internal RS:
* Logo Rumah Sakit / Medis
* Input NIP Petugas
* Input Password

### 📊 Dashboard Utama
* **Ringkasan Data**: Metrik analitis (Total Dokumen Rujukan, Total Pasien Terintegrasi).
* **Charts Analitis**: Grafik komparasi *Rujukan Masuk vs Rujukan Keluar*, serta doughnut chart persebaran asal Poliklinik (Poli Dalam, Poli Anak, dll).
* **Aktivitas Terkini (Notifikasi)**: Alur pantauan dokumen pasca terunggah (e.g. *Puskesmas Maju merujuk pasien Budi*).

---

## 4. 📂 Fitur Utama: Document Explorer

**Document Explorer** menstrukturkan dokumen dalam laci digital dua pilar utama.

### 📁 Struktur Folder Rujukan:
```
📁 Rujukan Masuk
   ├── 📂 Poli Jantung
   │   ├── RUJ_2041_Budi.pdf
   │   └── Lab_Budi.pdf
   ├── 📂 Poli Penyakit Dalam

📁 Rujukan Keluar
   ├── 📂 RS Dr. Kariadi
   │   └── Surat_Pindah_Rawat_Andi.pdf
```

---

## 5. 🔄 Alur Sistem (Flow) Pengelolaan Rujukan

Alur pengelolaan surat rujukan dalam DMS dimulai ketika rumah sakit menerima surat rujukan dari fasilitas kesehatan tingkat pertama (faskes) seperti puskesmas atau klinik. Admin kemudian menginput data pasien dan mengunggah dokumen rujukan tersebut ke dalam sistem sebagai **rujukan masuk**, yang selanjutnya disimpan secara terstruktur dalam Document Explorer dengan status aktif. Selama proses pelayanan berlangsung, status dokumen dapat diperbarui menjadi diproses hingga selesai, kemudian diarsipkan sebagai riwayat pasien. 

Selain itu, sistem juga mendukung pengelolaan **rujukan keluar**, yaitu ketika rumah sakit perlu merujuk pasien ke fasilitas kesehatan lain atau dokter spesialis. Dalam proses ini, admin membuat atau mengunggah surat rujukan keluar, menyimpan dokumen ke dalam sistem, serta memberikan status sesuai kondisi, seperti aktif atau selesai. 

Seluruh dokumen, baik rujukan masuk maupun keluar, tersimpan secara terpusat sehingga dapat dengan mudah dicari, diakses kembali, dan digunakan untuk keperluan administrasi serta pelacakan riwayat layanan pasien.

---

## 6. 🎯 Kesimpulan

Implementasi **Sistem Informasi Manajemen Surat Rujukan** mengubah total alur kerja administrasi manual Rumah Sakit menjadi ekosistem lincah digital. 
* Meminimalisir angka hilangnya form *Rujukan Pasien* 
* Mengakselerasi koordinasi antar Faskes
* Menciptakan *Database History* holistik bagi setiap pasien rujukan.