@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-circle me-2"></i>
        Mi Perfil
    </h1>
</div>

<!-- Profile Information -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    Información Personal
                </h5>
            </div>
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-lock me-2"></i>
                    Cambiar Contraseña
                </h5>
            </div>
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Zona Peligrosa
                </h5>
            </div>
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información de Cuenta
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-initial bg-primary rounded-circle text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    </div>
                    <h5>{{ auth()->user()->name }}</h5>
                    <p class="text-muted">{{ auth()->user()->email }}</p>
                </div>
                
                <hr>
                
                <div class="mb-2">
                    <small class="text-muted">Miembro desde:</small>
                    <div>{{ auth()->user()->created_at->format('d/m/Y') }}</div>
                </div>
                
                @if(auth()->user()->email_verified_at)
                <div class="mb-2">
                    <small class="text-muted">Email verificado:</small>
                    <div>
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Verificado
                        </span>
                    </div>
                </div>
                @else
                <div class="mb-2">
                    <small class="text-muted">Email:</small>
                    <div>
                        <span class="badge bg-warning">
                            <i class="fas fa-exclamation me-1"></i>No verificado
                        </span>
                    </div>
                </div>
                @endif
                
                @if(auth()->user()->roles->count() > 0)
                <div class="mb-2">
                    <small class="text-muted">Rol:</small>
                    <div>
                        @foreach(auth()->user()->roles as $role)
                        <span class="badge bg-info me-1">{{ $role->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-lg {
    width: 80px;
    height: 80px;
}

.avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 600;
}
</style>
@endpush
