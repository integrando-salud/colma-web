<?php

namespace App\Services;

use Illuminate\Http\Request;

class DashboardService
{
    public function handleIndexView(Request $request)
    {
        switch (auth()->user()->roles->first()->name) {
            case 'supervisor':
                $viewName = 'dashboard.index.supervisor';
                $viewData = [];
                break;
        }

        return [
            'name' => $viewName,
            'data' => $viewData,
        ];
    }
}
