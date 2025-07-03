@extends('layouts.app')

@section('title', 'Editar Servicio')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-edit me-2"></i>
        Editar: {{ $servicio->name }}
    </h1>
    <div class="btn-group">
        <a href="{{ route('servicios.show', $servicio) }}" class="btn btn-outline-info">
            <i class="fas fa-eye me-1"></i>
            Ver
        </a>
        <a href="{{ route('servicios.index') }}" class="btn btn-outline-secondary">
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
                    Información del Servicio
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('servicios.update', $servicio) }}" id="servicioForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">
                                Nombre del Servicio <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $servicio->name) }}" 
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
                                      placeholder="Describe en detalle lo que incluye este servicio" required>{{ old('description', $servicio->description) }}</textarea>
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
                                       id="price" name="price" value="{{ old('price', $servicio->price) }}" 
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
                                   id="duration_hours" name="duration_hours" value="{{ old('duration_hours', $servicio->duration_hours) }}" 
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
                                <option value="1" {{ old('status', $servicio->status) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('status', $servicio->status) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Creación</label>
                            <input type="text" class="form-control" value="{{ $servicio->created_at->format('d/m/Y H:i') }}" readonly>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('servicios.show', $servicio) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Actualizar Servicio
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
                    Estadísticas del Servicio
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end pe-3">
                            <h4 class="text-primary mb-1">{{ $servicio->ordenesTrabajo ? $servicio->ordenesTrabajo->count() : 0 }}</h4>
                            <small class="text-muted">Órdenes Totales</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1">${{ number_format($servicio->ordenesTrabajo ? $servicio->ordenesTrabajo->sum('total_amount') : 0, 2) }}</h4>
                        <small class="text-muted">Ingresos</small>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información importante:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Los cambios se aplicarán inmediatamente</li>
                        <li>El precio afectará las nuevas órdenes</li>
                        <li>Cambiar el estado a inactivo evitará nuevas asignaciones</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection