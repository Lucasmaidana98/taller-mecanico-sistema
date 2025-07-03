@extends('layouts.app')

@section('title', 'Vehículos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-car me-2"></i>
        Gestión de Vehículos
    </h1>
    @can('crear-vehiculos')
    <a href="{{ route('vehiculos.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>
        Nuevo Vehículo
    </a>
    @endcan
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('vehiculos.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Marca, modelo, placa..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label for="brand" class="form-label">Marca</label>
                <select class="form-select" id="brand" name="brand">
                    <option value="">Todas las marcas</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="year" class="form-label">Año</label>
                <select class="form-select" id="year" name="year">
                    <option value="">Todos los años</option>
                    @for($year = date('Y'); $year >= 1950; $year--)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos los estados</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactivo</option>
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
                <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-secondary">
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
                        <h5 class="card-title">Total Vehículos</h5>
                        <h3 class="mb-0">{{ $stats['total_vehiculos'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-car fa-2x opacity-75"></i>
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
                        <h3 class="mb-0">{{ $stats['vehiculos_activos'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
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
                        <h5 class="card-title">En Servicio</h5>
                        <h3 class="mb-0">{{ $stats['vehiculos_en_servicio'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-tools fa-2x opacity-75"></i>
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
                        <h5 class="card-title">Marcas Diferentes</h5>
                        <h3 class="mb-0">{{ $stats['marcas_diferentes'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-plus-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehículos Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Vehículos
        </h5>
    </div>
    <div class="card-body">
        @if($vehiculos->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover" id="vehiculosTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vehículo</th>
                        <th>Propietario</th>
                        <th>Placa</th>
                        <th>VIN</th>
                        <th>Color</th>
                        <th>Estado</th>
                        <th>Última Orden</th>
                        <th width="150">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehiculos as $vehiculo)
                    <tr>
                        <td>{{ $vehiculo->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="car-icon me-2">
                                    <i class="fas fa-car fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <strong>{{ $vehiculo->brand }} {{ $vehiculo->model }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $vehiculo->year }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('clientes.show', $vehiculo->cliente) }}" class="text-decoration-none">
                                {{ $vehiculo->cliente->name }}
                            </a>
                            <br>
                            <small class="text-muted">{{ $vehiculo->cliente->phone }}</small>
                        </td>
                        <td>
                            <span class="badge bg-dark">{{ $vehiculo->license_plate }}</span>
                        </td>
                        <td>
                            <small class="text-muted font-monospace">{{ $vehiculo->vin }}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="color-circle me-2" style="background-color: {{ $vehiculo->color }}; width: 20px; height: 20px; border-radius: 50%; border: 1px solid #dee2e6;"></div>
                                {{ ucfirst($vehiculo->color) }}
                            </div>
                        </td>
                        <td>
                            @if($vehiculo->status)
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
                            @if($vehiculo->ordenesTrabajo && $vehiculo->ordenesTrabajo->count() > 0)
                                @php $ultimaOrden = $vehiculo->ordenesTrabajo->first(); @endphp
                                <small class="text-muted">
                                    {{ optional($ultimaOrden->start_date)->format('d/m/Y') ?? 'Sin fecha' }}
                                    <br>
                                    @switch($ultimaOrden->status ?? 'unknown')
                                        @case('pending')
                                            <span class="badge bg-warning">Pendiente</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-info">En Progreso</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Completada</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Cancelada</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">Desconocido</span>
                                    @endswitch
                                </small>
                            @else
                                <small class="text-muted">Sin órdenes</small>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                @can('ver-vehiculos')
                                <a href="{{ route('vehiculos.show', $vehiculo) }}" 
                                   class="btn btn-outline-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                
                                @can('editar-vehiculos')
                                <a href="{{ route('vehiculos.edit', $vehiculo) }}" 
                                   class="btn btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                
                                @can('eliminar-vehiculos')
                                <form method="POST" action="{{ route('vehiculos.destroy', $vehiculo) }}" class="d-inline">
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
                    Mostrando {{ $vehiculos->firstItem() }} a {{ $vehiculos->lastItem() }} 
                    de {{ $vehiculos->total() }} resultados
                </p>
            </div>
            <div>
                {{ $vehiculos->appends(request()->query())->links() }}
            </div>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-car fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay vehículos registrados</h5>
            <p class="text-muted">Los vehículos que registres aparecerán aquí.</p>
            @can('crear-vehiculos')
            <a href="{{ route('vehiculos.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Registrar Primer Vehículo
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
    $('#vehiculosTable').DataTable({
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
.car-icon {
    width: 40px;
    text-align: center;
}

.color-circle {
    flex-shrink: 0;
}
</style>
@endpush