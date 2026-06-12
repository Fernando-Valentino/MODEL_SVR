<?php

namespace App\Http\Controllers\KepalaUpt;

use App\Http\Controllers\Controller;

class KepalaUptPrediksiController extends Controller
{
    public function index()
    {
        // Mock SVR parameters
        $best_params = [
            'c' => '22,3472',
            'epsilon' => '0,0021',
            'gamma' => '0,2451',
            'metode_terbaik' => 'SVR + GWO (Grey Wolf)'
        ];

        // Mock evaluation metrics
        $metrics = [
            'mae' => 'Rp 72.400',
            'rmse' => 'Rp 102.500',
            'mape' => '4,82% (Sangat Akurat)',
            'r2' => '0,93 (Model Kuat)'
        ];

        // Mock monthly predictions (5 Months)
        $predictions_monthly = [
            ['bulan' => 'Januari 2026', 'aktual' => 'Rp 168.450.000', 'prediksi' => 'Rp 170.210.000'],
            ['bulan' => 'Februari 2026', 'aktual' => 'Rp 154.210.000', 'prediksi' => 'Rp 152.900.000'],
            ['bulan' => 'Maret 2026', 'aktual' => 'Rp 182.110.000', 'prediksi' => 'Rp 179.800.000'],
            ['bulan' => 'April 2026', 'aktual' => 'Rp 176.400.000', 'prediksi' => 'Rp 178.100.000'],
            ['bulan' => 'Mei 2026', 'aktual' => 'Rp 192.800.000', 'prediksi' => 'Rp 190.500.000'],
        ];

        return view('kepala-upt.prediksi.index', compact('best_params', 'metrics', 'predictions_monthly'));
    }
}
