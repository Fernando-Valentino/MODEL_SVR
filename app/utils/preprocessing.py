import numpy as np
import pandas as pd
import datetime
import os
from app.models.schemas import PredictionInput

def extract_features(data: PredictionInput) -> np.ndarray:
    """
    Sistem akan secara OTOMATIS:
    1. Mengekstrak Tahun, Bulan, Tanggal, dan Hari dari input "tanggal"
    2. Mencari nilai Lag_1 (Pendapatan H-1) dan Lag_7 (Pendapatan H-7) dari file CSV
    """
    try:
        tgl_obj = datetime.datetime.strptime(data.tanggal, '%Y-%m-%d')
    except ValueError:
        raise ValueError("Format tanggal salah. Gunakan YYYY-MM-DD (Contoh: 2025-01-15).")

    tahun = tgl_obj.year
    bulan = tgl_obj.month
    tanggal_kalender = tgl_obj.day
    hari_dalam_minggu = tgl_obj.weekday() # 0:Senin, 6:Minggu
    
    file_path = 'DATA_PENDAPATAN_PARKIR_PER_HARI_2022-2025.csv'
    if not os.path.exists(file_path):
        raise ValueError("Dataset CSV historis tidak ditemukan di server API.")
        
    df = pd.read_csv(file_path, parse_dates=['Tanggal'])
    
    # Hitung Mundur Tanggal (H-1 dan H-7)
    tgl_lag_1 = tgl_obj - datetime.timedelta(days=1)
    tgl_lag_7 = tgl_obj - datetime.timedelta(days=7)
    
    # Cari nilai total pendapatan di tanggal tersebut dari CSV
    df_lag_1 = df[df['Tanggal'] == tgl_lag_1]
    df_lag_7 = df[df['Tanggal'] == tgl_lag_7]
    
    if df_lag_1.empty:
        raise ValueError(f"Prediksi Gagal: Nilai Lag 1 (Pendapatan tanggal {tgl_lag_1.strftime('%Y-%m-%d')}) tidak ditemukan di Database CSV.")
    if df_lag_7.empty:
        raise ValueError(f"Prediksi Gagal: Nilai Lag 7 (Pendapatan tanggal {tgl_lag_7.strftime('%Y-%m-%d')}) tidak ditemukan di Database CSV.")
        
    lag_1 = float(df_lag_1['Total_Pendapatan'].values[0])
    lag_7 = float(df_lag_7['Total_Pendapatan'].values[0])

    fitur = [
        tahun,
        bulan,
        tanggal_kalender,
        hari_dalam_minggu,
        data.libur_nasional,
        lag_1,
        lag_7
    ]
    return np.array(fitur).reshape(1, -1)
