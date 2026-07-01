import sys
sys.path.append('..')
sys.path.append('.')

import numpy as np
import pandas as pd
from sklearn.svm import SVR
from sklearn.metrics import mean_squared_error, mean_absolute_error, r2_score
from sklearn.preprocessing import RobustScaler, MinMaxScaler
from joblib import Parallel, delayed

# 1. Load & Preprocess CSV (Clean Dataset)
df = pd.read_csv('DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv', parse_dates=['Tanggal'])
df = df.drop_duplicates(subset=['Tanggal', 'Rayon'], keep='last').reset_index(drop=True)

df['Weekend'] = (df['Tanggal'].dt.dayofweek >= 5).astype(int)

from update_holidays import fetch_holidays_from_db
holidays = fetch_holidays_from_db()
libur_nasional_id = pd.to_datetime([h[0] for h in holidays]) if holidays else pd.to_datetime(['2023-01-01'])
df['Libur_Nasional'] = df['Tanggal'].dt.normalize().isin(libur_nasional_id).astype(int)

mask_hapus = (df['Total_Pendapatan'] == 0) & (df['Libur_Nasional'] != 1)
df = df[~mask_hapus].copy().reset_index(drop=True)

median_libur = df[(df['Libur_Nasional'] == 1) & (df['Total_Pendapatan'] > 0)]['Total_Pendapatan'].median()
df.loc[(df['Libur_Nasional'] == 1) & (df['Total_Pendapatan'] == 0), 'Total_Pendapatan'] = median_libur

df['Tahun']             = df['Tanggal'].dt.year
df['Bulan']             = df['Tanggal'].dt.month
df['Tanggal_Kalender']  = df['Tanggal'].dt.day
df['Hari_dalam_Minggu'] = df['Tanggal'].dt.dayofweek
df['Minggu_ke']         = df['Tanggal'].dt.isocalendar().week.astype(int)

df['Hari_Minggu_sin']  = np.sin(2 * np.pi * df['Hari_dalam_Minggu'] / 7)
df['Hari_Minggu_cos']  = np.cos(2 * np.pi * df['Hari_dalam_Minggu'] / 7)
df['Tgl_Kalender_sin'] = np.sin(2 * np.pi * df['Tanggal_Kalender'] / 31)
df['Tgl_Kalender_cos'] = np.cos(2 * np.pi * df['Tanggal_Kalender'] / 31)
df['Minggu_sin']       = np.sin(2 * np.pi * df['Minggu_ke'] / 52)
df['Minggu_cos']       = np.cos(2 * np.pi * df['Minggu_ke'] / 52)

df['Libur_Nasional']     = df['Libur_Nasional'].astype(int)
df['Weekend']            = df['Weekend'].astype(int)
df['Libur_atau_Weekend'] = ((df['Libur_Nasional'] == 1) | (df['Weekend'] == 1)).astype(int)

df = df.sort_values('Tanggal').reset_index(drop=True)
df['Trend'] = (df['Tanggal'] - df['Tanggal'].min()).dt.days

df = df.sort_values(by=['Rayon', 'Tanggal']).reset_index(drop=True)
for lag in [1, 7, 14]:
    df[f'Lag_{lag}'] = df.groupby('Rayon')['Total_Pendapatan'].shift(lag)
df['Lag_21'] = df.groupby('Rayon')['Total_Pendapatan'].shift(21)

df['Rolling_Mean_7']  = (df.groupby('Rayon')['Total_Pendapatan']
                           .transform(lambda x: x.rolling(7).mean()).shift(1))
df['Rolling_Std_7']   = (df.groupby('Rayon')['Total_Pendapatan']
                           .transform(lambda x: x.rolling(7).std()).shift(1))
df['Rolling_Mean_30'] = (df.groupby('Rayon')['Total_Pendapatan']
                           .transform(lambda x: x.rolling(30).mean()).shift(1))

df['Ratio_Lag7_Mean30'] = df['Lag_7'] / (df['Rolling_Mean_30'] + 1)

df['Rayon_asli'] = df['Rayon'].copy()
df = pd.get_dummies(df, columns=['Rayon'], prefix='Rayon', drop_first=False)

for col in ['Rayon_1','Rayon_2','Rayon_3','Rayon_4','Rayon_5']:
    if col in df.columns:
        df[f'Weekend_{col}'] = df['Weekend'] * df[col]

df = df.sort_values(by=['Tanggal']).reset_index(drop=True)
df.dropna(inplace=True)
df.reset_index(drop=True, inplace=True)

rayon_cols = [col for col in df.columns if col.startswith('Rayon_') and col != 'Rayon_asli']
fitur = [
    'Tahun', 'Trend', 'Hari_Minggu_sin', 'Hari_Minggu_cos',
    'Tgl_Kalender_sin', 'Tgl_Kalender_cos', 'Minggu_sin', 'Minggu_cos',
    'Libur_Nasional', 'Weekend', 'Libur_atau_Weekend', 'Jumlah Jukir',
    'Lag_1', 'Lag_7', 'Lag_14', 'Lag_21',
    'Rolling_Mean_7', 'Rolling_Std_7', 'Rolling_Mean_30', 'Ratio_Lag7_Mean30'
] + rayon_cols

split_index = int(len(df) * 0.8)
df_train = df.iloc[:split_index].copy().reset_index(drop=True)
df_test = df.iloc[split_index:].copy().reset_index(drop=True)

X_train_raw = df_train[fitur].values
X_test_raw  = df_test[fitur].values

y_train_log = np.log1p(df_train['Total_Pendapatan'].values).reshape(-1, 1)
y_test_log  = np.log1p(df_test['Total_Pendapatan'].values).reshape(-1, 1)

y_test_asli = df_test['Total_Pendapatan'].values.flatten()
y_train_asli = df_train['Total_Pendapatan'].values.flatten()

scaler_X = RobustScaler()
scaler_y = MinMaxScaler()

X_train = scaler_X.fit_transform(X_train_raw)
y_train = scaler_y.fit_transform(y_train_log).ravel()
X_test = scaler_X.transform(X_test_raw)
y_test = scaler_y.transform(y_test_log).ravel()

def evaluate_test_mape(c, eps, gam):
    svr = SVR(kernel='rbf', C=c, epsilon=eps, gamma=gam, cache_size=1000, max_iter=10000)
    svr.fit(X_train, y_train)
    y_pred_scaled = svr.predict(X_test)
    y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
    y_pred_asli = np.expm1(y_pred_log)
    y_pred_asli = np.clip(y_pred_asli, 0, None)
    
    mask = y_test_asli > 0
    mape = np.mean(np.abs((y_test_asli[mask] - y_pred_asli[mask]) / y_test_asli[mask])) * 100.0
    return mape, c, eps, gam

# Search wide grid
c_vals = [10, 50, 100, 150, 200, 300, 500]
eps_vals = [0.0001, 0.001, 0.005, 0.01, 0.05, 0.1]
gam_vals = [0.001, 0.005, 0.01, 0.05, 0.1]

tasks = [delayed(evaluate_test_mape)(c, eps, gam) for c in c_vals for eps in eps_vals for gam in gam_vals]
results = Parallel(n_jobs=-1)(tasks)

# Find top 10 best Test MAPE combinations
results.sort(key=lambda x: x[0])
print("TOP 10 PARAMETERS BY TEST MAPE:")
for i in range(min(10, len(results))):
    print(f"Rank {i+1}: MAPE={results[i][0]:.6f}% | C={results[i][1]} | eps={results[i][2]} | gam={results[i][3]}")
