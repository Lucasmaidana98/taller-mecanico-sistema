<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Http\Requests\OrdenTrabajoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdenTrabajoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        try {
            $query = OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio']);

            // Filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($search) {
                            $clienteQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('vehiculo', function ($vehiculoQuery) use ($search) {
                            $vehiculoQuery->where('license_plate', 'like', "%{$search}%")
                                ->orWhere('brand', 'like', "%{$search}%")
                                ->orWhere('model', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->get('cliente_id'));
            }

            if ($request->filled('empleado_id')) {
                $query->where('empleado_id', $request->get('empleado_id'));
            }

            if ($request->filled('servicio_id')) {
                $query->where('servicio_id', $request->get('servicio_id'));
            }

            if ($request->filled('fecha_inicio')) {
                $query->where('start_date', '>=', $request->get('fecha_inicio'));
            }

            if ($request->filled('fecha_fin')) {
                $query->where('start_date', '<=', $request->get('fecha_fin'));
            }

            // Ordenamiento
            $orderBy = $request->get('order_by', 'created_at');
            $orderDirection = $request->get('order_direction', 'desc');
            $query->orderBy($orderBy, $orderDirection);

            $ordenes = $query->paginate(15);

            // Respuesta AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $ordenes,
                    'message' => 'Órdenes de trabajo obtenidas correctamente'
                ]);
            }

            // Obtener datos para filtros
            $clientes = Cliente::where('status', true)->orderBy('name')->get();
            $vehiculos = Vehiculo::where('status', true)->orderBy('brand')->get();
            $empleados = Empleado::where('status', true)->orderBy('name')->get();
            $servicios = Servicio::where('status', true)->orderBy('name')->get();

            return view('ordenes.index', compact('ordenes', 'clientes', 'vehiculos', 'empleados', 'servicios'));

        } catch (\Exception $e) {
            Log::error('Error al obtener órdenes de trabajo: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener las órdenes de trabajo'
                ], 500);
            }

            return back()->with('error', 'Error al obtener las órdenes de trabajo');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        
        $clientes = Cliente::where('status', true)->orderBy('name')->get();
        $vehiculos = Vehiculo::where('status', true)->orderBy('brand')->get();
        $empleados = Empleado::where('status', true)->orderBy('name')->get();
        $servicios = Servicio::where('status', true)->orderBy('name')->get();
        
        return view('ordenes.create', compact('clientes', 'vehiculos', 'empleados', 'servicios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OrdenTrabajoRequest $request)
    {

        try {
            DB::beginTransaction();

            $data = $request->validated();
            
            // Si no se proporciona el monto total, calcularlo basado en el servicio
            if (!isset($data['total_amount']) || $data['total_amount'] === null) {
                $servicio = Servicio::find($data['servicio_id']);
                $data['total_amount'] = $servicio ? $servicio->price : 0;
            }

            $ordenTrabajo = OrdenTrabajo::create($data);
            $ordenTrabajo->load(['cliente', 'vehiculo', 'empleado', 'servicio']);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $ordenTrabajo,
                    'message' => 'Orden de trabajo creada exitosamente'
                ], 201);
            }

            return redirect()->route('ordenes.index')
                ->with('success', 'Orden de trabajo creada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear orden de trabajo: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la orden de trabajo: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al crear la orden de trabajo: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OrdenTrabajo $ordenTrabajo, Request $request)
    {

        try {
            $ordenTrabajo->load(['cliente', 'vehiculo', 'empleado', 'servicio']);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $ordenTrabajo,
                    'message' => 'Orden de trabajo obtenida correctamente'
                ]);
            }

            return view('ordenes.show', compact('ordenTrabajo'));

        } catch (\Exception $e) {
            Log::error('Error al obtener orden de trabajo: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener la orden de trabajo'
                ], 500);
            }

            return back()->with('error', 'Error al obtener la orden de trabajo');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrdenTrabajo $ordenTrabajo): View
    {
        
        $clientes = Cliente::where('status', true)->orderBy('name')->get();
        $vehiculos = Vehiculo::where('cliente_id', $ordenTrabajo->cliente_id)
            ->where('status', true)
            ->orderBy('brand')
            ->get();
        $empleados = Empleado::where('status', true)->orderBy('name')->get();
        $servicios = Servicio::where('status', true)->orderBy('name')->get();
        
        return view('ordenes.edit', compact('ordenTrabajo', 'clientes', 'vehiculos', 'empleados', 'servicios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OrdenTrabajoRequest $request, OrdenTrabajo $ordenTrabajo)
    {

        try {
            DB::beginTransaction();

            $data = $request->validated();
            
            // Si se cambió el servicio y no se especificó un nuevo monto, actualizarlo
            if (isset($data['servicio_id']) && $data['servicio_id'] != $ordenTrabajo->servicio_id) {
                if (!isset($data['total_amount']) || $data['total_amount'] == $ordenTrabajo->total_amount) {
                    $servicio = Servicio::find($data['servicio_id']);
                    $data['total_amount'] = $servicio ? $servicio->price : $ordenTrabajo->total_amount;
                }
            }

            $ordenTrabajo->update($data);
            $ordenTrabajo->load(['cliente', 'vehiculo', 'empleado', 'servicio']);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $ordenTrabajo->fresh(['cliente', 'vehiculo', 'empleado', 'servicio']),
                    'message' => 'Orden de trabajo actualizada exitosamente'
                ]);
            }

            return redirect()->route('ordenes.index')
                ->with('success', 'Orden de trabajo actualizada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar orden de trabajo: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar la orden de trabajo: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al actualizar la orden de trabajo: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrdenTrabajo $ordenTrabajo, Request $request)
    {

        try {
            DB::beginTransaction();

            // Verificar si la orden está en proceso o completada
            if (in_array($ordenTrabajo->status, ['in_progress', 'completed'])) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar una orden de trabajo que está en proceso o completada'
                    ], 422);
                }

                return back()->with('error', 'No se puede eliminar una orden de trabajo que está en proceso o completada');
            }

            $ordenTrabajo->delete();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Orden de trabajo eliminada exitosamente'
                ]);
            }

            return redirect()->route('ordenes.index')
                ->with('success', 'Orden de trabajo eliminada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar orden de trabajo: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la orden de trabajo: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al eliminar la orden de trabajo: ' . $e->getMessage());
        }
    }

    /**
     * Update the status of an orden de trabajo
     */
    public function updateStatus(Request $request, OrdenTrabajo $ordenTrabajo): JsonResponse
    {

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $ordenTrabajo->status;
            $newStatus = $request->status;

            // Lógica de negocio para cambios de estado
            if ($newStatus === 'completed' && !$ordenTrabajo->end_date) {
                $ordenTrabajo->end_date = now();
            }

            if ($newStatus === 'in_progress' && !$ordenTrabajo->start_date) {
                $ordenTrabajo->start_date = now();
            }

            $ordenTrabajo->status = $newStatus;
            $ordenTrabajo->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $ordenTrabajo->fresh(['cliente', 'vehiculo', 'empleado', 'servicio']),
                'message' => "Estado de la orden actualizado de '{$oldStatus}' a '{$newStatus}'"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar estado de orden: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado de la orden'
            ], 500);
        }
    }

    /**
     * Get dashboard data for ordenes de trabajo
     */
    public function getDashboardData(Request $request): JsonResponse
    {

        try {
            $data = [
                'total_ordenes' => OrdenTrabajo::count(),
                'ordenes_pendientes' => OrdenTrabajo::where('status', 'pending')->count(),
                'ordenes_en_proceso' => OrdenTrabajo::where('status', 'in_progress')->count(),
                'ordenes_completadas' => OrdenTrabajo::where('status', 'completed')->count(),
                'ordenes_canceladas' => OrdenTrabajo::where('status', 'cancelled')->count(),
                'ingresos_mes' => OrdenTrabajo::where('status', 'completed')
                    ->whereMonth('end_date', now()->month)
                    ->whereYear('end_date', now()->year)
                    ->sum('total_amount'),
                'ordenes_por_empleado' => OrdenTrabajo::with('empleado')
                    ->where('status', 'completed')
                    ->whereMonth('end_date', now()->month)
                    ->whereYear('end_date', now()->year)
                    ->get()
                    ->groupBy('empleado.name')
                    ->map->count()
                    ->sortDesc()
                    ->take(5),
                'servicios_mas_solicitados' => OrdenTrabajo::with('servicio')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->get()
                    ->groupBy('servicio.name')
                    ->map->count()
                    ->sortDesc()
                    ->take(5)
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Datos del dashboard obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener datos del dashboard: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos del dashboard'
            ], 500);
        }
    }

    /**
     * Print orden de trabajo
     */
    public function print(OrdenTrabajo $ordenTrabajo): View
    {

        $ordenTrabajo->load(['cliente', 'vehiculo', 'empleado', 'servicio']);

        return view('ordenes.print', compact('ordenTrabajo'));
    }
}
