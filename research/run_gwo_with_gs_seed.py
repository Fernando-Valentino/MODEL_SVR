import sys
sys.path.append('..')
sys.path.append('.')

import numpy as np
import pandas as pd
from sklearn.svm import SVR
from sklearn.metrics import mean_squared_error, mean_absolute_error, r2_score
from sklearn.preprocessing import RobustScaler, MinMaxScaler
from sklearn.model_selection import TimeSeriesSplit, cross_val_score

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

# GWO settings
NUM_WOLVES    = 12
MAX_ITER      = 20
DIM           = 3
N_SPLITS_GWO  = 5
EARLY_STOP    = 8
RESTART_FRAC  = 0.30
PERTURB_STD   = 0.08
RESTART_EVERY = 3

LB = np.array([1.0,   -4.0,   -3.301])
UB = np.array([2.477, -1.301, -1.0])

np.random.seed(42)
positions = np.random.uniform(0, 1, (NUM_WOLVES, DIM)) * (UB - LB) + LB

# Warm Start: plant best Grid Search parameter C=50, eps=0.01, gam=0.01
positions[0] = np.log10([50.0, 0.01, 0.01])
# Old GWO v2
positions[1] = np.log10([199.5, 0.000316, 0.00677])
# Old GWO v4
positions[2] = np.log10([199.5, 0.005012, 0.00481])

alpha_pos = np.zeros(DIM);  alpha_score = float("inf")
beta_pos  = np.zeros(DIM);  beta_score  = float("inf")
delta_pos = np.zeros(DIM);  delta_score = float("inf")

tscv_gwo = TimeSeriesSplit(n_splits=5)

def fitness_gwo(pos):
    model = SVR(
        kernel    = 'rbf',
        C         = 10 ** pos[0],
        epsilon   = 10 ** pos[1],
        gamma     = 10 ** pos[2],
        cache_size= 1000
    )
    scores = cross_val_score(
        model, X_train, y_train,
        cv      = tscv_gwo,
        scoring = 'neg_root_mean_squared_error',
        n_jobs  = -1
    )
    return -scores.mean()

for t in range(MAX_ITER):
    wolves_score = []
    for i in range(NUM_WOLVES):
        fit = fitness_gwo(positions[i])
        wolves_score.append(fit)

        if fit < alpha_score:
            delta_score, delta_pos = beta_score,  beta_pos.copy()
            beta_score,  beta_pos  = alpha_score, alpha_pos.copy()
            alpha_score, alpha_pos = fit,         positions[i].copy()
        elif fit < beta_score:
            delta_score, delta_pos = beta_score, beta_pos.copy()
            beta_score,  beta_pos  = fit,        positions[i].copy()
        elif fit < delta_score:
            delta_score, delta_pos = fit, positions[i].copy()

    # Update positions
    a = 2 - t * (2 / MAX_ITER)
    for i in range(NUM_WOLVES):
        for j in range(DIM):
            r1, r2 = np.random.rand(), np.random.rand()
            X1 = alpha_pos[j] - (2*a*r1 - a) * abs(2*r2*alpha_pos[j] - positions[i,j])
            r1, r2 = np.random.rand(), np.random.rand()
            X2 = beta_pos[j]  - (2*a*r1 - a) * abs(2*r2*beta_pos[j]  - positions[i,j])
            r1, r2 = np.random.rand(), np.random.rand()
            X3 = delta_pos[j] - (2*a*r1 - a) * abs(2*r2*delta_pos[j] - positions[i,j])
            positions[i,j] = np.clip((X1 + X2 + X3) / 3.0, LB[j], UB[j])

    improved = alpha_score < prev_alpha_score - 1e-6 if 'prev_alpha_score' in locals() else True
    if improved:
        no_improve_count = 0
        prev_alpha_score = alpha_score
    else:
        no_improve_count += 1
        if no_improve_count >= EARLY_STOP:
            break

# Final model evaluation
best_C = 10 ** alpha_pos[0]
best_eps = 10 ** alpha_pos[1]
best_gamma = 10 ** alpha_pos[2]

print("GWO WITH GS SEED BEST PARAMETERS:")
print(f"  C: {best_C:.6f}")
print(f"  epsilon: {best_eps:.6f}")
print(f"  gamma: {best_gamma:.6f}")
print(f"  RMSE CV: {alpha_score:.6f}")

# Train and predict
svr_gwo = SVR(kernel='rbf', C=best_C, epsilon=best_eps, gamma=best_gamma)
svr_gwo.fit(X_train, y_train)

y_pred_scaled = svr_gwo.predict(X_test)
y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
y_pred_asli = np.expm1(y_pred_log)
y_pred_asli = np.clip(y_pred_asli, 0, None)

mae = mean_absolute_error(y_test_asli, y_pred_asli)
rmse = np.sqrt(mean_squared_error(y_test_asli, y_pred_asli))
r2 = r2_score(y_test_asli, y_pred_asli)

mask = y_test_asli > 0
mape = np.mean(np.abs((y_test_asli[mask] - y_pred_asli[mask]) / y_test_asli[mask])) * 100.0

print("\nGWO WITH GS SEED TEST METRICS:")
print(f"  MAE: Rp {mae:,.2f}")
print(f"  RMSE: Rp {rmse:,.2f}")
print(f"  MAPE Test: {mape:.6f}%")
print(f"  R2: {r2:.6f}")
