<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    public static function rutasGetProtegidas(): array
    {
        return [
            'raiz' => ['/'],
            'designaciones' => ['/designaciones'],
            'notificaciones' => ['/notificaciones'],
        ];
    }

    #[DataProvider('rutasGetProtegidas')]
    public function test_invitado_redirigido_login_en_get(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    public function test_invitado_no_puede_acceder_login_si_ya_autenticado(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect();
    }

    public function test_login_muestra_formulario(): void
    {
        $this->get('/login')->assertOk()->assertSee('Email')->assertSee('Ingresar');
    }

    public function test_login_valido_redirige(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret'])->assertRedirect('/notificaciones');
        $this->assertAuthenticated();
    }

    public function test_login_invalido_rechaza(): void
    {
        User::factory()->create(['email' => 'test@test.com', 'password' => bcrypt('correct')]);

        $this->post('/login', ['email' => 'test@test.com', 'password' => 'wrong'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_invalido_se_bloquea_despues_de_cinco_intentos(): void
    {
        $email = 'limite@test.com';
        $throttleKey = $this->throttleKey($email);
        RateLimiter::clear($throttleKey);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos de inicio de sesi�n.', session('errors')->first('email'));
        RateLimiter::clear($throttleKey);
    }

    public function test_login_valido_limpia_el_contador_de_intentos(): void
    {
        $user = User::factory()->create(['email' => 'reinicio@test.com', 'password' => bcrypt('correct')]);
        $throttleKey = $this->throttleKey($user->email);
        RateLimiter::clear($throttleKey);
        for ($attempt = 0; $attempt < 4; $attempt++) {
            RateLimiter::hit($throttleKey, 60);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'correct'])->assertRedirect('/notificaciones');
        $this->assertSame(0, RateLimiter::attempts($throttleKey));
    }

    public function test_logout_cierra_sesion(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    private function throttleKey(string $email): string
    {
        return mb_strtolower($email).'|127.0.0.1';
    }

    public function test_login_form_uses_relative_action(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('action="/login"', false);
    }
}
