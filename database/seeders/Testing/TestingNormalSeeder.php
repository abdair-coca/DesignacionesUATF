<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class TestingNormalSeeder extends Seeder
{
    public function run(): void
    {
        TestingSeederSafety::assertSafe();
        $summary = TestingDatasetSupport::seed([
            'careers' => 8,
            'subjects' => 12,
            'groups' => 3,
            'teachers' => 150,
            'gestiones' => 10,
            'periodos' => 5,
            'users' => 20,
            'workflow' => 5,
        ]);
        $validation = TestingDatasetValidator::validate();
        $this->command?->info(json_encode(['profile' => 'normal', 'summary' => $summary['counts'], 'validation' => $validation, 'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2)], JSON_UNESCAPED_UNICODE));
    }
}
