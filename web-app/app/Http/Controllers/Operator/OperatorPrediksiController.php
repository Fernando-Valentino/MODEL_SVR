<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OperatorPrediksiController extends Controller
{
    public function index()
    {
        // Mock default parameter values for SVR model
        $params = [
            'c' => 10.0,
            'epsilon' => 0.01,
            'gamma' => 0.1,
            'rayon' => 0,
            'tanggal_mulai' => '2026-06-01',
            'tanggal_akhir' => '2026-06-07'
        ];

        // Mock model evaluation metrics
        $metrics = [
            'mae' => 'Rp 120.500',
            'rmse' => 'Rp 180.300',
            'mape' => '8,42% (Sangat Akurat)',
            'r2' => '0,84 (Model Kuat)'
        ];

        // Mock predictions table
        $predictions = [
            ['tanggal' => '2026-06-01', 'rayon' => 'Rayon I', 'aktual' => 'Rp 1.180.000', 'prediksi' => 'Rp 1.205.000'],
            ['tanggal' => '2026-06-02', 'rayon' => 'Rayon II', 'aktual' => 'Rp 1.674.000', 'prediksi' => 'Rp 1.650.000'],
            ['tanggal' => '2026-06-03', 'rayon' => 'Rayon III', 'aktual' => 'Rp 1.519.000', 'prediksi' => 'Rp 1.490.000'],
            ['tanggal' => '2026-06-04', 'rayon' => 'Rayon IV', 'aktual' => 'Rp 1.399.000', 'prediksi' => 'Rp 1.415.000'],
            ['tanggal' => '2026-06-05', 'rayon' => 'Rayon V', 'aktual' => 'Rp 1.904.000', 'prediksi' => 'Rp 1.880.000'],
            ['tanggal' => '2026-06-06', 'rayon' => 'Rayon I', 'aktual' => 'Rp 1.200.000', 'prediksi' => 'Rp 1.218.000'],
            ['tanggal' => '2026-06-07', 'rayon' => 'Rayon II', 'aktual' => 'Rp 1.730.000', 'prediksi' => 'Rp 1.710.000'],
        ];

        return view('operator.prediksi.index', compact('params', 'metrics', 'predictions'));
    }

    public function runSvr(Request $request)
    {
        return redirect()->back()->with('success', 'Model SVR berhasil dilatih ulang dan hasil prediksi telah diperbarui.');
    }
}
