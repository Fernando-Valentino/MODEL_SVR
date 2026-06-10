<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OperatorLaporanController extends Controller
{
    public function index()
    {
        // Mock summary cards
        $summary = [
            'periode' => '01 Jun 2026 - 07 Jun 2026',
            'total_data' => '35 Hari (Kombinasi Rayon)',
            'total_aktual' => 'Rp 48.654.000',
            'total_prediksi' => 'Rp 48.825.000'
        ];

        // Mock model evaluation metrics
        $metrics = [
            'mae' => 'Rp 72.400',
            'rmse' => 'Rp 102.500',
            'mape' => '4,82% (Sangat Akurat)',
            'r2' => '0,93 (Model Kuat)'
        ];

        // Mock daily reports
        $reports = [
            ['tanggal' => '2026-06-01', 'rayon' => 'Rayon I', 'aktual' => 1180000, 'prediksi' => 1205000, 'error' => -25000],
            ['tanggal' => '2026-06-02', 'rayon' => 'Rayon II', 'aktual' => 1674000, 'prediksi' => 1650000, 'error' => 24000],
            ['tanggal' => '2026-06-03', 'rayon' => 'Rayon III', 'aktual' => 1519000, 'prediksi' => 1490000, 'error' => 29000],
            ['tanggal' => '2026-06-04', 'rayon' => 'Rayon IV', 'aktual' => 1399000, 'prediksi' => 1415000, 'error' => -16000],
            ['tanggal' => '2026-06-05', 'rayon' => 'Rayon V', 'aktual' => 1904000, 'prediksi' => 1880000, 'error' => 24000],
            ['tanggal' => '2026-06-06', 'rayon' => 'Rayon I', 'aktual' => 1200000, 'prediksi' => 1218000, 'error' => -18000],
            ['tanggal' => '2026-06-07', 'rayon' => 'Rayon II', 'aktual' => 1730000, 'prediksi' => 1710000, 'error' => 20000],
        ];

        return view('operator.laporan.index', compact('summary', 'metrics', 'reports'));
    }

    public function exportPdf()
    {
        return response()->streamDownload(function () {
            echo "PDF Laporan Prediksi Retribusi Parkir Dishub Cirebon (MOCK)";
        }, 'laporan-prediksi.pdf');
    }

    public function exportExcel()
    {
        return response()->streamDownload(function () {
            echo "Excel Laporan Prediksi Retribusi Parkir Dishub Cirebon (MOCK)";
        }, 'laporan-prediksi.xlsx');
    }
}
