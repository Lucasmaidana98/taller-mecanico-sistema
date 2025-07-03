<form method="post" action="{{ route('profile.update') }}" id="profile-form">
    @csrf
    @method('patch')
    
    <p class="text-muted mb-4">
        Actualiza la información de tu cuenta y dirección de email.
    </p>

    <div class="mb-3">
        <label for="name" class="form-label">Nombre Completo</label>
        <input type="text" 
               class="form-control @error('name') is-invalid @enderror" 
               id="name" 
               name="name" 
               value="{{ old('name', $user->name) }}" 
               required 
               autofocus>
        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Dirección de Email</label>
        <input type="email" 
               class="form-control @error('email') is-invalid @enderror" 
               id="email" 
               name="email" 
               value="{{ old('email', $user->email) }}" 
               required>
        @error('email')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
        
        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="alert alert-warning mt-2">
                <small>
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Tu dirección de email no está verificada.
                </small>
                <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">
                        Haz clic aquí para reenviar el email de verificación.
                    </button>
                </form>
                
                @if (session('status') === 'verification-link-sent')
                    <div class="alert alert-success mt-2">
                        <small>Se ha enviado un nuevo enlace de verificación a tu dirección de email.</small>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>
            Guardar Cambios
        </button>
        
        @if (session('status') === 'profile-updated')
            <div class="alert alert-success mb-0 py-2" id="profile-saved-message">
                <small>
                    <i class="fas fa-check me-1"></i>
                    Guardado exitosamente.
                </small>
            </div>
        @endif
    </div>
</form>

@if (session('status') === 'profile-updated')
<script>
    // Auto-hide success message after 3 seconds
    setTimeout(function() {
        const message = document.getElementById('profile-saved-message');
        if (message) {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }
    }, 3000);
</script>
@endif
