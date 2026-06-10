<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rayon;
use App\Models\JuruParkir;

class RayonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rayons = [
            [
                'nama_rayon' => 'Rayon I',
                'kecamatan' => 'Kecamatan Kejaksan',
                'lokasi' => 'Jl. Siliwangi, Jl. Kartini',
                'karakteristik_area' => 'Pusat bisnis dan perkantoran',
                'jumlah_juru_parkir' => 80
            ],
            [
                'nama_rayon' => 'Rayon II',
                'kecamatan' => 'Kecamatan Lemahwungkuk & Pekalipan',
                'lokasi' => 'Jl. Karanggetas, Jl. Panjunan',
                'karakteristik_area' => 'Area perdagangan utama',
                'jumlah_juru_parkir' => 82
            ],
            [
                'nama_rayon' => 'Rayon III',
                'kecamatan' => 'Kecamatan Kesambi & Pekalipan',
                'lokasi' => 'Kanoman, Pulasaren',
                'karakteristik_area' => 'Pasar tradisional dan pusat aktivitas',
                'jumlah_juru_parkir' => 66
            ],
            [
                'nama_rayon' => 'Rayon IV',
                'kecamatan' => 'Kecamatan Harjamukti & Kesambi',
                'lokasi' => 'Pekalipan, Ciremai Raya',
                'karakteristik_area' => 'Wilayah selatan dan pemukiman',
                'jumlah_juru_parkir' => 122
            ],
            [
                'nama_rayon' => 'Rayon V',
                'kecamatan' => 'Kecamatan Lemahwungkuk',
                'lokasi' => 'Pasuketan, Pandesan',
                'karakteristik_area' => 'Titik parkir padat wisata/kuliner',
                'jumlah_juru_parkir' => 70
            ],
        ];

        foreach ($rayons as $data) {
            $rayon = Rayon::updateOrCreate(
                ['nama_rayon' => $data['nama_rayon']],
                [
                    'kecamatan' => $data['kecamatan'],
                    'lokasi' => $data['lokasi'],
                    'karakteristik_area' => $data['karakteristik_area'],
                    'jumlah_juru_parkir' => $data['jumlah_juru_parkir']
                ]
            );

            // Seed a matching JuruParkir record for foreign key references in tbl_pendapatan
            JuruParkir::updateOrCreate(
                ['rayon_id' => $rayon->id],
                ['jumlah_juru_parkir' => $data['jumlah_juru_parkir']]
            );
        }
    }
}
