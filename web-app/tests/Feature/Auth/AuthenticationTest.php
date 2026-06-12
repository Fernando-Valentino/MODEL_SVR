<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'operator',      'guard_name' => 'web']);
        Role::create(['name' => 'kepala_upt',    'guard_name' => 'web']);
        Role::create(['name' => 'kepala_dishub', 'guard_name' => 'web']);
    }

    public function test_halaman_login_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_root_url_redirect_ke_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_operator_dapat_login_dan_redirect_ke_dashboard_operator(): void
    {
        $user = User::factory()->create([
            'username' => 'operator_test',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('operator');

        $response = $this->post('/login', [
            'username' => 'operator_test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('operator.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_kepala_upt_dapat_login_dan_redirect_ke_dashboard_kepala_upt(): void
    {
        $user = User::factory()->create([
            'username' => 'kepala_upt_test',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('kepala_upt');

        $response = $this->post('/login', [
            'username' => 'kepala_upt_test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('kepala-upt.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_kepala_dishub_dapat_login_dan_redirect_ke_dashboard_kepala_dishub(): void
    {
        $user = User::factory()->create([
            'username' => 'kepala_dishub_test',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('kepala_dishub');

        $response = $this->post('/login', [
            'username' => 'kepala_dishub_test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('kepala-dishub.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_dengan_kredensial_salah_gagal(): void
    {
        User::factory()->create([
            'username' => 'operator_test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'username' => 'operator_test',
            'password' => 'password_salah',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_tanpa_username_gagal_validasi(): void
    {
        $response = $this->post('/login', [
            'username' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_login_tanpa_password_gagal_validasi(): void
    {
        $response = $this->post('/login', [
            'username' => 'operator_test',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_pengguna_yang_sudah_login_tidak_dapat_mengakses_halaman_login(): void
    {
        $user = User::factory()->create(['username' => 'op']);
        $user->assignRole('operator');

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/');
    }

    public function test_logout_berhasil_dan_redirect_ke_login(): void
    {
        $user = User::factory()->create(['username' => 'op']);
        $user->assignRole('operator');

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
