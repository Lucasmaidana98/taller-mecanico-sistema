@extends('layouts.app')

@section('title', 'Servicios')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-cogs me-2"></i>
        Gestión de Servicios
    </h1>
    @can('crear-servicios')
    <a href="{{ route('servicios.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>
        Nuevo Servicio
    </a>
    @endcan
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('servicios.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Nombre o descripción..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label for="price_min" class="form-label">Precio Mínimo</label>
                <input type="number" class="form-control" id="price_min" name="price_min" 
                       placeholder="0.00" step="0.01" value="{{ request('price_min') }}">
            </div>
            <div class="col-md-2">
                <label for="price_max" class="form-label">Precio Máximo</label>
                <input type="number" class="form-control" id="price_max" name="price_max" 
                       placeholder="999.99" step="0.01" value="{{ request('price_max') }}">
            </div>
            <div class="col-md-2">
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
                <a href="{{ route('servicios.index') }}" class="btn btn-outline-secondary">
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
                        <h5 class="card-title">Total Servicios</h5>
                        <h3 class="mb-0">{{ $servicios->total() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-cogs fa-2x opacity-75"></i>
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
                        <h3 class="mb-0">{{ $servicios->where('status', true)->count() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
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
                        <h5 class="card-title">Precio Promedio</h5>
                        <h3 class="mb-0">${{ number_format($servicios->where('status', true)->avg('price'), 2) }}</h3>
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
                        <h5 class="card-title">Más Solicitado</h5>
                        <h3 class="mb-0">
                            @php
                                $masPopular = $servicios->sortByDesc(function($servicio) {
                                    return $servicio->ordenTrabajos->count();
                                })->first();
                            @endphp
                            {{ $masPopular ? $masPopular->ordenTrabajos->count() : 0 }}
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-star fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Servicios Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Servicios
        </h5>
    </div>
    <div class="card-body">
        @if($servicios->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover" id="serviciosTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Servicio</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Duración</th>
                        <th>Popularidad</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th width="150">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($servicios as $servicio)
                    <tr>
                        <td>{{ $servicio->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="service-icon me-2">
                                    <i class="fas fa-tools fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <strong>{{ $servicio->name }}</strong>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted">{{ Str::limit($servicio->description, 50) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success fs-6">
                                ${{ number_format($servicio->price, 2) }}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                {{ $servicio->duration_hours }}h
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-info me-2">{{ $servicio->ordenTrabajos->count() }}</span>
                                <small class="text-muted">órdenes</small>
                            </div>
                        </td>
                        <td>
                            @if($servicio->status)
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
                            <small class="text-muted">
                                {{ $servicio->created_at->format('d/m/Y') }}
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                @can('ver-servicios')
                                <a href="{{ route('servicios.show', $servicio) }}" 
                                   class="btn btn-outline-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                
                                @can('editar-servicios')
                                <a href="{{ route('servicios.edit', $servicio) }}" 
                                   class="btn btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                
                                @can('eliminar-servicios')
                                <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" class="d-inline">
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
                    Mostrando {{ $servicios->firstItem() }} a {{ $servicios->lastItem() }} 
                    de {{ $servicios->total() }} resultados
                </p>
            </div>
            <div>
                {{ $servicios->appends(request()->query())->links() }}
            </div>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay servicios registrados</h5>
            <p class="text-muted">Los servicios que registres aparecerán aquí.</p>
            @can('crear-servicios')
            <a href="{{ route('servicios.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Registrar Primer Servicio
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
    $('#serviciosTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [8] },
            { type: 'currency', targets: [3] }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
});
</script>

<style>
.service-icon {
    width: 40px;
    text-align: center;
}
</style>
@endpush