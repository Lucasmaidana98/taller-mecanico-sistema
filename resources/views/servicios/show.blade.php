@extends('layouts.app')

@section('title', 'Detalles del Servicio')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-cogs me-2"></i>
        {{ $servicio->name }}
    </h1>
    <div class="btn-group">
        @can('editar-servicios')
        <a href="{{ route('servicios.edit', $servicio) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>
            Editar
        </a>
        @endcan
        <a href="{{ route('servicios.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
    </div>
</div>

<!-- Service Info Card -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información del Servicio
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Nombre</h6>
                        <p class="mb-3">{{ $servicio->name }}</p>
                        
                        <h6 class="text-muted">Precio</h6>
                        <p class="mb-3">
                            <span class="badge bg-success fs-6">${{ number_format($servicio->price, 2) }}</span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Duración</h6>
                        <p class="mb-3">
                            <i class="fas fa-clock me-1"></i>
                            {{ $servicio->duration_hours }} horas
                        </p>
                        
                        <h6 class="text-muted">Estado</h6>
                        <p class="mb-3">
                            @if($servicio->status)
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Activo
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="fas fa-times me-1"></i>Inactivo
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
                
                <h6 class="text-muted">Descripción</h6>
                <p class="mb-0">{{ $servicio->description }}</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estadísticas
                </h5>
            </div>
            <div class="card-body text-center">
                <div class="row">
                    <div class="col-12 mb-3">
                        <h3 class="text-primary">{{ $servicio->ordenTrabajos->count() }}</h3>
                        <small class="text-muted">Órdenes Totales</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <h4 class="text-success">{{ $servicio->ordenTrabajos->where('status', 'completed')->count() }}</h4>
                        <small class="text-muted">Completadas</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-warning">{{ $servicio->ordenTrabajos->whereIn('status', ['pending', 'in_progress'])->count() }}</h4>
                        <small class="text-muted">Pendientes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
@if($servicio->ordenTrabajos->count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Órdenes de Trabajo Recientes
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Monto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($servicio->ordenTrabajos->take(10) as $orden)
                    <tr>
                        <td><strong>#{{ $orden->id }}</strong></td>
                        <td>{{ $orden->cliente->name }}</td>
                        <td>{{ $orden->vehiculo->brand }} {{ $orden->vehiculo->model }}</td>
                        <td>{{ $orden->empleado->name }}</td>
                        <td>{{ $orden->start_date->format('d/m/Y') }}</td>
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
                        <td>${{ number_format($orden->total_amount, 2) }}</td>
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
@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No hay órdenes para este servicio</h5>
        <p class="text-muted">Las órdenes de trabajo que usen este servicio aparecerán aquí.</p>
    </div>
</div>
@endif
@endsection