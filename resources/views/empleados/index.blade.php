@extends('layouts.app')

@section('title', 'Empleados')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-tie me-2"></i>
        Gestión de Empleados
    </h1>
    @can('crear-empleados')
    <a href="{{ route('empleados.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>
        Nuevo Empleado
    </a>
    @endcan
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('empleados.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Nombre, email o posición..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label for="position" class="form-label">Posición</label>
                <select class="form-select" id="position" name="position">
                    <option value="">Todas las posiciones</option>
                    @foreach($positions as $position)
                        <option value="{{ $position }}" {{ request('position') == $position ? 'selected' : '' }}>{{ $position }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos los estados</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-search"></i>
                </button>
                <a href="{{ route('empleados.index') }}" class="btn btn-outline-secondary">
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
                        <h5 class="card-title">Total Empleados</h5>
                        <h3 class="mb-0">{{ $empleados->total() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-tie fa-2x opacity-75"></i>
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
                        <h5 class="card-title">Activos</h5>
                        <h3 class="mb-0">{{ $empleados->where('status', true)->count() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x opacity-75"></i>
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
                        <h5 class="card-title">Salario Promedio</h5>
                        <h3 class="mb-0">${{ number_format($empleados->where('status', true)->avg('salary'), 0) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
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
                        <h5 class="card-title">Órdenes Activas</h5>
                        <h3 class="mb-0">{{ $empleados->sum(function($emp) { return $emp->ordenesTrabajo ? $emp->ordenesTrabajo->whereIn('status', ['pending', 'in_progress'])->count() : 0; }) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Empleados Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Empleados
        </h5>
    </div>
    <div class="card-body">
        @if($empleados->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover" id="empleadosTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Posición</th>
                        <th>Salario</th>
                        <th>Fecha Contratación</th>
                        <th>Estado</th>
                        <th width="150">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($empleados as $empleado)
                    <tr>
                        <td>{{ $empleado->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-initial bg-success rounded-circle text-white">
                                        {{ strtoupper(substr($empleado->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div>
                                    <strong>{{ $empleado->name }}</strong>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="mailto:{{ $empleado->email }}" class="text-decoration-none">
                                {{ $empleado->email }}
                            </a>
                        </td>
                        <td>
                            <a href="tel:{{ $empleado->phone }}" class="text-decoration-none">
                                <i class="fas fa-phone me-1"></i>
                                {{ $empleado->phone }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $empleado->position }}</span>
                        </td>
                        <td>
                            <strong>${{ number_format($empleado->salary, 2) }}</strong>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($empleado->hire_date)->format('d/m/Y') }}
                            </small>
                        </td>
                        <td>
                            @if($empleado->status)
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Activo
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="fas fa-times me-1"></i>Inactivo
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                @can('ver-empleados')
                                <a href="{{ route('empleados.show', $empleado) }}" 
                                   class="btn btn-outline-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                
                                @can('editar-empleados')
                                <a href="{{ route('empleados.edit', $empleado) }}" 
                                   class="btn btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                
                                @can('eliminar-empleados')
                                <form method="POST" action="{{ route('empleados.destroy', $empleado) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-delete" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
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
                    Mostrando {{ $empleados->firstItem() }} a {{ $empleados->lastItem() }} 
                    de {{ $empleados->total() }} resultados
                </p>
            </div>
            <div>
                {{ $empleados->appends(request()->query())->links() }}
            </div>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay empleados registrados</h5>
            <p class="text-muted">Los empleados que registres aparecerán aquí.</p>
            @can('crear-empleados')
            <a href="{{ route('empleados.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Registrar Primer Empleado
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

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#empleadosTable').DataTable({
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