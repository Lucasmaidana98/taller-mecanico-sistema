@extends('layouts.app')

@section('title', 'Detalles del Cliente')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user me-2"></i>
        {{ $cliente->name }}
    </h1>
    <div class="btn-group">
        @can('editar-clientes')
        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>
            Editar
        </a>
        @endcan
        @can('crear-vehiculos')
        <a href="{{ route('vehiculos.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-success">
            <i class="fas fa-car me-1"></i>
            Nuevo Vehículo
        </a>
        @endcan
        <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
    </div>
</div>

<!-- Cliente Info Cards -->
<div class="row mb-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-lg mx-auto mb-3">
                    <div class="avatar-initial bg-primary rounded-circle text-white">
                        {{ strtoupper(substr($cliente->name, 0, 2)) }}
                    </div>
                </div>
                <h5 class="card-title">{{ $cliente->name }}</h5>
                <p class="text-muted mb-2">
                    <i class="fas fa-envelope me-1"></i>
                    {{ $cliente->email }}
                </p>
                <p class="text-muted mb-2">
                    <i class="fas fa-phone me-1"></i>
                    {{ $cliente->phone }}
                </p>
                <p class="text-muted mb-3">
                    <i class="fas fa-id-card me-1"></i>
                    {{ $cliente->document_number }}
                </p>
                @if($cliente->status)
                    <span class="badge bg-success">
                        <i class="fas fa-check me-1"></i>Activo
                    </span>
                @else
                    <span class="badge bg-danger">
                        <i class="fas fa-times me-1"></i>Inactivo
                    </span>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información Detallada
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Dirección</label>
                            <p class="mb-0">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                {{ $cliente->address }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Fecha de Registro</label>
                            <p class="mb-0">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $cliente->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Última Actualización</label>
                            <p class="mb-0">
                                <i class="fas fa-clock me-1"></i>
                                {{ $cliente->updated_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Tiempo como Cliente</label>
                            <p class="mb-0">
                                <i class="fas fa-hourglass-half me-1"></i>
                                {{ $cliente->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Vehículos</h5>
                        <h3 class="mb-0">{{ $cliente->vehiculos->count() }}</h3>
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
                        <h5 class="card-title">Órdenes Completadas</h5>
                        <h3 class="mb-0">{{ $cliente->ordenesTrabajo->where('status', 'completed')->count() }}</h3>
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
                        <h5 class="card-title">Órdenes Pendientes</h5>
                        <h3 class="mb-0">{{ $cliente->ordenesTrabajo->whereIn('status', ['pending', 'in_progress'])->count() }}</h3>
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
                        <h5 class="card-title">Total Gastado</h5>
                        <h3 class="mb-0">${{ number_format($cliente->ordenesTrabajo->where('status', 'completed')->sum('total_amount'), 2) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Vehículos -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-car me-2"></i>
                    Vehículos ({{ $cliente->vehiculos->count() }})
                </h5>
                @can('crear-vehiculos')
                <a href="{{ route('vehiculos.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Nuevo
                </a>
                @endcan
            </div>
            <div class="card-body">
                @if($cliente->vehiculos->count() > 0)
                    @foreach($cliente->vehiculos as $vehiculo)
                    <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $vehiculo->brand }} {{ $vehiculo->model }}</h6>
                            <p class="text-muted mb-1">
                                <i class="fas fa-calendar me-1"></i>{{ $vehiculo->year }} - 
                                <i class="fas fa-palette me-1"></i>{{ $vehiculo->color }}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-license me-1"></i>{{ $vehiculo->license_plate }}
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="btn-group btn-group-sm">
                                @can('ver-vehiculos')
                                <a href="{{ route('vehiculos.show', $vehiculo) }}" class="btn btn-outline-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                @can('editar-vehiculos')
                                <a href="{{ route('vehiculos.edit', $vehiculo) }}" class="btn btn-outline-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-car fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay vehículos registrados</p>
                        @can('crear-vehiculos')
                        <a href="{{ route('vehiculos.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Registrar Vehículo
                        </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Órdenes de Trabajo Recientes -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Órdenes Recientes
                </h5>
                @can('crear-ordenes')
                <a href="{{ route('ordenes.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>
                    Nueva Orden
                </a>
                @endcan
            </div>
            <div class="card-body">
                @if($cliente->ordenesTrabajo->count() > 0)
                    @foreach($cliente->ordenesTrabajo->take(5) as $orden)
                    <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Orden #{{ $orden->id }}</h6>
                            <p class="text-muted mb-1">
                                <i class="fas fa-car me-1"></i>{{ $orden->vehiculo->brand }} {{ $orden->vehiculo->model }}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-calendar me-1"></i>{{ $orden->start_date->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-end">
                            @switch($orden->status)
                                @case('pending')
                                    <span class="badge bg-warning mb-1">Pendiente</span>
                                    @break
                                @case('in_progress')
                                    <span class="badge bg-info mb-1">En Progreso</span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success mb-1">Completada</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger mb-1">Cancelada</span>
                                    @break
                            @endswitch
                            <br>
                            <small class="text-muted">${{ number_format($orden->total_amount, 2) }}</small>
                        </div>
                    </div>
                    @endforeach
                    
                    @if($cliente->ordenesTrabajo->count() > 5)
                    <div class="text-center mt-3">
                        @can('ver-ordenes')
                        <a href="{{ route('ordenes.index', ['cliente_id' => $cliente->id]) }}" class="btn btn-sm btn-outline-primary">
                            Ver todas las órdenes
                        </a>
                        @endcan
                    </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay órdenes de trabajo</p>
                        @can('crear-ordenes')
                        <a href="{{ route('ordenes.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>
                            Crear Primera Orden
                        </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($cliente->ordenesTrabajo->count() > 0)
<!-- Historial de Servicios -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Historial de Servicios
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Vehículo</th>
                                <th>Servicio</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cliente->ordenesTrabajo as $orden)
                            <tr>
                                <td>
                                    <strong>#{{ $orden->id }}</strong>
                                </td>
                                <td>
                                    {{ $orden->vehiculo->brand }} {{ $orden->vehiculo->model }}
                                    <br>
                                    <small class="text-muted">{{ $orden->vehiculo->license_plate }}</small>
                                </td>
                                <td>
                                    {{ $orden->servicio->name }}
                                    <br>
                                    <small class="text-muted">{{ Str::limit($orden->description, 50) }}</small>
                                </td>
                                <td>
                                    {{ $orden->start_date->format('d/m/Y') }}
                                    @if($orden->end_date)
                                        <br>
                                        <small class="text-muted">Fin: {{ $orden->end_date->format('d/m/Y') }}</small>
                                    @endif
                                </td>
                                <td>
                                    @switch($orden->status)
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
                                    @endswitch
                                </td>
                                <td>
                                    <strong>${{ number_format($orden->total_amount, 2) }}</strong>
                                </td>
                                <td>
                                    @can('ver-ordenes')
                                    <a href="{{ route('ordenes.show', $orden) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<style>
.avatar-lg {
    width: 80px;
    height: 80px;
}

.avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 600;
}
</style>
@endpush