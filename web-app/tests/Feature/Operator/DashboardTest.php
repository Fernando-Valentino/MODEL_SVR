<?php

namespace Tests\Feature\Operator;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class DashboardTest extends TestCase
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

    public function test_dashboard_operator_dapat_diakses_saat_login(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('operator.dashboard');
    }

    public function test_dashboard_operator_tidak_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('operator.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_menampilkan_variabel_yang_dibutuhkan(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.dashboard'));
        $response->assertViewHasAll([
            'metrics',
            'incomes',
            'chartLabels',
            'chartActualValues',
            'chartPredictGwoValues',
            'chartPredictGsValues',
            'rayons',
        ]);
    }

    public function test_kepala_upt_tidak_dapat_akses_dashboard_operator(): void
    {
        $kepalaUpt = User::factory()->create(['username' => 'ku']);
        $kepalaUpt->assignRole('kepala_upt');

        $response = $this->actingAs($kepalaUpt)->get(route('operator.dashboard'));
        $response->assertStatus(403);
    }
}
