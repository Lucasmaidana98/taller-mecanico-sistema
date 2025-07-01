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
        // Verificar permisos
        $this->authorize('view', 'dashboard');

        try {
            // Obtener estadísticas del dashboard
            $statistics = [
                'total_clientes' => Cliente::where('status', true)->count(),
                'total_vehiculos' => Vehiculo::where('status', true)->count(),
                'ordenes_pendientes' => OrdenTrabajo::where('status', 'pending')->count(),
                'ordenes_completadas' => OrdenTrabajo::where('status', 'completed')->count(),
                'ordenes_en_proceso' => OrdenTrabajo::where('status', 'in_progress')->count(),
                'total_empleados' => Empleado::where('status', true)->count(),
                'servicios_disponibles' => Servicio::where('status', true)->count(),
            ];

            // Obtener órdenes recientes para mostrar en el dashboard
            $ordenes_recientes = OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])
                ->latest()
                ->take(5)
                ->get();

            // Obtener ingresos del mes actual
            $ingresos_mes = OrdenTrabajo::where('status', 'completed')
                ->whereMonth('end_date', now()->month)
                ->whereYear('end_date', now()->year)
                ->sum('total_amount');

            return view('dashboard.index', compact(
                'statistics',
                'ordenes_recientes',
                'ingresos_mes'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Error al cargar el dashboard: ' . $e->getMessage());
        }
    }
}
