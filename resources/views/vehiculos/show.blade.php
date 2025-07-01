@extends('layouts.app')

@section('title', 'Detalles del Vehículo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-car me-2"></i>
        {{ $vehiculo->brand }} {{ $vehiculo->model }}
    </h1>
    <div class="btn-group">
        @can('editar-vehiculos')
        <a href="{{ route('vehiculos.edit', $vehiculo) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>
            Editar
        </a>
        @endcan
        @can('crear-ordenes')
        <a href="{{ route('ordenes.create', ['vehiculo_id' => $vehiculo->id]) }}" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>
            Nueva Orden
        </a>
        @endcan
        <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
    </div>
</div>

<!-- Vehicle Info Cards -->
<div class="row mb-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="car-icon mb-3">
                    <i class="fas fa-car fa-5x text-primary"></i>
                </div>
                <h5 class="card-title">{{ $vehiculo->brand }} {{ $vehiculo->model }}</h5>
                <p class="text-muted mb-2">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $vehiculo->year }}
                </p>
                <p class="text-muted mb-2">
                    <i class="fas fa-license me-1"></i>
                    {{ $vehiculo->license_plate }}
                </p>
                <p class="text-muted mb-3">
                    <i class="fas fa-palette me-1"></i>
                    {{ ucfirst($vehiculo->color) }}
                </p>
                @if($vehiculo->status)
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
                            <label class="form-label text-muted">VIN</label>
                            <p class="mb-0 font-monospace">
                                <i class="fas fa-barcode me-1"></i>
                                {{ $vehiculo->vin }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Fecha de Registro</label>
                            <p class="mb-0">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $vehiculo->created_at->format('d/m/Y H:i') }}
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
                                {{ $vehiculo->updated_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Tiempo Registrado</label>
                            <p class="mb-0">
                                <i class="fas fa-hourglass-half me-1"></i>
                                {{ $vehiculo->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Owner Info -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Información del Propietario
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-initial bg-primary rounded-circle text-white">
                                {{ strtoupper(substr($vehiculo->cliente->name, 0, 2)) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-1">{{ $vehiculo->cliente->name }}</h6>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-envelope me-1"></i>
                                    {{ $vehiculo->cliente->email }}
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-phone me-1"></i>
                                    {{ $vehiculo->cliente->phone }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">
                                    <i class="fas fa-id-card me-1"></i>
                                    {{ $vehiculo->cliente->document_number }}
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    {{ $vehiculo->cliente->address }}
                                </p>
                                <div class="mt-2">
                                    <a href="{{ route('clientes.show', $vehiculo->cliente) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>
                                        Ver Cliente
                                    </a>
                                </div>
                            </div>
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
                        <h5 class="card-title">Órdenes Totales</h5>
                        <h3 class="mb-0">{{ $vehiculo->ordenTrabajos->count() }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
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
                        <h3 class="mb-0">{{ $vehiculo->ordenTrabajos->where('status', 'completed')->count() }}</h3>
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
                        <h5 class="card-title">En Proceso</h5>
                        <h3 class="mb-0">{{ $vehiculo->ordenTrabajos->whereIn('status', ['pending', 'in_progress'])->count() }}</h3>
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
                        <h3 class="mb-0">${{ number_format($vehiculo->ordenTrabajos->where('status', 'completed')->sum('total_amount'), 2) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Órdenes de Trabajo -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Historial de Órdenes de Trabajo
                </h5>
                @can('crear-ordenes')
                <a href="{{ route('ordenes.create', ['vehiculo_id' => $vehiculo->id]) }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Nueva Orden
                </a>
                @endcan
            </div>
            <div class="card-body">
                @if($vehiculo->ordenTrabajos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Servicio</th>
                                <th>Empleado</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehiculo->ordenTrabajos as $orden)
                            <tr>
                                <td>
                                    <strong>#{{ $orden->id }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $orden->servicio->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ Str::limit($orden->description, 50) }}</small>
                                </td>
                                <td>
                                    {{ $orden->empleado->name }}
                                    <br>
                                    <small class="text-muted">{{ $orden->empleado->position }}</small>
                                </td>
                                <td>
                                    {{ $orden->start_date->format('d/m/Y') }}
                                    <br>
                                    <small class="text-muted">{{ $orden->start_date->format('H:i') }}</small>
                                </td>
                                <td>
                                    @if($orden->end_date)
                                        {{ $orden->end_date->format('d/m/Y') }}
                                        <br>
                                        <small class="text-muted">{{ $orden->end_date->format('H:i') }}</small>
                                    @else
                                        <span class="text-muted">Pendiente</span>
                                    @endif
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
                                                <i class="fas fa-play me-1"></i>En Progreso
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
                @else
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay órdenes de trabajo</h5>
                    <p class="text-muted">Las órdenes de trabajo para este vehículo aparecerán aquí.</p>
                    @can('crear-ordenes')
                    <a href="{{ route('ordenes.create', ['vehiculo_id' => $vehiculo->id]) }}" class="btn btn-primary">
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

@if($vehiculo->ordenTrabajos->where('status', 'completed')->count() > 0)
<!-- Servicios Más Frecuentes -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Servicios Más Frecuentes
                </h5>
            </div>
            <div class="card-body">
                @php
                    $servicios = $vehiculo->ordenTrabajos->where('status', 'completed')->groupBy('servicio_id')->map(function($items) {
                        return [
                            'servicio' => $items->first()->servicio,
                            'count' => $items->count(),
                            'total' => $items->sum('total_amount')
                        ];
                    })->sortByDesc('count')->take(5);
                @endphp
                
                @foreach($servicios as $servicio)
                <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                    <div>
                        <h6 class="mb-1">{{ $servicio['servicio']->name }}</h6>
                        <small class="text-muted">{{ $servicio['count'] }} veces</small>
                    </div>
                    <div class="text-end">
                        <strong>${{ number_format($servicio['total'], 2) }}</strong>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Mantenimiento Reciente
                </h5>
            </div>
            <div class="card-body">
                @php
                    $ultimasOrdenes = $vehiculo->ordenTrabajos->where('status', 'completed')->take(5);
                @endphp
                
                @foreach($ultimasOrdenes as $orden)
                <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                    <div>
                        <h6 class="mb-1">{{ $orden->servicio->name }}</h6>
                        <small class="text-muted">{{ $orden->end_date->format('d/m/Y') }}</small>
                    </div>
                    <div class="text-end">
                        <strong>${{ number_format($orden->total_amount, 2) }}</strong>
                        <br>
                        <small class="text-muted">{{ $orden->empleado->name }}</small>
                    </div>
                </div>
                @endforeach
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

.car-icon {
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
</style>
@endpush