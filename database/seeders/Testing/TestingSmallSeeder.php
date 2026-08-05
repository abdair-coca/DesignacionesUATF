<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class TestingSmallSeeder extends Seeder
{
    public function run(): void
    {
        TestingSeederSafety::assertSafe();
        $summary = TestingDatasetSupport::seed([
            'careers' => 4,
            'subjects' => 6,
            'groups' => 2,
            'teachers' => 6,
            'gestiones' => 3,
            'periodos' => 3,
            'users' => 5,
            'workflow' => 5,
        ]);
        $validation = TestingDatasetValidator::validate();
        $this->command?->info(json_encode(['profile' => 'small', 'summary' => $summary['counts'], 'validation' => $validation, 'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2)], JSON_UNESCAPED_UNICODE));
    }
}
