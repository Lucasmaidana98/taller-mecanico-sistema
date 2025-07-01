<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\Cliente;
use App\Http\Requests\VehiculoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehiculoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Vehiculo::class);

        try {
            $query = Vehiculo::with('cliente');

            // Filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhere('license_plate', 'like', "%{$search}%")
                      ->orWhere('vin', 'like', "%{$search}%")
                      ->orWhereHas('cliente', function ($clienteQuery) use ($search) {
                          $clienteQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->get('cliente_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('brand')) {
                $query->where('brand', 'like', '%' . $request->get('brand') . '%');
            }

            $vehiculos = $query->latest()->paginate(15);

            // Respuesta AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $vehiculos,
                    'message' => 'Vehículos obtenidos correctamente'
                ]);
            }

            $clientes = Cliente::where('status', true)->orderBy('name')->get();

            return view('vehiculos.index', compact('vehiculos', 'clientes'));

        } catch (\Exception $e) {
            Log::error('Error al obtener vehículos: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener los vehículos'
                ], 500);
            }

            return back()->with('error', 'Error al obtener los vehículos');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Vehiculo::class);
        
        $clientes = Cliente::where('status', true)->orderBy('name')->get();
        
        return view('vehiculos.create', compact('clientes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VehiculoRequest $request)
    {
        $this->authorize('create', Vehiculo::class);

        try {
            DB::beginTransaction();

            $vehiculo = Vehiculo::create($request->validated());
            $vehiculo->load('cliente');

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $vehiculo,
                    'message' => 'Vehículo creado exitosamente'
                ], 201);
            }

            return redirect()->route('vehiculos.index')
                ->with('success', 'Vehículo creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear vehículo: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el vehículo: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al crear el vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehiculo $vehiculo, Request $request)
    {
        $this->authorize('view', $vehiculo);

        try {
            $vehiculo->load(['cliente', 'ordenesTrabajo.servicio', 'ordenesTrabajo.empleado']);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $vehiculo,
                    'message' => 'Vehículo obtenido correctamente'
                ]);
            }

            return view('vehiculos.show', compact('vehiculo'));

        } catch (\Exception $e) {
            Log::error('Error al obtener vehículo: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener el vehículo'
                ], 500);
            }

            return back()->with('error', 'Error al obtener el vehículo');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehiculo $vehiculo): View
    {
        $this->authorize('update', $vehiculo);
        
        $clientes = Cliente::where('status', true)->orderBy('name')->get();
        
        return view('vehiculos.edit', compact('vehiculo', 'clientes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VehiculoRequest $request, Vehiculo $vehiculo)
    {
        $this->authorize('update', $vehiculo);

        try {
            DB::beginTransaction();

            $vehiculo->update($request->validated());
            $vehiculo->load('cliente');

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $vehiculo->fresh(['cliente']),
                    'message' => 'Vehículo actualizado exitosamente'
                ]);
            }

            return redirect()->route('vehiculos.index')
                ->with('success', 'Vehículo actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar vehículo: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el vehículo: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al actualizar el vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehiculo $vehiculo, Request $request)
    {
        $this->authorize('delete', $vehiculo);

        try {
            DB::beginTransaction();

            // Verificar si tiene órdenes de trabajo activas
            $ordenesActivas = $vehiculo->ordenesTrabajo()
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            if ($ordenesActivas > 0) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el vehículo porque tiene órdenes de trabajo activas'
                    ], 422);
                }

                return back()->with('error', 'No se puede eliminar el vehículo porque tiene órdenes de trabajo activas');
            }

            // Soft delete
            $vehiculo->update(['status' => false]);
            // O usar: $vehiculo->delete(); para hard delete

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vehículo eliminado exitosamente'
                ]);
            }

            return redirect()->route('vehiculos.index')
                ->with('success', 'Vehículo eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar vehículo: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el vehículo: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al eliminar el vehículo: ' . $e->getMessage());
        }
    }

    /**
     * Get vehicles by cliente for AJAX calls
     */
    public function getByCliente(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('viewAny', Vehiculo::class);

        try {
            $vehiculos = $cliente->vehiculos()
                ->where('status', true)
                ->orderBy('brand')
                ->orderBy('model')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $vehiculos,
                'message' => 'Vehículos del cliente obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener vehículos del cliente: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los vehículos del cliente'
            ], 500);
        }
    }
}
