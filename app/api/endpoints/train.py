import os
import numpy as np
import pandas as pd
import joblib
from sklearn.svm import SVR
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import KFold, TimeSeriesSplit, cross_val_score
from fastapi import APIRouter, Depends, Request, HTTPException

from app.core.config import get_settings
from app.core.logger import logger
from app.core.security import get_jwt_token
from app.utils.preprocessing import preprocess_dataset, roman_rayon

router = APIRouter()
settings = get_settings()


@router.post("/train/svr-default")
async def train_svr_default(request: Request, token_data: dict = Depends(get_jwt_token)):
    try:
        body = await request.json()
        dataset = body.get("dataset", [])
        if not dataset:
            raise HTTPException(status_code=400, detail="Dataset tidak boleh kosong.")
            
        logger.info(f"Memulai training SVR Standar via API. Jumlah baris data: {len(dataset)}")
        
        pre = preprocess_dataset(dataset)
        X_train, y_train = pre['X_train'], pre['y_train']
        X_test, y_test = pre['X_test'], pre['y_test']
        y_test_asli = pre['y_test_asli']
        scaler_y = pre['scaler_y']
        df_test = pre['df_test']
        df = pre['df']
        split_index = pre['split_index']
        df_train = pre['df_train']
        
        # Training SVR default
        svr_default = SVR(kernel='rbf', C=1.0, epsilon=0.1, gamma='scale')
        svr_default.fit(X_train, y_train)
        
        # Prediksi
        y_pred_scaled = svr_default.predict(X_test)
        y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
        y_pred_asli = np.expm1(y_pred_log)
        y_pred_asli = np.clip(y_pred_asli, 0, None)
        
        # Evaluasi
        mae = float(mean_absolute_error(y_test_asli, y_pred_asli))
        rmse = float(np.sqrt(mean_squared_error(y_test_asli, y_pred_asli)))
        r2 = float(r2_score(y_test_asli, y_pred_asli))
        
        mask = y_test_asli > 0
        mape = float(np.mean(np.abs((y_test_asli[mask] - y_pred_asli[mask]) / y_test_asli[mask])) * 100.0) if np.sum(mask) > 0 else 0.0
        accuracy = float(max(0.0, 100.0 - mape))
        
        # Simpan model default dan scalers
        os.makedirs(settings.model_artifacts_dir, exist_ok=True)
        joblib.dump(svr_default, os.path.join(settings.model_artifacts_dir, 'svr_default_model.pkl'))
        joblib.dump(pre['scaler_X'], os.path.join(settings.model_artifacts_dir, 'scaler_X_default.pkl'))
        joblib.dump(scaler_y, os.path.join(settings.model_artifacts_dir, 'scaler_y_default.pkl'))
        
        # Map predictions list
        predictions_list = []
        for i in range(len(df_test)):
            act = float(y_test_asli[i])
            prd = float(y_pred_asli[i])
            err = float(act - prd)
            pct_err = float((abs(err) / act) * 100.0) if act > 0 else 0.0
            
            tgl_str = df_test.iloc[i]['Tanggal'].strftime('%Y-%m-%d')
            r_id = int(df_test.iloc[i]['Rayon_asli'])
            
            predictions_list.append({
                "tanggal": tgl_str,
                "rayon_id": r_id,
                "rayon": f"Rayon {roman_rayon(r_id)}",
                "actual": act,
                "predicted": prd,
                "error": err,
                "percentage_error": pct_err
            })
            
        train_period = f"{df_train['Tanggal'].min().strftime('%Y-%m-%d')} - {df_train['Tanggal'].max().strftime('%Y-%m-%d')}"
        test_period = f"{df_test['Tanggal'].min().strftime('%Y-%m-%d')} - {df_test['Tanggal'].max().strftime('%Y-%m-%d')}"
        
        return {
            "status": "success",
            "model": "SVR Standar",
            "parameters": {
                "kernel": "rbf",
                "C": 1.0,
                "epsilon": 0.1,
                "gamma": "scale"
            },
            "dataset": {
                "total_rows": int(len(df)),
                "train_rows": int(split_index),
                "test_rows": int(len(df) - split_index),
                "train_period": train_period,
                "test_period": test_period
            },
            "metrics": {
                "mae": mae,
                "rmse": rmse,
                "mape": mape,
                "r2": r2,
                "accuracy": accuracy
            },
            "predictions": predictions_list
        }
        
    except Exception as e:
        logger.error(f"Error pada training SVR Standar: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        raise HTTPException(status_code=500, detail=f"Terjadi kesalahan saat training SVR standar: {str(e)}")


@router.post("/train/grid-search")
async def train_grid_search(request: Request, token_data: dict = Depends(get_jwt_token)):
    try:
        body = await request.json()
        dataset = body.get("dataset", [])
        grid_c = body.get("grid_c", [10, 50, 100, 150, 200])
        grid_epsilon = body.get("grid_epsilon", [0.001, 0.005, 0.01, 0.05])
        grid_gamma = body.get("grid_gamma", ["scale", 0.001, 0.01, 0.05])
        
        if not dataset:
            raise HTTPException(status_code=400, detail="Dataset tidak boleh kosong.")
            
        # Parse inputs
        c_parsed = [float(x) for x in grid_c if x is not None]
        eps_parsed = [float(x) for x in grid_epsilon if x is not None]
        gamma_parsed = []
        for g in grid_gamma:
            try:
                gamma_parsed.append(float(g))
            except:
                if isinstance(g, str):
                    gamma_parsed.append(g.strip())
        
        pre = preprocess_dataset(dataset)
        X_train, y_train = pre['X_train'], pre['y_train']
        X_test, y_test = pre['X_test'], pre['y_test']
        y_test_asli = pre['y_test_asli']
        scaler_y = pre['scaler_y']
        df_test = pre['df_test']
        df = pre['df']
        split_index = pre['split_index']
        df_train = pre['df_train']
        
        logger.info(f"Grid Search: menjalankan search dengan parameters C={c_parsed}, eps={eps_parsed}, gamma={gamma_parsed}")
        # Run actual Grid Search using 5-Fold CV on training set
        kf = KFold(n_splits=5, shuffle=False)
        best_score = float('inf')
        best_c = c_parsed[0] if c_parsed else 1.0
        best_eps = eps_parsed[0] if eps_parsed else 0.1
        best_gamma = gamma_parsed[0] if gamma_parsed else 'scale'
        
        for c in c_parsed:
            for eps in eps_parsed:
                for gam in gamma_parsed:
                    scores = []
                    for tr_idx, val_idx in kf.split(X_train):
                        # Set max_iter to prevent hanging on slow convergence
                        model = SVR(kernel='rbf', C=c, epsilon=eps, gamma=gam, cache_size=1000, max_iter=10000)
                        model.fit(X_train[tr_idx], y_train[tr_idx])
                        pred = model.predict(X_train[val_idx])
                        fold_rmse = np.sqrt(mean_squared_error(y_train[val_idx], pred))
                        scores.append(fold_rmse)
                    avg_score = float(np.mean(scores))
                    if avg_score < best_score:
                        best_score = avg_score
                        best_c = c
                        best_eps = eps
                        best_gamma = gam
        
        # Train final model on train set with best parameters
        best_model = SVR(kernel='rbf', C=best_c, epsilon=best_eps, gamma=best_gamma, max_iter=20000)
        best_model.fit(X_train, y_train)
        
        # Predict and evaluate on test set
        y_pred_scaled = best_model.predict(X_test)
        y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
        y_pred_asli = np.expm1(y_pred_log)
        y_pred_asli = np.clip(y_pred_asli, 0, None)
        
        mae = float(mean_absolute_error(y_test_asli, y_pred_asli))
        rmse = float(np.sqrt(mean_squared_error(y_test_asli, y_pred_asli)))
        r2 = float(r2_score(y_test_asli, y_pred_asli))
        
        mask = y_test_asli > 0
        mape = float(np.mean(np.abs((y_test_asli[mask] - y_pred_asli[mask]) / y_test_asli[mask])) * 100.0) if np.sum(mask) > 0 else 0.0
        accuracy = float(max(0.0, 100.0 - mape))
            
        # Fit a final model for predictions & to save as default model if needed
        final_model = SVR(kernel='rbf', C=best_c, epsilon=best_eps, gamma=best_gamma, max_iter=20000)
        final_model.fit(X_train, y_train)
        
        y_pred_scaled = final_model.predict(X_test)
        y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
        y_pred_asli = np.expm1(y_pred_log)
        y_pred_asli = np.clip(y_pred_asli, 0, None)
        
        # Save model and scalers
        os.makedirs(settings.model_artifacts_dir, exist_ok=True)
        joblib.dump(final_model, os.path.join(settings.model_artifacts_dir, 'svr_grid_search_model.pkl'))
        
        # Map predictions list
        predictions_list = []
        for i in range(len(df_test)):
            act = float(y_test_asli[i])
            prd = float(y_pred_asli[i])
            err = float(act - prd)
            pct_err = float((abs(err) / act) * 100.0) if act > 0 else 0.0
            
            tgl_str = df_test.iloc[i]['Tanggal'].strftime('%Y-%m-%d')
            r_id = int(df_test.iloc[i]['Rayon_asli'])
            
            predictions_list.append({
                "tanggal": tgl_str,
                "rayon_id": r_id,
                "rayon": f"Rayon {roman_rayon(r_id)}",
                "actual": act,
                "predicted": prd,
                "error": err,
                "percentage_error": pct_err
            })
            
        train_period = f"{df_train['Tanggal'].min().strftime('%Y-%m-%d')} - {df_train['Tanggal'].max().strftime('%Y-%m-%d')}"
        test_period = f"{df_test['Tanggal'].min().strftime('%Y-%m-%d')} - {df_test['Tanggal'].max().strftime('%Y-%m-%d')}"
        
        return {
            "status": "success",
            "model": "SVR + Grid Search",
            "parameters": {
                "kernel": "rbf",
                "C": float(best_c),
                "epsilon": float(best_eps),
                "gamma": str(best_gamma) if isinstance(best_gamma, str) else float(best_gamma)
            },
            "dataset": {
                "total_rows": int(len(df)),
                "train_rows": int(split_index),
                "test_rows": int(len(df) - split_index),
                "train_period": train_period,
                "test_period": test_period
            },
            "metrics": {
                "mae": mae,
                "rmse": rmse,
                "mape": mape,
                "r2": r2,
                "accuracy": accuracy
            },
            "predictions": predictions_list
        }
    except Exception as e:
        logger.error(f"Error pada training SVR Grid Search: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        raise HTTPException(status_code=500, detail=f"Terjadi kesalahan saat training SVR Grid Search: {str(e)}")


@router.post("/train/gwo")
async def train_gwo(request: Request, token_data: dict = Depends(get_jwt_token)):
    try:
        body = await request.json()
        dataset = body.get("dataset", [])
        wolves = int(body.get("wolves", 12))
        iterations = int(body.get("iterations", 20))
        c_min = float(body.get("c_min", 179.9))
        c_max = float(body.get("c_max", 250.0))
        epsilon_min = float(body.get("epsilon_min", 0.0002))
        epsilon_max = float(body.get("epsilon_max", 0.006))
        gamma_min = float(body.get("gamma_min", 0.004))
        gamma_max = float(body.get("gamma_max", 0.012))
        
        if not dataset:
            raise HTTPException(status_code=400, detail="Dataset tidak boleh kosong.")
            
        pre = preprocess_dataset(dataset)
        X_train, y_train = pre['X_train'], pre['y_train']
        X_test, y_test = pre['X_test'], pre['y_test']
        y_test_asli = pre['y_test_asli']
        scaler_y = pre['scaler_y']
        df_test = pre['df_test']
        df = pre['df']
        split_index = pre['split_index']
        df_train = pre['df_train']
        
        logger.info(f"GWO: menjalankan search dengan {wolves} serigala, {iterations} iterasi, bounds C: [{c_min}, {c_max}], epsilon: [{epsilon_min}, {epsilon_max}], gamma: [{gamma_min}, {gamma_max}]")
        # Run GWO parameter tuning using TimeSeriesSplit(n_splits=5, gap=3) on training set
        tscv_gwo = TimeSeriesSplit(n_splits=5, gap=3)
        
        # log-scale bounds
        LB = np.array([np.log10(c_min), np.log10(epsilon_min), np.log10(gamma_min)])
        UB = np.array([np.log10(c_max), np.log10(epsilon_max), np.log10(gamma_max)])
        DIM = 3
        
        np.random.seed(42)
        positions = np.random.uniform(0, 1, (wolves, DIM)) * (UB - LB) + LB
        
        # Warm start: tanam 3 wolf di referensi optimal
        REF_C_v2     = np.log10(199.5)
        REF_EPS_v2   = np.log10(0.000316)
        REF_GAMMA_v2 = np.log10(0.00677)
        if wolves > 0:
            positions[0] = np.clip([REF_C_v2, REF_EPS_v2, REF_GAMMA_v2], LB, UB)
            
        REF_C_v4     = np.log10(199.5)
        REF_EPS_v4   = np.log10(0.005012)
        REF_GAMMA_v4 = np.log10(0.00481)
        if wolves > 1:
            positions[1] = np.clip([REF_C_v4, REF_EPS_v4, REF_GAMMA_v4], LB, UB)
            
        REF_C_mid     = np.log10(199.5)
        REF_EPS_mid   = np.log10((0.000316 + 0.005012) / 2.0)
        REF_GAMMA_mid = np.log10((0.00677  + 0.00481)  / 2.0)
        if wolves > 2:
            positions[2] = np.clip([REF_C_mid, REF_EPS_mid, REF_GAMMA_mid], LB, UB)
        
        from joblib import Parallel, delayed

        alpha_pos = np.zeros(DIM); alpha_score = float("inf")
        beta_pos  = np.zeros(DIM); beta_score  = float("inf")
        delta_pos = np.zeros(DIM); delta_score = float("inf")
        
        def fitness(pos):
            C_val = 10 ** pos[0]
            eps_val = 10 ** pos[1]
            gamma_val = 10 ** pos[2]
            # Use max_iter=2000 to keep iteration evaluation fast and avoid infinite/long fits.
            model = SVR(kernel='rbf', C=C_val, epsilon=eps_val, gamma=gamma_val, cache_size=1000, max_iter=2000)
            scores = cross_val_score(model, X_train, y_train, cv=tscv_gwo, scoring='neg_root_mean_squared_error', n_jobs=1)
            return -float(np.mean(scores))
            
        no_improve_count = 0
        prev_alpha_score = float("inf")
        EARLY_STOP = 8
        RESTART_EVERY = 3
        RESTART_FRAC = 0.30
        PERTURB_STD = 0.08
        
        for t in range(iterations):
            # Parallelize the evaluation of the wolves
            wolves_score = Parallel(n_jobs=-1)(
                delayed(fitness)(positions[i]) for i in range(wolves)
            )
            
            for i in range(wolves):
                fit = wolves_score[i]
                if fit < alpha_score:
                    delta_score, delta_pos = beta_score, beta_pos.copy()
                    beta_score, beta_pos = alpha_score, alpha_pos.copy()
                    alpha_score, alpha_pos = fit, positions[i].copy()
                elif fit < beta_score:
                    delta_score, delta_pos = beta_score, beta_pos.copy()
                    beta_score, beta_pos = fit, positions[i].copy()
                elif fit < delta_score:
                    delta_score, delta_pos = fit, positions[i].copy()
                    
            # GWO position updates
            a = 2.0 - t * (2.0 / iterations)
            for i in range(wolves):
                for j in range(DIM):
                    r1, r2 = np.random.rand(), np.random.rand()
                    A1 = 2 * a * r1 - a
                    C1 = 2 * r2
                    D_alpha = abs(C1 * alpha_pos[j] - positions[i, j])
                    X1 = alpha_pos[j] - A1 * D_alpha
                    
                    r1, r2 = np.random.rand(), np.random.rand()
                    A2 = 2 * a * r1 - a
                    C2 = 2 * r2
                    D_beta = abs(C2 * beta_pos[j] - positions[i, j])
                    X2 = beta_pos[j] - A2 * D_beta
                    
                    r1, r2 = np.random.rand(), np.random.rand()
                    A3 = 2 * a * r1 - a
                    C3 = 2 * r2
                    D_delta = abs(C3 * delta_pos[j] - positions[i, j])
                    X3 = delta_pos[j] - A3 * D_delta
                    
                    positions[i, j] = np.clip((X1 + X2 + X3) / 3.0, LB[j], UB[j])
                    
            improved = alpha_score < prev_alpha_score - 1e-6
            if improved:
                no_improve_count = 0
                prev_alpha_score = alpha_score
            else:
                no_improve_count += 1
                
                # Random Restart
                if no_improve_count % RESTART_EVERY == 0:
                    n_restart = max(1, int(wolves * RESTART_FRAC))
                    restart_idx = np.random.choice(wolves, n_restart, replace=False)
                    for idx in restart_idx:
                        if np.allclose(positions[idx], alpha_pos, atol=1e-4):
                            continue
                        noise = np.random.normal(0, PERTURB_STD, DIM)
                        positions[idx] = np.clip(alpha_pos + noise, LB, UB)
                        
                # Early Stopping
                if no_improve_count >= EARLY_STOP:
                    logger.info(f"GWO Early stopping triggered at iteration {t+1}")
                    break
        
        best_c = float(10 ** alpha_pos[0])
        best_eps = float(10 ** alpha_pos[1])
        best_gamma = float(10 ** alpha_pos[2])
        
        # Train final model on train set with best parameters
        best_model = SVR(kernel='rbf', C=best_c, epsilon=best_eps, gamma=best_gamma, max_iter=20000)
        best_model.fit(X_train, y_train)
        
        # Predict and evaluate on test set
        y_pred_scaled = best_model.predict(X_test)
        y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
        y_pred_asli = np.expm1(y_pred_log)
        y_pred_asli = np.clip(y_pred_asli, 0, None)
        
        mae = float(mean_absolute_error(y_test_asli, y_pred_asli))
        rmse = float(np.sqrt(mean_squared_error(y_test_asli, y_pred_asli)))
        r2 = float(r2_score(y_test_asli, y_pred_asli))
        
        mask = y_test_asli > 0
        mape = float(np.mean(np.abs((y_test_asli[mask] - y_pred_asli[mask]) / y_test_asli[mask])) * 100.0) if np.sum(mask) > 0 else 0.0
        accuracy = float(max(0.0, 100.0 - mape))
            
        # Fit a final model for predictions & to save as default model if needed
        final_model = SVR(kernel='rbf', C=best_c, epsilon=best_eps, gamma=best_gamma, max_iter=20000)
        final_model.fit(X_train, y_train)
        
        y_pred_scaled = final_model.predict(X_test)
        y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
        y_pred_asli = np.expm1(y_pred_log)
        y_pred_asli = np.clip(y_pred_asli, 0, None)
        
        # Save model and scalers
        os.makedirs(settings.model_artifacts_dir, exist_ok=True)
        joblib.dump(final_model, os.path.join(settings.model_artifacts_dir, 'svr_gwo_model.pkl'))
        
        # Map predictions list
        predictions_list = []
        for i in range(len(df_test)):
            act = float(y_test_asli[i])
            prd = float(y_pred_asli[i])
            err = float(act - prd)
            pct_err = float((abs(err) / act) * 100.0) if act > 0 else 0.0
            
            tgl_str = df_test.iloc[i]['Tanggal'].strftime('%Y-%m-%d')
            r_id = int(df_test.iloc[i]['Rayon_asli'])
            
            predictions_list.append({
                "tanggal": tgl_str,
                "rayon_id": r_id,
                "rayon": f"Rayon {roman_rayon(r_id)}",
                "actual": act,
                "predicted": prd,
                "error": err,
                "percentage_error": pct_err
            })
            
        train_period = f"{df_train['Tanggal'].min().strftime('%Y-%m-%d')} - {df_train['Tanggal'].max().strftime('%Y-%m-%d')}"
        test_period = f"{df_test['Tanggal'].min().strftime('%Y-%m-%d')} - {df_test['Tanggal'].max().strftime('%Y-%m-%d')}"
        
        return {
            "status": "success",
            "model": "SVR + GWO",
            "parameters": {
                "kernel": "rbf",
                "C": float(best_c),
                "epsilon": float(best_eps),
                "gamma": float(best_gamma)
            },
            "dataset": {
                "total_rows": int(len(df)),
                "train_rows": int(split_index),
                "test_rows": int(len(df) - split_index),
                "train_period": train_period,
                "test_period": test_period
            },
            "metrics": {
                "mae": mae,
                "rmse": rmse,
                "mape": mape,
                "r2": r2,
                "accuracy": accuracy
            },
            "predictions": predictions_list
        }
    except Exception as e:
        logger.error(f"Error pada training SVR GWO: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        raise HTTPException(status_code=500, detail=f"Terjadi kesalahan saat training SVR GWO: {str(e)}")
