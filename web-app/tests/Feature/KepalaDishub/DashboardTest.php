<?php

namespace Tests\Feature\KepalaDishub;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $kepalaDishub;
    private User $operator;
    private User $kepalaUpt;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'operator',      'guard_name' => 'web']);
        Role::create(['name' => 'kepala_upt',    'guard_name' => 'web']);
        Role::create(['name' => 'kepala_dishub', 'guard_name' => 'web']);

        $this->kepalaDishub = User::factory()->create(['username' => 'kd']);
        $this->kepalaDishub->assignRole('kepala_dishub');

        $this->operator = User::factory()->create(['username' => 'op']);
        $this->operator->assignRole('operator');

        $this->kepalaUpt = User::factory()->create(['username' => 'ku']);
        $this->kepalaUpt->assignRole('kepala_upt');
    }

    public function test_dashboard_kepala_dishub_dapat_diakses_saat_login(): void
    {
        $response = $this->actingAs($this->kepalaDishub)->get(route('kepala-dishub.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('kepala-dishub.dashboard');
    }

    public function test_dashboard_kepala_dishub_tidak_dapat_akses_tanpa_login(): void
    {
        $response = $this->get(route('kepala-dishub.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_kepala_dishub_menampilkan_variabel_yang_dibutuhkan(): void
    {
        $response = $this->actingAs($this->kepalaDishub)->get(route('kepala-dishub.dashboard'));
        $response->assertViewHasAll([
            'metrics',
            'incomes',
        ]);
    }

    public function test_operator_tidak_dapat_akses_dashboard_kepala_dishub(): void
    {
        $response = $this->actingAs($this->operator)->get(route('kepala-dishub.dashboard'));
        $response->assertStatus(403);
    }

    public function test_kepala_upt_tidak_dapat_akses_dashboard_kepala_dishub(): void
    {
        $response = $this->actingAs($this->kepalaUpt)->get(route('kepala-dishub.dashboard'));
        $response->assertStatus(403);
    }
}
