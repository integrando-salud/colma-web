<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use UnexpectedValueException;

class IndexController extends Controller
{
    /**
     * Muestra la pantalla inicial correspondiente al rol de panel del usuario.
     *
     * El servicio determina tanto el componente de Inertia como sus propiedades
     * y filtra los parámetros de la solicitud que puede consumir cada pantalla.
     * Una cuenta autenticada sin un rol de panel válido recibe una respuesta 403
     * en lugar de exponer una excepción interna.
     *
     * @throws AccessDeniedHttpException Si el usuario no tiene un rol compatible con el panel.
     */
    public function showIndex(Request $request, DashboardService $dashboardService): Response
    {
        try {
            $view = $dashboardService->handleIndexView($request);
        } catch (UnexpectedValueException $exception) {
            throw new AccessDeniedHttpException(
                'El usuario autenticado no tiene acceso al panel.',
                $exception,
            );
        }

        return Inertia::render($view['name'], $view['data']);
    }
}
