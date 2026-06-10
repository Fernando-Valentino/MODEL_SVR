<?php

namespace App\Http\Controllers\KepalaDishub;

use App\Http\Controllers\Controller;

class KepalaDishubOptimasiController extends Controller
{
    public function index()
    {
        // Mock Grid Search Best Parameters
        $grid_best = [
            'c' => '15,0000',
            'epsilon' => '0,0050',
            'gamma' => '0,1500',
            'accuracy' => '89% (R² = 0,89)'
        ];

        // Mock GWO Best Parameters
        $gwo_best = [
            'c' => '22,3472',
            'epsilon' => '0,0021',
            'gamma' => '0,2451',
            'accuracy' => '93% (R² = 0,93)'
        ];

        // Mock comparative table
        $comparisons = [
            [
                'metode' => 'SVR Standar (Default)',
                'c' => '10,0000',
                'epsilon' => '0,0100',
                'gamma' => '0,1000',
                'mae' => 'Rp 120.500',
                'rmse' => 'Rp 180.300',
                'mape' => '8,42%',
                'r2' => '0,84',
                'akurasi' => '91,58%'
            ],
            [
                'metode' => 'SVR + Grid Search',
                'c' => '15,0000',
                'epsilon' => '0,0050',
                'gamma' => '0,1500',
                'mae' => 'Rp 92.100',
                'rmse' => 'Rp 135.200',
                'mape' => '6,15%',
                'r2' => '0,89',
                'akurasi' => '93,85%'
            ],
            [
                'metode' => 'SVR + GWO (Grey Wolf)',
                'c' => '22,3472',
                'epsilon' => '0,0021',
                'gamma' => '0,2451',
                'mae' => 'Rp 72.400',
                'rmse' => 'Rp 102.500',
                'mape' => '4,82%',
                'r2' => '0,93',
                'akurasi' => '95,18%'
            ]
        ];

        return view('kepala-dishub.optimasi.index', compact('grid_best', 'gwo_best', 'comparisons'));
    }
}
