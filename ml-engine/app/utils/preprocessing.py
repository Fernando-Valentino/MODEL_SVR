import numpy as np
import pandas as pd
import datetime
import os
import holidays as pyholidays
from sklearn.preprocessing import RobustScaler, MinMaxScaler
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


def roman_rayon(rayon_id: int) -> str:
    mapping = {1: "I", 2: "II", 3: "III", 4: "IV", 5: "V"}
    return mapping.get(rayon_id, str(rayon_id))


def preprocess_dataset(dataset: list) -> dict:
    df = pd.DataFrame(dataset)
    df['Tanggal'] = pd.to_datetime(df['Tanggal'])
    
    # Hapus pendapatan = 0 kecuali hari libur
    mask_hapus = (df['Total_Pendapatan'] == 0) & (df['Libur_Nasional'] != 1)
    df = df[~mask_hapus].copy().reset_index(drop=True)
    
    # Mengisi pendapatan 0 pada hari libur dengan median pendapatan hari libur
    median_libur = df[(df['Libur_Nasional'] == 1) & (df['Total_Pendapatan'] > 0)]['Total_Pendapatan'].median()
    if pd.isna(median_libur): 
        median_libur = 1000.0
    df.loc[(df['Libur_Nasional'] == 1) & (df['Total_Pendapatan'] == 0), 'Total_Pendapatan'] = median_libur
    
    # Fitur temporal
    df['Tahun']             = df['Tanggal'].dt.year
    df['Bulan']             = df['Tanggal'].dt.month
    df['Tanggal_Kalender']  = df['Tanggal'].dt.day
    df['Hari_dalam_Minggu'] = df['Tanggal'].dt.dayofweek
    df['Minggu_ke']         = df['Tanggal'].dt.isocalendar().week.astype(int)
    
    # Cyclical encoding
    df['Hari_Minggu_sin']  = np.sin(2 * np.pi * df['Hari_dalam_Minggu'] / 7.0)
    df['Hari_Minggu_cos']  = np.cos(2 * np.pi * df['Hari_dalam_Minggu'] / 7.0)
    df['Tgl_Kalender_sin'] = np.sin(2 * np.pi * df['Tanggal_Kalender'] / 31.0)
    df['Tgl_Kalender_cos'] = np.cos(2 * np.pi * df['Tanggal_Kalender'] / 31.0)
    df['Minggu_sin']       = np.sin(2 * np.pi * df['Minggu_ke'] / 52.0)
    df['Minggu_cos']       = np.cos(2 * np.pi * df['Minggu_ke'] / 52.0)
    
    # Encoding kategorikal
    df['Libur_Nasional']     = df['Libur_Nasional'].astype(int)
    df['Weekend']            = df['Weekend'].astype(int)
    df['Libur_atau_Weekend'] = ((df['Libur_Nasional'] == 1) | (df['Weekend'] == 1)).astype(int)
    
    # Fitur Trend
    df = df.sort_values('Tanggal').reset_index(drop=True)
    df['Trend'] = (df['Tanggal'] - df['Tanggal'].min()).dt.days
    
    # Lag features per Rayon
    df = df.sort_values(by=['Rayon', 'Tanggal']).reset_index(drop=True)
    for lag in [1, 7, 14]:
        df[f'Lag_{lag}'] = df.groupby('Rayon')['Total_Pendapatan'].shift(lag)
    df['Lag_21'] = df.groupby('Rayon')['Total_Pendapatan'].shift(21)
    
    # Rolling features
    df['Rolling_Mean_7']  = (df.groupby('Rayon')['Total_Pendapatan']
                               .transform(lambda x: x.rolling(7).mean()).shift(1))
    df['Rolling_Std_7']   = (df.groupby('Rayon')['Total_Pendapatan']
                               .transform(lambda x: x.rolling(7).std()).shift(1))
    df['Rolling_Mean_30'] = (df.groupby('Rayon')['Total_Pendapatan']
                               .transform(lambda x: x.rolling(30).mean()).shift(1))
    
    # Ratio
    df['Ratio_Lag7_Mean30'] = df['Lag_7'] / (df['Rolling_Mean_30'] + 1.0)
    
    # Simpan Rayon asli & One-Hot
    df['Rayon_asli'] = df['Rayon'].copy()
    
    # Dummy encoding rayon secara manual untuk menjamin output konsisten
    for r_id in range(1, 6):
        df[f'Rayon_{r_id}'] = (df['Rayon'] == r_id).astype(int)
        
    # Interaksi Weekend x Rayon
    for r_id in range(1, 6):
        df[f'Weekend_Rayon_{r_id}'] = df['Weekend'] * df[f'Rayon_{r_id}']
        
    # Sort & hapus NaN
    df = df.sort_values(by=['Tanggal']).reset_index(drop=True)
    df.dropna(inplace=True)
    df.reset_index(drop=True, inplace=True)
    
    if len(df) < 10:
        raise ValueError("Data setelah dibersihkan tidak cukup untuk training model.")
        
    # Data Splitting 80:20 secara kronologis
    split_index = int(len(df) * 0.8)
    df_train = df.iloc[:split_index].copy().reset_index(drop=True)
    df_test = df.iloc[split_index:].copy().reset_index(drop=True)
    
    X_train_raw = df_train[FITUR_COLS].values
    X_test_raw  = df_test[FITUR_COLS].values
    
    y_train_log = np.log1p(df_train['Total_Pendapatan'].values).reshape(-1, 1)
    y_test_log  = np.log1p(df_test['Total_Pendapatan'].values).reshape(-1, 1)
    
    y_train_asli = df_train['Total_Pendapatan'].values.flatten()
    y_test_asli  = df_test['Total_Pendapatan'].values.flatten()
    
    # Normalisasi
    scaler_X = RobustScaler()
    scaler_y = MinMaxScaler()
    
    X_train = scaler_X.fit_transform(X_train_raw)
    y_train = scaler_y.fit_transform(y_train_log).ravel()
    
    X_test = scaler_X.transform(X_test_raw)
    y_test = scaler_y.transform(y_test_log).ravel()
    
    return {
        'df': df,
        'df_train': df_train,
        'df_test': df_test,
        'X_train': X_train,
        'y_train': y_train,
        'X_test': X_test,
        'y_test': y_test,
        'y_train_asli': y_train_asli,
        'y_test_asli': y_test_asli,
        'scaler_X': scaler_X,
        'scaler_y': scaler_y,
        'split_index': split_index
    }

