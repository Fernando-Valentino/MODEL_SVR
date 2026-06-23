# Progress Pengembangan Sistem REVORA (Revenue Estimation, Visualization, Optimization, Reporting, and Analytics)

Dokumen ini mencatat ringkasan progres pengerjaan dan status pengembangan sistem prediksi retribusi parkir Dinas Perhubungan Kota Cirebon berbasis **Support Vector Regression (SVR)** dan **Grey Wolf Optimizer (GWO)**.

---

## 📊 Status Progres Saat Ini
- **Status Proyek**: Backend API & Frontend terintegrasi, siap diuji coba (Testing coverage 100% Sukses).
- **Arsitektur**: Decoupled (Laravel sebagai Frontend & Auth Provider + FastAPI Python sebagai Machine Learning Engine).
- **Pengaman API**: JWT (JSON Web Token) bertanda tangan HS256 dengan masa aktif sesi **6 jam**.
- **Basis Data**: MySQL (Docker container `mysql_db` untuk produksi/lokal dan `svr_parkir_test` untuk pengujian terisolasi).

---

## 🛠️ Modul yang Selesai Dikembangkan

### 1. Frontend Laravel (Aplikasi Utama & Antarmuka)
- **Sistem Autentikasi & Otorisasi**:
  - Auth Multi-Role menggunakan `spatie/laravel-permission` dengan 3 aktor: **Operator UPT Parkir**, **Kepala UPT Parkir**, dan **Kepala Dinas Perhubungan (Dishub)**.
  - Sesi login Laravel terkonfigurasi kedaluwarsa setelah 6 jam (`SESSION_LIFETIME=360`) menyelaraskan dengan token JWT.
- **Menu Master Data (Operator)**:
  - **Data Rayon**: CRUD data wilayah parkir, kecamatan, lokasi, karakteristik area, dan kapasitas parkir.
  - **Data Juru Parkir**: CRUD penempatan jukir di setiap rayon.
  - **Data Hari Libur & Weekend**: Kalender hari libur nasional dan fitur generator otomatis hari weekend (Sabtu & Minggu) untuk penanda fitur khusus model regresi.
  - **Data Pendapatan**: Pencatatan pendapatan riil harian, template ekspor/impor Excel/CSV untuk dataset latih.
- **Dashboard Multi-Aktor**:
  - **Dashboard Operator**: Statistik ringkas pendapatan, status model, dan visualisasi grafik tren data aktual vs hasil prediksi.
  - **Dashboard Kepala UPT**: Statistik pemantauan tingkat akurasi model dan data pendapatan harian.
  - **Dashboard Kepala Dishub**: Ringkasan eksekutif tren pendapatan retribusi dan pemantauan performa tahunan.
- **Modul Prediksi & Optimasi**:
  - Form eksekusi pelatihan SVR dengan parameter kustom ($C$, $\epsilon$, $\gamma$).
  - Form optimasi hyperparameter menggunakan Grid Search dan Grey Wolf Optimizer (GWO).
- **Modul Laporan**:
  - Cetak laporan pendapatan & hasil prediksi dalam format **PDF** dan **Excel**.

### 2. Backend Python (FastAPI & Machine Learning Engine)
- **SVR Engine**:
  - Logika pemodelan regresi SVR autoregresif menggunakan data historis 2023-2025 dengan 25 fitur olahan (termasuk lag harian/mingguan, one-hot encoding rayon, status hari libur, dan weekend).
- **Optimization Algorithms**:
  - Grid Search untuk pencarian parameter SVR secara exhaustif.
  - Grey Wolf Optimizer (GWO) untuk optimasi parameter heuristik berkecepatan tinggi.
- **Data Pipeline & Training**:
  - Skrip preprocessing dan normalisasi data berbasis `scikit-learn` (`MinMaxScaler` dan `RobustScaler`).
  - Mekanisme penyimpanan artifact model (`.pkl`), data evaluasi (`evaluation.json`), dan dataset.
- **Endpoint API Terproteksi JWT**:
  - `/api/v1/predict` (POST): Melakukan peramalan pendapatan berdasarkan rentang tanggal.
  - `/api/v1/upload-dataset` (POST): Mengunggah berkas CSV dataset baru dan melatih ulang model.
  - `/api/v1/train-existing` (POST): Melatih ulang model dari data CSV yang sudah ada di server.
  - `/api/v1/load-existing` (GET): Memuat model terlatih dan diagram perbandingan performa.
  - `/api/v1/retrain` (POST): Menjalankan training SSE streaming untuk pemantauan realtime.

---

## 🧪 Jaminan Kualitas & Pengujian (Test Suite)

Pengujian otomatis telah dibuat lengkap untuk memastikan sistem tidak rusak saat ada pembaruan kode di masa mendatang (*Regression Prevention*).

### 🟢 1. Test Suite Laravel (PHPUnit)
- **Jumlah Test Case**: **94 tes**
- **Jumlah Assertion**: **207 assertion**
- **Status**: **100% PASSED**
- **Cakupan Pengujian**:
  - Autentikasi login, logout, dan pengalihan rute sesuai role.
  - Pencegahan akses rute ilegal (contoh: operator mencoba membuka dashboard eksekutif kepala dishub akan menerima `403 Forbidden`).
  - CRUD Master Data (Rayon, Jukir, Hari Libur, Pendapatan) lengkap dengan validasi form.
  - Integritas data (*foreign key constraints* pada basis data).
  - Integrasi pembentukan token JWT dinamis saat memanggil API Python.

### 🟢 2. Test Suite Python (pytest)
- **Jumlah Test Case**: **44 tes**
- **Status**: **100% PASSED**
- **Cakupan Pengujian**:
  - Proteksi JWT Bearer token pada seluruh endpoint ML.
  - Penolakan akses tanpa token atau token rusak (`401 Unauthorized`).
  - Skema input/output Pydantic untuk validasi tipe data payload.
  - Unit test preprocessing data, ekstraksi fitur kalender, dan fungsi autoregressive.
  - Mocking MLService untuk pengujian logika endpoint yang independen.

---

## 🚀 Rencana Langkah Selanjutnya (Roadmap)
1. **Penyempurnaan Modul Optimasi Parameter**:
   - Melakukan penyesuaian akhir (finetuning) dan pengujian lebih lanjut untuk fitur Grid Search & GWO agar visualisasi dan pergerakan loading pipeline stabil 100%.
2. **Dashboard Multi-Role**:
   - Menyempurnakan dan mempercantik visualisasi dashboard untuk 3 aktor utama (Operator, Kepala UPT, Kepala Dishub) dengan chart ringkasan pendapatan, akurasi SVR, dan data real-time.
3. **Modul Laporan**:
   - Memperbaiki dan melengkapi fitur laporan hasil prediksi dan riwayat pendapatan retribusi baik dalam bentuk file PDF maupun ekspor Excel/CSV.
4. **Portal Pengguna Kepala UPT & Kepala Dishub**:
   - Menyempurnakan modul antarmuka visual khusus untuk Kepala UPT dan Dinas Perhubungan (Dishub) pada sub-menu prediksi, optimasi, dan ekspor laporan yang terotorisasi dengan benar.
5. **Deployment & Staging**:
   - Menyiapkan konfigurasi docker-compose untuk staging server atau production.
