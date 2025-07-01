<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Http\Requests\EmpleadoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Empleado::class);

        try {
            $query = Empleado::query();

            // Filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('position', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('position')) {
                $query->where('position', 'like', '%' . $request->get('position') . '%');
            }

            if ($request->filled('salary_min')) {
                $query->where('salary', '>=', $request->get('salary_min'));
            }

            if ($request->filled('salary_max')) {
                $query->where('salary', '<=', $request->get('salary_max'));
            }

            $empleados = $query->latest()->paginate(15);

            // Respuesta AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $empleados,
                    'message' => 'Empleados obtenidos correctamente'
                ]);
            }

            return view('empleados.index', compact('empleados'));

        } catch (\Exception $e) {
            Log::error('Error al obtener empleados: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener los empleados'
                ], 500);
            }

            return back()->with('error', 'Error al obtener los empleados');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Empleado::class);
        
        return view('empleados.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmpleadoRequest $request)
    {
        $this->authorize('create', Empleado::class);

        try {
            DB::beginTransaction();

            $empleado = Empleado::create($request->validated());

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $empleado,
                    'message' => 'Empleado creado exitosamente'
                ], 201);
            }

            return redirect()->route('empleados.index')
                ->with('success', 'Empleado creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear empleado: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el empleado: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al crear el empleado: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Empleado $empleado, Request $request)
    {
        $this->authorize('view', $empleado);

        try {
            $empleado->load(['ordenesTrabajo.cliente', 'ordenesTrabajo.vehiculo', 'ordenesTrabajo.servicio']);

            // Estadísticas del empleado
            $estadisticas = [
                'total_ordenes' => $empleado->ordenesTrabajo()->count(),
                'ordenes_completadas' => $empleado->ordenesTrabajo()->where('status', 'completed')->count(),
                'ordenes_pendientes' => $empleado->ordenesTrabajo()->where('status', 'pending')->count(),
                'ordenes_en_proceso' => $empleado->ordenesTrabajo()->where('status', 'in_progress')->count(),
                'ingresos_generados' => $empleado->ordenesTrabajo()->where('status', 'completed')->sum('total_amount'),
                'promedio_ordenes_mes' => $empleado->ordenesTrabajo()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $empleado,
                    'estadisticas' => $estadisticas,
                    'message' => 'Empleado obtenido correctamente'
                ]);
            }

            return view('empleados.show', compact('empleado', 'estadisticas'));

        } catch (\Exception $e) {
            Log::error('Error al obtener empleado: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener el empleado'
                ], 500);
            }

            return back()->with('error', 'Error al obtener el empleado');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empleado $empleado): View
    {
        $this->authorize('update', $empleado);
        
        return view('empleados.edit', compact('empleado'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmpleadoRequest $request, Empleado $empleado)
    {
        $this->authorize('update', $empleado);

        try {
            DB::beginTransaction();

            $empleado->update($request->validated());

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $empleado->fresh(),
                    'message' => 'Empleado actualizado exitosamente'
                ]);
            }

            return redirect()->route('empleados.index')
                ->with('success', 'Empleado actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar empleado: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el empleado: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al actualizar el empleado: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empleado $empleado, Request $request)
    {
        $this->authorize('delete', $empleado);

        try {
            DB::beginTransaction();

            // Verificar si tiene órdenes de trabajo activas
            $ordenesActivas = $empleado->ordenesTrabajo()
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            if ($ordenesActivas > 0) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el empleado porque tiene órdenes de trabajo activas'
                    ], 422);
                }

                return back()->with('error', 'No se puede eliminar el empleado porque tiene órdenes de trabajo activas');
            }

            // Soft delete
            $empleado->update(['status' => false]);
            // O usar: $empleado->delete(); para hard delete

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Empleado eliminado exitosamente'
                ]);
            }

            return redirect()->route('empleados.index')
                ->with('success', 'Empleado eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar empleado: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el empleado: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al eliminar el empleado: ' . $e->getMessage());
        }
    }

    /**
     * Get active employees for select options
     */
    public function getActive(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Empleado::class);

        try {
            $empleados = Empleado::where('status', true)
                ->orderBy('name')
                ->get(['id', 'name', 'position']);

            return response()->json([
                'success' => true,
                'data' => $empleados,
                'message' => 'Empleados activos obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener empleados activos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los empleados activos'
            ], 500);
        }
    }

    /**
     * Get employee performance report
     */
    public function getPerformance(Empleado $empleado, Request $request): JsonResponse
    {
        $this->authorize('view', $empleado);

        try {
            $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth());
            $fechaFin = $request->get('fecha_fin', now()->endOfMonth());

            $performance = [
                'ordenes_completadas' => $empleado->ordenesTrabajo()
                    ->where('status', 'completed')
                    ->whereBetween('end_date', [$fechaInicio, $fechaFin])
                    ->count(),
                'ingresos_generados' => $empleado->ordenesTrabajo()
                    ->where('status', 'completed')
                    ->whereBetween('end_date', [$fechaInicio, $fechaFin])
                    ->sum('total_amount'),
                'tiempo_promedio' => $empleado->ordenesTrabajo()
                    ->where('status', 'completed')
                    ->whereBetween('end_date', [$fechaInicio, $fechaFin])
                    ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_days')
                    ->value('avg_days'),
                'servicios_mas_realizados' => $empleado->ordenesTrabajo()
                    ->with('servicio')
                    ->where('status', 'completed')
                    ->whereBetween('end_date', [$fechaInicio, $fechaFin])
                    ->get()
                    ->groupBy('servicio.name')
                    ->map->count()
                    ->sortDesc()
                    ->take(5),
            ];

            return response()->json([
                'success' => true,
                'data' => $performance,
                'message' => 'Reporte de rendimiento obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener reporte de rendimiento: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el reporte de rendimiento'
            ], 500);
        }
    }
}
