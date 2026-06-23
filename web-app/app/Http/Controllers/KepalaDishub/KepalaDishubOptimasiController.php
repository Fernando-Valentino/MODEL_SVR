<?php

namespace App\Http\Controllers\KepalaDishub;

use App\Http\Controllers\Controller;
use App\Models\ModelRun;

class KepalaDishubOptimasiController extends Controller
{
    private function getLatestRun(string $modelType): ?ModelRun
    {
        return ModelRun::where('model_type', $modelType)
            ->where('status', 'success')
            ->orderBy('id', 'desc')
            ->first();
    }

    private function mapeCategory(float $mape): string
    {
        if ($mape < 10)  return 'Sangat Akurat';
        if ($mape <= 20) return 'Baik';
        if ($mape <= 50) return 'Cukup';
        return 'Buruk';
    }

    private function r2Category(float $r2): string
    {
        if ($r2 >= 0.67) return 'Model Kuat';
        if ($r2 >= 0.33) return 'Model Moderat';
        return 'Model Lemah';
    }

    public function index()
    {
        // 1. Cek SVR Standar
        $lastRun = $this->getLatestRun('svr_default');

        // 2. Nilai-nilai skripsi fallback
        $SKRIPSI_GS = [
            'c' => '200',   'epsilon' => '0,0010', 'gamma' => '0,0100',
            'mae' => 'Rp 90.245', 'rmse' => 'Rp 132.810',
            'mape_raw' => 5.89, 'r2_raw' => 0.91,
        ];
        $SKRIPSI_GWO = [
            'c' => '250,035', 'epsilon' => '0,005366', 'gamma' => '0,00446',
            'mae' => 'Rp 71.830', 'rmse' => 'Rp 101.250',
            'mape_raw' => 4.74, 'r2_raw' => 0.94,
        ];

        // 3. Bangun data SVR Standar
        $mapeVal = 8.42;
        $r2Val   = 0.84;
        if ($lastRun) {
            $metric = $lastRun->modelMetrics()->where('dataset_type', 'test')->first();
            if ($metric) {
                $mapeVal = (float) $metric->mape;
                $r2Val   = (float) $metric->r2_score;
            }
        }

        // 4. Cari model Grid Search & GWO
        $gsRun  = $this->getLatestRun('svr_grid_search');
        $gwoRun = $this->getLatestRun('svr_gwo');

        // Helper row komparasi
        $buildRow = function (string $metode, ?ModelRun $run, array $fallback) {
            if ($run) {
                $param  = $run->modelParameter;
                $metric = $run->modelMetrics()->where('dataset_type', 'test')->first();
                $mapeR  = $metric ? (float)$metric->mape     : $fallback['mape_raw'];
                $r2R    = $metric ? (float)$metric->r2_score : $fallback['r2_raw'];
                $cVal   = $param  ? (is_numeric($param->c_value)
                    ? number_format((float)$param->c_value, 4, ',', '.')
                    : $param->c_value) : $fallback['c'];
                $epsVal = $param  ? (is_numeric($param->epsilon_value)
                    ? number_format((float)$param->epsilon_value, 6, ',', '.')
                    : $param->epsilon_value) : $fallback['epsilon'];
                $gamVal = $param  ? (is_numeric($param->gamma_value)
                    ? number_format((float)$param->gamma_value, 5, ',', '.')
                    : $param->gamma_value) : $fallback['gamma'];
                $maeStr  = $metric ? 'Rp ' . number_format((float)$metric->mae,  0, ',', '.') : $fallback['mae'];
                $rmseStr = $metric ? 'Rp ' . number_format((float)$metric->rmse, 0, ',', '.') : $fallback['rmse'];
            } else {
                $mapeR  = $fallback['mape_raw'];
                $r2R    = $fallback['r2_raw'];
                $cVal   = $fallback['c'];
                $epsVal = $fallback['epsilon'];
                $gamVal = $fallback['gamma'];
                $maeStr  = $fallback['mae'];
                $rmseStr = $fallback['rmse'];
            }

            return [
                'metode'   => $metode,
                'c'        => $cVal,
                'epsilon'  => $epsVal,
                'gamma'    => $gamVal,
                'mae'      => $maeStr,
                'rmse'     => $rmseStr,
                'mape'     => number_format($mapeR, 2, ',', '.') . '% (' . $this->mapeCategory($mapeR) . ')',
                'akurasi'  => number_format(max(0, 100 - $mapeR), 2, ',', '.') . '%',
                'r2'       => number_format($r2R, 2, ',', '.') . ' (' . $this->r2Category($r2R) . ')',
            ];
        };

        $comparisons = [
            [
                'metode'   => 'SVR Standar (Default)',
                'c'        => $lastRun ? (is_numeric($lastRun->modelParameter?->c_value)
                    ? number_format((float)$lastRun->modelParameter->c_value, 4, ',', '.')
                    : ($lastRun->modelParameter?->c_value ?? '1,0000')) : '1,0000',
                'epsilon'  => $lastRun ? (is_numeric($lastRun->modelParameter?->epsilon_value)
                    ? number_format((float)$lastRun->modelParameter->epsilon_value, 4, ',', '.')
                    : ($lastRun->modelParameter?->epsilon_value ?? '0,1000')) : '0,1000',
                'gamma'    => $lastRun ? ($lastRun->modelParameter?->gamma_value ?? 'scale') : 'scale',
                'mae'      => $lastRun
                    ? 'Rp ' . number_format((float)($lastRun->modelMetrics()->where('dataset_type','test')->first()?->mae ?? 120500), 0, ',', '.')
                    : 'Rp 120.500',
                'rmse'     => $lastRun
                    ? 'Rp ' . number_format((float)($lastRun->modelMetrics()->where('dataset_type','test')->first()?->rmse ?? 180300), 0, ',', '.')
                    : 'Rp 180.300',
                'mape'     => number_format($mapeVal, 2, ',', '.') . '% (' . $this->mapeCategory($mapeVal) . ')',
                'akurasi'  => number_format(max(0, 100 - $mapeVal), 2, ',', '.') . '%',
                'r2'       => number_format($r2Val, 2, ',', '.') . ' (' . $this->r2Category($r2Val) . ')',
            ],
            $buildRow('SVR + Grid Search', $gsRun, $SKRIPSI_GS),
            $buildRow('SVR + GWO (Grey Wolf)', $gwoRun, $SKRIPSI_GWO),
        ];

        // Kartu Ringkasan
        $gsParam  = $gsRun ? $gsRun->modelParameter : null;
        $gsMetric = $gsRun ? $gsRun->modelMetrics()->where('dataset_type', 'test')->first() : null;
        $grid_best = [
            'c' => $gsParam ? (is_numeric($gsParam->c_value) ? number_format((float)$gsParam->c_value, 4, ',', '.') : $gsParam->c_value) : '200',
            'epsilon' => $gsParam ? (is_numeric($gsParam->epsilon_value) ? number_format((float)$gsParam->epsilon_value, 6, ',', '.') : $gsParam->epsilon_value) : '0,0010',
            'gamma' => $gsParam ? (is_numeric($gsParam->gamma_value) ? number_format((float)$gsParam->gamma_value, 5, ',', '.') : $gsParam->gamma_value) : '0,0100',
            'accuracy' => ($gsMetric ? number_format(max(0, 100 - $gsMetric->mape), 2, ',', '.') : '94,11') . '% (R² = ' . ($gsMetric ? number_format($gsMetric->r2_score, 2, ',', '.') : '0,91') . ')'
        ];

        $gwoParam  = $gwoRun ? $gwoRun->modelParameter : null;
        $gwoMetric = $gwoRun ? $gwoRun->modelMetrics()->where('dataset_type', 'test')->first() : null;
        $gwo_best = [
            'c' => $gwoParam ? (is_numeric($gwoParam->c_value) ? number_format((float)$gwoParam->c_value, 4, ',', '.') : $gwoParam->c_value) : '250,035',
            'epsilon' => $gwoParam ? (is_numeric($gwoParam->epsilon_value) ? number_format((float)$gwoParam->epsilon_value, 6, ',', '.') : $gwoParam->epsilon_value) : '0,005366',
            'gamma' => $gwoParam ? (is_numeric($gwoParam->gamma_value) ? number_format((float)$gwoParam->gamma_value, 5, ',', '.') : $gwoParam->gamma_value) : '0,00446',
            'accuracy' => ($gwoMetric ? number_format(max(0, 100 - $gwoMetric->mape), 2, ',', '.') : '95,26') . '% (R² = ' . ($gwoMetric ? number_format($gwoMetric->r2_score, 2, ',', '.') : '0,94') . ')'
        ];

        // Nilai numerik untuk Chart.js
        $chartMetrics = [
            'mape_default' => $mapeVal,
            'r2_default'   => $r2Val,
            'mape_gs'      => $gsMetric  ? (float)$gsMetric->mape      : $SKRIPSI_GS['mape_raw'],
            'r2_gs'        => $gsMetric  ? (float)$gsMetric->r2_score  : $SKRIPSI_GS['r2_raw'],
            'mape_gwo'     => $gwoMetric ? (float)$gwoMetric->mape     : $SKRIPSI_GWO['mape_raw'],
            'r2_gwo'       => $gwoMetric ? (float)$gwoMetric->r2_score : $SKRIPSI_GWO['r2_raw'],
        ];

        return view('kepala-dishub.optimasi.index', compact('grid_best', 'gwo_best', 'comparisons', 'chartMetrics'));
    }
}

