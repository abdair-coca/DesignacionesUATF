<?php

namespace App\Auth\Demo;

use App\Models\Carrera;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Arr;

class DemoUserProvider implements UserProvider
{
    private string $passwordHash;

    /**
     * @param  array<int, array<string, mixed>>  $accounts
     */
    public function __construct(
        private Hasher $hasher,
        private array $accounts,
        string $password,
    ) {
        $this->passwordHash = $this->hasher->make($password);
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $account = collect($this->accounts)->first(
            fn (array $account): bool => (string) ($account['id'] ?? '') === (string) $identifier,
        );

        return $account ? $this->makeUser($account) : null;
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
        // Las cuentas demo no tienen almacenamiento persistente.
        $user->setRememberToken($token);
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        $email = strtolower(trim((string) Arr::get($credentials, 'email', '')));

        if ($email === '') {
            return null;
        }

        $account = collect($this->accounts)->first(
            fn (array $account): bool => strtolower((string) ($account['email'] ?? '')) === $email,
        );

        return $account ? $this->makeUser($account) : null;
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        return is_string($password) && $this->hasher->check($password, $this->passwordHash);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        // No existe almacenamiento donde persistir una nueva contraseña demo.
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function makeUser(array $account): DemoUser
    {
        $user = new DemoUser;
        $user->setRawAttributes([
            'demo_id' => $account['id'],
            'name' => $account['name'],
            'email' => $account['email'],
            'rol' => $account['rol'],
            'carrera_id' => $account['carrera_id'] ?? null,
            'remember_token' => null,
        ], true);

        if (isset($account['carrera'])) {
            $career = new Carrera;
            $career->setRawAttributes([
                'id' => $account['carrera_id'],
                'sigla' => $account['carrera']['sigla'],
                'nombre' => $account['carrera']['nombre'],
            ], true);
            $user->setRelation('carrera', $career);
        }

        return $user;
    }
}
