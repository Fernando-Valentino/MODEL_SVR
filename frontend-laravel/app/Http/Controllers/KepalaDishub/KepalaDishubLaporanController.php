<?php

namespace App\Http\Controllers\KepalaDishub;

use App\Http\Controllers\Controller;

class KepalaDishubLaporanController extends Controller
{
    public function index()
    {
        // Mock summary card values
        $summary = [
            'periode' => '01 Jun 2026 - 07 Jun 2026',
            'total_data' => '35 Hari',
            'total_aktual' => 'Rp 10.606.000',
            'total_prediksi' => 'Rp 10.658.000',
            'mape' => '4,82%'
        ];

        // Mock daily reports
        $reports = [
            ['no' => 1, 'tanggal' => '2026-06-01', 'rayon' => 'Rayon I', 'aktual' => 1180000, 'prediksi' => 1205000, 'error' => -25000, 'pct_error' => '2,12%'],
            ['no' => 2, 'tanggal' => '2026-06-02', 'rayon' => 'Rayon II', 'aktual' => 1674000, 'prediksi' => 1650000, 'error' => 24000, 'pct_error' => '1,43%'],
            ['no' => 3, 'tanggal' => '2026-06-03', 'rayon' => 'Rayon III', 'aktual' => 1519000, 'prediksi' => 1490000, 'error' => 29000, 'pct_error' => '1,91%'],
            ['no' => 4, 'tanggal' => '2026-06-04', 'rayon' => 'Rayon IV', 'aktual' => 1399000, 'prediksi' => 1415000, 'error' => -16000, 'pct_error' => '1,14%'],
            ['no' => 5, 'tanggal' => '2026-06-05', 'rayon' => 'Rayon V', 'aktual' => 1904000, 'prediksi' => 1880000, 'error' => 24000, 'pct_error' => '1,26%'],
            ['no' => 6, 'tanggal' => '2026-06-06', 'rayon' => 'Rayon I', 'aktual' => 1200000, 'prediksi' => 1218000, 'error' => -18000, 'pct_error' => '1,50%'],
            ['no' => 7, 'tanggal' => '2026-06-07', 'rayon' => 'Rayon II', 'aktual' => 1730000, 'prediksi' => 1710000, 'error' => 20000, 'pct_error' => '1,16%'],
        ];

        $total_period = [
            'aktual' => 'Rp 10.606.000',
            'prediksi' => 'Rp 10.658.000',
            'error' => 'Rp 38.000',
            'pct_error' => '1,50%'
        ];

        return view('kepala-dishub.laporan.index', compact('summary', 'reports', 'total_period'));
    }

    public function exportPdf()
    {
        return response()->streamDownload(function () {
            echo "PDF Laporan Prediksi Retribusi Kepala Dishub (MOCK)";
        }, 'laporan-prediksi-dishub.pdf');
    }
}
