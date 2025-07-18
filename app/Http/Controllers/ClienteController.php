<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Http\Requests\ClienteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Cliente::query();

            // Filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Get per_page parameter, default to 15
            $perPage = $request->get('per_page', 15);
            $clientes = $query->latest()->paginate($perPage);

            // Get statistics for the dashboard cards (without filters for global stats)
            $stats = [
                'total' => Cliente::count(),
                'activos' => Cliente::where('status', true)->count(),
                'inactivos' => Cliente::where('status', false)->count(),
                'nuevos' => Cliente::where('created_at', '>=', now()->subDays(30))->count(),
            ];

            // Respuesta AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $clientes,
                    'stats' => $stats,
                    'message' => 'Clientes obtenidos correctamente'
                ]);
            }

            return view('clientes.index', compact('clientes', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error al obtener clientes: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener los clientes'
                ], 500);
            }

            return back()->with('error', 'Error al obtener los clientes');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('clientes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClienteRequest $request)
    {
        try {
            DB::beginTransaction();

            $cliente = Cliente::create($request->validated());

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $cliente,
                    'message' => 'Cliente creado exitosamente'
                ], 201);
            }

            return redirect()->route('clientes.index')
                ->with('success', 'Cliente creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cliente: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el cliente: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al crear el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente, Request $request)
    {
        try {
            $cliente->load(['vehiculos', 'ordenesTrabajo.servicio']);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $cliente,
                    'message' => 'Cliente obtenido correctamente'
                ]);
            }

            return view('clientes.show', compact('cliente'));

        } catch (\Exception $e) {
            Log::error('Error al obtener cliente: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener el cliente'
                ], 500);
            }

            return back()->with('error', 'Error al obtener el cliente');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cliente $cliente): View
    {
        // Load relationships needed by the edit view
        $cliente->load(['vehiculos', 'ordenesTrabajo']);
        
        return view('clientes.edit', compact('cliente'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClienteRequest $request, Cliente $cliente)
    {
        try {
            DB::beginTransaction();

            $cliente->update($request->validated());

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $cliente->fresh(),
                    'message' => 'Cliente actualizado exitosamente'
                ]);
            }

            return redirect()->route('clientes.index')
                ->with('success', 'Cliente actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cliente: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el cliente: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error al actualizar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente, Request $request)
    {
        try {
            DB::beginTransaction();

            // Verificar si tiene órdenes de trabajo activas
            $ordenesActivas = $cliente->ordenesTrabajo()
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            if ($ordenesActivas > 0) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el cliente porque tiene órdenes de trabajo activas'
                    ], 422);
                }

                return back()->with('error', 'No se puede eliminar el cliente porque tiene órdenes de trabajo activas');
            }

            // Verificar y eliminar relaciones dependientes si es necesario
            $vehiculosCount = $cliente->vehiculos()->count();
            $ordenesCompletasCount = $cliente->ordenesTrabajo()
                ->whereIn('status', ['completed', 'cancelled'])
                ->count();
            
            if ($vehiculosCount > 0) {
                // Desasociar vehículos o eliminarlos según la lógica de negocio
                $cliente->vehiculos()->update(['cliente_id' => null]);
            }
            
            if ($ordenesCompletasCount > 0) {
                // Mantener historial de órdenes completadas con referencia nula
                $cliente->ordenesTrabajo()
                    ->whereIn('status', ['completed', 'cancelled'])
                    ->update(['cliente_id' => null]);
            }
            
            // Hard delete - eliminar completamente el registro
            $cliente->delete();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente eliminado exitosamente'
                ]);
            }

            return redirect()->route('clientes.index')
                ->with('success', 'Cliente eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar cliente: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el cliente: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al eliminar el cliente: ' . $e->getMessage());
        }
    }
}
