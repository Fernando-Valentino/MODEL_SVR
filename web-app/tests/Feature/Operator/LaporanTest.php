<?php

namespace Tests\Feature\Operator;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class LaporanTest extends TestCase
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
    // LAPORAN – OPERATOR
    // ══════════════════════════════════════════

    public function test_halaman_laporan_operator_dapat_diakses(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.laporan.index'));
        $response->assertStatus(200);
        $response->assertViewIs('operator.laporan.index');
    }

    public function test_halaman_laporan_operator_tidak_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('operator.laporan.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_halaman_laporan_menampilkan_variabel_summary_metrics_reports(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.laporan.index'));
        $response->assertViewHasAll(['summary', 'metrics', 'reports']);
    }

    public function test_export_pdf_operator_menghasilkan_respon_download(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.laporan.export-pdf'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('laporan-prediksi.pdf', $response->headers->get('content-disposition'));
    }

    public function test_export_excel_operator_menghasilkan_respon_download(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.laporan.export-excel'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('laporan-prediksi.xlsx', $response->headers->get('content-disposition'));
    }

    // ══════════════════════════════════════════
    // LAPORAN – KEPALA UPT
    // ══════════════════════════════════════════

    public function test_halaman_laporan_kepala_upt_dapat_diakses(): void
    {
        $response = $this->actingAs($this->kepalaUpt)->get(route('kepala-upt.laporan.index'));
        $response->assertStatus(200);
    }

    public function test_export_pdf_kepala_upt_menghasilkan_respon_download(): void
    {
        $response = $this->actingAs($this->kepalaUpt)->get(route('kepala-upt.laporan.export-pdf'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_kepala_upt_tidak_dapat_akses_laporan_operator(): void
    {
        $response = $this->actingAs($this->kepalaUpt)->get(route('operator.laporan.index'));
        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════
    // LAPORAN – KEPALA DISHUB
    // ══════════════════════════════════════════

    public function test_halaman_laporan_kepala_dishub_dapat_diakses(): void
    {
        $response = $this->actingAs($this->kepalaDishub)->get(route('kepala-dishub.laporan.index'));
        $response->assertStatus(200);
    }

    public function test_export_pdf_kepala_dishub_menghasilkan_respon_download(): void
    {
        $response = $this->actingAs($this->kepalaDishub)->get(route('kepala-dishub.laporan.export-pdf'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_kepala_dishub_tidak_dapat_akses_laporan_operator(): void
    {
        $response = $this->actingAs($this->kepalaDishub)->get(route('operator.laporan.index'));
        $response->assertStatus(403);
    }
}
