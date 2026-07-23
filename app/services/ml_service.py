import os
import joblib
import numpy as np
import pandas as pd
import datetime
import holidays as pyholidays
from app.core.config import get_settings
from app.core.logger import logger
from app.core.constants import LIBUR_NASIONAL_ID, JUKIR_MAP
from app.utils.preprocessing import extract_features_for_day, roman_rayon

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
            # Prefer GWO, then Grid Search, then Default
            model_names = ['svr_gwo_model.pkl', 'svr_grid_search_model.pkl', 'svr_default_model.pkl']
            model_path = None
            
            for m_name in model_names:
                p = os.path.join(self.artifacts_dir, m_name)
                if os.path.exists(p):
                    model_path = p
                    break
                    
            if model_path:
                self.model = joblib.load(model_path)
                
                # Check for appropriate scalers (default back to general scaler_X/y)
                scaler_X_path = os.path.join(self.artifacts_dir, 'scaler_X.pkl')
                if not os.path.exists(scaler_X_path):
                    scaler_X_path = os.path.join(self.artifacts_dir, 'scaler_X_default.pkl')
                    
                scaler_y_path = os.path.join(self.artifacts_dir, 'scaler_y.pkl')
                if not os.path.exists(scaler_y_path):
                    scaler_y_path = os.path.join(self.artifacts_dir, 'scaler_y_default.pkl')
                
                self.scaler_X = joblib.load(scaler_X_path)
                self.scaler_y = joblib.load(scaler_y_path)
                logger.info(f"ML artifacts loaded successfully using model: {os.path.basename(model_path)}.")
            else:
                logger.warning(f"No ML artifacts found in {self.artifacts_dir}.")
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
        file_path = 'research/DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv'
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

    def _get_model_and_scalers(self, model_type: str):
        """
        Dynamically load model and scalers based on model_type:
        - 'baseline': svr_default_model.pkl, scaler_X_default.pkl, scaler_y_default.pkl
        - 'grid_search': svr_grid_search_model.pkl, scaler_X.pkl, scaler_y.pkl
        - 'gwo': svr_gwo_model.pkl, scaler_X.pkl, scaler_y.pkl
        """
        if model_type == 'baseline':
            m_name = 'svr_default_model.pkl'
            s_x_name = 'scaler_X_default.pkl'
            s_y_name = 'scaler_y_default.pkl'
        elif model_type == 'grid_search':
            m_name = 'svr_grid_search_model.pkl'
            s_x_name = 'scaler_X.pkl'
            s_y_name = 'scaler_y.pkl'
        elif model_type == 'gwo':
            m_name = 'svr_gwo_model.pkl'
            s_x_name = 'scaler_X.pkl'
            s_y_name = 'scaler_y.pkl'
        else:
            raise ValueError(f"Model type '{model_type}' tidak valid. Gunakan 'baseline', 'grid_search', atau 'gwo'.")

        m_path = os.path.join(self.artifacts_dir, m_name)
        if not os.path.exists(m_path):
            if model_type in ['grid_search', 'gwo']:
                raise ValueError(f"Model SVR [{model_type.upper()}] belum dilatih. Harap lakukan training atau optimasi model terlebih dahulu.")
            raise ValueError(f"Berkas model {m_name} tidak ditemukan di server.")

        s_x_path = os.path.join(self.artifacts_dir, s_x_name)
        if not os.path.exists(s_x_path):
            s_x_path = os.path.join(self.artifacts_dir, 'scaler_X_default.pkl')
            
        s_y_path = os.path.join(self.artifacts_dir, s_y_name)
        if not os.path.exists(s_y_path):
            s_y_path = os.path.join(self.artifacts_dir, 'scaler_y_default.pkl')

        if not os.path.exists(s_x_path) or not os.path.exists(s_y_path):
            raise ValueError("Berkas scaler SVR tidak ditemukan di server.")

        model = joblib.load(m_path)
        scaler_X = joblib.load(s_x_path)
        scaler_y = joblib.load(s_y_path)
        return model, scaler_X, scaler_y

    def forecast_recursive(self, rayon_id: int, horizon_days: int, model_type: str, seed_data: list) -> list:
        model, scaler_X, scaler_y = self._get_model_and_scalers(model_type)

        if not seed_data:
            raise ValueError("Data seed untuk forecasting kosong!")

        parsed_seed = []
        for item in seed_data:
            tgl_obj = pd.to_datetime(item["Tanggal"])
            parsed_seed.append({
                'Tanggal': tgl_obj,
                'Rayon': int(item["Rayon"]),
                'Total_Pendapatan': float(item["Total_Pendapatan"]),
                'Jumlah Jukir': int(item.get("Jumlah Jukir", JUKIR_MAP.get(int(item["Rayon"]), 80)))
            })
        
        df_state = pd.DataFrame(parsed_seed)
        
        libur_nasional_id = pd.to_datetime(LIBUR_NASIONAL_ID)
        id_holidays = pyholidays.Indonesia()
        
        df_state['Libur_Nasional'] = df_state['Tanggal'].dt.normalize().isin(libur_nasional_id).astype(int)
        
        for idx, row in df_state.iterrows():
            tgl_str = row['Tanggal'].strftime('%Y-%m-%d')
            is_libur = row['Libur_Nasional'] == 1 or (row['Tanggal'].to_pydatetime() in id_holidays)
            df_state.at[idx, 'Libur_Nasional'] = 1 if is_libur else 0
            
        df_state['Weekend'] = (df_state['Tanggal'].dt.dayofweek >= 5).astype(int)
        df_state = df_state.sort_values(by=['Rayon', 'Tanggal']).reset_index(drop=True)
        
        last_actual_date = df_state['Tanggal'].max()
        current_date = last_actual_date + datetime.timedelta(days=1)
        
        results = []
        start_forecast_date = current_date
        end_forecast_date = last_actual_date + datetime.timedelta(days=horizon_days)
        
        while current_date <= end_forecast_date:
            curr_str = current_date.strftime('%Y-%m-%d')
            rayons_to_predict = [rayon_id] if rayon_id > 0 else range(1, 6)
            
            pred_vals = {}
            for r in rayons_to_predict:
                X_today = extract_features_for_day(curr_str, r, [], df_history_override=df_state)
                X_scaled = scaler_X.transform(X_today)
                pred_scaled = model.predict(X_scaled).reshape(-1, 1)
                pred_log = scaler_y.inverse_transform(pred_scaled).flatten()
                pred_val = np.expm1(pred_log)[0]
                pred_val = max(0.0, float(pred_val))
                pred_vals[r] = pred_val
                
            is_libur_nasional = (curr_str in LIBUR_NASIONAL_ID) or (current_date in id_holidays)
            libur = 1 if is_libur_nasional else 0
            weekend = 1 if current_date.weekday() >= 5 else 0
            
            new_rows = []
            for r, val in pred_vals.items():
                new_rows.append({
                    'Tanggal': current_date,
                    'Rayon': r,
                    'Total_Pendapatan': val,
                    'Libur_Nasional': libur,
                    'Weekend': weekend,
                    'Jumlah Jukir': JUKIR_MAP.get(r, 80)
                })
            df_new = pd.DataFrame(new_rows)
            df_state = pd.concat([df_state, df_new], ignore_index=True)
            
            day_offset = (current_date - start_forecast_date).days + 1
            if day_offset <= 7:
                confidence = "Tinggi"
                confidence_note = "Akurasi tinggi. Sangat andal karena dekat dengan data aktual terakhir."
            elif day_offset <= 30:
                confidence = "Cukup Tinggi"
                confidence_note = "Akurasi cukup tinggi. Andal untuk estimasi bulanan."
            elif day_offset <= 90:
                confidence = "Sedang"
                confidence_note = "Akurasi sedang. Cocok untuk estimasi triwulanan dan melihat tren."
            else:
                confidence = "Rendah"
                confidence_note = "Akurasi rendah. Gunakan terutama untuk melihat kecenderungan tren jangka panjang."
                
            source_features = "actual" if day_offset <= 1 else "recursive"
            
            for r, val in pred_vals.items():
                results.append({
                    "tanggal": curr_str,
                    "rayon_id": r,
                    "rayon": f"Rayon {roman_rayon(r)}",
                    "prediksi_rp": float(val),
                    "source_features": source_features,
                    "confidence": confidence,
                    "confidence_note": confidence_note
                })
                
            current_date += datetime.timedelta(days=1)
            
        return results

ml_service = MLService()
