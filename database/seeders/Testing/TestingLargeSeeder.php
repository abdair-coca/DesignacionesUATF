<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class TestingLargeSeeder extends Seeder
{
    public function run(): void
    {
        TestingSeederSafety::assertSafe();
        $multiplier = max(0.01, (float) env('TESTING_LARGE_MULTIPLIER', '1'));
        $scale = static fn (int $value): int => max(1, (int) round($value * $multiplier));
        $summary = TestingDatasetSupport::seed([
            'careers' => $scale(20),
            'subjects' => $scale(10),
            'groups' => $scale(5),
            'teachers' => $scale(500),
            'gestiones' => $scale(20),
            'periodos' => $scale(5),
            'users' => $scale(100),
            'workflow' => min(5, $scale(5)),
        ]);
        $validation = TestingDatasetValidator::validate();
        $this->command?->info(json_encode([
            'profile' => 'large',
            'multiplier' => $multiplier,
            'summary' => $summary['counts'],
            'validation' => $validation,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        ], JSON_UNESCAPED_UNICODE));
    }
}
