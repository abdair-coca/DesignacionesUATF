<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;
use InvalidArgumentException;

class TestingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        TestingSeederSafety::assertSafe();
        $profile = strtolower((string) env('TESTING_DATASET_PROFILE', ''));
        $class = match ($profile) {
            'small' => TestingSmallSeeder::class,
            'normal' => TestingNormalSeeder::class,
            'large' => TestingLargeSeeder::class,
            'edge', 'edge-cases' => TestingEdgeCasesSeeder::class,
            default => throw new InvalidArgumentException(
                'Set TESTING_DATASET_PROFILE=small|normal|large|edge or call a profile seeder explicitly.',
            ),
        };

        $this->call($class);
    }
}
