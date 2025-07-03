<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;

class ReporteController extends Controller
{
    /**
     * Display a listing of reports.
     */
    public function index(Request $request): View
    {
        try {
            // Generar estadísticas básicas para mostrar en la vista
            $stats = [
                'ordenes_completadas' => OrdenTrabajo::where('status', 'completed')->count(),
                'ingresos_totales' => OrdenTrabajo::where('status', 'completed')->sum('total_amount'),
                'clientes_atendidos' => OrdenTrabajo::distinct('cliente_id')->count('cliente_id'),
                'promedio_orden' => OrdenTrabajo::where('status', 'completed')->avg('total_amount') ?? 0,
            ];

            // Initialize empty arrays for the reports
            $reporteServicios = [];
            $reporteEmpleados = [];
            $ordenes = [];

            return view('reportes.index_simple', compact('stats'));

        } catch (\Exception $e) {
            Log::error('Error al cargar página de reportes: ' . $e->getMessage());
            
            // Estadísticas por defecto en caso de error
            $stats = [
                'ordenes_completadas' => 0,
                'ingresos_totales' => 0,
                'clientes_atendidos' => 0,
                'promedio_orden' => 0,
            ];

            // Initialize empty arrays for the reports
            $reporteServicios = [];
            $reporteEmpleados = [];
            $ordenes = [];

            return view('reportes.index_simple', compact('stats'));
        }
    }

    /**
     * Generate a new report with filters.
     */
    public function generar(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_reporte' => 'required|in:ordenes,clientes,empleados,servicios,ingresos,vehiculos',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'cliente_id' => 'nullable|exists:clientes,id',
            'empleado_id' => 'nullable|exists:empleados,id',
            'servicio_id' => 'nullable|exists:servicios,id',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
        ]);

        try {
            $tipoReporte = $request->get('tipo_reporte');
            $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth());
            $fechaFin = $request->get('fecha_fin', now()->endOfMonth());

            $data = [];

            switch ($tipoReporte) {
                case 'ordenes':
                    $data = $this->generarReporteOrdenes($request, $fechaInicio, $fechaFin);
                    break;
                case 'clientes':
                    $data = $this->generarReporteClientes($request, $fechaInicio, $fechaFin);
                    break;
                case 'empleados':
                    $data = $this->generarReporteEmpleados($request, $fechaInicio, $fechaFin);
                    break;
                case 'servicios':
                    $data = $this->generarReporteServicios($request, $fechaInicio, $fechaFin);
                    break;
                case 'ingresos':
                    $data = $this->generarReporteIngresos($request, $fechaInicio, $fechaFin);
                    break;
                case 'vehiculos':
                    $data = $this->generarReporteVehiculos($request, $fechaInicio, $fechaFin);
                    break;
                default:
                    throw new \Exception('Tipo de reporte no válido');
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'tipo_reporte' => $tipoReporte,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'message' => 'Reporte generado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al generar reporte: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to PDF using dompdf.
     */
    public function exportarPDF(Request $request): Response
    {
        $request->validate([
            'tipo_reporte' => 'required|in:ordenes,clientes,empleados,servicios,ingresos,vehiculos',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        try {
            $tipoReporte = $request->get('tipo_reporte');
            $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth());
            $fechaFin = $request->get('fecha_fin', now()->endOfMonth());

            // Generar los datos del reporte
            $data = [];
            switch ($tipoReporte) {
                case 'ordenes':
                    $data = $this->generarReporteOrdenes($request, $fechaInicio, $fechaFin);
                    break;
                case 'clientes':
                    $data = $this->generarReporteClientes($request, $fechaInicio, $fechaFin);
                    break;
                case 'empleados':
                    $data = $this->generarReporteEmpleados($request, $fechaInicio, $fechaFin);
                    break;
                case 'servicios':
                    $data = $this->generarReporteServicios($request, $fechaInicio, $fechaFin);
                    break;
                case 'ingresos':
                    $data = $this->generarReporteIngresos($request, $fechaInicio, $fechaFin);
                    break;
                case 'vehiculos':
                    $data = $this->generarReporteVehiculos($request, $fechaInicio, $fechaFin);
                    break;
            }

            // Configurar dompdf
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);

            // Generar el HTML del reporte
            $html = view('reportes.pdf.' . $tipoReporte, [
                'data' => $data,
                'tipoReporte' => $tipoReporte,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'fechaGeneracion' => now(),
            ])->render();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = "reporte_{$tipoReporte}_" . now()->format('Y-m-d_H-i-s') . '.pdf';

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Error al exportar PDF: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte a PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate ordenes de trabajo report.
     */
    private function generarReporteOrdenes(Request $request, $fechaInicio, $fechaFin): array
    {
        $query = OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->get('cliente_id'));
        }

        if ($request->filled('empleado_id')) {
            $query->where('empleado_id', $request->get('empleado_id'));
        }

        if ($request->filled('servicio_id')) {
            $query->where('servicio_id', $request->get('servicio_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $ordenes = $query->orderBy('created_at', 'desc')->get();

        $estadisticas = [
            'total_ordenes' => $ordenes->count(),
            'ordenes_pendientes' => $ordenes->where('status', 'pending')->count(),
            'ordenes_en_proceso' => $ordenes->where('status', 'in_progress')->count(),
            'ordenes_completadas' => $ordenes->where('status', 'completed')->count(),
            'ordenes_canceladas' => $ordenes->where('status', 'cancelled')->count(),
            'ingresos_total' => $ordenes->where('status', 'completed')->sum('total_amount'),
            'tiempo_promedio_completado' => $ordenes->where('status', 'completed')
                ->filter(function ($orden) {
                    return $orden->start_date && $orden->end_date;
                })
                ->avg(function ($orden) {
                    return Carbon::parse($orden->start_date)->diffInDays(Carbon::parse($orden->end_date));
                })
        ];

        return [
            'ordenes' => $ordenes,
            'estadisticas' => $estadisticas
        ];
    }

    /**
     * Generate clientes report.
     */
    private function generarReporteClientes(Request $request, $fechaInicio, $fechaFin): array
    {
        $clientes = Cliente::with(['vehiculos', 'ordenesTrabajo' => function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }])->where('status', true)->get();

        $clientesConEstadisticas = $clientes->map(function ($cliente) {
            return [
                'cliente' => $cliente,
                'total_ordenes' => $cliente->ordenesTrabajo->count(),
                'ordenes_completadas' => $cliente->ordenesTrabajo->where('status', 'completed')->count(),
                'total_gastado' => $cliente->ordenesTrabajo->where('status', 'completed')->sum('total_amount'),
                'total_vehiculos' => $cliente->vehiculos->count(),
            ];
        });

        $estadisticas = [
            'total_clientes' => $clientes->count(),
            'clientes_con_ordenes' => $clientes->filter(function ($cliente) {
                return $cliente->ordenesTrabajo->count() > 0;
            })->count(),
            'ingreso_promedio_por_cliente' => $clientesConEstadisticas->avg('total_gastado'),
            'cliente_mayor_gasto' => $clientesConEstadisticas->sortByDesc('total_gastado')->first(),
        ];

        return [
            'clientes' => $clientesConEstadisticas,
            'estadisticas' => $estadisticas
        ];
    }

    /**
     * Generate empleados report.
     */
    private function generarReporteEmpleados(Request $request, $fechaInicio, $fechaFin): array
    {
        $empleados = Empleado::with(['ordenesTrabajo' => function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }])->where('status', true)->get();

        $empleadosConEstadisticas = $empleados->map(function ($empleado) {
            return [
                'empleado' => $empleado,
                'total_ordenes' => $empleado->ordenesTrabajo->count(),
                'ordenes_completadas' => $empleado->ordenesTrabajo->where('status', 'completed')->count(),
                'ingresos_generados' => $empleado->ordenesTrabajo->where('status', 'completed')->sum('total_amount'),
                'eficiencia' => $empleado->ordenesTrabajo->count() > 0 
                    ? ($empleado->ordenesTrabajo->where('status', 'completed')->count() / $empleado->ordenesTrabajo->count()) * 100 
                    : 0,
            ];
        });

        $estadisticas = [
            'total_empleados' => $empleados->count(),
            'empleados_con_ordenes' => $empleados->filter(function ($empleado) {
                return $empleado->ordenesTrabajo->count() > 0;
            })->count(),
            'empleado_mas_productivo' => $empleadosConEstadisticas->sortByDesc('ordenes_completadas')->first(),
            'empleado_mayor_ingresos' => $empleadosConEstadisticas->sortByDesc('ingresos_generados')->first(),
        ];

        return [
            'empleados' => $empleadosConEstadisticas,
            'estadisticas' => $estadisticas
        ];
    }

    /**
     * Generate servicios report.
     */
    private function generarReporteServicios(Request $request, $fechaInicio, $fechaFin): array
    {
        $servicios = Servicio::with(['ordenesTrabajo' => function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }])->where('status', true)->get();

        $serviciosConEstadisticas = $servicios->map(function ($servicio) {
            return [
                'servicio' => $servicio,
                'total_ordenes' => $servicio->ordenesTrabajo->count(),
                'ordenes_completadas' => $servicio->ordenesTrabajo->where('status', 'completed')->count(),
                'ingresos_generados' => $servicio->ordenesTrabajo->where('status', 'completed')->sum('total_amount'),
                'tiempo_promedio' => $servicio->duration_hours,
            ];
        });

        $estadisticas = [
            'total_servicios' => $servicios->count(),
            'servicio_mas_solicitado' => $serviciosConEstadisticas->sortByDesc('total_ordenes')->first(),
            'servicio_mayor_ingresos' => $serviciosConEstadisticas->sortByDesc('ingresos_generados')->first(),
            'ingresos_total_servicios' => $serviciosConEstadisticas->sum('ingresos_generados'),
        ];

        return [
            'servicios' => $serviciosConEstadisticas,
            'estadisticas' => $estadisticas
        ];
    }

    /**
     * Generate ingresos report.
     */
    private function generarReporteIngresos(Request $request, $fechaInicio, $fechaFin): array
    {
        $ordenes = OrdenTrabajo::with(['cliente', 'servicio'])
            ->where('status', 'completed')
            ->whereBetween('end_date', [$fechaInicio, $fechaFin])
            ->get();

        // Agrupar por mes
        $ingresosPorMes = $ordenes->groupBy(function ($orden) {
            return Carbon::parse($orden->end_date)->format('Y-m');
        })->map(function ($ordenesMes) {
            return [
                'total' => $ordenesMes->sum('total_amount'),
                'cantidad_ordenes' => $ordenesMes->count(),
                'promedio' => $ordenesMes->avg('total_amount'),
            ];
        });

        // Agrupar por servicio
        $ingresosPorServicio = $ordenes->groupBy('servicio.name')->map(function ($ordenesServicio) {
            return [
                'total' => $ordenesServicio->sum('total_amount'),
                'cantidad_ordenes' => $ordenesServicio->count(),
                'promedio' => $ordenesServicio->avg('total_amount'),
            ];
        });

        $estadisticas = [
            'ingresos_total' => $ordenes->sum('total_amount'),
            'total_ordenes' => $ordenes->count(),
            'ingreso_promedio_orden' => $ordenes->avg('total_amount'),
            'mejor_mes' => $ingresosPorMes->sortByDesc('total')->first(),
            'mejor_servicio' => $ingresosPorServicio->sortByDesc('total')->first(),
        ];

        return [
            'ordenes' => $ordenes,
            'ingresos_por_mes' => $ingresosPorMes,
            'ingresos_por_servicio' => $ingresosPorServicio,
            'estadisticas' => $estadisticas
        ];
    }

    /**
     * Generate vehiculos report.
     */
    private function generarReporteVehiculos(Request $request, $fechaInicio, $fechaFin): array
    {
        $vehiculos = Vehiculo::with(['cliente', 'ordenesTrabajo' => function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }])->where('status', true)->get();

        $vehiculosConEstadisticas = $vehiculos->map(function ($vehiculo) {
            return [
                'vehiculo' => $vehiculo,
                'total_ordenes' => $vehiculo->ordenesTrabajo->count(),
                'ordenes_completadas' => $vehiculo->ordenesTrabajo->where('status', 'completed')->count(),
                'total_gastado' => $vehiculo->ordenesTrabajo->where('status', 'completed')->sum('total_amount'),
                'ultima_orden' => $vehiculo->ordenesTrabajo->sortByDesc('created_at')->first(),
            ];
        });

        // Agrupar por marca
        $vehiculosPorMarca = $vehiculos->groupBy('brand')->map->count();

        $estadisticas = [
            'total_vehiculos' => $vehiculos->count(),
            'vehiculos_con_ordenes' => $vehiculos->filter(function ($vehiculo) {
                return $vehiculo->ordenesTrabajo->count() > 0;
            })->count(),
            'marca_mas_comun' => $vehiculosPorMarca->sortDesc()->first(),
            'vehiculo_mas_servicios' => $vehiculosConEstadisticas->sortByDesc('total_ordenes')->first(),
        ];

        return [
            'vehiculos' => $vehiculosConEstadisticas,
            'vehiculos_por_marca' => $vehiculosPorMarca,
            'estadisticas' => $estadisticas
        ];
    }

    /**
     * Get available filter options for reports.
     */
    public function getFilterOptions(): JsonResponse
    {
        try {
            $data = [
                'clientes' => Cliente::where('status', true)->orderBy('name')->get(['id', 'name']),
                'empleados' => Empleado::where('status', true)->orderBy('name')->get(['id', 'name']),
                'servicios' => Servicio::where('status', true)->orderBy('name')->get(['id', 'name']),
                'status_options' => [
                    'pending' => 'Pendiente',
                    'in_progress' => 'En Proceso',
                    'completed' => 'Completada',
                    'cancelled' => 'Cancelada'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Opciones de filtro obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener opciones de filtro: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las opciones de filtro'
            ], 500);
        }
    }
}
