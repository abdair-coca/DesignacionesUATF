<?php

namespace Database\Seeders\Testing;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestingUsersSeeder extends Seeder
{
    public const PASSWORD = 'testing-password';

    public function run(): void
    {
        TestingSeederSafety::assertSafe();
        $careers = TestingDatasetSupport::ensureCareers(4);
        self::seedForCareers($careers, 5);
        $this->command?->info('Testing users ready: password documented in DATASETS.md');
    }

    public static function seedForCareers(Collection $careers, int $totalUsers): void
    {
        $totalUsers = max(2, $totalUsers);
        $password = Hash::make(self::PASSWORD);

        User::updateOrCreate(
            ['email' => 'vicerrectorado.testing@example.test'],
            [
                'name' => 'Vicerrectorado de Prueba',
                'password' => $password,
                'rol' => User::ROL_VICERRECTORADO,
                'carrera_id' => null,
            ],
        );

        $careers = $careers->values();
        for ($index = 0; $index < $totalUsers - 1; $index++) {
            $career = $careers[$index % $careers->count()];
            $email = sprintf('director.%s.%02d.testing@example.test', strtolower($career->sigla), $index + 1);

            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Director de Prueba {$career->sigla} ".($index + 1),
                    'password' => $password,
                    'rol' => User::ROL_DIRECTOR_CARRERA,
                    'carrera_id' => $career->id,
                ],
            );
        }
    }
}
