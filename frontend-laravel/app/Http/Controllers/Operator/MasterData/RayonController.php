<?php

namespace App\Http\Controllers\Operator\MasterData;

use App\Http\Controllers\Controller;

class RayonController extends Controller
{
    public function index()
    {
        $rayons = [
            [
                'no' => 1,
                'nama_rayon' => 'Rayon I',
                'cakupan_wilayah' => 'Kec. Kejaksan',
                'karakteristik_area' => 'Pusat bisnis dan perkantoran',
                'jumlah_juru_parkir' => 80
            ],
            [
                'no' => 2,
                'nama_rayon' => 'Rayon II',
                'cakupan_wilayah' => 'Kec. Lemahwungkuk & Pekalipan',
                'karakteristik_area' => 'Area perdagangan utama',
                'jumlah_juru_parkir' => 82
            ],
            [
                'no' => 3,
                'nama_rayon' => 'Rayon III',
                'cakupan_wilayah' => 'Kec. Kesambi & Pekalipan',
                'karakteristik_area' => 'Pasar tradisional dan pusat aktivitas',
                'jumlah_juru_parkir' => 66
            ],
            [
                'no' => 4,
                'nama_rayon' => 'Rayon IV',
                'cakupan_wilayah' => 'Kec. Harjamukti & Kesambi',
                'karakteristik_area' => 'Wilayah selatan dan pemukiman',
                'jumlah_juru_parkir' => 122
            ],
            [
                'no' => 5,
                'nama_rayon' => 'Rayon V',
                'cakupan_wilayah' => 'Kec. Lemahwungkuk',
                'karakteristik_area' => 'Wisata/kuliner',
                'jumlah_juru_parkir' => 70
            ]
        ];

        return view('operator.master-data.rayon.index', compact('rayons'));
    }

    public function create() { return 'Rayon Create Form'; }
    public function store() { return 'Rayon Store Handler'; }
    public function show($id) { return 'Rayon Details: ' . $id; }
    public function edit($id) { return 'Rayon Edit Form for ID: ' . $id; }
    public function update($id) { return 'Rayon Update Handler for ID: ' . $id; }
    public function destroy($id) { return 'Rayon Delete Handler for ID: ' . $id; }
}
