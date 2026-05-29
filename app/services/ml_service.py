import os
import joblib
import numpy as np
import pandas as pd
import datetime
import holidays as pyholidays
from app.core.config import get_settings
from app.core.logger import logger
from app.core.constants import LIBUR_NASIONAL_ID, JUKIR_MAP
from app.utils.preprocessing import extract_features_for_day

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

            if os.path.exists(model_path):
                self.model = joblib.load(model_path)
                self.scaler_X = joblib.load(scaler_X_path)
                self.scaler_y = joblib.load(scaler_y_path)
                logger.info("ML artifacts loaded successfully.")
            else:
                logger.warning(f"ML artifacts not found in {self.artifacts_dir}.")
        except Exception as e:
            logger.error(f"Error loading artifacts: {str(e)}")

    def autoregressive_predict(self, start_date_str: str, end_date_str: str, holidays: list, rayon_id: int = 0) -> list:
        if self.model is None or self.scaler_X is None or self.scaler_y is None:
            raise ValueError("Model artifacts belum di-load. Silakan upload dataset dan train dulu.")

        try:
            start_date = datetime.datetime.strptime(start_date_str, '%Y-%m-%d')
            end_date = datetime.datetime.strptime(end_date_str, '%Y-%m-%d')
        except ValueError:
            raise ValueError("Format tanggal salah! Gunakan format YYYY-MM-DD.")

        if end_date < start_date:
            raise ValueError("Tanggal akhir tidak boleh mundur dari tanggal awal!")

        # 1. Load histori CSV asli untuk awalan pemicu
        file_path = 'DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv'
        if not os.path.exists(file_path):
            raise ValueError("Dataset histori (CSV) tidak ditemukan di server.")
        
        df_history = pd.read_csv(file_path, parse_dates=['Tanggal'])
        
        # ── Preprocess history exactly as during training ──
        libur_nasional_id = pd.to_datetime(LIBUR_NASIONAL_ID)
        df_history['Libur_Nasional'] = df_history['Tanggal'].dt.normalize().isin(libur_nasional_id).astype(int)
        
        mask_hapus = (df_history['Total_Pendapatan'] == 0) & (df_history['Libur_Nasional'] != 1)
        df_history = df_history[~mask_hapus].copy().reset_index(drop=True)
        
        median_libur = df_history[(df_history['Libur_Nasional'] == 1) & (df_history['Total_Pendapatan'] > 0)]['Total_Pendapatan'].median()
        if pd.isna(median_libur): median_libur = 1000
        df_history.loc[(df_history['Libur_Nasional'] == 1) & (df_history['Total_Pendapatan'] == 0), 'Total_Pendapatan'] = median_libur
        
        last_known_date = df_history['Tanggal'].max()
        
        # 2. Setup running state
        df_predict_state = df_history[['Tanggal', 'Rayon', 'Total_Pendapatan', 'Libur_Nasional', 'Weekend', 'Jumlah Jukir']].copy()
        
        results = []
        
        # Determine current date simulation starting point
        if start_date <= last_known_date + datetime.timedelta(days=1):
            current_date = start_date
        else:
            current_date = last_known_date + datetime.timedelta(days=1)
            logger.info(f"Otomatis me-rolling data kosong dari {current_date.strftime('%Y-%m-%d')} untuk mencapai target {start_date_str}")
            
        id_holidays = pyholidays.Indonesia()
        
        # 3. Autoregressive loop
        while current_date <= end_date:
            curr_str = current_date.strftime('%Y-%m-%d')
            
            # Predict for each rayon
            pred_asli = []
            for r in range(1, 6):
                # Call extract_features_for_day from preprocessing.py, passing in-memory state override
                X_today = extract_features_for_day(curr_str, r, holidays, df_history_override=df_predict_state)
                X_scaled = self.scaler_X.transform(X_today)
                pred_scaled = self.model.predict(X_scaled).reshape(-1, 1)
                pred_log = self.scaler_y.inverse_transform(pred_scaled).flatten()
                pred_val = np.expm1(pred_log)[0]
                pred_asli.append(pred_val)
                
            # Fill the predicted values back to the prediction state for today
            is_libur_nasional = (curr_str in LIBUR_NASIONAL_ID) or (current_date in id_holidays) or (curr_str in holidays)
            libur = 1 if is_libur_nasional else 0
            weekend = 1 if current_date.weekday() >= 5 else 0
            
            new_rows = []
            for idx, r in enumerate(range(1, 6)):
                new_rows.append({
                    'Tanggal': current_date,
                    'Rayon': r,
                    'Total_Pendapatan': pred_asli[idx],
                    'Libur_Nasional': libur,
                    'Weekend': weekend,
                    'Jumlah Jukir': JUKIR_MAP[r]
                })
            df_new = pd.DataFrame(new_rows)
            df_predict_state = pd.concat([df_predict_state, df_new], ignore_index=True)
            
            # Add to results if within user range
            if current_date >= start_date:
                if rayon_id > 0:
                    # Return only the specific rayon's prediction
                    selected_revenue = float(pred_asli[rayon_id - 1])
                    results.append({
                        "tanggal": curr_str,
                        "pendapatan": selected_revenue
                    })
                else:
                    # Return sum of all rayons
                    total_daily_revenue = float(np.sum(pred_asli))
                    results.append({
                        "tanggal": curr_str,
                        "pendapatan": total_daily_revenue
                    })
                
            current_date += datetime.timedelta(days=1)
            
        return results

ml_service = MLService()
