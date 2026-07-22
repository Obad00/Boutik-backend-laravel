<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\PlatformStatsService;

class StatsController extends Controller
{
    public function __construct(private readonly PlatformStatsService $stats)
    {
    }

    public function index()
    {
        return response()->json($this->stats->getStats());
    }
}
