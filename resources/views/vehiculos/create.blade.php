@extends('layouts.app')

@section('title', 'Crear Vehículo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-car me-2"></i>
        Registrar Nuevo Vehículo
    </h1>
    <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>
        Volver
    </a>
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
                <form method="POST" action="{{ route('vehiculos.store') }}" id="vehiculoForm">
                    @csrf
                    
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
                                            {{ old('cliente_id', request('cliente_id')) == $cliente->id ? 'selected' : '' }}>
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
                                   id="brand" name="brand" value="{{ old('brand') }}" 
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
                                   id="model" name="model" value="{{ old('model') }}" 
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
                                    <option value="{{ $year }}" {{ old('year') == $year ? 'selected' : '' }}>
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
                                   id="license_plate" name="license_plate" value="{{ old('license_plate') }}" 
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
                                <option value="blanco" {{ old('color') == 'blanco' ? 'selected' : '' }}>Blanco</option>
                                <option value="negro" {{ old('color') == 'negro' ? 'selected' : '' }}>Negro</option>
                                <option value="gris" {{ old('color') == 'gris' ? 'selected' : '' }}>Gris</option>
                                <option value="plata" {{ old('color') == 'plata' ? 'selected' : '' }}>Plata</option>
                                <option value="azul" {{ old('color') == 'azul' ? 'selected' : '' }}>Azul</option>
                                <option value="rojo" {{ old('color') == 'rojo' ? 'selected' : '' }}>Rojo</option>
                                <option value="verde" {{ old('color') == 'verde' ? 'selected' : '' }}>Verde</option>
                                <option value="amarillo" {{ old('color') == 'amarillo' ? 'selected' : '' }}>Amarillo</option>
                                <option value="marron" {{ old('color') == 'marron' ? 'selected' : '' }}>Marrón</option>
                                <option value="otro" {{ old('color') == 'otro' ? 'selected' : '' }}>Otro</option>
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
                                   id="vin" name="vin" value="{{ old('vin') }}" 
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
                                <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('vehiculos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Guardar Vehículo
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
                        <li>La placa debe ser única en el sistema</li>
                        <li>El VIN debe ser único y tener 17 caracteres</li>
                        <li>Selecciona el propietario antes de continuar</li>
                    </ul>
                </div>
                
                <div class="alert alert-success mt-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Después de registrar el vehículo:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Podrás crear órdenes de trabajo</li>
                        <li>Consultar el historial de servicios</li>
                        <li>Generar reportes específicos</li>
                        <li>Programar mantenimientos</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Formato de VIN:</strong>
                    <p class="mb-0 mt-2">
                        El VIN es un código único de 17 caracteres que identifica al vehículo. 
                        Puedes encontrarlo en el tablero, la puerta del conductor o los documentos del vehículo.
                    </p>
                </div>
            </div>
        </div>
        
        @if(request('cliente_id'))
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Cliente Seleccionado
                </h5>
            </div>
            <div class="card-body">
                @php $clienteSeleccionado = $clientes->find(request('cliente_id')); @endphp
                @if($clienteSeleccionado)
                <div class="text-center">
                    <div class="avatar-sm mx-auto mb-3">
                        <div class="avatar-initial bg-primary rounded-circle text-white">
                            {{ strtoupper(substr($clienteSeleccionado->name, 0, 1)) }}
                        </div>
                    </div>
                    <h6>{{ $clienteSeleccionado->name }}</h6>
                    <p class="text-muted mb-0">{{ $clienteSeleccionado->email }}</p>
                    <p class="text-muted">{{ $clienteSeleccionado->phone }}</p>
                </div>
                @endif
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