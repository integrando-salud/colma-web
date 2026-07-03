<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Services\AlertaService;

class AlertaController extends Controller
{

    public function cancelAlerta(Alerta $alerta, AlertaService $alertaService) {
        $alertaService->cancelAlert($alerta);
    }
}
