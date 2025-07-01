@extends('layouts.app')

@section('title', 'Crear Servicio')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-plus me-2"></i>
        Crear Nuevo Servicio
    </h1>
    <a href="{{ route('servicios.index') }}" class="btn btn-outline-secondary">
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
                    Información del Servicio
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('servicios.store') }}" id="servicioForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">
                                Nombre del Servicio <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="Cambio de aceite, Frenos, etc." required>
                            @error('name')
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
                                      placeholder="Describe en detalle lo que incluye este servicio" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">
                                Precio <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control @error('price') is-invalid @enderror" 
                                       id="price" name="price" value="{{ old('price') }}" 
                                       placeholder="0.00" step="0.01" min="0" required>
                            </div>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="duration_hours" class="form-label">
                                Duración (Horas) <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control @error('duration_hours') is-invalid @enderror" 
                                   id="duration_hours" name="duration_hours" value="{{ old('duration_hours') }}" 
                                   placeholder="1.5" step="0.25" min="0.25" required>
                            @error('duration_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                        <a href="{{ route('servicios.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Guardar Servicio
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
                        <li>El precio debe ser mayor a 0</li>
                        <li>La duración se especifica en horas</li>
                        <li>Puedes usar decimales (ej: 1.5 horas)</li>
                    </ul>
                </div>
                
                <div class="alert alert-success mt-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Después de crear el servicio:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Aparecerá en las órdenes de trabajo</li>
                        <li>Los empleados podrán asignarlo</li>
                        <li>Se incluirá en reportes y estadísticas</li>
                        <li>Podrás editarlo cuando sea necesario</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('#servicioForm').on('submit', function(e) {
        let valid = true;
        
        // Check required fields
        $('#servicioForm input[required], #servicioForm textarea[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Price validation
        const price = parseFloat($('#price').val());
        if (price <= 0) {
            $('#price').addClass('is-invalid');
            valid = false;
        }
        
        // Duration validation
        const duration = parseFloat($('#duration_hours').val());
        if (duration <= 0) {
            $('#duration_hours').addClass('is-invalid');
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
    $('#servicioForm input, #servicioForm textarea').on('blur', function() {
        if ($(this).prop('required') && !$(this).val().trim()) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>
@endpush