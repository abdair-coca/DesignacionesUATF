<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DemoAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auth.guards.web.provider' => 'demo',
            'demo-auth.password' => 'demo-password',
        ]);
        $this->app['auth']->forgetGuards();
    }

    #[DataProvider('demoAccounts')]
    public function test_demo_account_can_login_without_a_users_query(string $email, string $role, ?string $career): void
    {
        $this->withoutExceptionHandling();

        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'demo-password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
        $this->assertSame($email, auth()->user()->email);
        $this->assertSame($role, auth()->user()->rol);
        $this->assertSame($career, auth()->user()->carrera?->sigla);
    }

    public function test_invalid_demo_password_is_rejected(): void
    {
        $this->post('/login', [
            'email' => 'director.inf@uatf.edu.bo',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_demo_session_survives_a_second_request(): void
    {
        $this->post('/login', [
            'email' => 'director.med@uatf.edu.bo',
            'password' => 'demo-password',
        ])->assertRedirect(route('designaciones.index'));

        $this->get('/')->assertRedirect(route('designaciones.index'));
        $this->assertSame('MED', auth()->user()->carrera->sigla);
    }

    /**
     * @return array<string, array{string, string, string|null}>
     */
    public static function demoAccounts(): array
    {
        return [
            'vicerrectorado' => ['admin@uatf.edu.bo', User::ROL_VICERRECTORADO, null],
            'informatica' => ['director.inf@uatf.edu.bo', User::ROL_DIRECTOR_CARRERA, 'INF'],
            'medicina' => ['director.med@uatf.edu.bo', User::ROL_DIRECTOR_CARRERA, 'MED'],
            'mecanica' => ['director.mec@uatf.edu.bo', User::ROL_DIRECTOR_CARRERA, 'MEC'],
        ];
    }
}
