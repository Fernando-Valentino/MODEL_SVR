import os
import joblib
import numpy as np
import pandas as pd
import datetime
import holidays as pyholidays
from app.core.config import get_settings
from app.core.logger import logger

class MLService:
    def __init__(self):
        settings = get_settings()
        self.artifacts_dir = settings.model_artifacts_dir
        self.model = None
        self.scaler_X = None
        self.scaler_y = None
        self._load_artifacts()

    def _load_artifacts(self):
        try:
            model_path = os.path.join(self.artifacts_dir, 'svr_gwo_model.pkl')
            scaler_X_path = os.path.join(self.artifacts_dir, 'scaler_X.pkl')
            scaler_y_path = os.path.join(self.artifacts_dir, 'scaler_y.pkl')

            # Periksa apakah file tersedia (agar tak crash saat awal setup tanpa model)
            if os.path.exists(model_path):
                self.model = joblib.load(model_path)
                self.scaler_X = joblib.load(scaler_X_path)
                self.scaler_y = joblib.load(scaler_y_path)
                logger.info("ML artifacts loaded successfully.")
            else:
                logger.warning(f"ML artifacts not found in {self.artifacts_dir}.")
        except Exception as e:
            logger.error(f"Error loading artifacts: {str(e)}")

    def autoregressive_predict(self, start_date_str: str, end_date_str: str, holidays: list) -> list:
        if self.model is None or self.scaler_X is None or self.scaler_y is None:
            raise ValueError("Model artifacts belum di-load. Silakan upload dataset dan train dulu.")

        try:
            start_date = datetime.datetime.strptime(start_date_str, '%Y-%m-%d')
            end_date = datetime.datetime.strptime(end_date_str, '%Y-%m-%d')
        except ValueError:
            raise ValueError("Format tanggal salah! Gunakan format YYYY-MM-DD.")

        # Validasi logis tanggal
        if end_date < start_date:
            raise ValueError("Tanggal akhir tidak boleh mundur dari tanggal awal!")

        # 1. Load histori CSV asli untuk awalan pemicu (Trigger Lag)
        file_path = 'DATA_PENDAPATAN_PARKIR_PER_HARI_2022-2025.csv'
        if not os.path.exists(file_path):
            raise ValueError("Dataset histori (CSV) tidak ditemukan di server.")
        
        df_history = pd.read_csv(file_path, parse_dates=['Tanggal'])
        
        # 2. Kamus Memori (RAM Lookup): Menyimpan kombinasi Rekam Jejak CSV + Rekam Jejak Prediksi Baru
        revenue_lookup = dict(zip(df_history['Tanggal'].dt.strftime('%Y-%m-%d'), df_history['Total_Pendapatan']))

        results = []
        
        last_known_date = df_history['Tanggal'].max()
        
        # Jika user meminta meramal dari hari esok saja
        if start_date <= last_known_date + datetime.timedelta(days=1):
            current_date = start_date
        else:
            # Jika user meminta tahun 2026, padahal data cuma sampe 2025.
            # Sistem secara pintar harus "Melatih/Menaruh Memori Tebakan" per-hari sejak 2025 s/d 2026.
            current_date = last_known_date + datetime.timedelta(days=1)
            logger.info(f"Otomatis me-rolling data kosong dari {current_date.strftime('%Y-%m-%d')} untuk mencapai target {start_date_str}")

        logger.info(f"Mengeksekusi prediksi Autoregressive rentang: {start_date_str} s/d {end_date_str}")
        
        # 3. Looping dari Hari Pertama s/d Hari Terakhir (Target Akhir)
        while current_date <= end_date:
            curr_str = current_date.strftime('%Y-%m-%d')
            
            # Cari lag 1 dan lag 7 secara mundur dari RAM (Bisa dari masa lalu asli, atau tebakan buatan sistem 2 hari lalu)
            lag_1_date = (current_date - datetime.timedelta(days=1)).strftime('%Y-%m-%d')
            lag_7_date = (current_date - datetime.timedelta(days=7)).strftime('%Y-%m-%d')

            lag_1 = revenue_lookup.get(lag_1_date)
            lag_7 = revenue_lookup.get(lag_7_date)

            if lag_1 is None:
                lag_1 = list(revenue_lookup.values())[-1] # Fallback Anti-Error
            if lag_7 is None:
                 lag_7 = list(revenue_lookup.values())[-7] if len(revenue_lookup) >= 7 else lag_1 # Fallback Anti-Error

            # Menyusun Fitur SVR untuk hari ini
            tahun = current_date.year
            bulan = current_date.month
            tgl = current_date.day
            hari_index = current_date.weekday() # 0:Senin, 6:Minggu
            hari = hari_index + 1 # Sesuaikan Label Encoding: Senin=1, Minggu=7
            
            # Deteksi Libur Nasional Otomatis (Indonesia) + Libur Manual (Opsional)
            id_holidays = pyholidays.Indonesia()
            is_libur_nasional = current_date in id_holidays
            is_libur_manual = curr_str in holidays
            
            libur = 1 if (is_libur_nasional or is_libur_manual) else 0
            
            # Mendeteksi fitur yang diharapkan oleh Model (7 tanpa Weekend, atau 8 pake Weekend)
            if hasattr(self.scaler_X, 'n_features_in_') and self.scaler_X.n_features_in_ == 8:
                weekend = 1 if hari >= 6 else 0
                fitur = np.array([tahun, bulan, tgl, hari, libur, weekend, lag_1, lag_7]).reshape(1, -1)
            else:
                fitur = np.array([tahun, bulan, tgl, hari, libur, lag_1, lag_7]).reshape(1, -1)
            
            # Predict (Normalisasi dulu -> Model GWO -> Kembalikan ke Rupiah)
            X_scaled = self.scaler_X.transform(fitur)
            pred_scaled = self.model.predict(X_scaled).reshape(-1, 1)
            pred_asli = self.scaler_y.inverse_transform(pred_scaled).flatten()[0]
            
            # SIMPAN hasil tebakan ke RAM untuk jembatan besoknya
            revenue_lookup[curr_str] = float(pred_asli)
            
            # Hanya catat hasil output jika tanggalnya sudah menyentuh target user (start_date)
            if current_date >= start_date:
                results.append({
                    "tanggal": curr_str,
                    "pendapatan": float(pred_asli)
                })
            
            current_date += datetime.timedelta(days=1)
            
        return results

# Instansiasi Singleton MLService
ml_service = MLService()
