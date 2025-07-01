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
        $this->authorize('viewAny', Cliente::class);

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

            $clientes = $query->latest()->paginate(15);

            // Respuesta AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $clientes,
                    'message' => 'Clientes obtenidos correctamente'
                ]);
            }

            return view('clientes.index', compact('clientes'));

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
        $this->authorize('create', Cliente::class);
        
        return view('clientes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClienteRequest $request)
    {
        $this->authorize('create', Cliente::class);

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
        $this->authorize('view', $cliente);

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
        $this->authorize('update', $cliente);
        
        return view('clientes.edit', compact('cliente'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $this->authorize('update', $cliente);

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
        $this->authorize('delete', $cliente);

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

            // Soft delete o hard delete según la lógica de negocio
            $cliente->update(['status' => false]);
            // O usar: $cliente->delete(); para hard delete

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
