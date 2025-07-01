@extends('layouts.app')

@section('title', 'Detalles de la Orden de Trabajo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-clipboard-list me-2"></i>
        Orden de Trabajo #{{ $ordenTrabajo->id }}
    </h1>
    <div class="btn-group">
        @can('editar-ordenes')
        <a href="{{ route('ordenes.edit', $ordenTrabajo) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>
            Editar
        </a>
        @endcan
        <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
    </div>
</div>

<!-- Status and Summary -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2">{{ $ordenTrabajo->servicio->name }}</h4>
                        <p class="text-muted mb-3">{{ $ordenTrabajo->description }}</p>
                        <div class="d-flex gap-3">
                            <div>
                                <small class="text-muted">Cliente:</small><br>
                                <strong>{{ $ordenTrabajo->cliente->name }}</strong>
                            </div>
                            <div>
                                <small class="text-muted">Vehículo:</small><br>
                                <strong>{{ $ordenTrabajo->vehiculo->brand }} {{ $ordenTrabajo->vehiculo->model }}</strong>
                            </div>
                            <div>
                                <small class="text-muted">Empleado:</small><br>
                                <strong>{{ $ordenTrabajo->empleado->name }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="mb-3">
                            @switch($ordenTrabajo->status)
                                @case('pending')
                                    <span class="badge bg-warning fs-6 px-3 py-2">
                                        <i class="fas fa-clock me-1"></i>Pendiente
                                    </span>
                                    @break
                                @case('in_progress')
                                    <span class="badge bg-info fs-6 px-3 py-2">
                                        <i class="fas fa-cog me-1"></i>En Progreso
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="fas fa-check me-1"></i>Completada
                                    </span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger fs-6 px-3 py-2">
                                        <i class="fas fa-times me-1"></i>Cancelada
                                    </span>
                                    @break
                            @endswitch
                        </div>
                        <h3 class="text-success mb-0">${{ number_format($ordenTrabajo->total_amount, 2) }}</h3>
                        <small class="text-muted">Monto Total</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Información del Cliente -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Información del Cliente
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-lg me-3">
                        <div class="avatar-initial bg-primary rounded-circle text-white">
                            {{ strtoupper(substr($ordenTrabajo->cliente->name, 0, 2)) }}
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-1">{{ $ordenTrabajo->cliente->name }}</h5>
                        <p class="text-muted mb-0">{{ $ordenTrabajo->cliente->document_number }}</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 mb-2">
                        <strong>Email:</strong><br>
                        <a href="mailto:{{ $ordenTrabajo->cliente->email }}" class="text-decoration-none">
                            <i class="fas fa-envelope me-1"></i>{{ $ordenTrabajo->cliente->email }}
                        </a>
                    </div>
                    <div class="col-12 mb-2">
                        <strong>Teléfono:</strong><br>
                        <a href="tel:{{ $ordenTrabajo->cliente->phone }}" class="text-decoration-none">
                            <i class="fas fa-phone me-1"></i>{{ $ordenTrabajo->cliente->phone }}
                        </a>
                    </div>
                    <div class="col-12">
                        <strong>Dirección:</strong><br>
                        <i class="fas fa-map-marker-alt me-1"></i>{{ $ordenTrabajo->cliente->address }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información del Vehículo -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-car me-2"></i>
                    Información del Vehículo
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Marca y Modelo:</strong><br>
                        {{ $ordenTrabajo->vehiculo->brand }} {{ $ordenTrabajo->vehiculo->model }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Año:</strong><br>
                        {{ $ordenTrabajo->vehiculo->year }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Placa:</strong><br>
                        <span class="badge bg-secondary fs-6">{{ $ordenTrabajo->vehiculo->license_plate }}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Color:</strong><br>
                        {{ $ordenTrabajo->vehiculo->color }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Kilometraje:</strong><br>
                        {{ number_format($ordenTrabajo->vehiculo->mileage) }} km
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Tipo de Motor:</strong><br>
                        {{ $ordenTrabajo->vehiculo->engine_type }}
                    </div>
                </div>
                
                @if($ordenTrabajo->vehiculo->notes)
                <div class="mt-3">
                    <strong>Notas del Vehículo:</strong><br>
                    <p class="text-muted mb-0">{{ $ordenTrabajo->vehiculo->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Detalles del Servicio y Empleado -->
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Detalles del Servicio
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <strong>Servicio:</strong><br>
                        <h5 class="text-primary">{{ $ordenTrabajo->servicio->name }}</h5>
                    </div>
                    <div class="col-12 mb-3">
                        <strong>Descripción del Servicio:</strong><br>
                        <p class="text-muted">{{ $ordenTrabajo->servicio->description }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Precio Base:</strong><br>
                        <span class="text-success">${{ number_format($ordenTrabajo->servicio->price, 2) }}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Duración Estimada:</strong><br>
                        {{ $ordenTrabajo->servicio->estimated_duration }} minutos
                    </div>
                </div>
                
                <div class="mt-3">
                    <strong>Descripción del Trabajo:</strong><br>
                    <div class="alert alert-light">
                        {{ $ordenTrabajo->description }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-tie me-2"></i>
                    Empleado Asignado
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-lg me-3">
                        <div class="avatar-initial bg-success rounded-circle text-white">
                            {{ strtoupper(substr($ordenTrabajo->empleado->name, 0, 2)) }}
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-1">{{ $ordenTrabajo->empleado->name }}</h5>
                        <p class="text-muted mb-0">{{ $ordenTrabajo->empleado->position }}</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 mb-2">
                        <strong>Email:</strong><br>
                        <a href="mailto:{{ $ordenTrabajo->empleado->email }}" class="text-decoration-none">
                            <i class="fas fa-envelope me-1"></i>{{ $ordenTrabajo->empleado->email }}
                        </a>
                    </div>
                    <div class="col-12 mb-2">
                        <strong>Teléfono:</strong><br>
                        <a href="tel:{{ $ordenTrabajo->empleado->phone }}" class="text-decoration-none">
                            <i class="fas fa-phone me-1"></i>{{ $ordenTrabajo->empleado->phone }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <strong>Salario:</strong><br>
                        ${{ number_format($ordenTrabajo->empleado->salary, 2) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Fecha de Contratación:</strong><br>
                        {{ $ordenTrabajo->empleado->hire_date->format('d/m/Y') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Timeline y Fechas -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar me-2"></i>
                    Timeline de la Orden
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <div class="avatar-sm mx-auto mb-2 bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-plus text-white"></i>
                            </div>
                            <h6>Creación</h6>
                            <p class="text-muted mb-0">{{ $ordenTrabajo->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <div class="avatar-sm mx-auto mb-2 {{ $ordenTrabajo->start_date ? 'bg-info' : 'bg-light' }} rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-play {{ $ordenTrabajo->start_date ? 'text-white' : 'text-muted' }}"></i>
                            </div>
                            <h6>Inicio</h6>
                            <p class="text-muted mb-0">
                                {{ $ordenTrabajo->start_date ? $ordenTrabajo->start_date->format('d/m/Y H:i') : 'No iniciado' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <div class="avatar-sm mx-auto mb-2 {{ $ordenTrabajo->end_date ? 'bg-success' : 'bg-light' }} rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-check {{ $ordenTrabajo->end_date ? 'text-white' : 'text-muted' }}"></i>
                            </div>
                            <h6>Finalización</h6>
                            <p class="text-muted mb-0">
                                {{ $ordenTrabajo->end_date ? $ordenTrabajo->end_date->format('d/m/Y H:i') : 'Pendiente' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <div class="avatar-sm mx-auto mb-2 bg-warning rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                            <h6>Duración</h6>
                            <p class="text-muted mb-0">
                                @if($ordenTrabajo->start_date && $ordenTrabajo->end_date)
                                    {{ $ordenTrabajo->start_date->diffInHours($ordenTrabajo->end_date) }} horas
                                @elseif($ordenTrabajo->start_date)
                                    {{ $ordenTrabajo->start_date->diffInHours(now()) }} horas transcurridas
                                @else
                                    No iniciado
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumen Financiero -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-dollar-sign me-2"></i>
                    Resumen Financiero
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-info mb-1">${{ number_format($ordenTrabajo->servicio->price, 2) }}</h4>
                            <small class="text-muted">Precio Base del Servicio</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-success mb-1">${{ number_format($ordenTrabajo->total_amount, 2) }}</h4>
                            <small class="text-muted">Total Final</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-warning mb-1">
                                ${{ number_format($ordenTrabajo->total_amount - $ordenTrabajo->servicio->price, 2) }}
                            </h4>
                            <small class="text-muted">Diferencia</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-primary mb-1">
                                @if($ordenTrabajo->status == 'completed')
                                    <i class="fas fa-check-circle"></i>
                                @else
                                    <i class="fas fa-clock"></i>
                                @endif
                            </h4>
                            <small class="text-muted">
                                {{ $ordenTrabajo->status == 'completed' ? 'Pagado' : 'Pendiente de Pago' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
.avatar-lg {
    width: 60px;
    height: 60px;
}

.avatar-sm {
    width: 40px;
    height: 40px;
}

.avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 600;
}
</style>
@endpush