<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Http\Requests\ServicioRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Servicio::class);

        try {
            $query = Servicio::query();

            // Filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('price_min')) {
                $query->where('price', '>=', $request->get('price_min'));
            }

            if ($request->filled('price_max')) {
                $query->where('price', '<=', $request->get('price_max'));
            }

            $servicios = $query->latest()->paginate(15);

            // Respuesta AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $servicios,
                    'message' => 'Servicios obtenidos correctamente'
                ]);
            }

            return view('servicios.index', compact('servicios'));

        } catch (\Exception $e) {
            Log::error('Error al obtener servicios: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener los servicios'
                ], 500);
            }

            return back()->with('error', 'Error al obtener los servicios');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Servicio::class);
        
        return view('servicios.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServicioRequest $request)
    {
        $this->authorize('create', Servicio::class);

        try {
            DB::beginTransaction();

            $servicio = Servicio::create($request->validated());

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $servicio,
                    'message' => 'Servicio creado exitosamente'
                ], 201);
            }

            return redirect()->route('servicios.index')
                ->with('success', 'Servicio creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear servicio: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el servicio: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al crear el servicio: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Servicio $servicio, Request $request)
    {
        $this->authorize('view', $servicio);

        try {
            $servicio->load(['ordenesTrabajo.cliente', 'ordenesTrabajo.vehiculo']);

            // Estadísticas del servicio
            $estadisticas = [
                'total_ordenes' => $servicio->ordenesTrabajo()->count(),
                'ordenes_completadas' => $servicio->ordenesTrabajo()->where('status', 'completed')->count(),
                'ordenes_pendientes' => $servicio->ordenesTrabajo()->where('status', 'pending')->count(),
                'ingresos_total' => $servicio->ordenesTrabajo()->where('status', 'completed')->sum('total_amount'),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $servicio,
                    'estadisticas' => $estadisticas,
                    'message' => 'Servicio obtenido correctamente'
                ]);
            }

            return view('servicios.show', compact('servicio', 'estadisticas'));

        } catch (\Exception $e) {
            Log::error('Error al obtener servicio: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener el servicio'
                ], 500);
            }

            return back()->with('error', 'Error al obtener el servicio');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Servicio $servicio): View
    {
        $this->authorize('update', $servicio);
        
        return view('servicios.edit', compact('servicio'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServicioRequest $request, Servicio $servicio)
    {
        $this->authorize('update', $servicio);

        try {
            DB::beginTransaction();

            $servicio->update($request->validated());

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $servicio->fresh(),
                    'message' => 'Servicio actualizado exitosamente'
                ]);
            }

            return redirect()->route('servicios.index')
                ->with('success', 'Servicio actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar servicio: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el servicio: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al actualizar el servicio: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $servicio, Request $request)
    {
        $this->authorize('delete', $servicio);

        try {
            DB::beginTransaction();

            // Verificar si tiene órdenes de trabajo activas
            $ordenesActivas = $servicio->ordenesTrabajo()
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            if ($ordenesActivas > 0) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el servicio porque tiene órdenes de trabajo activas'
                    ], 422);
                }

                return back()->with('error', 'No se puede eliminar el servicio porque tiene órdenes de trabajo activas');
            }

            // Soft delete
            $servicio->update(['status' => false]);
            // O usar: $servicio->delete(); para hard delete

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Servicio eliminado exitosamente'
                ]);
            }

            return redirect()->route('servicios.index')
                ->with('success', 'Servicio eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar servicio: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el servicio: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al eliminar el servicio: ' . $e->getMessage());
        }
    }

    /**
     * Get active services for select options
     */
    public function getActive(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Servicio::class);

        try {
            $servicios = Servicio::where('status', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'duration_hours']);

            return response()->json([
                'success' => true,
                'data' => $servicios,
                'message' => 'Servicios activos obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener servicios activos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los servicios activos'
            ], 500);
        }
    }
}
