@extends('layouts.app')

@section('title', 'Crear Orden de Trabajo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-plus me-2"></i>
        Crear Nueva Orden de Trabajo
    </h1>
    <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>
        Volver
    </a>
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
                <form method="POST" action="{{ route('ordenes.store') }}" id="ordenForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cliente_id" class="form-label">
                                Cliente <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('cliente_id') is-invalid @enderror" 
                                    id="cliente_id" name="cliente_id" required>
                                <option value="">Seleccione un cliente</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
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
                                    id="vehiculo_id" name="vehiculo_id" required disabled>
                                <option value="">Primero seleccione un cliente</option>
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
                                    <option value="{{ $empleado->id }}" {{ old('empleado_id') == $empleado->id ? 'selected' : '' }}>
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
                                            {{ old('servicio_id') == $servicio->id ? 'selected' : '' }}>
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
                                      placeholder="Describa los detalles del trabajo a realizar..." required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                                <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completada</option>
                                <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
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
                                       id="total_amount" name="total_amount" value="{{ old('total_amount') }}" 
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
                                   id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d\TH:i')) }}" required>
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
                                   id="end_date" name="end_date" value="{{ old('end_date') }}">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Solo completar si la orden ya está finalizada</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('ordenes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Crear Orden
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
                    <i class="fas fa-lightbulb me-2"></i>
                    Consejos
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información importante:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Todos los campos marcados con <span class="text-danger">*</span> son obligatorios</li>
                        <li>El monto se actualizará automáticamente según el servicio seleccionado</li>
                        <li>Los vehículos se filtran por el cliente seleccionado</li>
                    </ul>
                </div>
                
                <div class="alert alert-success mt-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Estados de la orden:</strong>
                    <ul class="mb-0 mt-2">
                        <li><span class="badge bg-warning me-1">Pendiente</span>Orden creada</li>
                        <li><span class="badge bg-info me-1">En Progreso</span>Trabajo iniciado</li>
                        <li><span class="badge bg-success me-1">Completada</span>Trabajo finalizado</li>
                        <li><span class="badge bg-danger me-1">Cancelada</span>Orden cancelada</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Cliente Info Card (will be populated by JS) -->
        <div class="card mt-4" id="clienteInfoCard" style="display: none;">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Información del Cliente
                </h5>
            </div>
            <div class="card-body" id="clienteInfo">
                <!-- Content will be populated by JavaScript -->
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
        
        vehiculoSelect.empty().append('<option value="">Seleccione un vehículo</option>');
        
        if (clienteId && vehiculosPorCliente[clienteId]) {
            vehiculosPorCliente[clienteId].forEach(function(vehiculo) {
                vehiculoSelect.append(
                    `<option value="${vehiculo.id}">
                        ${vehiculo.brand} ${vehiculo.model} - ${vehiculo.license_plate}
                    </option>`
                );
            });
            vehiculoSelect.prop('disabled', false);
            
            // Show client info
            showClienteInfo(clienteId);
        } else {
            vehiculoSelect.prop('disabled', true);
            $('#clienteInfoCard').hide();
        }
    });
    
    // Servicio change handler - update total amount
    $('#servicio_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price');
        
        if (price) {
            $('#total_amount').val(price);
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
    
    // Show cliente info
    function showClienteInfo(clienteId) {
        const clienteOption = $('#cliente_id option:selected');
        const clienteText = clienteOption.text();
        
        if (clienteText !== 'Seleccione un cliente') {
            const [nombre, documento] = clienteText.split(' - ');
            
            $('#clienteInfo').html(`
                <div class="mb-2">
                    <strong>Nombre:</strong><br>
                    ${nombre}
                </div>
                <div class="mb-2">
                    <strong>Documento:</strong><br>
                    ${documento}
                </div>
            `);
            
            $('#clienteInfoCard').show();
        }
    }
    
    // Initialize if there's an old cliente_id value
    if ($('#cliente_id').val()) {
        $('#cliente_id').trigger('change');
    }
    
    // Initialize if there's an old servicio_id value
    if ($('#servicio_id').val()) {
        $('#servicio_id').trigger('change');
    }
});
</script>
@endpush