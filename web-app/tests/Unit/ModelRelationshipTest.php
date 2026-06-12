<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\HariLibur;
use App\Models\Rayon;
use App\Models\JuruParkir;
use App\Models\Pendapatan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'operator',      'guard_name' => 'web']);
        Role::create(['name' => 'kepala_upt',    'guard_name' => 'web']);
        Role::create(['name' => 'kepala_dishub', 'guard_name' => 'web']);
    }

    // ══════════════════════════════════════════
    // USER MODEL
    // ══════════════════════════════════════════

    public function test_user_dapat_memiliki_role_operator(): void
    {
        $user = User::factory()->create(['username' => 'op']);
        $user->assignRole('operator');

        $this->assertTrue($user->hasRole('operator'));
        $this->assertFalse($user->hasRole('kepala_upt'));
    }

    public function test_user_dapat_memiliki_role_kepala_upt(): void
    {
        $user = User::factory()->create(['username' => 'ku']);
        $user->assignRole('kepala_upt');

        $this->assertTrue($user->hasRole('kepala_upt'));
        $this->assertFalse($user->hasRole('operator'));
    }

    public function test_user_dapat_memiliki_role_kepala_dishub(): void
    {
        $user = User::factory()->create(['username' => 'kd']);
        $user->assignRole('kepala_dishub');

        $this->assertTrue($user->hasRole('kepala_dishub'));
    }

    // ══════════════════════════════════════════
    // HARI LIBUR MODEL
    // ══════════════════════════════════════════

    public function test_model_hari_libur_dapat_dibuat(): void
    {
        $hariLibur = HariLibur::create([
            'tanggal'    => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan',
            'tipe'       => 'Libur Nasional',
        ]);

        $this->assertInstanceOf(HariLibur::class, $hariLibur);
        $this->assertDatabaseHas('hari_liburs', ['tanggal' => '2026-08-17']);
    }

    public function test_model_hari_libur_tipe_adalah_weekend_atau_libur_nasional(): void
    {
        $libur   = HariLibur::create(['tanggal' => '2026-01-01', 'keterangan' => 'Tahun Baru',  'tipe' => 'Libur Nasional']);
        $weekend = HariLibur::create(['tanggal' => '2026-01-03', 'keterangan' => 'Weekend',     'tipe' => 'Weekend']);

        $this->assertEquals('Libur Nasional', $libur->tipe);
        $this->assertEquals('Weekend', $weekend->tipe);
    }

    // ══════════════════════════════════════════
    // RAYON MODEL
    // ══════════════════════════════════════════

    public function test_model_rayon_dapat_dibuat(): void
    {
        $rayon = Rayon::create([
            'nama_rayon'         => 'Rayon I',
            'kecamatan'          => 'Kejaksan',
            'lokasi'             => 'Jl. Siliwangi',
            'karakteristik_area' => 'Komersial',
            'jumlah_juru_parkir' => 5,
        ]);

        $this->assertInstanceOf(Rayon::class, $rayon);
        $this->assertEquals('Rayon I', $rayon->nama_rayon);
    }

    public function test_rayon_memiliki_relasi_juru_parkir(): void
    {
        $rayon = Rayon::create([
            'nama_rayon'         => 'Rayon II',
            'kecamatan'          => 'Pekalipan',
            'lokasi'             => 'Jl. Pasuketan',
            'karakteristik_area' => 'Komersial',
            'jumlah_juru_parkir' => 3,
        ]);

        JuruParkir::create([
            'rayon_id'           => $rayon->id,
            'jumlah_juru_parkir' => 3,
        ]);

        $this->assertCount(1, $rayon->juruParkirs);
        $this->assertInstanceOf(JuruParkir::class, $rayon->juruParkirs->first());
    }

    public function test_rayon_memiliki_relasi_pendapatan(): void
    {
        $rayon = Rayon::create([
            'nama_rayon'         => 'Rayon III',
            'kecamatan'          => 'Harjamukti',
            'lokasi'             => 'Jl. Cipto',
            'karakteristik_area' => 'Residensial',
            'jumlah_juru_parkir' => 4,
        ]);

        $juruParkir = JuruParkir::create([
            'rayon_id'           => $rayon->id,
            'jumlah_juru_parkir' => 4,
        ]);

        Pendapatan::create([
            'tanggal'        => '2025-01-01',
            'rayon_id'       => $rayon->id,
            'juru_parkir_id' => $juruParkir->id,
            'jumlah'         => 1500000,
        ]);

        $this->assertCount(1, $rayon->pendapatans);
    }

    // ══════════════════════════════════════════
    // JURU PARKIR MODEL
    // ══════════════════════════════════════════

    public function test_juru_parkir_memiliki_relasi_belongs_to_rayon(): void
    {
        $rayon = Rayon::create([
            'nama_rayon'         => 'Rayon IV',
            'kecamatan'          => 'Lemahwungkuk',
            'lokasi'             => 'Jl. Benteng',
            'karakteristik_area' => 'Wisata',
            'jumlah_juru_parkir' => 6,
        ]);

        $juruParkir = JuruParkir::create([
            'rayon_id'           => $rayon->id,
            'jumlah_juru_parkir' => 6,
        ]);

        $this->assertInstanceOf(Rayon::class, $juruParkir->rayon);
        $this->assertEquals($rayon->id, $juruParkir->rayon->id);
    }

    // ══════════════════════════════════════════
    // HELPER & LOGIKA KALENDER
    // ══════════════════════════════════════════

    public function test_carbon_dapat_mendeteksi_hari_weekend(): void
    {
        $sabtu = Carbon::parse('2026-01-03'); // Sabtu
        $minggu = Carbon::parse('2026-01-04'); // Minggu
        $senin  = Carbon::parse('2026-01-05'); // Senin

        $this->assertTrue($sabtu->isWeekend());
        $this->assertTrue($minggu->isWeekend());
        $this->assertFalse($senin->isWeekend());
    }

    public function test_pendapatan_dapat_dihitung_total_per_hari(): void
    {
        $rayon = Rayon::create([
            'nama_rayon'         => 'Rayon V',
            'kecamatan'          => 'Harjamukti',
            'lokasi'             => 'Jl. Perjuangan',
            'karakteristik_area' => 'Campuran',
            'jumlah_juru_parkir' => 7,
        ]);

        $juruParkir = JuruParkir::create([
            'rayon_id'           => $rayon->id,
            'jumlah_juru_parkir' => 7,
        ]);

        Pendapatan::create(['tanggal' => '2025-06-01', 'rayon_id' => $rayon->id, 'juru_parkir_id' => $juruParkir->id, 'jumlah' => 1200000]);
        Pendapatan::create(['tanggal' => '2025-06-01', 'rayon_id' => $rayon->id, 'juru_parkir_id' => $juruParkir->id, 'jumlah' => 800000]);

        $total = Pendapatan::where('tanggal', '2025-06-01')->sum('jumlah');
        $this->assertEquals(2000000, $total);
    }
}
