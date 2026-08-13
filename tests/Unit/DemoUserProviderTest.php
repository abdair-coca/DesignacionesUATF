<?php

namespace Tests\Unit;

use App\Auth\Demo\DemoUserProvider;
use App\Models\User;
use Illuminate\Hashing\BcryptHasher;
use PHPUnit\Framework\TestCase;

class DemoUserProviderTest extends TestCase
{
    public function test_retrieves_a_director_without_using_eloquent(): void
    {
        $provider = new DemoUserProvider(new BcryptHasher(['rounds' => 4]), [
            [
                'id' => 'demo-inf',
                'name' => 'Director Ingeniería Informática',
                'email' => 'director.inf@uatf.edu.bo',
                'rol' => User::ROL_DIRECTOR_CARRERA,
                'carrera_id' => 1,
                'carrera' => [
                    'sigla' => 'INF',
                    'nombre' => 'Ingeniería Informática',
                ],
            ],
        ], 'demo-password');

        $user = $provider->retrieveByCredentials([
            'email' => 'DIRECTOR.INF@UATF.EDU.BO',
            'password' => 'demo-password',
        ]);

        $this->assertNotNull($user);
        $this->assertTrue($provider->validateCredentials($user, ['password' => 'demo-password']));
        $this->assertFalse($provider->validateCredentials($user, ['password' => 'wrong-password']));
        $this->assertSame('director.inf@uatf.edu.bo', $user->email);
        $this->assertSame(User::ROL_DIRECTOR_CARRERA, $user->rol);
        $this->assertSame('INF', $user->carrera->sigla);
        $this->assertSame($user->id, $provider->retrieveById('demo-inf')?->id);
    }
}
