<form method="post" action="{{ route('password.update') }}" id="password-form">
    @csrf
    @method('put')
    
    <p class="text-muted mb-4">
        Asegúrate de que tu cuenta use una contraseña larga y aleatoria para mantenerte seguro.
    </p>

    <div class="mb-3">
        <label for="current_password" class="form-label">Contraseña Actual</label>
        <input type="password" 
               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
               id="current_password" 
               name="current_password" 
               autocomplete="current-password">
        @error('current_password', 'updatePassword')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Nueva Contraseña</label>
        <input type="password" 
               class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
               id="password" 
               name="password" 
               autocomplete="new-password">
        @error('password', 'updatePassword')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
        <div class="form-text">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                Mínimo 8 caracteres. Se recomienda incluir mayúsculas, minúsculas, números y símbolos.
            </small>
        </div>
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
        <input type="password" 
               class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" 
               id="password_confirmation" 
               name="password_confirmation" 
               autocomplete="new-password">
        @error('password_confirmation', 'updatePassword')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-warning">
            <i class="fas fa-key me-1"></i>
            Actualizar Contraseña
        </button>
        
        @if (session('status') === 'password-updated')
            <div class="alert alert-success mb-0 py-2" id="password-saved-message">
                <small>
                    <i class="fas fa-check me-1"></i>
                    Contraseña actualizada exitosamente.
                </small>
            </div>
        @endif
    </div>
</form>

@if (session('status') === 'password-updated')
<script>
    // Auto-hide success message after 3 seconds
    setTimeout(function() {
        const message = document.getElementById('password-saved-message');
        if (message) {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }
    }, 3000);
</script>
@endif
