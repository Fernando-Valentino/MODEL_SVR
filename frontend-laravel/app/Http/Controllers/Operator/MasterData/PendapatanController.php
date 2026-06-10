<?php

namespace App\Http\Controllers\Operator\MasterData;

use App\Http\Controllers\Controller;

class PendapatanController extends Controller
{
    public function index()
    {
        $pendapatans = [
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
            ],
            [
                'no' => 6,
                'tanggal' => '2026-06-04',
                'rayon' => 'Rayon I',
                'juru_parkir' => '80 Jukir',
                'jumlah' => 'Rp 1.210.000'
            ],
            [
                'no' => 7,
                'tanggal' => '2026-06-04',
                'rayon' => 'Rayon II',
                'juru_parkir' => '82 Jukir',
                'jumlah' => 'Rp 1.670.000'
            ]
        ];

        return view('operator.master-data.pendapatan.index', compact('pendapatans'));
    }

    public function create() { return 'Pendapatan Create Form'; }
    public function store() { return 'Pendapatan Store Handler'; }
    public function show($id) { return 'Pendapatan Details: ' . $id; }
    public function edit($id) { return 'Pendapatan Edit Form for ID: ' . $id; }
    public function update($id) { return 'Pendapatan Update Handler for ID: ' . $id; }
    public function destroy($id) { return 'Pendapatan Delete Handler for ID: ' . $id; }
    public function import() { return 'Pendapatan Import Handler'; }
}
