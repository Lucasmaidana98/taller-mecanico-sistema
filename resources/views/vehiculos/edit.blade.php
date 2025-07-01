@extends('layouts.app')

@section('title', 'Editar Vehículo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-car me-2"></i>
        Editar: {{ $vehiculo->brand }} {{ $vehiculo->model }}
    </h1>
    <div class="btn-group">
        <a href="{{ route('vehiculos.show', $vehiculo) }}" class="btn btn-outline-info">
            <i class="fas fa-eye me-1"></i>
            Ver
        </a>
        <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información del Vehículo
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('vehiculos.update', $vehiculo) }}" id="vehiculoForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="cliente_id" class="form-label">
                                Propietario <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('cliente_id') is-invalid @enderror" 
                                    id="cliente_id" name="cliente_id" required>
                                <option value="">Seleccione un cliente</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" 
                                            {{ old('cliente_id', $vehiculo->cliente_id) == $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->name }} - {{ $cliente->document_number }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cliente_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="brand" class="form-label">
                                Marca <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('brand') is-invalid @enderror" 
                                   id="brand" name="brand" value="{{ old('brand', $vehiculo->brand) }}" 
                                   placeholder="Toyota, Honda, Ford..." required>
                            @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label">
                                Modelo <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('model') is-invalid @enderror" 
                                   id="model" name="model" value="{{ old('model', $vehiculo->model) }}" 
                                   placeholder="Corolla, Civic, Focus..." required>
                            @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="year" class="form-label">
                                Año <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('year') is-invalid @enderror" 
                                    id="year" name="year" required>
                                <option value="">Seleccione el año</option>
                                @for($year = date('Y') + 1; $year >= 1950; $year--)
                                    <option value="{{ $year }}" {{ old('year', $vehiculo->year) == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                            @error('year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="license_plate" class="form-label">
                                Placa <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control text-uppercase @error('license_plate') is-invalid @enderror" 
                                   id="license_plate" name="license_plate" value="{{ old('license_plate', $vehiculo->license_plate) }}" 
                                   placeholder="ABC-123" required>
                            @error('license_plate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="color" class="form-label">
                                Color <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('color') is-invalid @enderror" 
                                    id="color" name="color" required>
                                <option value="">Seleccione el color</option>
                                <option value="blanco" {{ old('color', $vehiculo->color) == 'blanco' ? 'selected' : '' }}>Blanco</option>
                                <option value="negro" {{ old('color', $vehiculo->color) == 'negro' ? 'selected' : '' }}>Negro</option>
                                <option value="gris" {{ old('color', $vehiculo->color) == 'gris' ? 'selected' : '' }}>Gris</option>
                                <option value="plata" {{ old('color', $vehiculo->color) == 'plata' ? 'selected' : '' }}>Plata</option>
                                <option value="azul" {{ old('color', $vehiculo->color) == 'azul' ? 'selected' : '' }}>Azul</option>
                                <option value="rojo" {{ old('color', $vehiculo->color) == 'rojo' ? 'selected' : '' }}>Rojo</option>
                                <option value="verde" {{ old('color', $vehiculo->color) == 'verde' ? 'selected' : '' }}>Verde</option>
                                <option value="amarillo" {{ old('color', $vehiculo->color) == 'amarillo' ? 'selected' : '' }}>Amarillo</option>
                                <option value="marron" {{ old('color', $vehiculo->color) == 'marron' ? 'selected' : '' }}>Marrón</option>
                                <option value="otro" {{ old('color', $vehiculo->color) == 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="vin" class="form-label">
                                VIN (Número de Identificación) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control text-uppercase @error('vin') is-invalid @enderror" 
                                   id="vin" name="vin" value="{{ old('vin', $vehiculo->vin) }}" 
                                   placeholder="17 caracteres alfanuméricos" maxlength="17" required>
                            @error('vin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                El VIN debe tener exactamente 17 caracteres alfanuméricos.
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="1" {{ old('status', $vehiculo->status) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('status', $vehiculo->status) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Registro</label>
                            <input type="text" class="form-control" value="{{ $vehiculo->created_at->format('d/m/Y H:i') }}" readonly>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('vehiculos.show', $vehiculo) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Actualizar Vehículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Actividad del Vehículo
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end pe-3">
                            <h4 class="text-primary mb-1">{{ $vehiculo->ordenTrabajos->count() }}</h4>
                            <small class="text-muted">Órdenes Totales</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1">{{ $vehiculo->ordenTrabajos->where('status', 'completed')->count() }}</h4>
                        <small class="text-muted">Completadas</small>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información importante:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Los cambios se aplicarán inmediatamente</li>
                        <li>La placa debe ser única en el sistema</li>
                        <li>El VIN debe ser único y tener 17 caracteres</li>
                        <li>Cambiar el propietario afectará los reportes</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Propietario Actual
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <div class="avatar-sm mx-auto mb-3">
                        <div class="avatar-initial bg-primary rounded-circle text-white">
                            {{ strtoupper(substr($vehiculo->cliente->name, 0, 1)) }}
                        </div>
                    </div>
                    <h6>{{ $vehiculo->cliente->name }}</h6>
                    <p class="text-muted mb-0">{{ $vehiculo->cliente->email }}</p>
                    <p class="text-muted mb-0">{{ $vehiculo->cliente->phone }}</p>
                    <p class="text-muted">{{ $vehiculo->cliente->document_number }}</p>
                    <a href="{{ route('clientes.show', $vehiculo->cliente) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>
                        Ver Cliente
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Órdenes recientes -->
        @if($vehiculo->ordenTrabajos->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Órdenes Recientes
                </h5>
            </div>
            <div class="card-body">
                @foreach($vehiculo->ordenTrabajos->take(3) as $orden)
                <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2 pb-2 border-bottom' : '' }}">
                    <div>
                        <strong>Orden #{{ $orden->id }}</strong>
                        <br>
                        <small class="text-muted">{{ $orden->start_date->format('d/m/Y') }}</small>
                    </div>
                    <div class="text-end">
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
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for cliente dropdown
    $('#cliente_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar cliente...',
        allowClear: true
    });
    
    // License plate formatting
    $('#license_plate').on('input', function() {
        let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (value.length > 3) {
            value = value.substring(0, 3) + '-' + value.substring(3, 6);
        }
        this.value = value;
    });
    
    // VIN formatting and validation
    $('#vin').on('input', function() {
        let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        this.value = value;
        
        if (value.length !== 17 && value.length > 0) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Form validation
    $('#vehiculoForm').on('submit', function(e) {
        let valid = true;
        
        // Check required fields
        $('#vehiculoForm input[required], #vehiculoForm select[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // VIN validation
        const vin = $('#vin').val();
        if (vin.length !== 17) {
            $('#vin').addClass('is-invalid');
            valid = false;
        }
        
        if (!valid) {
            e.preventDefault();
            Swal.fire({
                title: 'Error de validación',
                text: 'Por favor, complete todos los campos requeridos correctamente.',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        }
    });
    
    // Real-time validation
    $('#vehiculoForm input[required], #vehiculoForm select[required]').on('blur change', function() {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>

<style>
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
    font-size: 1rem;
    font-weight: 600;
}

.select2-container--bootstrap-5 .select2-selection {
    min-height: calc(2.25rem + 2px);
}
</style>
@endpush