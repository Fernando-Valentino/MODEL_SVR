import pandas as pd
import numpy as np

# Load
df = pd.read_csv('research/DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv', parse_dates=['Tanggal'])
df = df.drop_duplicates(subset=['Tanggal', 'Rayon'], keep='last').reset_index(drop=True)
print("Initial CSV length:", len(df))

# Feature engineering
libur_nasional_id = pd.to_datetime([
    '2023-01-01', '2023-01-10', '2023-01-23', '2023-02-18', '2023-03-22',
    '2023-03-23', '2023-04-07', '2023-04-19', '2023-04-20', '2023-04-21',
    '2023-04-22', '2023-04-23', '2023-04-24', '2023-04-25', '2023-05-01',
    '2023-05-18', '2023-06-01', '2023-06-02', '2023-06-04', '2023-06-28',
    '2023-06-29', '2023-06-30', '2023-07-19', '2023-08-17', '2023-09-28',
    '2023-12-25', '2023-12-26', '2024-01-01', '2024-02-08', '2024-02-09',
    '2024-02-10', '2024-03-11', '2024-03-12', '2024-03-29', '2024-03-31',
    '2024-04-08', '2024-04-09', '2024-04-10', '2024-04-11', '2024-04-12',
    '2024-04-15', '2024-05-01', '2024-05-09', '2024-05-10', '2024-05-23',
    '2024-05-24', '2024-06-01', '2024-06-14', '2024-06-17', '2024-07-07',
    '2024-08-17', '2024-09-16', '2024-12-25', '2024-12-26', '2025-01-01',
    '2025-01-27', '2025-01-28', '2025-01-29', '2025-03-28', '2025-03-29',
    '2025-03-31', '2025-04-01', '2025-04-02', '2025-04-03', '2025-04-04',
    '2025-04-07', '2025-04-18', '2025-04-20', '2025-05-01', '2025-05-12',
    '2025-05-13', '2025-05-29', '2025-05-30', '2025-06-01', '2025-06-06',
    '2025-06-09', '2025-06-27', '2025-08-17', '2025-08-18', '2025-09-05',
    '2025-12-25', '2025-12-26', '2026-01-01', '2026-01-16', '2026-02-17',
    '2026-03-18', '2026-03-19', '2026-03-20', '2026-03-21', '2026-03-22',
    '2026-03-23', '2026-03-24', '2026-04-03', '2026-04-05', '2026-05-01',
    '2026-05-14', '2026-05-15', '2026-05-27', '2026-05-28', '2026-05-31',
    '2026-06-01', '2026-06-16', '2026-08-17', '2026-08-25', '2026-12-24',
    '2026-12-25', '2027-01-01', '2027-01-05', '2027-02-06', '2027-03-09',
    '2027-03-10', '2027-03-26', '2027-05-01', '2027-05-06', '2027-05-17',
    '2027-05-20', '2027-06-01', '2027-06-06', '2027-08-15', '2027-08-17',
    '2027-12-25', '2027-12-26'
])

df['Libur_Nasional'] = df['Tanggal'].dt.normalize().isin(libur_nasional_id).astype(int)
mask_hapus = (df['Total_Pendapatan'] == 0) & (df['Libur_Nasional'] != 1)
df = df[~mask_hapus].copy().reset_index(drop=True)

# Lags
df = df.sort_values(by=['Rayon', 'Tanggal']).reset_index(drop=True)
for lag in [1, 7, 14]:
    df[f'Lag_{lag}'] = df.groupby('Rayon')['Total_Pendapatan'].shift(lag)
df['Lag_21'] = df.groupby('Rayon')['Total_Pendapatan'].shift(21)

# Rolling
df['Rolling_Mean_7']  = (df.groupby('Rayon')['Total_Pendapatan'].transform(lambda x: x.rolling(7).mean()).shift(1))
df['Rolling_Std_7']   = (df.groupby('Rayon')['Total_Pendapatan'].transform(lambda x: x.rolling(7).std()).shift(1))
df['Rolling_Mean_30'] = (df.groupby('Rayon')['Total_Pendapatan'].transform(lambda x: x.rolling(30).mean()).shift(1))

# Drop NaN
df = df.sort_values(by=['Tanggal']).reset_index(drop=True)
df.dropna(inplace=True)
df.reset_index(drop=True, inplace=True)
print("Clean dataset length:", len(df))

# Split
split_idx = int(len(df) * 0.8)
df_train = df.iloc[:split_idx]
df_test = df.iloc[split_idx:]
print("Train length:", len(df_train))
print("Test length:", len(df_test))
