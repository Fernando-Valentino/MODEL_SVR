<?php

namespace App\Http\Controllers\Operator\MasterData;

use App\Http\Controllers\Controller;

class JuruParkirController extends Controller
{
    public function index()
    {
        $juruParkirs = [
            [
                'no' => 1,
                'nama' => 'Budi Santoso',
                'rayon' => 'Rayon I',
                'status' => 'Aktif'
            ],
            [
                'no' => 2,
                'nama' => 'Agus Wijaya',
                'rayon' => 'Rayon II',
                'status' => 'Aktif'
            ],
            [
                'no' => 3,
                'nama' => 'Hendra Setiawan',
                'rayon' => 'Rayon III',
                'status' => 'Aktif'
            ],
            [
                'no' => 4,
                'nama' => 'Joko Susilo',
                'rayon' => 'Rayon IV',
                'status' => 'Aktif'
            ],
            [
                'no' => 5,
                'nama' => 'Bambang Hermawan',
                'rayon' => 'Rayon V',
                'status' => 'Tidak Aktif'
            ],
            [
                'no' => 6,
                'nama' => 'Eko Prasetyo',
                'rayon' => 'Rayon I',
                'status' => 'Aktif'
            ],
            [
                'no' => 7,
                'nama' => 'Supriadi',
                'rayon' => 'Rayon II',
                'status' => 'Tidak Aktif'
            ]
        ];

        return view('operator.master-data.juru-parkir.index', compact('juruParkirs'));
    }

    public function create() { return 'Juru Parkir Create Form'; }
    public function store() { return 'Juru Parkir Store Handler'; }
    public function show($id) { return 'Juru Parkir Details: ' . $id; }
    public function edit($id) { return 'Juru Parkir Edit Form for ID: ' . $id; }
    public function update($id) { return 'Juru Parkir Update Handler for ID: ' . $id; }
    public function destroy($id) { return 'Juru Parkir Delete Handler for ID: ' . $id; }
}
