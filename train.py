import os
import json
import pandas as pd
import numpy as np
import joblib
import time
from sklearn.svm import SVR
from sklearn.preprocessing import MinMaxScaler
from sklearn.model_selection import GridSearchCV, TimeSeriesSplit, cross_val_score
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from app.core.logger import logger
from app.core.config import get_settings

def hitung_metrik(y_true, y_pred):
    mae = mean_absolute_error(y_true, y_pred)
    rmse = np.sqrt(mean_squared_error(y_true, y_pred))
    mse = mean_squared_error(y_true, y_pred)
    mask = y_true != 0
    mape = np.mean(np.abs((y_true[mask] - y_pred[mask]) / y_true[mask])) * 100 if np.sum(mask) > 0 else 0
    r2 = r2_score(y_true, y_pred)
    return mse, rmse, mae, mape, r2

def train_and_evaluate():
    logger.info("Mulai proses preprocessing dan training SVR (Grid Search vs GWO)...")
    settings = get_settings()
    os.makedirs(settings.model_artifacts_dir, exist_ok=True)
    
    # 1. READ & PREPROCESSING DATA
    file_path = 'DATA_PENDAPATAN_PARKIR_PER_HARI_2022-2025.csv'
    df = pd.read_csv(file_path, parse_dates=['Tanggal'])
    
    # [SNAPSHOT] Simpan 5 baris data mentah pertama untuk dikonsumsi UI (Step 1)
    # Ubah format tanggal jadi string agar bisa di-serialize ke JSON
    df_temp_raw = df.head(5).copy()
    if 'Tanggal' in df_temp_raw.columns:
        df_temp_raw['Tanggal'] = df_temp_raw['Tanggal'].dt.strftime('%Y-%m-%d')
    raw_data_snapshot = df_temp_raw.to_dict(orient='records')
    
    # Label Encoding (Teks -> Angka)
    if df['Hari_dalam_Minggu'].dtype == object:
        df['Hari_dalam_Minggu'] = df['Hari_dalam_Minggu'].map({'Senin':1, 'Selasa':2, 'Rabu':3, 'Kamis':4, 'Jumat':5, 'Sabtu':6, 'Minggu':7})
    if df['Libur_Nasional'].dtype == object:
        df['Libur_Nasional'] = df['Libur_Nasional'].replace({'Libur': 1, 'Tidak Libur': 0})
    if 'Weekend' in df.columns and df['Weekend'].dtype == object:
        df['Weekend'] = df['Weekend'].map({'Akhir Pekan': 1, 'Hari Kerja': 0})
        
    df['Lag_1'] = df['Total_Pendapatan'].shift(1)
    df['Lag_7'] = df['Total_Pendapatan'].shift(7)
    df.dropna(inplace=True)
    df.reset_index(drop=True, inplace=True)
    
    fitur = ['Tahun', 'Bulan', 'Tanggal_Kalender', 'Hari_dalam_Minggu', 'Libur_Nasional']
    if 'Weekend' in df.columns:
        fitur.append('Weekend')
    fitur.extend(['Lag_1', 'Lag_7'])
    
    target = 'Total_Pendapatan'

    # Pembagian Train / Test Otomatis (80% Train, 20% Test)
    split_index = int(len(df) * 0.8)
    df_train = df.iloc[:split_index].copy()
    df_test = df.iloc[split_index:].copy()

    X_train_raw = df_train[fitur].values
    y_train_raw = df_train[target].values.reshape(-1, 1)
    X_test_raw = df_test[fitur].values
    y_test_raw = df_test[target].values.reshape(-1, 1)

    # 2. NORMALISASI MINMAX
    scaler_X = MinMaxScaler()
    scaler_y = MinMaxScaler()
    X_train = scaler_X.fit_transform(X_train_raw)
    y_train = scaler_y.fit_transform(y_train_raw).ravel()
    
    # [SNAPSHOT] Simpan 5 baris data yang sudah di normalisasi (Step 2)
    preprocessed_snapshot = []
    for i in range(min(5, len(X_train))):
        row_dict = {f"Fitur_X{j+1}": round(X_train[i][j], 4) for j in range(len(fitur))}
        row_dict["Target_y"] = round(y_train[i], 4)
        preprocessed_snapshot.append(row_dict)
        
    X_test = scaler_X.transform(X_test_raw)
    y_test_asli = y_test_raw.flatten()

    tscv = TimeSeriesSplit(n_splits=3)
    
    import sys
    is_continue = "--continue" in sys.argv
    
    eval_path = os.path.join(settings.model_artifacts_dir, "evaluation.json")
    prev_gwo_metrics = None
    prev_gs_metrics = None
    if is_continue and os.path.exists(eval_path):
        with open(eval_path, "r") as f:
            prev_eval = json.load(f)
            prev_gs_metrics = prev_eval.get("SVR_GridSearch", None)
            prev_gwo_metrics = prev_eval.get("SVR_GWO", None)

    # ==========================================
    # 3. SVR + GRID SEARCH (Pencarian Hyperparameter)
    # ==========================================
    if is_continue and prev_gs_metrics is not None:
        logger.info("Skip Grid Search, menggunakan model dan metrik sebelumnya...")
        mse_gs = prev_gs_metrics["MSE"]
        rmse_gs = prev_gs_metrics["RMSE"]
        mae_gs = prev_gs_metrics["MAE"]
        mape_gs = float(prev_gs_metrics["MAPE"].replace(" %", ""))
        r2_gs = prev_gs_metrics["R2"]
    else:
        logger.info("Menjalankan SVR + Grid Search...")
        print("[INFO] Sedang Melatih SVR + Grid Search (Ini butuh waktu agak lama...)", flush=True)
        param_grid = {
            'C': [0.1, 1, 10, 50, 100, 200, 500, 1000],          # Diperluas hingga 1000 iterasi
            'epsilon': [0.0001, 0.001, 0.01, 0.05, 0.1, 0.5, 1], # Diperketat presisinya
            'gamma': [0.0001, 0.001, 0.01, 0.1, 0.5, 1, 'scale', 'auto'] # Ditambah variasi heuristik
        }
        grid_search = GridSearchCV(SVR(kernel='rbf'), param_grid, cv=tscv, scoring='neg_mean_squared_error', n_jobs=-1)
        grid_search.fit(X_train, y_train)
        svr_gs = grid_search.best_estimator_
        y_pred_gs_test = scaler_y.inverse_transform(svr_gs.predict(X_test).reshape(-1, 1)).flatten()
        mse_gs, rmse_gs, mae_gs, mape_gs, r2_gs = hitung_metrik(y_test_asli, y_pred_gs_test)

    # ==========================================
    # 4. SVR + GREY WOLF OPTIMIZER (GWO)
    # ==========================================
    logger.info("Menjalankan SVR + GWO...")
    print("[INFO] Beralih Melatih SVR + Grey Wolf Optimizer (GWO)...", flush=True)
    def fitness_function(params):
        C_val = 10 ** params[0]
        eps_val = 10 ** params[1]
        gamma_val = 10 ** params[2]
        model = SVR(kernel='rbf', C=C_val, epsilon=eps_val, gamma=gamma_val)
        mse_val = cross_val_score(model, X_train, y_train, cv=tscv, scoring='neg_mean_squared_error')
        return -mse_val.mean()

    num_wolves = 50  # Ditingkatkan ke 50 serigala atas permintaan pengguna
    max_iter = 150   # Ditingkatkan ke 150 untuk pencarian lebih mendalam
    dim = 3
    lb = np.array([-2.0, -3.0, -4.0])
    ub = np.array([3.0, 0.0, 1.0])
    positions = np.random.uniform(0, 1, (num_wolves, dim)) * (ub - lb) + lb
    
    # Injeksi Serigala Elite (Model GWO Terbaik Sebelumnya)
    model_path = os.path.join(settings.model_artifacts_dir, "svr_gwo_model.pkl")
    if is_continue and prev_gwo_metrics is not None and os.path.exists(model_path):
        prev_model = joblib.load(model_path)
        elite_pos = np.array([np.log10(prev_model.C), np.log10(prev_model.epsilon), np.log10(prev_model.gamma)])
        elite_pos = np.clip(elite_pos, lb, ub)
        positions[0] = elite_pos
        logger.info("Elitism: Memasukkan kembali Serigala Alpha terbaik masa lalu ke dalam perburuan.")
        
    alpha_pos = np.zeros(dim)
    alpha_score = float("inf")
    beta_pos = np.zeros(dim)
    beta_score = float("inf")
    delta_pos = np.zeros(dim)
    delta_score = float("inf")

    for t in range(max_iter):
        for i in range(num_wolves):
            fitness = fitness_function(positions[i])
            if fitness < alpha_score:
                delta_score, delta_pos = beta_score, beta_pos.copy()
                beta_score, beta_pos = alpha_score, alpha_pos.copy()
                alpha_score, alpha_pos = fitness, positions[i].copy()
            elif fitness < beta_score:
                delta_score, delta_pos = beta_score, beta_pos.copy()
                beta_score, beta_pos = fitness, positions[i].copy()
            elif fitness < delta_score:
                delta_score, delta_pos = fitness, positions[i].copy()

        a = 2 - t * (2 / max_iter)
        for i in range(num_wolves):
            for j in range(dim):
                r1, r2 = np.random.rand(), np.random.rand()
                A1, C1 = 2 * a * r1 - a, 2 * r2
                D_alpha = abs(C1 * alpha_pos[j] - positions[i, j])
                X1 = alpha_pos[j] - A1 * D_alpha

                r1, r2 = np.random.rand(), np.random.rand()
                A2, C2 = 2 * a * r1 - a, 2 * r2
                D_beta = abs(C2 * beta_pos[j] - positions[i, j])
                X2 = beta_pos[j] - A2 * D_beta

                r1, r2 = np.random.rand(), np.random.rand()
                A3, C3 = 2 * a * r1 - a, 2 * r2
                D_delta = abs(C3 * delta_pos[j] - positions[i, j])
                X3 = delta_pos[j] - A3 * D_delta

                positions[i, j] = np.clip((X1 + X2 + X3) / 3.0, lb[j], ub[j])
                
        # --- Tambahan untuk Tampilan Progress di Web ---
        # Print progress tiap 10% iterasi
        if t % (max_iter // 10) == 0 or t == max_iter - 1:
            progress_pct = int(((t + 1) / max_iter) * 100)
            print(f"[PROGRESS_GWO_{progress_pct}] Iterasi GWO: {t+1}/{max_iter} | Best Error GWO Sementara: {-alpha_score:.4f}", flush=True)

    best_C = 10 ** alpha_pos[0]
    best_eps = 10 ** alpha_pos[1]
    best_gamma = 10 ** alpha_pos[2]

    svr_gwo = SVR(kernel='rbf', C=best_C, epsilon=best_eps, gamma=best_gamma)
    svr_gwo.fit(X_train, y_train)
    y_pred_gwo_test = scaler_y.inverse_transform(svr_gwo.predict(X_test).reshape(-1, 1)).flatten()
    mse_gwo, rmse_gwo, mae_gwo, mape_gwo, r2_gwo = hitung_metrik(y_test_asli, y_pred_gwo_test)

    # Validasi Hasil GWO Baru vs GWO Lama pada Dataset Test
    is_improved = True
    status_update_msg = "Pertama kali ditraining."
    if is_continue and prev_gwo_metrics is not None:
        prev_mape = float(prev_gwo_metrics["MAPE"].replace(" %", ""))
        if mape_gwo < prev_mape:
            status_update_msg = f"Meningkat! MAPE turun dari {prev_mape}% menjadi {mape_gwo:.2f}%."
            logger.info("GWO menemukan parameter yang lebih baik! Menyimpan...")
        else:
            status_update_msg = f"Mentok. Tidak ada serigala yang mengalahkan akurasi {100-prev_mape:.2f}%."
            logger.info("GWO tidak mengalahkan model Alpha masa lalu. Mengembalikan skor lama.")
            is_improved = False
            mse_gwo = prev_gwo_metrics["MSE"]
            rmse_gwo = prev_gwo_metrics["RMSE"]
            mae_gwo = prev_gwo_metrics["MAE"]
            mape_gwo = prev_mape
            r2_gwo = prev_gwo_metrics["R2"]

    # ==========================================
    # 5. SIMPAN MODEL GWO & HASIL EVALUASI
    # ==========================================
    scaler_X_path = os.path.join(settings.model_artifacts_dir, "scaler_X.pkl")
    scaler_y_path = os.path.join(settings.model_artifacts_dir, "scaler_y.pkl")
    joblib.dump(scaler_X, scaler_X_path)
    joblib.dump(scaler_y, scaler_y_path)
    
    if is_improved:
        joblib.dump(svr_gwo, model_path)
    
    # Hitung rata-rata nilai aktual untuk normalisasi metrik
    mean_actual = float(np.mean(y_test_asli))
    
    # Metrik persentase (normalisasi terhadap mean nilai aktual)
    def to_pct(val, mean, squared=False):
        denom = (mean ** 2) if squared else mean
        return round((float(val) / denom) * 100, 4)
    
    eval_result = {
        "SVR_GridSearch": {
            "MSE": float(mse_gs),
            "MSE_pct": f"{to_pct(mse_gs, mean_actual, squared=True):.4f} %",
            "RMSE": float(rmse_gs),
            "RMSE_pct": f"{to_pct(rmse_gs, mean_actual):.2f} %",
            "MAE": float(mae_gs),
            "MAE_pct": f"{to_pct(mae_gs, mean_actual):.2f} %",
            "MAPE": f"{float(mape_gs):.2f} %",
            "Akurasi": f"{float(r2_gs) * 100:.2f} %",
            "R2": float(r2_gs)
        },
        "SVR_GWO": {
            "MSE": float(mse_gwo),
            "MSE_pct": f"{to_pct(mse_gwo, mean_actual, squared=True):.4f} %",
            "RMSE": float(rmse_gwo),
            "RMSE_pct": f"{to_pct(rmse_gwo, mean_actual):.2f} %",
            "MAE": float(mae_gwo),
            "MAE_pct": f"{to_pct(mae_gwo, mean_actual):.2f} %",
            "MAPE": f"{float(mape_gwo):.2f} %",
            "Akurasi": f"{float(r2_gwo) * 100:.2f} %",
            "R2": float(r2_gwo)
        },
        "Status_Retrain": status_update_msg
    }
    
    with open(eval_path, "w") as f:
        json.dump(eval_result, f, indent=4)
        
    pipeline_data = {
        "raw_data": raw_data_snapshot,
        "preprocessed_data": preprocessed_snapshot,
        "fitur_list": fitur
    }
    with open(os.path.join(settings.model_artifacts_dir, "pipeline_data.json"), "w") as f:
        json.dump(pipeline_data, f, indent=4)
        
    logger.info("Training dan Evaluasi Selesai. Hasil JSON tersimpan.")

if __name__ == "__main__":
    train_and_evaluate()
