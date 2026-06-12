<?php

namespace Tests\Feature\Operator\MasterData;

use Tests\TestCase;
use App\Models\User;
use App\Models\Rayon;
use App\Models\JuruParkir;
use App\Models\Pendapatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class RayonTest extends TestCase
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

    private function rayonData(array $overrides = []): array
    {
        return array_merge([
            'nama_rayon'         => 'Rayon I',
            'kecamatan'          => 'Kejaksan',
            'lokasi'             => 'Jl. Siliwangi',
            'karakteristik_area' => 'Komersial',
            'jumlah_juru_parkir' => 5,
        ], $overrides);
    }

    // ─────────── INDEX ───────────

    public function test_halaman_rayon_dapat_diakses(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.rayon.index'));
        $response->assertStatus(200);
        $response->assertViewIs('operator.master-data.rayon.index');
    }

    public function test_halaman_rayon_tidak_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('operator.rayon.index'));
        $response->assertRedirect(route('login'));
    }

    // ─────────── STORE ───────────

    public function test_dapat_menyimpan_rayon_baru(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.rayon.store'), $this->rayonData());

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('rayons', ['nama_rayon' => 'Rayon I']);
    }

    public function test_tidak_dapat_menyimpan_rayon_dengan_nama_duplikat(): void
    {
        Rayon::create($this->rayonData());

        $response = $this->actingAs($this->operator)->postJson(route('operator.rayon.store'), $this->rayonData());

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_tidak_dapat_menyimpan_rayon_tanpa_field_wajib(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('operator.rayon.store'), [
            'nama_rayon' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['nama_rayon']]);
    }

    public function test_tidak_dapat_menyimpan_jumlah_juru_parkir_negatif(): void
    {
        $response = $this->actingAs($this->operator)->postJson(
            route('operator.rayon.store'),
            $this->rayonData(['jumlah_juru_parkir' => -1])
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['jumlah_juru_parkir']]);
    }

    // ─────────── SHOW ───────────

    public function test_dapat_mengambil_detail_rayon(): void
    {
        $rayon = Rayon::create($this->rayonData());

        $response = $this->actingAs($this->operator)->getJson(route('operator.rayon.show', $rayon->id));
        $response->assertStatus(200);
        $response->assertJsonPath('nama_rayon', 'Rayon I');
    }

    // ─────────── UPDATE ───────────

    public function test_dapat_memperbarui_data_rayon(): void
    {
        $rayon = Rayon::create($this->rayonData());

        $response = $this->actingAs($this->operator)->putJson(
            route('operator.rayon.update', $rayon->id),
            $this->rayonData(['kecamatan' => 'Lemahwungkuk'])
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('rayons', ['kecamatan' => 'Lemahwungkuk']);
    }

    // ─────────── DESTROY ───────────

    public function test_dapat_menghapus_rayon_tanpa_pendapatan(): void
    {
        $rayon = Rayon::create($this->rayonData());

        $response = $this->actingAs($this->operator)->deleteJson(route('operator.rayon.destroy', $rayon->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('rayons', ['id' => $rayon->id]);
    }

    public function test_tidak_dapat_menghapus_rayon_yang_memiliki_pendapatan(): void
    {
        $rayon = Rayon::create($this->rayonData());
        $juruParkir = JuruParkir::create([
            'rayon_id'           => $rayon->id,
            'jumlah_juru_parkir' => 5,
        ]);
        Pendapatan::create([
            'tanggal'        => '2025-01-01',
            'rayon_id'       => $rayon->id,
            'juru_parkir_id' => $juruParkir->id,
            'jumlah'         => 1000000,
        ]);

        $response = $this->actingAs($this->operator)->deleteJson(route('operator.rayon.destroy', $rayon->id));

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('rayons', ['id' => $rayon->id]);
    }
}
