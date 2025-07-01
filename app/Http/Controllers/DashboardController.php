<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\OrdenTrabajo;
use App\Models\Empleado;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with statistics.
     */
    public function index(): View
    {
        try {
            // Obtener estadísticas del dashboard
            $stats = [
                'total_clientes' => Cliente::where('status', true)->count(),
                'total_vehiculos' => Vehiculo::where('status', true)->count(),
                'ordenes_pendientes' => OrdenTrabajo::where('status', 'pending')->count(),
                'ordenes_completadas' => OrdenTrabajo::where('status', 'completed')->count(),
                'ordenes_proceso' => OrdenTrabajo::where('status', 'in_progress')->count(),
                'total_empleados' => Empleado::where('status', true)->count(),
                'total_servicios' => Servicio::where('status', true)->count(),
            ];

            // Calcular total de órdenes
            $stats['total_ordenes'] = OrdenTrabajo::count();

            // Obtener órdenes recientes para mostrar en el dashboard
            $ordenes_recientes = OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])
                ->latest()
                ->take(5)
                ->get();

            // Obtener ingresos del mes actual
            $stats['ingresos_mes'] = OrdenTrabajo::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount');

            return view('dashboard', compact(
                'stats',
                'ordenes_recientes'
            ));

        } catch (\Exception $e) {
            return view('dashboard', [
                'stats' => [
                    'total_clientes' => 0,
                    'total_vehiculos' => 0,
                    'ordenes_pendientes' => 0,
                    'ordenes_completadas' => 0,
                    'ordenes_proceso' => 0,
                    'total_empleados' => 0,
                    'total_servicios' => 0,
                    'total_ordenes' => 0,
                    'ingresos_mes' => 0,
                ],
                'ordenes_recientes' => collect([])
            ])->with('error', 'Error al cargar el dashboard: ' . $e->getMessage());
        }
    }
}
