<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Clientes</title>
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
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REPORTE DE CLIENTES</div>
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
                    <div style="font-size: 16px; font-weight: bold; color: #2563eb;">{{ $data['estadisticas']['total_clientes'] }}</div>
                    <div style="font-size: 10px; color: #666;">Total Clientes</div>
                </div>
                <div style="display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #ddd;">
                    <div style="font-size: 16px; font-weight: bold; color: #2563eb;">{{ $data['estadisticas']['clientes_con_ordenes'] }}</div>
                    <div style="font-size: 10px; color: #666;">Con Órdenes</div>
                </div>
                <div style="display: table-cell; width: 50%; text-align: center; padding: 10px; border: 1px solid #ddd;">
                    <div style="font-size: 16px; font-weight: bold; color: #2563eb;">${{ number_format($data['estadisticas']['ingreso_promedio_por_cliente'] ?? 0, 0, ',', '.') }}</div>
                    <div style="font-size: 10px; color: #666;">Ingreso Promedio por Cliente</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(isset($data['clientes']) && count($data['clientes']) > 0)
    <div class="info-section">
        <h3>Detalle de Clientes</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Total Órdenes</th>
                    <th>Órdenes Completadas</th>
                    <th>Total Gastado</th>
                    <th>Vehículos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['clientes'] as $clienteData)
                <tr>
                    <td>{{ $clienteData['cliente']->name }}</td>
                    <td>{{ $clienteData['cliente']->email }}</td>
                    <td>{{ $clienteData['cliente']->phone }}</td>
                    <td>{{ $clienteData['total_ordenes'] }}</td>
                    <td>{{ $clienteData['ordenes_completadas'] }}</td>
                    <td>${{ number_format($clienteData['total_gastado'], 0, ',', '.') }}</td>
                    <td>{{ $clienteData['total_vehiculos'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="info-section">
        <p>No se encontraron clientes para el período seleccionado.</p>
    </div>
    @endif

    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #666;">
        Reporte generado por Sistema de Taller - {{ now()->format('Y') }}
    </div>
</body>
</html>