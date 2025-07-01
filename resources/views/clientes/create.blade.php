@extends('layouts.app')

@section('title', 'Crear Cliente')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-plus me-2"></i>
        Crear Nuevo Cliente
    </h1>
    <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>
        Volver
    </a>
</div>

<div class="row">
    <div class="col-lg-8 col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información del Cliente
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('clientes.store') }}" id="clienteForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">
                                Nombre Completo <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="Ingrese el nombre completo" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" 
                                   placeholder="correo@ejemplo.com" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">
                                Teléfono <span class="text-danger">*</span>
                            </label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone') }}" 
                                   placeholder="(000) 000-0000" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="document_number" class="form-label">
                                Número de Documento <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('document_number') is-invalid @enderror" 
                                   id="document_number" name="document_number" value="{{ old('document_number') }}" 
                                   placeholder="Cédula, DNI, Pasaporte, etc." required>
                            @error('document_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="address" class="form-label">
                                Dirección <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" name="address" rows="3" 
                                      placeholder="Ingrese la dirección completa" required>{{ old('address') }}</textarea>
                            @error('address')
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
                        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-xl-6">
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
                        <li>El email debe ser único en el sistema</li>
                        <li>El número de documento debe ser único</li>
                        <li>La dirección debe ser lo más específica posible</li>
                    </ul>
                </div>
                
                <div class="alert alert-success mt-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Después de crear el cliente:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Podrás registrar sus vehículos</li>
                        <li>Crear órdenes de trabajo</li>
                        <li>Generar reportes de servicios</li>
                        <li>Consultar el historial completo</li>
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
    // Phone number formatting
    $('#phone').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 6) {
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        } else if (value.length >= 3) {
            value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
        }
        this.value = value;
    });
    
    // Form validation
    $('#clienteForm').on('submit', function(e) {
        let valid = true;
        
        // Check required fields
        $('#clienteForm input[required], #clienteForm textarea[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Email validation
        const email = $('#email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $('#email').addClass('is-invalid');
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
    $('#clienteForm input, #clienteForm textarea').on('blur', function() {
        if ($(this).prop('required') && !$(this).val().trim()) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Email validation on blur
    $('#email').on('blur', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
        } else if (email) {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>
@endpush