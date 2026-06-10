<?php

namespace App\Http\Controllers\KepalaUpt;

use App\Http\Controllers\Controller;

class KepalaUptDashboardController extends Controller
{
    public function index()
    {
        // Mock data for the Kepala UPT Dashboard
        $metrics = [
            'total_pendapatan_harian' => 'Rp 6.345.000',
            'hasil_prediksi_terkini' => 'Rp 6.512.000',
            'akurasi_model' => '8,42% (Sangat Akurat)',
        ];

        $incomes = [
            [
                'no' => 1,
                'tanggal' => '2026-06-05',
                'rayon' => 'Rayon I',
                'juru_parkir' => '80 Jukir',
                'jumlah' => 'Rp 1.250.000'
            ],
            [
                'no' => 2,
                'tanggal' => '2026-06-05',
                'rayon' => 'Rayon II',
                'juru_parkir' => '82 Jukir',
                'jumlah' => 'Rp 1.680.000'
            ],
            [
                'no' => 3,
                'tanggal' => '2026-06-05',
                'rayon' => 'Rayon III',
                'juru_parkir' => '66 Jukir',
                'jumlah' => 'Rp 1.150.000'
            ],
            [
                'no' => 4,
                'tanggal' => '2026-06-05',
                'rayon' => 'Rayon IV',
                'juru_parkir' => '122 Jukir',
                'jumlah' => 'Rp 1.390.000'
            ],
            [
                'no' => 5,
                'tanggal' => '2026-06-05',
                'rayon' => 'Rayon V',
                'juru_parkir' => '70 Jukir',
                'jumlah' => 'Rp 875.000'
            ]
        ];

        return view('kepala-upt.dashboard', compact('metrics', 'incomes'));
    }
}
