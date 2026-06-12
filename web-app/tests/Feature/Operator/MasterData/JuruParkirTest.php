<?php

namespace Tests\Feature\Operator\MasterData;

use Tests\TestCase;
use App\Models\User;
use App\Models\Rayon;
use App\Models\JuruParkir;
use App\Models\Pendapatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class JuruParkirTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Rayon $rayon;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'operator',      'guard_name' => 'web']);
        Role::create(['name' => 'kepala_upt',    'guard_name' => 'web']);
        Role::create(['name' => 'kepala_dishub', 'guard_name' => 'web']);

        $this->operator = User::factory()->create(['username' => 'op']);
        $this->operator->assignRole('operator');

        $this->rayon = Rayon::create([
            'nama_rayon'         => 'Rayon I',
            'kecamatan'          => 'Kejaksan',
            'lokasi'             => 'Jl. Siliwangi',
            'karakteristik_area' => 'Komersial',
            'jumlah_juru_parkir' => 5,
        ]);
    }

    // ─────────── INDEX ───────────

    public function test_halaman_juru_parkir_dapat_diakses(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.juru-parkir.index'));
        $response->assertStatus(200);
        $response->assertViewIs('operator.master-data.juru-parkir.index');
    }

    public function test_halaman_juru_parkir_tidak_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('operator.juru-parkir.index'));
        $response->assertRedirect(route('login'));
    }

    // ─────────── STORE ───────────

    public function test_dapat_menyimpan_juru_parkir_baru(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.juru-parkir.store'), [
            'rayon_id'           => $this->rayon->id,
            'jumlah_juru_parkir' => 8,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('juru_parkirs', [
            'rayon_id'           => $this->rayon->id,
            'jumlah_juru_parkir' => 8,
        ]);
    }

    public function test_tidak_dapat_menyimpan_juru_parkir_duplikat_rayon(): void
    {
        JuruParkir::create([
            'rayon_id'           => $this->rayon->id,
            'jumlah_juru_parkir' => 5,
        ]);

        $response = $this->actingAs($this->operator)->postJson(route('operator.juru-parkir.store'), [
            'rayon_id'           => $this->rayon->id,
            'jumlah_juru_parkir' => 8,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_tidak_dapat_menyimpan_juru_parkir_rayon_tidak_ada(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.juru-parkir.store'), [
            'rayon_id'           => 9999,
            'jumlah_juru_parkir' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['rayon_id']]);
    }

    // ─────────── SHOW ───────────

    public function test_dapat_mengambil_detail_juru_parkir(): void
    {
        $juruParkir = JuruParkir::create([
            'rayon_id'           => $this->rayon->id,
            'jumlah_juru_parkir' => 7,
        ]);

        $response = $this->actingAs($this->operator)->getJson(route('operator.juru-parkir.show', $juruParkir->id));
        $response->assertStatus(200);
        $response->assertJsonPath('jumlah_juru_parkir', 7);
    }

    // ─────────── UPDATE ───────────

    public function test_dapat_memperbarui_juru_parkir(): void
    {
        $juruParkir = JuruParkir::create([
            'rayon_id'           => $this->rayon->id,
            'jumlah_juru_parkir' => 5,
        ]);

        $response = $this->actingAs($this->operator)->putJson(
            route('operator.juru-parkir.update', $juruParkir->id),
            [
                'rayon_id'           => $this->rayon->id,
                'jumlah_juru_parkir' => 12,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('juru_parkirs', ['jumlah_juru_parkir' => 12]);
    }

    // ─────────── DESTROY ───────────

    public function test_dapat_menghapus_juru_parkir_tanpa_pendapatan(): void
    {
        $juruParkir = JuruParkir::create([
            'rayon_id'           => $this->rayon->id,
            'jumlah_juru_parkir' => 5,
        ]);

        $response = $this->actingAs($this->operator)->deleteJson(route('operator.juru-parkir.destroy', $juruParkir->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('juru_parkirs', ['id' => $juruParkir->id]);
    }

    public function test_tidak_dapat_menghapus_juru_parkir_yang_memiliki_riwayat_pendapatan(): void
    {
        $juruParkir = JuruParkir::create([
            'rayon_id'           => $this->rayon->id,
            'jumlah_juru_parkir' => 5,
        ]);
        Pendapatan::create([
            'tanggal'        => '2025-01-01',
            'rayon_id'       => $this->rayon->id,
            'juru_parkir_id' => $juruParkir->id,
            'jumlah'         => 1000000,
        ]);

        $response = $this->actingAs($this->operator)->deleteJson(route('operator.juru-parkir.destroy', $juruParkir->id));

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('juru_parkirs', ['id' => $juruParkir->id]);
    }
}
