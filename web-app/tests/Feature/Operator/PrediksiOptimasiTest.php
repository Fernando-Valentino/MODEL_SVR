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

        // Seed a successful SVR Default run to satisfy the SVR Standar check for optimization
        $run = \App\Models\ModelRun::create([
            'model_name' => 'SVR Standar',
            'model_type' => 'svr_default',
            'status' => 'success',
            'started_at' => now(),
            'finished_at' => now(),
            'total_rows' => 100,
            'train_rows' => 80,
            'test_rows' => 20,
            'train_period' => '2026-06-01 - 2026-06-05',
            'test_period' => '2026-06-06 - 2026-06-07',
            'created_by' => 'op',
        ]);
        
        \App\Models\ModelParameter::create([
            'model_run_id' => $run->id,
            'kernel' => 'rbf',
            'c_value' => '1.0',
            'epsilon_value' => '0.1',
            'gamma_value' => 'scale',
        ]);
        
        \App\Models\ModelMetric::create([
            'model_run_id' => $run->id,
            'mae' => 5000.0,
            'rmse' => 7000.0,
            'mape' => 5.2,
            'r2_score' => 0.85,
            'accuracy' => 94.8,
            'dataset_type' => 'test'
        ]);
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

    public function test_run_svr_berhasil_dan_mengembalikan_json_sukses(): void
    {
        // Seed required dataset
        $rayon = \App\Models\Rayon::create([
            'nama_rayon' => 'Rayon A',
            'kecamatan' => 'Kecamatan A',
            'lokasi' => 'Lokasi A',
            'karakteristik_area' => 'Komersial',
            'jumlah_juru_parkir' => 5,
        ]);
        
        $jukir = \App\Models\JuruParkir::create([
            'rayon_id' => $rayon->id,
            'jumlah_juru_parkir' => 5,
        ]);
        
        \App\Models\HariLibur::create([
            'tanggal' => '2026-06-01',
            'keterangan' => 'Libur Nasional',
            'tipe' => 'Libur Nasional',
        ]);
        
        \App\Models\Pendapatan::create([
            'tanggal' => '2026-06-01',
            'rayon_id' => $rayon->id,
            'juru_parkir_id' => $jukir->id,
            'jumlah' => 1500000,
        ]);

        // Mock FastAPI service
        $this->mock(\App\Services\FastApiService::class, function ($mock) use ($rayon) {
            $mock->shouldReceive('post')
                ->once()
                ->andReturn([
                    'status' => 'success',
                    'dataset' => [
                        'total_rows' => 1,
                        'train_rows' => 1,
                        'test_rows' => 0,
                        'train_period' => '2026-06-01',
                        'test_period' => '2026-06-01',
                    ],
                    'parameters' => [
                        'kernel' => 'RBF',
                        'C' => 1.0,
                        'epsilon' => 0.1,
                        'gamma' => 'scale',
                    ],
                    'metrics' => [
                        'mae' => 5000.0,
                        'rmse' => 7000.0,
                        'mape' => 5.2,
                        'r2' => 0.85,
                        'accuracy' => 94.8,
                    ],
                    'predictions' => [
                        [
                            'tanggal' => '2026-06-01',
                            'rayon_id' => $rayon->id,
                            'rayon' => $rayon->nama_rayon,
                            'actual' => 1500000.0,
                            'predicted' => 1450000.0,
                            'error' => 50000.0,
                            'percentage_error' => 3.33
                        ]
                    ]
                ]);
        });

        $response = $this->actingAs($this->operator)->post(route('operator.prediksi.run-svr'), [
            'c'             => 10.0,
            'epsilon'       => 0.01,
            'gamma'         => 0.1,
            'tanggal_mulai' => '2026-06-01',
            'tanggal_akhir' => '2026-06-07',
            'rayon_id'      => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Proses generate prediksi SVR standar berhasil diselesaikan.'
        ]);
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
        $this->mock(\App\Services\FastApiService::class, function ($mock) {
            $mock->shouldReceive('post')
                ->once()
                ->andReturn([
                    'status' => 'success',
                    'dataset' => [
                        'total_rows' => 100,
                        'train_rows' => 80,
                        'test_rows' => 20,
                        'train_period' => '2026-06-01 - 2026-06-05',
                        'test_period' => '2026-06-06 - 2026-06-07',
                    ],
                    'parameters' => [
                        'kernel' => 'rbf',
                        'C' => 100.0,
                        'epsilon' => 0.001,
                        'gamma' => 'scale',
                    ],
                    'metrics' => [
                        'mae' => 90000.0,
                        'rmse' => 130000.0,
                        'mape' => 5.1,
                        'r2' => 0.92,
                        'accuracy' => 94.9,
                    ],
                    'predictions' => []
                ]);
        });

        $response = $this->actingAs($this->operator)->post(route('operator.optimasi.grid-search'), [
            'grid_c'       => '[0.1, 1, 10]',
            'grid_epsilon' => '[0.001, 0.01]',
            'grid_gamma'   => '[0.001, 0.01]',
        ]);

        $response->assertRedirect();
        $this->assertTrue(
            $response->getSession()->has('success') || $response->getSession()->has('info'),
            'Expected session to have either success or info key'
        );
    }

    public function test_run_gwo_berhasil_dan_redirect_dengan_pesan_sukses(): void
    {
        $this->mock(\App\Services\FastApiService::class, function ($mock) {
            $mock->shouldReceive('post')
                ->once()
                ->andReturn([
                    'status' => 'success',
                    'dataset' => [
                        'total_rows' => 100,
                        'train_rows' => 80,
                        'test_rows' => 20,
                        'train_period' => '2026-06-01 - 2026-06-05',
                        'test_period' => '2026-06-06 - 2026-06-07',
                    ],
                    'parameters' => [
                        'kernel' => 'rbf',
                        'C' => 150.0,
                        'epsilon' => 0.005,
                        'gamma' => 0.01,
                    ],
                    'metrics' => [
                        'mae' => 75000.0,
                        'rmse' => 105000.0,
                        'mape' => 4.8,
                        'r2' => 0.93,
                        'accuracy' => 95.2,
                    ],
                    'predictions' => []
                ]);
        });

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
        $this->assertTrue(
            $response->getSession()->has('success') || $response->getSession()->has('info'),
            'Expected session to have either success or info key'
        );
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

    public function test_halaman_optimasi_terkunci_jika_svr_standar_belum_dilatih(): void
    {
        // Hapus run standar
        \App\Models\ModelRun::where('model_type', 'svr_default')->delete();

        $response = $this->actingAs($this->operator)->get(route('operator.optimasi.index'));
        $response->assertStatus(200);
        $response->assertSee('Optimasi Parameter Terkunci');
        $response->assertSee('Jalankan SVR Standar Terlebih Dahulu');
    }

    public function test_run_grid_search_gagal_jika_svr_standar_belum_dilatih(): void
    {
        // Hapus run standar
        \App\Models\ModelRun::where('model_type', 'svr_default')->delete();

        $response = $this->actingAs($this->operator)->post(route('operator.optimasi.grid-search'), [
            'grid_c'       => '[0.1, 1, 10]',
            'grid_epsilon' => '[0.001, 0.01]',
            'grid_gamma'   => '[0.001, 0.01]',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_run_gwo_gagal_jika_svr_standar_belum_dilatih(): void
    {
        // Hapus run standar
        \App\Models\ModelRun::where('model_type', 'svr_default')->delete();

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
        $response->assertSessionHas('error');
    }
}
