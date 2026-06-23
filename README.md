# MODEL_SVR - Computational Machine Learning Engine (FastAPI)

Computational Machine Learning Engine untuk sistem **REVORA** (Revenue Estimation, Visualization, Optimization, Reporting, and Analytics) Kota Cirebon. Layanan ini menangani preprocessing data, pelatihan model SVR, dan pencarian hyperparameter optimal menggunakan **Grid Search** dan **Grey Wolf Optimizer (GWO)**.

Layanan ini dibangun menggunakan **FastAPI Python 3.10** dan disajikan melalui REST API dengan keamanan **JWT Bearer Token**.

---

## 🏗️ Fitur Utama
- **Model SVR**: Pemodelan berbasis waktu dengan dynamic scaling menggunakan `RobustScaler` (fitur) dan `MinMaxScaler` (target log1p).
- **Grid Search**: Tuning hyperparameter menggunakan 5-Fold Cross Validation secara diskrit.
- **Grey Wolf Optimizer (GWO)**: Tuning hyperparameter menggunakan meta-heuristik dengan populasi serigala dan 3-Fold TimeSeriesSplit. Dioptimasi secara paralel untuk kecepatan pemrosesan tinggi.
- **Security**: Verifikasi request secara dinamis menggunakan JWT (JSON Web Token) dengan masa kedaluwarsa 6 jam.

---

## 🚀 Cara Menjalankan Secara Mandiri (Lokal)

Pastikan Python 3.10+ sudah terinstal di komputer Anda:

1. **Setup Virtual Environment**:
   ```bash
   python -m venv venv
   ```
2. **Aktifkan Virtual Environment**:
   - **Windows (PowerShell)**: `.\venv\Scripts\Activate.ps1`
   - **Windows (CMD)**: `.\venv\Scripts\activate.bat`
   - **macOS/Linux**: `source venv/bin/activate`
3. **Install Dependensi**:
   ```bash
   pip install -r requirements.txt
   ```
4. **Setup Environment (.env)**:
   Salin berkas `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
5. **Jalankan FastAPI Server**:
   ```bash
   uvicorn main:app --reload --host 127.0.0.1 --port 8000
   ```
6. Akses Dokumentasi Swagger di [http://127.0.0.1:8000/docs](http://127.0.0.1:8000/docs).

---

## 🧪 Menjalankan Pengujian (Testing)
Untuk menjalankan pengujian backend:
```bash
pytest
```
