import numpy as np
import pandas as pd
import datetime
import os
import holidays as pyholidays
from app.core.constants import LIBUR_NASIONAL_ID, JUKIR_MAP, FITUR_COLS

def extract_features_for_day(tanggal_str: str, rayon: int, libur_manual_list: list = [], df_history_override: pd.DataFrame = None) -> np.ndarray:
    """
    Ekstrak 25 fitur untuk suatu tanggal dan Rayon tertentu berdasarkan histori data 2023-2025.
    """
    try:
        tgl_obj = datetime.datetime.strptime(tanggal_str, '%Y-%m-%d')
    except ValueError:
        raise ValueError("Format tanggal salah. Gunakan YYYY-MM-DD.")

    if df_history_override is not None:
        df_history = df_history_override.copy()
    else:
        file_path = 'research/DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv'
        if not os.path.exists(file_path):
            raise ValueError("Dataset CSV historis tidak ditemukan di server API.")
        df_history = pd.read_csv(file_path, parse_dates=['Tanggal'])

        # Preprocessing sama seperti training
        libur_nasional_id = pd.to_datetime(LIBUR_NASIONAL_ID)
        df_history['Libur_Nasional'] = df_history['Tanggal'].dt.normalize().isin(libur_nasional_id).astype(int)
        
        mask_hapus = (df_history['Total_Pendapatan'] == 0) & (df_history['Libur_Nasional'] != 1)
        df_history = df_history[~mask_hapus].copy().reset_index(drop=True)
        
        median_libur = df_history[(df_history['Libur_Nasional'] == 1) & (df_history['Total_Pendapatan'] > 0)]['Total_Pendapatan'].median()
        if pd.isna(median_libur): median_libur = 1000
        df_history.loc[(df_history['Libur_Nasional'] == 1) & (df_history['Total_Pendapatan'] == 0), 'Total_Pendapatan'] = median_libur

    min_date = df_history['Tanggal'].min()
    
    id_holidays = pyholidays.Indonesia()
    is_libur_nasional = (tanggal_str in LIBUR_NASIONAL_ID) or (tgl_obj in id_holidays) or (tanggal_str in libur_manual_list)
    libur = 1 if is_libur_nasional else 0
    weekend = 1 if tgl_obj.weekday() >= 5 else 0
    
    # Append baris baru untuk simulasi fitur lag/rolling
    df_new = pd.DataFrame([{
        'Tanggal': tgl_obj,
        'Rayon': rayon,
        'Total_Pendapatan': np.nan,
        'Libur_Nasional': libur,
        'Weekend': weekend,
        'Jumlah Jukir': JUKIR_MAP.get(rayon, 80)
    }])
    
    df_state = pd.concat([df_history[['Tanggal', 'Rayon', 'Total_Pendapatan', 'Libur_Nasional', 'Weekend', 'Jumlah Jukir']], df_new], ignore_index=True)
    df_state = df_state.sort_values(by=['Rayon', 'Tanggal']).reset_index(drop=True)
    
    # Hitung fitur
    df_state['Tahun']             = df_state['Tanggal'].dt.year
    df_state['Bulan']             = df_state['Tanggal'].dt.month
    df_state['Tanggal_Kalender']  = df_state['Tanggal'].dt.day
    df_state['Hari_dalam_Minggu'] = df_state['Tanggal'].dt.dayofweek
    df_state['Minggu_ke']         = df_state['Tanggal'].dt.isocalendar().week.astype(int)
    
    df_state['Hari_Minggu_sin']  = np.sin(2 * np.pi * df_state['Hari_dalam_Minggu'] / 7)
    df_state['Hari_Minggu_cos']  = np.cos(2 * np.pi * df_state['Hari_dalam_Minggu'] / 7)
    df_state['Tgl_Kalender_sin'] = np.sin(2 * np.pi * df_state['Tanggal_Kalender'] / 31)
    df_state['Tgl_Kalender_cos'] = np.cos(2 * np.pi * df_state['Tanggal_Kalender'] / 31)
    df_state['Minggu_sin']       = np.sin(2 * np.pi * df_state['Minggu_ke'] / 52)
    df_state['Minggu_cos']       = np.cos(2 * np.pi * df_state['Minggu_ke'] / 52)
    
    df_state['Libur_atau_Weekend'] = ((df_state['Libur_Nasional'] == 1) | (df_state['Weekend'] == 1)).astype(int)
    df_state['Trend'] = (df_state['Tanggal'] - min_date).dt.days
    
    for lag in [1, 7, 14]:
        df_state[f'Lag_{lag}'] = df_state.groupby('Rayon')['Total_Pendapatan'].shift(lag)
    df_state['Lag_21'] = df_state.groupby('Rayon')['Total_Pendapatan'].shift(21)
    
    df_state['Rolling_Mean_7']  = (df_state.groupby('Rayon')['Total_Pendapatan']
                                       .transform(lambda x: x.rolling(7).mean()).shift(1))
    df_state['Rolling_Std_7']   = (df_state.groupby('Rayon')['Total_Pendapatan']
                                       .transform(lambda x: x.rolling(7).std()).shift(1))
    df_state['Rolling_Mean_30'] = (df_state.groupby('Rayon')['Total_Pendapatan']
                                       .transform(lambda x: x.rolling(30).mean()).shift(1))
    
    df_state['Ratio_Lag7_Mean30'] = df_state['Lag_7'] / (df_state['Rolling_Mean_30'] + 1)
    
    for i in range(1, 6):
        df_state[f'Rayon_{i}'] = (df_state['Rayon'] == i).astype(int)
        
    df_row = df_state[(df_state['Tanggal'] == tgl_obj) & (df_state['Rayon'] == rayon)]
    return df_row[FITUR_COLS].values
