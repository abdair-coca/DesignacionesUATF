<?php

namespace App\Console\Commands;

use App\Support\ApplicationHealth;
use Illuminate\Console\Command;

class ApplicationHealthCommand extends Command
{
    protected $signature = 'app:health {--json : Imprime el resultado como JSON}';

    protected $description = 'Verifica la salud de la aplicacion y su base propia';

    public function handle(ApplicationHealth $health): int
    {
        $report = $health->check();

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } else {
            $this->line('Estado: '.$report['status']);

            foreach ($report['checks'] as $name => $check) {
                $this->line(sprintf('%s: %s', $name, $check['status']));
            }
        }

        return $report['status'] === 'ok' ? self::SUCCESS : self::FAILURE;
    }
}
