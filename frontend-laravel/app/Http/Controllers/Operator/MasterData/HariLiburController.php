<?php

namespace App\Http\Controllers\Operator\MasterData;

use App\Http\Controllers\Controller;

class HariLiburController extends Controller
{
    public function index()
    {
        $hariLiburs = [
            [
                'no' => 1,
                'tanggal' => '2026-01-01',
                'hari' => 'Kamis',
                'keterangan' => 'Tahun Baru Masehi',
                'tipe' => 'Libur Nasional'
            ],
            [
                'no' => 2,
                'tanggal' => '2026-01-03',
                'hari' => 'Sabtu',
                'keterangan' => 'Akhir Pekan',
                'tipe' => 'Weekend'
            ],
            [
                'no' => 3,
                'tanggal' => '2026-01-04',
                'hari' => 'Minggu',
                'keterangan' => 'Akhir Pekan',
                'tipe' => 'Weekend'
            ],
            [
                'no' => 4,
                'tanggal' => '2026-02-17',
                'hari' => 'Selasa',
                'keterangan' => 'Isra Mikraj Nabi Muhammad SAW',
                'tipe' => 'Libur Nasional'
            ],
            [
                'no' => 5,
                'tanggal' => '2026-02-24',
                'hari' => 'Selasa',
                'keterangan' => 'Hari Raya Nyepi',
                'tipe' => 'Libur Nasional'
            ],
            [
                'no' => 6,
                'tanggal' => '2026-03-29',
                'hari' => 'Minggu',
                'keterangan' => 'Hari Raya Idul Fitri',
                'tipe' => 'Libur Nasional'
            ],
            [
                'no' => 7,
                'tanggal' => '2026-03-30',
                'hari' => 'Senin',
                'keterangan' => 'Cuti Bersama Idul Fitri',
                'tipe' => 'Libur Nasional'
            ]
        ];

        return view('operator.master-data.hari-libur.index', compact('hariLiburs'));
    }

    public function create() { return 'Hari Libur Create Form'; }
    public function store() { return 'Hari Libur Store Handler'; }
    public function show($id) { return 'Hari Libur Details: ' . $id; }
    public function edit($id) { return 'Hari Libur Edit Form for ID: ' . $id; }
    public function update($id) { return 'Hari Libur Update Handler for ID: ' . $id; }
    public function destroy($id) { return 'Hari Libur Delete Handler for ID: ' . $id; }
}
