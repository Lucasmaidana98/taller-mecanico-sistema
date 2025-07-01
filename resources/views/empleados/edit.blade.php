@extends('layouts.app')

@section('title', 'Editar Empleado')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-edit me-2"></i>
        Editar Empleado: {{ $empleado->name }}
    </h1>
    <div class="btn-group">
        <a href="{{ route('empleados.show', $empleado) }}" class="btn btn-outline-info">
            <i class="fas fa-eye me-1"></i>
            Ver
        </a>
        <a href="{{ route('empleados.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Volver
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información del Empleado
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('empleados.update', $empleado) }}" id="empleadoForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">
                                Nombre Completo <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $empleado->name) }}" 
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
                                   id="email" name="email" value="{{ old('email', $empleado->email) }}" 
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
                                   id="phone" name="phone" value="{{ old('phone', $empleado->phone) }}" 
                                   placeholder="(000) 000-0000" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="position" class="form-label">
                                Posición <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('position') is-invalid @enderror" 
                                   id="position" name="position" value="{{ old('position', $empleado->position) }}" 
                                   placeholder="Ej: Mecánico, Electricista, Supervisor" required>
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="salary" class="form-label">
                                Salario <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control @error('salary') is-invalid @enderror" 
                                       id="salary" name="salary" value="{{ old('salary', $empleado->salary) }}" 
                                       placeholder="0.00" step="0.01" min="0" required>
                            </div>
                            @error('salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="hire_date" class="form-label">
                                Fecha de Contratación <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control @error('hire_date') is-invalid @enderror" 
                                   id="hire_date" name="hire_date" value="{{ old('hire_date', $empleado->hire_date->format('Y-m-d')) }}" required>
                            @error('hire_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="1" {{ old('status', $empleado->status) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('status', $empleado->status) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Registro</label>
                            <input type="text" class="form-control" value="{{ $empleado->created_at->format('d/m/Y H:i') }}" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tiempo en la Empresa</label>
                            <input type="text" class="form-control" value="{{ $empleado->hire_date->diffForHumans() }}" readonly>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('empleados.show', $empleado) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Actualizar Empleado
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
                    <i class="fas fa-chart-line me-2"></i>
                    Estadísticas del Empleado
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end pe-3">
                            <h4 class="text-primary mb-1">{{ $empleado->ordenesTrabajo->count() }}</h4>
                            <small class="text-muted">Órdenes Asignadas</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1">{{ $empleado->ordenesTrabajo->where('status', 'completed')->count() }}</h4>
                        <small class="text-muted">Completadas</small>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información importante:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Los cambios se aplicarán inmediatamente</li>
                        <li>El email debe ser único en el sistema</li>
                        <li>La fecha de contratación no puede ser futura</li>
                        <li>Si cambias el estado a inactivo, no podrás asignar nuevas órdenes</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Órdenes de trabajo del empleado -->
        @if($empleado->ordenesTrabajo->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Órdenes Asignadas ({{ $empleado->ordenesTrabajo->count() }})
                </h5>
            </div>
            <div class="card-body">
                @foreach($empleado->ordenesTrabajo->take(5) as $orden)
                <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2 pb-2 border-bottom' : '' }}">
                    <div>
                        <strong>Orden #{{ $orden->id }}</strong>
                        <br>
                        <small class="text-muted">{{ $orden->vehiculo->brand }} {{ $orden->vehiculo->model }}</small>
                        <br>
                        <small class="text-muted">{{ $orden->start_date->format('d/m/Y') }}</small>
                    </div>
                    <div class="text-end">
                        @switch($orden->status)
                            @case('pending')
                                <span class="badge bg-warning mb-1">Pendiente</span>
                                @break
                            @case('in_progress')
                                <span class="badge bg-info mb-1">En Progreso</span>
                                @break
                            @case('completed')
                                <span class="badge bg-success mb-1">Completada</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger mb-1">Cancelada</span>
                                @break
                        @endswitch
                        <br>
                        @can('ver-ordenes')
                        <a href="{{ route('ordenes.show', $orden) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endcan
                    </div>
                </div>
                @endforeach
                
                @if($empleado->ordenesTrabajo->count() > 5)
                <div class="text-center mt-3">
                    @can('ver-ordenes')
                    <a href="{{ route('ordenes.index', ['empleado_id' => $empleado->id]) }}" class="btn btn-sm btn-outline-primary">
                        Ver todas las órdenes
                    </a>
                    @endcan
                </div>
                @endif
            </div>
        </div>
        @endif
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
    
    // Salary formatting
    $('#salary').on('input', function() {
        let value = parseFloat(this.value);
        if (isNaN(value) || value < 0) {
            this.value = '';
        }
    });
    
    // Form validation
    $('#empleadoForm').on('submit', function(e) {
        let valid = true;
        
        // Check required fields
        $('#empleadoForm input[required], #empleadoForm select[required]').each(function() {
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
        
        // Date validation (no future dates)
        const hireDate = new Date($('#hire_date').val());
        const today = new Date();
        if (hireDate > today) {
            $('#hire_date').addClass('is-invalid');
            valid = false;
        }
        
        // Salary validation
        const salary = parseFloat($('#salary').val());
        if (isNaN(salary) || salary <= 0) {
            $('#salary').addClass('is-invalid');
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
    $('#empleadoForm input, #empleadoForm select').on('blur', function() {
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
    
    // Date validation on blur
    $('#hire_date').on('blur', function() {
        const hireDate = new Date($(this).val());
        const today = new Date();
        if (hireDate > today) {
            $(this).addClass('is-invalid');
        } else if ($(this).val()) {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Salary validation on blur
    $('#salary').on('blur', function() {
        const salary = parseFloat($(this).val());
        if (isNaN(salary) || salary <= 0) {
            $(this).addClass('is-invalid');
        } else if ($(this).val()) {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>
@endpush