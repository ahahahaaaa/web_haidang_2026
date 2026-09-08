<?php

namespace App\Http\Controllers;

use App\Services\Cms\DashboardStatsService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardStatsService $dashboardStats): View
    {
        return view('dashboard', [
            'cards' => $dashboardStats->cards(),
        ]);
    }
}
