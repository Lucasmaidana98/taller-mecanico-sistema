@extends('layouts.app')

@section('title', 'Editar Orden de Trabajo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-edit me-2"></i>
        Editar Orden de Trabajo #{{ $ordenTrabajo->id }}
    </h1>
    <div class="btn-group">
        <a href="{{ route('ordenes.show', $ordenTrabajo) }}" class="btn btn-outline-info">
            <i class="fas fa-eye me-1"></i>
            Ver
        </a>
        <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-xl-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información de la Orden
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('ordenes.update', $ordenTrabajo) }}" id="ordenForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cliente_id" class="form-label">
                                Cliente <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('cliente_id') is-invalid @enderror" 
                                    id="cliente_id" name="cliente_id" required>
                                <option value="">Seleccione un cliente</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" 
                                            {{ old('cliente_id', $ordenTrabajo->cliente_id) == $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->name }} - {{ $cliente->document_number }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cliente_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vehiculo_id" class="form-label">
                                Vehículo <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('vehiculo_id') is-invalid @enderror" 
                                    id="vehiculo_id" name="vehiculo_id" required>
                                <option value="">Seleccione un vehículo</option>
                                @foreach($vehiculos as $vehiculo)
                                    <option value="{{ $vehiculo->id }}" 
                                            {{ old('vehiculo_id', $ordenTrabajo->vehiculo_id) == $vehiculo->id ? 'selected' : '' }}>
                                        {{ $vehiculo->brand }} {{ $vehiculo->model }} - {{ $vehiculo->license_plate }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehiculo_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="empleado_id" class="form-label">
                                Empleado Asignado <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('empleado_id') is-invalid @enderror" 
                                    id="empleado_id" name="empleado_id" required>
                                <option value="">Seleccione un empleado</option>
                                @foreach($empleados as $empleado)
                                    <option value="{{ $empleado->id }}" 
                                            {{ old('empleado_id', $ordenTrabajo->empleado_id) == $empleado->id ? 'selected' : '' }}>
                                        {{ $empleado->name }} - {{ $empleado->position }}
                                    </option>
                                @endforeach
                            </select>
                            @error('empleado_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="servicio_id" class="form-label">
                                Servicio <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('servicio_id') is-invalid @enderror" 
                                    id="servicio_id" name="servicio_id" required>
                                <option value="">Seleccione un servicio</option>
                                @foreach($servicios as $servicio)
                                    <option value="{{ $servicio->id }}" 
                                            data-price="{{ $servicio->price }}"
                                            {{ old('servicio_id', $ordenTrabajo->servicio_id) == $servicio->id ? 'selected' : '' }}>
                                        {{ $servicio->name }} - ${{ number_format($servicio->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('servicio_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">
                                Descripción <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" 
                                      placeholder="Describa los detalles del trabajo a realizar..." required>{{ old('description', $ordenTrabajo->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="pending" {{ old('status', $ordenTrabajo->status) == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="in_progress" {{ old('status', $ordenTrabajo->status) == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                                <option value="completed" {{ old('status', $ordenTrabajo->status) == 'completed' ? 'selected' : '' }}>Completada</option>
                                <option value="cancelled" {{ old('status', $ordenTrabajo->status) == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="total_amount" class="form-label">
                                Monto Total <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control @error('total_amount') is-invalid @enderror" 
                                       id="total_amount" name="total_amount" 
                                       value="{{ old('total_amount', $ordenTrabajo->total_amount) }}" 
                                       step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            @error('total_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="start_date" class="form-label">
                                Fecha de Inicio <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" 
                                   id="start_date" name="start_date" 
                                   value="{{ old('start_date', $ordenTrabajo->start_date ? $ordenTrabajo->start_date->format('Y-m-d\TH:i') : '') }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">
                                Fecha de Finalización (Opcional)
                            </label>
                            <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" 
                                   id="end_date" name="end_date" 
                                   value="{{ old('end_date', $ordenTrabajo->end_date ? $ordenTrabajo->end_date->format('Y-m-d\TH:i') : '') }}">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Solo completar si la orden ya está finalizada</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Creación</label>
                            <input type="text" class="form-control" 
                                   value="{{ $ordenTrabajo->created_at->format('d/m/Y H:i') }}" readonly>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('ordenes.show', $ordenTrabajo) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Actualizar Orden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-xl-3">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Estado Actual
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    @switch($ordenTrabajo->status)
                        @case('pending')
                            <div class="avatar-lg mx-auto mb-2 bg-warning rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-clock text-white fa-2x"></i>
                            </div>
                            <h5 class="text-warning">Pendiente</h5>
                            @break
                        @case('in_progress')
                            <div class="avatar-lg mx-auto mb-2 bg-info rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-cog text-white fa-2x"></i>
                            </div>
                            <h5 class="text-info">En Progreso</h5>
                            @break
                        @case('completed')
                            <div class="avatar-lg mx-auto mb-2 bg-success rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-check text-white fa-2x"></i>
                            </div>
                            <h5 class="text-success">Completada</h5>
                            @break
                        @case('cancelled')
                            <div class="avatar-lg mx-auto mb-2 bg-danger rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-times text-white fa-2x"></i>
                            </div>
                            <h5 class="text-danger">Cancelada</h5>
                            @break
                    @endswitch
                    <p class="text-muted">Estado actual de la orden</p>
                </div>
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end pe-2">
                            <h4 class="text-success mb-1">${{ number_format($ordenTrabajo->total_amount, 2) }}</h4>
                            <small class="text-muted">Monto Total</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ps-2">
                            <h4 class="text-primary mb-1">
                                @if($ordenTrabajo->start_date && $ordenTrabajo->end_date)
                                    {{ $ordenTrabajo->start_date->diffInHours($ordenTrabajo->end_date) }}h
                                @elseif($ordenTrabajo->start_date)
                                    {{ $ordenTrabajo->start_date->diffInHours(now()) }}h
                                @else
                                    0h
                                @endif
                            </h4>
                            <small class="text-muted">Duración</small>
                        </div>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Cambios de Estado:</strong>
                    <ul class="mb-0 mt-2 small">
                        <li>Al cambiar a "En Progreso" se actualiza la fecha de inicio</li>
                        <li>Al cambiar a "Completada" se establece la fecha de finalización</li>
                        <li>Solo se pueden eliminar órdenes pendientes</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Historial de la Orden -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Historial
                </h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Orden Creada</h6>
                            <small class="text-muted">{{ $ordenTrabajo->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                    </div>
                    
                    @if($ordenTrabajo->start_date)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Trabajo Iniciado</h6>
                            <small class="text-muted">{{ $ordenTrabajo->start_date->format('d/m/Y H:i') }}</small>
                        </div>
                    </div>
                    @endif
                    
                    @if($ordenTrabajo->end_date)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Trabajo Completado</h6>
                            <small class="text-muted">{{ $ordenTrabajo->end_date->format('d/m/Y H:i') }}</small>
                        </div>
                    </div>
                    @endif
                    
                    @if($ordenTrabajo->updated_at != $ordenTrabajo->created_at)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Última Actualización</h6>
                            <small class="text-muted">{{ $ordenTrabajo->updated_at->format('d/m/Y H:i') }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Vehicle data for filtering
    const vehiculosPorCliente = @json($vehiculos->groupBy('cliente_id'));
    
    // Cliente change handler
    $('#cliente_id').on('change', function() {
        const clienteId = $(this).val();
        const vehiculoSelect = $('#vehiculo_id');
        const selectedVehiculo = '{{ old("vehiculo_id", $ordenTrabajo->vehiculo_id) }}';
        
        vehiculoSelect.empty().append('<option value="">Seleccione un vehículo</option>');
        
        if (clienteId && vehiculosPorCliente[clienteId]) {
            vehiculosPorCliente[clienteId].forEach(function(vehiculo) {
                const selected = vehiculo.id == selectedVehiculo ? 'selected' : '';
                vehiculoSelect.append(
                    `<option value="${vehiculo.id}" ${selected}>
                        ${vehiculo.brand} ${vehiculo.model} - ${vehiculo.license_plate}
                    </option>`
                );
            });
        }
    });
    
    // Servicio change handler - update total amount
    $('#servicio_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price');
        const currentAmount = $('#total_amount').val();
        
        if (price && (!currentAmount || currentAmount == 0)) {
            $('#total_amount').val(price);
        }
    });
    
    // Status change handler - auto-set dates
    $('#status').on('change', function() {
        const status = $(this).val();
        const now = new Date().toISOString().slice(0, 16);
        
        if (status === 'in_progress' && !$('#start_date').val()) {
            $('#start_date').val(now);
        }
        
        if (status === 'completed' && !$('#end_date').val()) {
            $('#end_date').val(now);
        }
        
        if (status === 'pending') {
            $('#end_date').val('');
        }
    });
    
    // Form validation
    $('#ordenForm').on('submit', function(e) {
        let valid = true;
        
        // Check required fields
        $('#ordenForm input[required], #ordenForm textarea[required], #ordenForm select[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Date validation
        const startDate = new Date($('#start_date').val());
        const endDate = $('#end_date').val() ? new Date($('#end_date').val()) : null;
        
        if (endDate && endDate < startDate) {
            $('#end_date').addClass('is-invalid');
            valid = false;
            Swal.fire({
                title: 'Error de validación',
                text: 'La fecha de finalización no puede ser anterior a la fecha de inicio.',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        }
        
        if (!valid) {
            e.preventDefault();
            if (!endDate || endDate >= startDate) {
                Swal.fire({
                    title: 'Error de validación',
                    text: 'Por favor, complete todos los campos requeridos.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            }
        }
    });
    
    // Real-time validation
    $('#ordenForm input, #ordenForm textarea, #ordenForm select').on('blur change', function() {
        if ($(this).prop('required') && !$(this).val()) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Initialize on page load
    $('#cliente_id').trigger('change');
});
</script>

<style>
.avatar-lg {
    width: 80px;
    height: 80px;
}

.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -1rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 0.375rem;
    border-left: 3px solid #dee2e6;
}
</style>
@endpush