<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IndexController extends Controller
{
    public function showIndex(Request $request, DashboardService $dashboardService)
    {
        $view = $dashboardService->handleIndexView($request);

        return Inertia::render($view['name'], $view['data']);
    }
}
