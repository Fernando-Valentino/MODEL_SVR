<?php

namespace Tests\Feature\Operator\MasterData;

use Tests\TestCase;
use App\Models\User;
use App\Models\HariLibur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class HariLiburTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'operator',      'guard_name' => 'web']);
        Role::create(['name' => 'kepala_upt',    'guard_name' => 'web']);
        Role::create(['name' => 'kepala_dishub', 'guard_name' => 'web']);

        $this->operator = User::factory()->create(['username' => 'op']);
        $this->operator->assignRole('operator');
    }

    // ─────────── INDEX ───────────

    public function test_halaman_hari_libur_dapat_diakses(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.hari-libur.index'));
        $response->assertStatus(200);
        $response->assertViewIs('operator.master-data.hari-libur.index');
    }

    public function test_halaman_hari_libur_tidak_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('operator.hari-libur.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_halaman_hari_libur_menampilkan_data_dari_database(): void
    {
        HariLibur::create([
            'tanggal'    => '2026-01-01',
            'keterangan' => 'Tahun Baru',
            'tipe'       => 'Libur Nasional',
        ]);

        $response = $this->actingAs($this->operator)->get(route('operator.hari-libur.index'));
        $response->assertSee('Tahun Baru');
    }

    // ─────────── STORE ───────────

    public function test_dapat_menyimpan_hari_libur_baru(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.hari-libur.store'), [
            'tanggal'    => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI',
            'tipe'       => 'Libur Nasional',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('hari_liburs', [
            'tanggal'    => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI',
        ]);
    }

    public function test_tidak_dapat_menyimpan_hari_libur_dengan_tanggal_duplikat(): void
    {
        HariLibur::create([
            'tanggal'    => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI',
            'tipe'       => 'Libur Nasional',
        ]);

        $response = $this->actingAs($this->operator)->postJson(route('operator.hari-libur.store'), [
            'tanggal'    => '2026-08-17',
            'keterangan' => 'Duplikat',
            'tipe'       => 'Libur Nasional',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonPath('errors.tanggal.0', 'Tanggal ini sudah terdaftar sebagai hari libur.');
    }

    public function test_tidak_dapat_menyimpan_hari_libur_tanpa_tanggal(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.hari-libur.store'), [
            'tanggal'    => '',
            'keterangan' => 'Test',
            'tipe'       => 'Libur Nasional',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['tanggal']]);
    }

    public function test_tidak_dapat_menyimpan_dengan_tipe_tidak_valid(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.hari-libur.store'), [
            'tanggal'    => '2026-09-01',
            'keterangan' => 'Test Tipe Invalid',
            'tipe'       => 'Tipe Tidak Dikenal',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['tipe']]);
    }

    // ─────────── SHOW ───────────

    public function test_dapat_mengambil_detail_hari_libur(): void
    {
        $hariLibur = HariLibur::create([
            'tanggal'    => '2026-01-01',
            'keterangan' => 'Tahun Baru',
            'tipe'       => 'Libur Nasional',
        ]);

        $response = $this->actingAs($this->operator)->getJson(route('operator.hari-libur.show', $hariLibur->id));
        $response->assertStatus(200);
        $response->assertJsonPath('keterangan', 'Tahun Baru');
    }

    // ─────────── UPDATE ───────────

    public function test_dapat_memperbarui_hari_libur(): void
    {
        $hariLibur = HariLibur::create([
            'tanggal'    => '2026-01-01',
            'keterangan' => 'Tahun Baru Lama',
            'tipe'       => 'Libur Nasional',
        ]);

        $response = $this->actingAs($this->operator)->putJson(route('operator.hari-libur.update', $hariLibur->id), [
            'tanggal'    => '2026-01-01',
            'keterangan' => 'Tahun Baru Diperbarui',
            'tipe'       => 'Libur Nasional',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('hari_liburs', ['keterangan' => 'Tahun Baru Diperbarui']);
    }

    // ─────────── DESTROY ───────────

    public function test_dapat_menghapus_hari_libur(): void
    {
        $hariLibur = HariLibur::create([
            'tanggal'    => '2026-03-30',
            'keterangan' => 'Hari Raya Idul Fitri',
            'tipe'       => 'Libur Nasional',
        ]);

        $response = $this->actingAs($this->operator)->deleteJson(route('operator.hari-libur.destroy', $hariLibur->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('hari_liburs', ['id' => $hariLibur->id]);
    }

    // ─────────── GENERATE ───────────

    public function test_generate_weekend_berhasil_untuk_tahun_baru(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.hari-libur.generate'), [
            'year'             => 2030,
            'import_api'       => false,
            'generate_weekend' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_generate_gagal_jika_data_tahun_sudah_ada(): void
    {
        HariLibur::create([
            'tanggal'    => '2026-01-03',
            'keterangan' => 'Weekend',
            'tipe'       => 'Weekend',
        ]);

        $response = $this->actingAs($this->operator)->postJson(route('operator.hari-libur.generate'), [
            'year'             => 2026,
            'import_api'       => false,
            'generate_weekend' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_generate_gagal_jika_tahun_tidak_valid(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.hari-libur.generate'), [
            'year'             => 1990,
            'import_api'       => false,
            'generate_weekend' => false,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['year']]);
    }
}
