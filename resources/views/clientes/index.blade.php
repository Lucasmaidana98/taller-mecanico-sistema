@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-users me-2"></i>
        Gestión de Clientes
    </h1>
    @can('crear-clientes')
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>
        Nuevo Cliente
    </a>
    @endcan
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('clientes.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Nombre, email o teléfono..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos los estados</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="per_page" class="form-label">Mostrar</label>
                <select class="form-select" id="per_page" name="per_page">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-search"></i>
                </button>
                <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Clientes</h5>
                        <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Activos</h5>
                        <h3 class="mb-0">{{ $stats['activos'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Inactivos</h5>
                        <h3 class="mb-0">{{ $stats['inactivos'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-times fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Nuevos (30 días)</h5>
                        <h3 class="mb-0">{{ $stats['nuevos'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-plus fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Clientes Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Clientes
        </h5>
    </div>
    <div class="card-body">
        @if($clientes->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover" id="clientesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Documento</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th width="150">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $cliente)
                    <tr>
                        <td>{{ $cliente->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-initial bg-primary rounded-circle text-white">
                                        {{ strtoupper(substr($cliente->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div>
                                    <strong>{{ $cliente->name }}</strong>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="mailto:{{ $cliente->email }}" class="text-decoration-none">
                                {{ $cliente->email }}
                            </a>
                        </td>
                        <td>
                            <a href="tel:{{ $cliente->phone }}" class="text-decoration-none">
                                <i class="fas fa-phone me-1"></i>
                                {{ $cliente->phone }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $cliente->document_number }}</span>
                        </td>
                        <td>
                            @if($cliente->status)
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Activo
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="fas fa-times me-1"></i>Inactivo
                                </span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $cliente->created_at->format('d/m/Y H:i') }}
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                @can('ver-clientes')
                                <a href="{{ route('clientes.show', $cliente) }}" 
                                   class="btn btn-outline-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                
                                @can('editar-clientes')
                                <a href="{{ route('clientes.edit', $cliente) }}" 
                                   class="btn btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                
                                @can('eliminar-clientes')
                                <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-delete" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                <p class="text-muted mb-0">
                    Mostrando {{ $clientes->firstItem() }} a {{ $clientes->lastItem() }} 
                    de {{ $clientes->total() }} resultados
                </p>
            </div>
            <div>
                {{ $clientes->appends(request()->query())->links() }}
            </div>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay clientes registrados</h5>
            <p class="text-muted">Los clientes que registres aparecerán aquí.</p>
            @can('crear-clientes')
            <a href="{{ route('clientes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Registrar Primer Cliente
            </a>
            @endcan
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#clientesTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [7] }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
});
</script>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
}

.avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}
</style>
@endpush