<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OperatorOptimasiController extends Controller
{
    public function index()
    {
        // Mock optimization results comparisons table
        $comparisons = [
            [
                'metode' => 'SVR Standar (Default)',
                'c' => '10,0000',
                'epsilon' => '0,0100',
                'gamma' => '0,1000',
                'mae' => 'Rp 120.500',
                'rmse' => 'Rp 180.300',
                'mape' => '8,42% (Sangat Akurat)',
                'r2' => '0,84 (Model Kuat)'
            ],
            [
                'metode' => 'SVR + Grid Search',
                'c' => '15,0000',
                'epsilon' => '0,0050',
                'gamma' => '0,1500',
                'mae' => 'Rp 92.100',
                'rmse' => 'Rp 135.200',
                'mape' => '6,15% (Sangat Akurat)',
                'r2' => '0,89 (Model Kuat)'
            ],
            [
                'metode' => 'SVR + GWO (Grey Wolf)',
                'c' => '22,3472',
                'epsilon' => '0,0021',
                'gamma' => '0,2451',
                'mae' => 'Rp 72.400',
                'rmse' => 'Rp 102.500',
                'mape' => '4,82% (Sangat Akurat)',
                'r2' => '0,93 (Model Kuat)'
            ]
        ];

        return view('operator.optimasi.index', compact('comparisons'));
    }

    public function runGridSearch(Request $request)
    {
        return redirect()->back()->with('success', 'Optimasi Grid Search selesai dilaksanakan. Model SVR diperbarui dengan parameter terbaik.');
    }

    public function runGwo(Request $request)
    {
        return redirect()->back()->with('success', 'Optimasi Grey Wolf Optimizer (GWO) selesai dilaksanakan. Model SVR diperbarui dengan parameter global optimal.');
    }
}
