<?php

namespace App\Http\Controllers;

use App\Support\ApplicationHealth;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(ApplicationHealth $health): JsonResponse
    {
        $report = $health->check();

        return response()->json($report, $report['status'] === 'ok' ? 200 : 503);
    }
}
