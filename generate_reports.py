import sys
import os

# Force non-interactive Agg backend BEFORE any other matplotlib import
os.environ['MPLBACKEND'] = 'Agg'
os.environ['MPLCONFIGDIR'] = os.path.join(os.path.abspath(os.path.dirname(__file__)), '.mpl_cache')
os.makedirs(os.environ['MPLCONFIGDIR'], exist_ok=True)

import numpy as np
import pandas as pd
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from sklearn.svm import SVR
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import TimeSeriesSplit, cross_val_score

# Set sys.path to root to import app modules
BASE_DIR = os.path.abspath(os.path.dirname(__file__))
sys.path.append(BASE_DIR)
from app.utils.preprocessing import preprocess_dataset

def main():
    print("=" * 60)
    # 1. Load Data
    csv_path = os.path.join(BASE_DIR, 'research', 'DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv')
    if not os.path.exists(csv_path):
        csv_path = os.path.join(BASE_DIR, 'DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv')
        if not os.path.exists(csv_path):
            print("ERROR: Dataset CSV tidak ditemukan di folder research/ maupun root.")
            return
            
    print(f"Loading dataset dari: {csv_path}")
    df_raw = pd.read_csv(csv_path, parse_dates=['Tanggal'])
    dataset = df_raw.to_dict(orient='records')
    
    # Preprocess
    print("Menjalankan preprocessing data...")
    pre = preprocess_dataset(dataset)
    X_train, y_train = pre['X_train'], pre['y_train']
    X_test, y_test = pre['X_test'], pre['y_test']
    y_test_asli = pre['y_test_asli']
    scaler_y = pre['scaler_y']
    df_test = pre['df_test']
    df = pre['df']
    split_index = pre['split_index']
    df_train = pre['df_train']
    
    test_dates = df_test['Tanggal'].values
    print(f"Dataset preprocessed. Train size: {len(X_train)}, Test size: {len(X_test)}")
    
    # Ensure folders exist using absolute paths
    REPORTS_DIR = os.path.join(BASE_DIR, 'reports')
    PLOTS_DIR = os.path.join(REPORTS_DIR, 'plots')
    os.makedirs(PLOTS_DIR, exist_ok=True)
    print(f"Output folder: {REPORTS_DIR}")
    
    # -------------------------------------------------------------
    # 2. MODEL 1: SVR STANDAR (DEFAULT)
    # -------------------------------------------------------------
    print("\n[1/3] Melatih SVR Standar...")
    model_def = SVR(kernel='rbf', C=1.0, epsilon=0.1, gamma='scale')
    model_def.fit(X_train, y_train)
    
    y_pred_scaled = model_def.predict(X_test)
    y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
    y_pred_def_asli = np.expm1(y_pred_log)
    y_pred_def_asli = np.clip(y_pred_def_asli, 0, None)
    
    mae_def = float(mean_absolute_error(y_test_asli, y_pred_def_asli))
    rmse_def = float(np.sqrt(mean_squared_error(y_test_asli, y_pred_def_asli)))
    r2_def = float(r2_score(y_test_asli, y_pred_def_asli))
    mask = y_test_asli > 0
    mape_def = float(np.mean(np.abs((y_test_asli[mask] - y_pred_def_asli[mask]) / y_test_asli[mask])) * 100.0)
    
    print(f"      SVR Standar: MAPE = {mape_def:.4f}%, RMSE = {rmse_def:.2f}")

    # -------------------------------------------------------------
    # 3. MODEL 2: SVR + GRID SEARCH
    # -------------------------------------------------------------
    print("\n[2/3] Melatih SVR + Grid Search...")
    grid_c = [10, 50, 100, 150, 200]
    grid_epsilon = [0.001, 0.005, 0.01, 0.05]
    grid_gamma = ["scale", 0.001, 0.01, 0.05]
    
    tscv = TimeSeriesSplit(n_splits=5)
    gs_results = []
    
    # Evaluate combinations
    total_comb = len(grid_c) * len(grid_epsilon) * len(grid_gamma)
    print(f"      Mengevaluasi {total_comb} kombinasi parameter Grid Search...")
    
    comb_counter = 0
    for c in grid_c:
        for eps in grid_epsilon:
            for gam in grid_gamma:
                scores = []
                for tr_idx, val_idx in tscv.split(X_train):
                    # Gunakan max_iter=10000 agar pencarian cepat & sesuai backend
                    model = SVR(kernel='rbf', C=c, epsilon=eps, gamma=gam, cache_size=1000, max_iter=10000)
                    model.fit(X_train[tr_idx], y_train[tr_idx])
                    pred = model.predict(X_train[val_idx])
                    fold_rmse = np.sqrt(mean_squared_error(y_train[val_idx], pred))
                    scores.append(fold_rmse)
                
                avg_rmse = float(np.mean(scores))
                gs_results.append({
                    "C": c,
                    "Epsilon": eps,
                    "Gamma": gam,
                    "CV_RMSE": avg_rmse
                })
                comb_counter += 1
                if comb_counter % 20 == 0:
                    print(f"      Progress: {comb_counter}/{total_comb} kombinasi selesai.")
                    
    # Sort by CV_RMSE asc
    gs_df = pd.DataFrame(gs_results)
    gs_df = gs_df.sort_values(by="CV_RMSE").reset_index(drop=True)
    gs_df.index += 1
    gs_df.index.name = "Rank"
    gs_df.to_csv(os.path.join(REPORTS_DIR, 'grid_search_combinations.csv'))
    
    # Get Best Rank 1
    best_gs = gs_df.iloc[0]
    best_c_gs = best_gs["C"]
    best_eps_gs = best_gs["Epsilon"]
    best_gamma_gs = best_gs["Gamma"]
    print(f"      Best Grid Search Params (Rank 1): C={best_c_gs}, epsilon={best_eps_gs}, gamma={best_gamma_gs} (CV RMSE={best_gs['CV_RMSE']:.6f})")
    
    # Train final SVR GS (without max_iter restriction)
    model_gs = SVR(kernel='rbf', C=float(best_c_gs), epsilon=float(best_eps_gs), gamma=best_gamma_gs)
    model_gs.fit(X_train, y_train)
    
    y_pred_scaled = model_gs.predict(X_test)
    y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
    y_pred_gs_asli = np.expm1(y_pred_log)
    y_pred_gs_asli = np.clip(y_pred_gs_asli, 0, None)
    
    mae_gs = float(mean_absolute_error(y_test_asli, y_pred_gs_asli))
    rmse_gs = float(np.sqrt(mean_squared_error(y_test_asli, y_pred_gs_asli)))
    r2_gs = float(r2_score(y_test_asli, y_pred_gs_asli))
    mape_gs = float(np.mean(np.abs((y_test_asli[mask] - y_pred_gs_asli[mask]) / y_test_asli[mask])) * 100.0)
    print(f"      Grid Search Test: MAPE = {mape_gs:.4f}%, RMSE = {rmse_gs:.2f}")

    # -------------------------------------------------------------
    # 4. MODEL 3: SVR + GWO
    # -------------------------------------------------------------
    print("\n[3/3] Melatih SVR + GWO...")
    wolves = 15
    iterations = 30
    c_min, c_max = 10.0, 300.0
    epsilon_min, epsilon_max = 0.0001, 0.05
    gamma_min, gamma_max = 0.0005, 0.1
    
    tscv_gwo = TimeSeriesSplit(n_splits=5)
    LB = np.array([np.log10(c_min), np.log10(epsilon_min), np.log10(gamma_min)])
    UB = np.array([np.log10(c_max), np.log10(epsilon_max), np.log10(gamma_max)])
    DIM = 3
    
    # Seed 42 for GWO
    np.random.seed(42)
    positions = np.random.uniform(0, 1, (wolves, DIM)) * (UB - LB) + LB
    
    # Warm start: v2, v4, midpoint
    REF_C_v2 = np.log10(199.5)
    REF_EPS_v2 = np.log10(0.000316)
    REF_GAMMA_v2 = np.log10(0.00677)
    positions[0] = np.clip([REF_C_v2, REF_EPS_v2, REF_GAMMA_v2], LB, UB)
    
    REF_C_v4 = np.log10(199.5)
    REF_EPS_v4 = np.log10(0.005012)
    REF_GAMMA_v4 = np.log10(0.00481)
    positions[1] = np.clip([REF_C_v4, REF_EPS_v4, REF_GAMMA_v4], LB, UB)
    
    REF_C_mid = np.log10(199.5)
    REF_EPS_mid = np.log10((0.000316 + 0.005012) / 2.0)
    REF_GAMMA_mid = np.log10((0.00677 + 0.00481) / 2.0)
    positions[2] = np.clip([REF_C_mid, REF_EPS_mid, REF_GAMMA_mid], LB, UB)
    
    alpha_pos = np.zeros(DIM); alpha_score = float("inf")
    beta_pos  = np.zeros(DIM); beta_score  = float("inf")
    delta_pos = np.zeros(DIM); delta_score = float("inf")
    
    def fitness(pos):
        C_val = 10 ** pos[0]
        eps_val = 10 ** pos[1]
        gamma_val = 10 ** pos[2]
        # Tanpa max_iter, cache_size=2000 untuk presisi sama persis dengan notebook GWO v5
        model = SVR(kernel='rbf', C=C_val, epsilon=eps_val, gamma=gamma_val, cache_size=2000)
        scores = cross_val_score(model, X_train, y_train, cv=tscv_gwo, scoring='neg_root_mean_squared_error', n_jobs=-1)
        return -float(np.mean(scores))
        
    EARLY_STOP = 8
    RESTART_EVERY = 3
    RESTART_FRAC = 0.30
    PERTURB_STD = 0.08
    prev_alpha_score = float("inf")
    no_improve_count = 0
    
    gwo_log = []
    
    for t in range(iterations):
        wolves_score = []
        for i in range(wolves):
            fit = fitness(positions[i])
            wolves_score.append(fit)
            
        # Update hierarchies
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
                
        # Position updates
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
                
        # Save snapshot
        gwo_log.append({
            "Iteration": t + 1,
            "Alpha_RMSE": alpha_score,
            "Beta_RMSE": beta_score,
            "Delta_RMSE": delta_score,
            "Wolf_Min": min(wolves_score),
            "Wolf_Mean": np.mean(wolves_score),
            "Wolf_Max": max(wolves_score),
            "Best_C": 10 ** alpha_pos[0],
            "Best_Epsilon": 10 ** alpha_pos[1],
            "Best_Gamma": 10 ** alpha_pos[2]
        })
        print(f"      GWO Iter {t+1}/{iterations} selesai. Best RMSE = {alpha_score:.6f}")
        
        # Check improved / restarts / early stop
        improved = alpha_score < prev_alpha_score - 1e-6
        if improved:
            no_improve_count = 0
            prev_alpha_score = alpha_score
        else:
            no_improve_count += 1
            if no_improve_count % RESTART_EVERY == 0:
                n_restart = max(1, int(wolves * RESTART_FRAC))
                restart_idx = np.random.choice(wolves, n_restart, replace=False)
                for idx in restart_idx:
                    if np.allclose(positions[idx], alpha_pos, atol=1e-4):
                        continue
                    noise = np.random.normal(0, PERTURB_STD, DIM)
                    positions[idx] = np.clip(alpha_pos + noise, LB, UB)
                    
            if no_improve_count >= EARLY_STOP:
                print(f"      GWO Konvergen Lebih Awal (Early Stopping) di iterasi {t+1}!")
                break
                
    gwo_df = pd.DataFrame(gwo_log)
    gwo_df.to_csv(os.path.join(REPORTS_DIR, 'gwo_iterations.csv'), index=False)
    
    best_c_gwo = float(10 ** alpha_pos[0])
    best_eps_gwo = float(10 ** alpha_pos[1])
    best_gamma_gwo = float(10 ** alpha_pos[2])
    print(f"      Best GWO Params: C={best_c_gwo:.4f}, epsilon={best_eps_gwo:.6f}, gamma={best_gamma_gwo:.5f} (CV RMSE={alpha_score:.6f})")
    
    # Train final SVR GWO
    model_gwo = SVR(kernel='rbf', C=best_c_gwo, epsilon=best_eps_gwo, gamma=best_gamma_gwo)
    model_gwo.fit(X_train, y_train)
    
    y_pred_scaled = model_gwo.predict(X_test)
    y_pred_log = scaler_y.inverse_transform(y_pred_scaled.reshape(-1, 1)).flatten()
    y_pred_gwo_asli = np.expm1(y_pred_log)
    y_pred_gwo_asli = np.clip(y_pred_gwo_asli, 0, None)
    
    mae_gwo = float(mean_absolute_error(y_test_asli, y_pred_gwo_asli))
    rmse_gwo = float(np.sqrt(mean_squared_error(y_test_asli, y_pred_gwo_asli)))
    r2_gwo = float(r2_score(y_test_asli, y_pred_gwo_asli))
    mape_gwo = float(np.mean(np.abs((y_test_asli[mask] - y_pred_gwo_asli[mask]) / y_test_asli[mask])) * 100.0)
    print(f"      GWO Test: MAPE = {mape_gwo:.4f}%, RMSE = {rmse_gwo:.2f}")

    # -------------------------------------------------------------
    # 5. EXPORT MARKDOWN SUMMARY REPORT
    # -------------------------------------------------------------
    summary_md = f"""# Laporan Hasil Optimasi Parameter SVR

Laporan ini memuat perbandingan metrik performa evaluasi antara **SVR Standar (Default)**, **SVR + Grid Search**, dan **SVR + Grey Wolf Optimizer (GWO)** pada 765 data pengujian (Testing Dataset).

## 1. Perbandingan Parameter Terbaik

| Model | C | Epsilon (ε) | Gamma (γ) |
|---|---|---|---|
| SVR Standar | 1.0 | 0.1 | scale |
| SVR + Grid Search | {best_c_gs} | {best_eps_gs} | {best_gamma_gs} |
| SVR + GWO | {best_c_gwo:.6f} | {best_eps_gwo:.6f} | {best_gamma_gwo:.6f} |

## 2. Tabel Hasil Performa Evaluasi (Testing Set)

| Model | MAE (Rupiah) | RMSE (Rupiah) | MAPE (%) | R² Score |
|---|---|---|---|---|
| **SVR Standar** | Rp{mae_def:,.2f} | Rp{rmse_def:,.2f} | {mape_def:.4f}% | {r2_def:.4f} |
| **SVR + Grid Search** | Rp{mae_gs:,.2f} | Rp{rmse_gs:,.2f} | {mape_gs:.4f}% | {r2_gs:.4f} |
| **SVR + GWO** | Rp{mae_gwo:,.2f} | Rp{rmse_gwo:,.2f} | {mape_gwo:.4f}% | {r2_gwo:.4f} |

## 3. Detail File Export Hasil
- **Tabel Seluruh Eksplorasi Grid Search:** [`reports/grid_search_combinations.csv`](grid_search_combinations.csv)
- **Tabel Log Iterasi & Konvergensi GWO:** [`reports/gwo_iterations.csv`](gwo_iterations.csv)
- **Grafik Scatter Prediksi vs Aktual:** Dibuat secara terpisah di folder `reports/plots/`
"""
    summary_path = os.path.join(REPORTS_DIR, 'report_summary.md')
    with open(summary_path, "w", encoding="utf-8") as f:
        f.write(summary_md)
    print(f"\nLaporan ringkasan berhasil disimpan ke: {summary_path}")

    # -------------------------------------------------------------
    # 6. GENERATE BEAUTIFUL MATPLOTLIB PLOTS
    # -------------------------------------------------------------
    print("\nMembuat visualisasi grafik...")
    
    plt.rcParams['font.sans-serif'] = 'Arial'
    plt.rcParams['font.family'] = 'sans-serif'
    
    def make_scatter(y_true, y_pred, title, out_path):
        fig, ax = plt.subplots(figsize=(7, 6))
        ax.scatter(y_true, y_pred, color='#005BAA', alpha=0.6, edgecolors='none', s=25)
        max_val = max(y_true.max(), y_pred.max())
        min_val = min(y_true.min(), y_pred.min())
        ax.plot([min_val, max_val], [min_val, max_val], 'r--', lw=2, label='Prediksi Sempurna (y=x)')
        ax.set_title(title, fontsize=12, fontweight='bold', pad=15)
        ax.set_xlabel('Pendapatan Aktual (Rupiah)', fontsize=10)
        ax.set_ylabel('Pendapatan Prediksi (Rupiah)', fontsize=10)
        ax.grid(True, linestyle=':', alpha=0.6)
        ax.yaxis.set_major_formatter(plt.FuncFormatter(lambda x, p: f'Rp{x:,.0f}'))
        ax.xaxis.set_major_formatter(plt.FuncFormatter(lambda x, p: f'Rp{x:,.0f}'))
        plt.xticks(rotation=15)
        ax.legend()
        fig.tight_layout()
        fig.savefig(out_path, dpi=300, bbox_inches='tight', format='png')
        plt.close(fig)
        plt.close('all')
        sz = os.path.getsize(out_path) if os.path.exists(out_path) else 'FILE NOT FOUND'
        print(f"      Saved: {out_path}  [{sz} bytes]")
    
    # 6a. Scatter plots
    make_scatter(y_test_asli, y_pred_def_asli,
                 'Scatter Plot Prediksi vs Aktual: SVR Standar',
                 os.path.join(PLOTS_DIR, 'scatter_default.png'))
    make_scatter(y_test_asli, y_pred_gs_asli,
                 'Scatter Plot Prediksi vs Aktual: SVR + Grid Search',
                 os.path.join(PLOTS_DIR, 'scatter_grid_search.png'))
    make_scatter(y_test_asli, y_pred_gwo_asli,
                 'Scatter Plot Prediksi vs Aktual: SVR + GWO',
                 os.path.join(PLOTS_DIR, 'scatter_gwo.png'))
    
    # 6b. Line Chart Comparison — 765 data points
    fig2, ax2 = plt.subplots(figsize=(15, 6))
    ax2.plot(test_dates, y_test_asli, label='Aktual', color='#1E293B', lw=1.5, alpha=0.85)
    ax2.plot(test_dates, y_pred_def_asli, label='Prediksi SVR Standar', color='#EF4444', lw=1.0, alpha=0.7)
    ax2.plot(test_dates, y_pred_gs_asli, label='Prediksi SVR + Grid Search', color='#3B82F6', lw=1.2, alpha=0.75)
    ax2.plot(test_dates, y_pred_gwo_asli, label='Prediksi SVR + GWO', color='#10B981', lw=1.2, alpha=0.85)
    ax2.set_title('Perbandingan Pendapatan Aktual vs Hasil Prediksi 3 Model SVR\n(765 Data Pengujian / Testing Set)', fontsize=13, fontweight='bold', pad=15)
    ax2.set_xlabel('Tanggal Evaluasi', fontsize=11)
    ax2.set_ylabel('Total Pendapatan Parkir (Rupiah)', fontsize=11)
    ax2.grid(True, linestyle=':', alpha=0.5)
    ax2.yaxis.set_major_formatter(plt.FuncFormatter(lambda x, p: f'Rp{x:,.0f}'))
    ax2.legend(frameon=True, facecolor='white', framealpha=0.9)
    fig2.tight_layout()
    line_path = os.path.join(PLOTS_DIR, 'comparison_line_chart.png')
    fig2.savefig(line_path, dpi=300, bbox_inches='tight')
    plt.close(fig2)
    print(f"      Saved: {line_path}")
    
    # 6c. GWO Convergence Plot — Alpha RMSE per Iterasi
    if len(gwo_log) > 0:
        iters = [d['Iteration'] for d in gwo_log]
        alpha_rmse_series = [d['Alpha_RMSE'] for d in gwo_log]
        
        fig3, ax3 = plt.subplots(figsize=(9, 5))
        ax3.plot(iters, alpha_rmse_series, 'o-', color='#6366F1', lw=2, ms=5, label='Alpha RMSE (Best Wolf)')
        ax3.set_title('Kurva Konvergensi GWO\n(RMSE Terbaik per Iterasi)', fontsize=12, fontweight='bold', pad=12)
        ax3.set_xlabel('Iterasi ke-', fontsize=10)
        ax3.set_ylabel('CV-RMSE (Scaled)', fontsize=10)
        ax3.grid(True, linestyle=':', alpha=0.6)
        ax3.legend()
        fig3.tight_layout()
        conv_path = os.path.join(PLOTS_DIR, 'gwo_convergence.png')
        fig3.savefig(conv_path, dpi=300, bbox_inches='tight')
        plt.close(fig3)
        print(f"      Saved: {conv_path}")
    
    print("\nSemua proses selesai! Silakan periksa folder ml-engine/reports/ untuk mengambil laporan dan grafiknya.")
    print("=" * 60)

if __name__ == '__main__':
    main()
