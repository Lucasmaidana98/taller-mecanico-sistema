@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-tachometer-alt me-2 text-primary"></i>
        Dashboard
    </h1>
    <div class="text-muted">
        <i class="fas fa-calendar me-2"></i>
        {{ now()->format('d/m/Y') }}
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-gradient rounded-circle p-3">
                            <i class="fas fa-users text-white fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Clientes Activos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $stats['total_clientes'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-gradient rounded-circle p-3">
                            <i class="fas fa-car text-white fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Vehículos Activos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $stats['total_vehiculos'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-gradient rounded-circle p-3">
                            <i class="fas fa-clipboard-list text-white fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Órdenes Pendientes
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $stats['ordenes_pendientes'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-gradient rounded-circle p-3">
                            <i class="fas fa-dollar-sign text-white fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Ingresos del Mes
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ${{ number_format($stats['ingresos_mes'] ?? 0, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders and Status Overview -->
<div class="row">
    <div class="col-xl-8 col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-clock me-2"></i>
                    Órdenes Recientes
                </h6>
            </div>
            <div class="card-body">
                @if(isset($ordenes_recientes) && $ordenes_recientes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Vehículo</th>
                                    <th>Servicio</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordenes_recientes as $orden)
                                <tr>
                                    <td>{{ $orden->cliente->name ?? 'N/A' }}</td>
                                    <td>{{ $orden->vehiculo->brand ?? 'N/A' }} {{ $orden->vehiculo->model ?? '' }}</td>
                                    <td>{{ $orden->servicio->name ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match($orden->status) {
                                                'pending' => 'bg-warning',
                                                'in_progress' => 'bg-info',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            $statusText = match($orden->status) {
                                                'pending' => 'Pendiente',
                                                'in_progress' => 'En Proceso',
                                                'completed' => 'Completado',
                                                'cancelled' => 'Cancelado',
                                                default => $orden->status
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                                    </td>
                                    <td>{{ $orden->start_date ? $orden->start_date->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard text-muted fa-3x mb-3"></i>
                        <p class="text-muted">No hay órdenes recientes</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-pie me-2"></i>
                    Estado de Órdenes
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-sm font-weight-bold">Pendientes</span>
                        <span class="badge bg-warning">{{ $stats['ordenes_pendientes'] ?? 0 }}</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: {{ $stats['total_ordenes'] > 0 ? (($stats['ordenes_pendientes'] ?? 0) / $stats['total_ordenes']) * 100 : 0 }}%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-sm font-weight-bold">En Proceso</span>
                        <span class="badge bg-info">{{ $stats['ordenes_proceso'] ?? 0 }}</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: {{ $stats['total_ordenes'] > 0 ? (($stats['ordenes_proceso'] ?? 0) / $stats['total_ordenes']) * 100 : 0 }}%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-sm font-weight-bold">Completadas</span>
                        <span class="badge bg-success">{{ $stats['ordenes_completadas'] ?? 0 }}</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ $stats['total_ordenes'] > 0 ? (($stats['ordenes_completadas'] ?? 0) / $stats['total_ordenes']) * 100 : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt me-2"></i>
                    Acciones Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @can('crear-ordenes')
                    <a href="{{ route('ordenes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Nueva Orden
                    </a>
                    @endcan
                    
                    @can('crear-clientes')
                    <a href="{{ route('clientes.create') }}" class="btn btn-success">
                        <i class="fas fa-user-plus me-2"></i>
                        Nuevo Cliente
                    </a>
                    @endcan
                    
                    @can('ver-reportes')
                    <a href="{{ route('reportes.index') }}" class="btn btn-info">
                        <i class="fas fa-chart-bar me-2"></i>
                        Ver Reportes
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
