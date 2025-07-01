@extends('layouts.app')

@section('title', 'Detalles del Empleado')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-tie me-2"></i>
        {{ $empleado->name }}
    </h1>
    <div class="btn-group">
        @can('editar-empleados')
        <a href="{{ route('empleados.edit', $empleado) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>
            Editar
        </a>
        @endcan
        @can('crear-ordenes')
        <a href="{{ route('ordenes.create', ['empleado_id' => $empleado->id]) }}" class="btn btn-success">
            <i class="fas fa-clipboard-list me-1"></i>
            Asignar Orden
        </a>
        @endcan
        <a href="{{ route('empleados.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
    </div>
</div>

<!-- Empleado Info Cards -->
<div class="row mb-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-lg mx-auto mb-3">
                    <div class="avatar-initial bg-success rounded-circle text-white">
                        {{ strtoupper(substr($empleado->name, 0, 2)) }}
                    </div>
                </div>
                <h5 class="card-title">{{ $empleado->name }}</h5>
                <p class="text-muted mb-2">
                    <i class="fas fa-briefcase me-1"></i>
                    {{ $empleado->position }}
                </p>
                <p class="text-muted mb-2">
                    <i class="fas fa-envelope me-1"></i>
                    {{ $empleado->email }}
                </p>
                <p class="text-muted mb-2">
                    <i class="fas fa-phone me-1"></i>
                    {{ $empleado->phone }}
                </p>
                <p class="text-muted mb-3">
                    <i class="fas fa-dollar-sign me-1"></i>
                    ${{ number_format($empleado->salary, 2) }}
                </p>
                @if($empleado->status)
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
                            <label class="form-label text-muted">Posición</label>
                            <p class="mb-0">
                                <i class="fas fa-briefcase me-1"></i>
                                {{ $empleado->position }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Salario</label>
                            <p class="mb-0">
                                <i class="fas fa-dollar-sign me-1"></i>
                                ${{ number_format($empleado->salary, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Fecha de Contratación</label>
                            <p class="mb-0">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $empleado->hire_date->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Tiempo en la Empresa</label>
                            <p class="mb-0">
                                <i class="fas fa-hourglass-half me-1"></i>
                                {{ $empleado->hire_date->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Fecha de Registro</label>
                            <p class="mb-0">
                                <i class="fas fa-user-plus me-1"></i>
                                {{ $empleado->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Última Actualización</label>
                            <p class="mb-0">
                                <i class="fas fa-clock me-1"></i>
                                {{ $empleado->updated_at->format('d/m/Y H:i') }}
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
                        <h5 class="card-title">Órdenes Asignadas</h5>
                        <h3 class="mb-0">{{ $empleado->ordenesTrabajo->count() }}</h3>
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
                        <h3 class="mb-0">{{ $empleado->ordenesTrabajo->where('status', 'completed')->count() }}</h3>
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
                        <h5 class="card-title">En Progreso</h5>
                        <h3 class="mb-0">{{ $empleado->ordenesTrabajo->whereIn('status', ['pending', 'in_progress'])->count() }}</h3>
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
                        <h5 class="card-title">Eficiencia</h5>
                        <h3 class="mb-0">
                            @if($empleado->ordenesTrabajo->count() > 0)
                                {{ round(($empleado->ordenesTrabajo->where('status', 'completed')->count() / $empleado->ordenesTrabajo->count()) * 100) }}%
                            @else
                                0%
                            @endif
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Órdenes de trabajo asignadas -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Órdenes de Trabajo Asignadas ({{ $empleado->ordenesTrabajo->count() }})
                </h5>
                @can('crear-ordenes')
                <a href="{{ route('ordenes.create', ['empleado_id' => $empleado->id]) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>
                    Asignar Nueva Orden
                </a>
                @endcan
            </div>
            <div class="card-body">
                @if($empleado->ordenesTrabajo->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="ordenesTable">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Servicio</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($empleado->ordenesTrabajo as $orden)
                            <tr>
                                <td>
                                    <strong>#{{ $orden->id }}</strong>
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
                                            <small class="text-muted">{{ $orden->cliente->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $orden->vehiculo->brand }} {{ $orden->vehiculo->model }}
                                    <br>
                                    <small class="text-muted">{{ $orden->vehiculo->license_plate }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $orden->servicio->name }}</span>
                                    <br>
                                    <small class="text-muted">{{ Str::limit($orden->description, 30) }}</small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $orden->start_date->format('d/m/Y') }}
                                    </small>
                                </td>
                                <td>
                                    @if($orden->end_date)
                                        <small class="text-muted">
                                            {{ $orden->end_date->format('d/m/Y') }}
                                        </small>
                                    @else
                                        <small class="text-muted">-</small>
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
                                    <strong>${{ number_format($orden->total_amount, 2) }}</strong>
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
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay órdenes de trabajo asignadas</h5>
                        <p class="text-muted">Las órdenes que se asignen a este empleado aparecerán aquí.</p>
                        @can('crear-ordenes')
                        <a href="{{ route('ordenes.create', ['empleado_id' => $empleado->id]) }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>
                            Asignar Primera Orden
                        </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($empleado->ordenesTrabajo->count() > 0)
<!-- Resumen de Productividad -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Resumen de Órdenes por Estado
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Completadas</span>
                    <span class="badge bg-success">{{ $empleado->ordenesTrabajo->where('status', 'completed')->count() }}</span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: {{ $empleado->ordenesTrabajo->count() > 0 ? ($empleado->ordenesTrabajo->where('status', 'completed')->count() / $empleado->ordenesTrabajo->count()) * 100 : 0 }}%"></div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>En Progreso</span>
                    <span class="badge bg-info">{{ $empleado->ordenesTrabajo->where('status', 'in_progress')->count() }}</span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-info" role="progressbar" 
                         style="width: {{ $empleado->ordenesTrabajo->count() > 0 ? ($empleado->ordenesTrabajo->where('status', 'in_progress')->count() / $empleado->ordenesTrabajo->count()) * 100 : 0 }}%"></div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Pendientes</span>
                    <span class="badge bg-warning">{{ $empleado->ordenesTrabajo->where('status', 'pending')->count() }}</span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-warning" role="progressbar" 
                         style="width: {{ $empleado->ordenesTrabajo->count() > 0 ? ($empleado->ordenesTrabajo->where('status', 'pending')->count() / $empleado->ordenesTrabajo->count()) * 100 : 0 }}%"></div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Canceladas</span>
                    <span class="badge bg-danger">{{ $empleado->ordenesTrabajo->where('status', 'cancelled')->count() }}</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-danger" role="progressbar" 
                         style="width: {{ $empleado->ordenesTrabajo->count() > 0 ? ($empleado->ordenesTrabajo->where('status', 'cancelled')->count() / $empleado->ordenesTrabajo->count()) * 100 : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estadísticas de Rendimiento
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end pe-3">
                            <h4 class="text-primary mb-1">
                                @if($empleado->ordenesTrabajo->count() > 0)
                                    {{ round(($empleado->ordenesTrabajo->where('status', 'completed')->count() / $empleado->ordenesTrabajo->count()) * 100) }}%
                                @else
                                    0%
                                @endif
                            </h4>
                            <small class="text-muted">Tasa de Éxito</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1">
                            ${{ number_format($empleado->ordenesTrabajo->where('status', 'completed')->sum('total_amount'), 2) }}
                        </h4>
                        <small class="text-muted">Total Generado</small>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end pe-3">
                            <h4 class="text-info mb-1">
                                @if($empleado->ordenesTrabajo->where('status', 'completed')->count() > 0)
                                    ${{ number_format($empleado->ordenesTrabajo->where('status', 'completed')->avg('total_amount'), 2) }}
                                @else
                                    $0.00
                                @endif
                            </h4>
                            <small class="text-muted">Promedio por Orden</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-warning mb-1">{{ $empleado->ordenesTrabajo->whereIn('status', ['pending', 'in_progress'])->count() }}</h4>
                        <small class="text-muted">Órdenes Activas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

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
});
</script>

<style>
.avatar-lg {
    width: 80px;
    height: 80px;
}

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

.avatar-lg .avatar-initial {
    font-size: 1.5rem;
}
</style>
@endpush