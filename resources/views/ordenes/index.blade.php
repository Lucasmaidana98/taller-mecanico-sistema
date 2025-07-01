@extends('layouts.app')

@section('title', 'Órdenes de Trabajo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-clipboard-list me-2"></i>
        Gestión de Órdenes de Trabajo
    </h1>
    @can('crear-ordenes')
    <a href="{{ route('ordenes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>
        Nueva Orden
    </a>
    @endcan
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('ordenes.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Cliente, vehículo o descripción..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos los estados</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completada</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="cliente_id" class="form-label">Cliente</label>
                <select class="form-select" id="cliente_id" name="cliente_id">
                    <option value="">Todos los clientes</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="empleado_id" class="form-label">Empleado</label>
                <select class="form-select" id="empleado_id" name="empleado_id">
                    <option value="">Todos los empleados</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}" {{ request('empleado_id') == $empleado->id ? 'selected' : '' }}>
                            {{ $empleado->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="per_page" class="form-label">Mostrar</label>
                <select class="form-select" id="per_page" name="per_page">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-search"></i>
                </button>
                <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Órdenes</h5>
                        <h3 class="mb-0">{{ $ordenes->total() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Pendientes</h5>
                        <h3 class="mb-0">{{ $ordenes->where('status', 'pending')->count() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">En Progreso</h5>
                        <h3 class="mb-0">{{ $ordenes->where('status', 'in_progress')->count() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-cog fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Completadas</h5>
                        <h3 class="mb-0">{{ $ordenes->where('status', 'completed')->count() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Órdenes Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Órdenes de Trabajo
        </h5>
    </div>
    <div class="card-body">
        @if($ordenes->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover" id="ordenesTable">
                <thead>
                    <tr>
                        <th>Orden #</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Servicio</th>
                        <th>Empleado</th>
                        <th>Estado</th>
                        <th>Fecha Inicio</th>
                        <th>Monto</th>
                        <th width="150">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ordenes as $orden)
                    <tr>
                        <td>
                            <strong class="text-primary">#{{ $orden->id }}</strong>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-initial bg-primary rounded-circle text-white">
                                        {{ strtoupper(substr($orden->cliente->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div>
                                    <strong>{{ $orden->cliente->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $orden->cliente->phone }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong>{{ $orden->vehiculo->brand }} {{ $orden->vehiculo->model }}</strong>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-license me-1"></i>{{ $orden->vehiculo->license_plate }}
                                </small>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong>{{ $orden->servicio->name }}</strong>
                                <br>
                                <small class="text-muted">{{ Str::limit($orden->description, 30) }}</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $orden->empleado->name }}</span>
                        </td>
                        <td>
                            @switch($orden->status)
                                @case('pending')
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Pendiente
                                    </span>
                                    @break
                                @case('in_progress')
                                    <span class="badge bg-info">
                                        <i class="fas fa-cog me-1"></i>En Progreso
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Completada
                                    </span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times me-1"></i>Cancelada
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $orden->start_date->format('d/m/Y H:i') }}
                            </small>
                        </td>
                        <td>
                            <strong class="text-success">${{ number_format($orden->total_amount, 2) }}</strong>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                @can('ver-ordenes')
                                <a href="{{ route('ordenes.show', $orden) }}" 
                                   class="btn btn-outline-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                
                                @can('editar-ordenes')
                                <a href="{{ route('ordenes.edit', $orden) }}" 
                                   class="btn btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                
                                @can('eliminar-ordenes')
                                @if($orden->status === 'pending')
                                <form method="POST" action="{{ route('ordenes.destroy', $orden) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-delete" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                <p class="text-muted mb-0">
                    Mostrando {{ $ordenes->firstItem() }} a {{ $ordenes->lastItem() }} 
                    de {{ $ordenes->total() }} resultados
                </p>
            </div>
            <div>
                {{ $ordenes->appends(request()->query())->links() }}
            </div>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay órdenes de trabajo registradas</h5>
            <p class="text-muted">Las órdenes de trabajo que registres aparecerán aquí.</p>
            @can('crear-ordenes')
            <a href="{{ route('ordenes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Registrar Primera Orden
            </a>
            @endcan
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#ordenesTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [8] }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    // Delete confirmation
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer. Solo se pueden eliminar órdenes pendientes.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
}

.avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}
</style>
@endpush