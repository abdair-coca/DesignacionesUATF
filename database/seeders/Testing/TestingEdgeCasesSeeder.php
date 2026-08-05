<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class TestingEdgeCasesSeeder extends Seeder
{
    public function run(): void
    {
        TestingSeederSafety::assertSafe();
        $summary = TestingDatasetSupport::seedEdgeCases([
            'careers' => 2,
            'subjects' => 4,
            'groups' => 4,
            'teachers' => 4,
            'gestiones' => 3,
            'periodos' => 3,
            'users' => 5,
            'workflow' => 5,
        ]);
        $validation = TestingDatasetValidator::validate();
        $this->command?->info(json_encode(['profile' => 'edge', 'summary' => $summary['counts'], 'validation' => $validation, 'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2)], JSON_UNESCAPED_UNICODE));
    }
}
