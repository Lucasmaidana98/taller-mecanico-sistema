@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-chart-bar me-2"></i>
        Centro de Reportes
    </h1>
    <div class="btn-group">
        <button type="button" class="btn btn-success" onclick="exportToPDF()">
            <i class="fas fa-file-pdf me-1"></i>
            Exportar PDF
        </button>
        <button type="button" class="btn btn-info" onclick="exportToExcel()">
            <i class="fas fa-file-excel me-1"></i>
            Exportar Excel
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter me-2"></i>
            Filtros de Reporte
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reportes.index') }}" class="row g-3" id="reporteForm">
            <div class="col-md-3">
                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                       value="{{ request('fecha_inicio', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                       value="{{ request('fecha_fin', now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                <select class="form-select" id="tipo_reporte" name="tipo_reporte">
                    <option value="general" {{ request('tipo_reporte', 'general') == 'general' ? 'selected' : '' }}>General</option>
                    <option value="servicios" {{ request('tipo_reporte') == 'servicios' ? 'selected' : '' }}>Por Servicios</option>
                    <option value="empleados" {{ request('tipo_reporte') == 'empleados' ? 'selected' : '' }}>Por Empleados</option>
                    <option value="clientes" {{ request('tipo_reporte') == 'clientes' ? 'selected' : '' }}>Por Clientes</option>
                    <option value="vehiculos" {{ request('tipo_reporte') == 'vehiculos' ? 'selected' : '' }}>Por Vehículos</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-chart-line me-1"></i>
                    Generar Reporte
                </button>
                <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-refresh"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Órdenes Completadas</h5>
                        <h3 class="mb-0">{{ $stats['ordenes_completadas'] ?? 0 }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
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
                        <h5 class="card-title">Ingresos Totales</h5>
                        <h3 class="mb-0">${{ number_format($stats['ingresos_totales'] ?? 0, 2) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
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
                        <h5 class="card-title">Clientes Atendidos</h5>
                        <h3 class="mb-0">{{ $stats['clientes_atendidos'] ?? 0 }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x opacity-75"></i>
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
                        <h5 class="card-title">Promedio por Orden</h5>
                        <h3 class="mb-0">${{ number_format($stats['promedio_orden'] ?? 0, 2) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calculator fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Ingresos por Día
                </h5>
            </div>
            <div class="card-body">
                <canvas id="ingresosChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Servicios Más Populares
                </h5>
            </div>
            <div class="card-body">
                <canvas id="serviciosChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Reports -->
@switch(request('tipo_reporte', 'general'))
    @case('servicios')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-cogs me-2"></i>
                    Reporte por Servicios
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Cantidad</th>
                                <th>Ingresos</th>
                                <th>Promedio</th>
                                <th>Popularidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reporteServicios ?? [] as $servicio)
                            <tr>
                                <td>{{ $servicio['nombre'] }}</td>
                                <td>{{ $servicio['cantidad'] }}</td>
                                <td>${{ number_format($servicio['ingresos'], 2) }}</td>
                                <td>${{ number_format($servicio['promedio'], 2) }}</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" style="width: {{ $servicio['porcentaje'] }}%">
                                            {{ $servicio['porcentaje'] }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay datos disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @break
        
    @case('empleados')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-tie me-2"></i>
                    Reporte por Empleados
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Posición</th>
                                <th>Órdenes</th>
                                <th>Ingresos Generados</th>
                                <th>Eficiencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reporteEmpleados ?? [] as $empleado)
                            <tr>
                                <td>{{ $empleado['nombre'] }}</td>
                                <td><span class="badge bg-info">{{ $empleado['posicion'] }}</span></td>
                                <td>{{ $empleado['ordenes'] }}</td>
                                <td>${{ number_format($empleado['ingresos'], 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $empleado['eficiencia'] >= 80 ? 'success' : ($empleado['eficiencia'] >= 60 ? 'warning' : 'danger') }}">
                                        {{ $empleado['eficiencia'] }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay datos disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @break
        
    @default
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Reporte General
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Servicio</th>
                                <th>Empleado</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ordenes ?? [] as $orden)
                            <tr>
                                <td><strong>#{{ $orden->id }}</strong></td>
                                <td>{{ $orden->cliente->name }}</td>
                                <td>{{ $orden->vehiculo->brand }} {{ $orden->vehiculo->model }}</td>
                                <td>{{ $orden->servicio->name }}</td>
                                <td>{{ $orden->empleado->name }}</td>
                                <td>{{ $orden->start_date->format('d/m/Y') }}</td>
                                <td>
                                    @switch($orden->status)
                                        @case('completed')
                                            <span class="badge bg-success">Completada</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-info">En Progreso</span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-warning">Pendiente</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Cancelada</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>${{ number_format($orden->total_amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No hay datos disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
@endswitch
@endsection

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Ingresos Chart
const ingresosCtx = document.getElementById('ingresosChart').getContext('2d');
const ingresosChart = new Chart(ingresosCtx, {
    type: 'line',
    data: {
        labels: @json($chartData['labels'] ?? []),
        datasets: [{
            label: 'Ingresos Diarios',
            data: @json($chartData['ingresos'] ?? []),
            borderColor: 'rgb(37, 99, 235)',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Servicios Chart
const serviciosCtx = document.getElementById('serviciosChart').getContext('2d');
const serviciosChart = new Chart(serviciosCtx, {
    type: 'doughnut',
    data: {
        labels: @json($chartData['servicios_labels'] ?? []),
        datasets: [{
            data: @json($chartData['servicios_data'] ?? []),
            backgroundColor: [
                '#2563eb',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#06b6d4'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Export functions
function exportToPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'pdf');
    window.open('{{ route("reportes.exportar") }}?' + params.toString(), '_blank');
}

function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'excel');
    window.open('{{ route("reportes.exportar") }}?' + params.toString(), '_blank');
}
</script>
@endpush