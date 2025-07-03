<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Órdenes de Trabajo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .stat-box {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            width: 23%;
        }
        .stat-number {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
        }
        .stat-label {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REPORTE DE ÓRDENES DE TRABAJO</div>
        <div class="subtitle">
            Período: {{ Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
        </div>
        <div class="subtitle">
            Generado: {{ $fechaGeneracion->format('d/m/Y H:i:s') }}
        </div>
    </div>

    @if(isset($data['estadisticas']))
    <div class="info-section">
        <h3>Resumen Estadístico</h3>
        <div style="display: table; width: 100%; margin-bottom: 20px;">
            <div style="display: table-row;">
                <div style="display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #ddd;">
                    <div class="stat-number">{{ $data['estadisticas']['total_ordenes'] }}</div>
                    <div class="stat-label">Total Órdenes</div>
                </div>
                <div style="display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #ddd;">
                    <div class="stat-number">{{ $data['estadisticas']['ordenes_completadas'] }}</div>
                    <div class="stat-label">Completadas</div>
                </div>
                <div style="display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #ddd;">
                    <div class="stat-number">{{ $data['estadisticas']['ordenes_pendientes'] }}</div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div style="display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #ddd;">
                    <div class="stat-number">${{ number_format($data['estadisticas']['ingresos_total'], 0, ',', '.') }}</div>
                    <div class="stat-label">Ingresos Total</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(isset($data['ordenes']) && $data['ordenes']->count() > 0)
    <div class="info-section">
        <h3>Detalle de Órdenes</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Servicio</th>
                    <th>Empleado</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['ordenes'] as $orden)
                <tr>
                    <td>{{ $orden->id }}</td>
                    <td>{{ $orden->cliente->name ?? 'N/A' }}</td>
                    <td>{{ $orden->vehiculo->license_plate ?? 'N/A' }} - {{ $orden->vehiculo->brand ?? '' }} {{ $orden->vehiculo->model ?? '' }}</td>
                    <td>{{ $orden->servicio->name ?? 'N/A' }}</td>
                    <td>{{ $orden->empleado->name ?? 'N/A' }}</td>
                    <td>
                        @switch($orden->status)
                            @case('pending') Pendiente @break
                            @case('in_progress') En Proceso @break
                            @case('completed') Completada @break
                            @case('cancelled') Cancelada @break
                            @default {{ $orden->status }}
                        @endswitch
                    </td>
                    <td>{{ $orden->created_at->format('d/m/Y') }}</td>
                    <td>${{ number_format($orden->total_amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="info-section">
        <p>No se encontraron órdenes de trabajo para el período seleccionado.</p>
    </div>
    @endif

    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #666;">
        Reporte generado por Sistema de Taller - {{ now()->format('Y') }}
    </div>
</body>
</html>