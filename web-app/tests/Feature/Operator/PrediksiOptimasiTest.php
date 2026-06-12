<?php

namespace Tests\Feature\Operator;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class PrediksiOptimasiTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private User $kepalaUpt;
    private User $kepalaDishub;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'operator',      'guard_name' => 'web']);
        Role::create(['name' => 'kepala_upt',    'guard_name' => 'web']);
        Role::create(['name' => 'kepala_dishub', 'guard_name' => 'web']);

        $this->operator = User::factory()->create(['username' => 'op']);
        $this->operator->assignRole('operator');

        $this->kepalaUpt = User::factory()->create(['username' => 'ku']);
        $this->kepalaUpt->assignRole('kepala_upt');

        $this->kepalaDishub = User::factory()->create(['username' => 'kd']);
        $this->kepalaDishub->assignRole('kepala_dishub');
    }

    // ══════════════════════════════════════════
    // PREDIKSI – OPERATOR
    // ══════════════════════════════════════════

    public function test_halaman_prediksi_operator_dapat_diakses(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.prediksi.index'));
        $response->assertStatus(200);
        $response->assertViewIs('operator.prediksi.index');
    }

    public function test_halaman_prediksi_operator_tidak_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('operator.prediksi.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_halaman_prediksi_menampilkan_variabel_params_metrics_predictions(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.prediksi.index'));
        $response->assertViewHasAll(['params', 'metrics', 'predictions']);
    }

    public function test_run_svr_berhasil_dan_redirect_dengan_pesan_sukses(): void
    {
        $response = $this->actingAs($this->operator)->post(route('operator.prediksi.run-svr'), [
            'c'             => 10.0,
            'epsilon'       => 0.01,
            'gamma'         => 0.1,
            'tanggal_mulai' => '2026-06-01',
            'tanggal_akhir' => '2026-06-07',
            'rayon_id'      => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ══════════════════════════════════════════
    // PREDIKSI – KEPALA UPT
    // ══════════════════════════════════════════

    public function test_halaman_prediksi_kepala_upt_dapat_diakses(): void
    {
        $response = $this->actingAs($this->kepalaUpt)->get(route('kepala-upt.prediksi.index'));
        $response->assertStatus(200);
    }

    public function test_operator_tidak_dapat_akses_halaman_prediksi_kepala_upt(): void
    {
        $response = $this->actingAs($this->operator)->get(route('kepala-upt.prediksi.index'));
        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════
    // PREDIKSI – KEPALA DISHUB
    // ══════════════════════════════════════════

    public function test_halaman_prediksi_kepala_dishub_dapat_diakses(): void
    {
        $response = $this->actingAs($this->kepalaDishub)->get(route('kepala-dishub.prediksi.index'));
        $response->assertStatus(200);
    }

    // ══════════════════════════════════════════
    // OPTIMASI – OPERATOR
    // ══════════════════════════════════════════

    public function test_halaman_optimasi_operator_dapat_diakses(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.optimasi.index'));
        $response->assertStatus(200);
        $response->assertViewIs('operator.optimasi.index');
    }

    public function test_halaman_optimasi_tidak_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('operator.optimasi.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_halaman_optimasi_menampilkan_variabel_comparisons(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.optimasi.index'));
        $response->assertViewHas('comparisons');
    }

    public function test_run_grid_search_berhasil_dan_redirect_dengan_pesan_sukses(): void
    {
        $response = $this->actingAs($this->operator)->post(route('operator.optimasi.grid-search'), [
            'grid_c'       => '[0.1, 1, 10]',
            'grid_epsilon' => '[0.001, 0.01]',
            'grid_gamma'   => '[0.001, 0.01]',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_run_gwo_berhasil_dan_redirect_dengan_pesan_sukses(): void
    {
        $response = $this->actingAs($this->operator)->post(route('operator.optimasi.gwo'), [
            'wolves'      => 10,
            'iterations'  => 50,
            'c_min'       => 0.1,
            'c_max'       => 100.0,
            'epsilon_min' => 0.0001,
            'epsilon_max' => 0.1,
            'gamma_min'   => 0.0001,
            'gamma_max'   => 1.0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ══════════════════════════════════════════
    // OPTIMASI – KEPALA UPT & DISHUB (READ ONLY)
    // ══════════════════════════════════════════

    public function test_halaman_optimasi_kepala_upt_dapat_diakses(): void
    {
        $response = $this->actingAs($this->kepalaUpt)->get(route('kepala-upt.optimasi.index'));
        $response->assertStatus(200);
    }

    public function test_halaman_optimasi_kepala_dishub_dapat_diakses(): void
    {
        $response = $this->actingAs($this->kepalaDishub)->get(route('kepala-dishub.optimasi.index'));
        $response->assertStatus(200);
    }
}
