import sys
import os
import pandas as pd
import numpy as np
from sklearn.preprocessing import RobustScaler, MinMaxScaler
from sklearn.svm import SVR

sys.path.append(os.path.abspath('.'))
sys.path.append(os.path.abspath('..'))
sys.path.append(os.path.abspath('research'))
sys.path.append(os.path.abspath('research/scratch'))

from app.core.constants import FITUR_COLS, LIBUR_NASIONAL_ID, JUKIR_MAP, RAYON_COLS

def main():
    print("Loading data...")
    df = pd.read_csv('research/DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv', parse_dates=['Tanggal'])
    df = df.drop_duplicates(subset=['Tanggal', 'Rayon'], keep='last').reset_index(drop=True)

    libur_nasional_dt = pd.to_datetime(LIBUR_NASIONAL_ID)
    df['Libur_Nasional'] = df['Tanggal'].dt.normalize().isin(libur_nasional_dt).astype(int)
    mask_hapus = (df['Total_Pendapatan'] == 0) & (df['Libur_Nasional'] != 1)
    df = df[~mask_hapus].copy().reset_index(drop=True)

    median_libur = df[(df['Libur_Nasional'] == 1) & (df['Total_Pendapatan'] > 0)]['Total_Pendapatan'].median()
    if pd.isna(median_libur): median_libur = 1000
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

    rayon_cols = RAYON_COLS
    for col in rayon_cols:
        if col in df.columns:
            df[col] = df[col].astype(int)
        else:
            df[col] = 0
            
    for col in rayon_cols:
        df[f'Weekend_{col}'] = df['Weekend'] * df[col]
        
    df = df.sort_values(by=['Tanggal']).reset_index(drop=True)
    df.dropna(inplace=True)
    df.reset_index(drop=True, inplace=True)

    fitur = FITUR_COLS
    target = 'Total_Pendapatan'

    df_sorted = df.sort_values('Tanggal').reset_index(drop=True)
    split_index = int(len(df_sorted) * 0.8)
    df_train = df_sorted.iloc[:split_index].copy().reset_index(drop=True)
    df_test = df_sorted.iloc[split_index:].copy().reset_index(drop=True)

    X_train_raw = df_train[fitur].values
    X_test_raw  = df_test[fitur].values
    y_train_log = np.log1p(df_train[target].values).reshape(-1, 1)
    y_test_log  = np.log1p(df_test[target].values).reshape(-1, 1)

    y_test_asli  = df_test[target].values.flatten()

    scaler_X = RobustScaler()
    scaler_y = MinMaxScaler()
    scaler_X.fit(X_train_raw)
    scaler_y.fit(y_train_log)

    X_test = scaler_X.transform(X_test_raw)

    def inverse_pred(y_scaled):
        y_log = scaler_y.inverse_transform(y_scaled.reshape(-1, 1)).flatten()
        return np.expm1(y_log)

    print("Fitting models...")
    # SVR Standar (Default)
    svr_default = SVR(kernel='rbf', C=1.0, epsilon=0.1, gamma='scale')
    svr_default.fit(scaler_X.transform(X_train_raw), scaler_y.transform(y_train_log).ravel())
    y_pred_default = inverse_pred(svr_default.predict(X_test))

    # SVR + Grid Search
    svr_gs = SVR(kernel='rbf', C=100, epsilon=0.001, gamma=0.01)
    svr_gs.fit(scaler_X.transform(X_train_raw), scaler_y.transform(y_train_log).ravel())
    y_pred_gs = inverse_pred(svr_gs.predict(X_test))

    # SVR + GWO
    svr_gwo = SVR(kernel='rbf', C=250.034536, epsilon=0.00536603, gamma=0.0044554)
    svr_gwo.fit(scaler_X.transform(X_train_raw), scaler_y.transform(y_train_log).ravel())
    y_pred_gwo = inverse_pred(svr_gwo.predict(X_test))

    # Import full excel exporter functions
    from generate_full_reproduced_excel import export_to_excel_file
    
    print(f"Total test rows: {len(y_test_asli)}")
    export_to_excel_file(df_test, y_test_asli, y_pred_default, y_pred_gs, y_pred_gwo)

if __name__ == "__main__":
    main()
