<div class="mb-4">
    <p class="text-muted">
        Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. 
        Antes de eliminar tu cuenta, por favor descarga cualquier dato o información que desees conservar.
    </p>
</div>

<button type="button" 
        class="btn btn-danger" 
        data-bs-toggle="modal" 
        data-bs-target="#deleteAccountModal">
    <i class="fas fa-trash me-1"></i>
    Eliminar Cuenta
</button>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Eliminación de Cuenta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="{{ route('profile.destroy') }}" id="delete-account-form">
                @csrf
                @method('delete')
                
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>¿Estás seguro de que quieres eliminar tu cuenta?</strong>
                    </div>
                    
                    <p class="text-muted">
                        Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. 
                        Por favor ingresa tu contraseña para confirmar que deseas eliminar permanentemente tu cuenta.
                    </p>

                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Contraseña</label>
                        <input type="password" 
                               class="form-control @error('password', 'userDeletion') is-invalid @enderror" 
                               id="delete_password" 
                               name="password" 
                               placeholder="Ingresa tu contraseña"
                               required>
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Eliminar Cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->userDeletion->isNotEmpty())
<script>
    // Show modal if there are deletion errors
    document.addEventListener('DOMContentLoaded', function() {
        const modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
        modal.show();
    });
</script>
@endif