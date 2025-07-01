<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reporte->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        
        .header h2 {
            color: #64748b;
            margin: 5px 0 0 0;
            font-size: 18px;
            font-weight: normal;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            padding: 8px 15px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }
        
        .info-label {
            font-weight: bold;
            color: #475569;
            width: 30%;
        }
        
        .section-title {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 12px 20px;
            margin: 25px 0 15px 0;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .data-table th {
            background-color: #1e293b;
            color: white;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #334155;
            font-weight: bold;
        }
        
        .data-table td {
            padding: 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            color: white;
        }
        
        .status-pending { background-color: #f59e0b; }
        .status-in_progress { background-color: #06b6d4; }
        .status-completed { background-color: #10b981; }
        .status-cancelled { background-color: #ef4444; }
        .status-active { background-color: #10b981; }
        .status-inactive { background-color: #6b7280; }
        
        .summary-stats {
            display: table;
            width: 100%;
            margin: 20px 0;
        }
        
        .stat-row {
            display: table-row;
        }
        
        .stat-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 15px;
            border: 2px solid #e2e8f0;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
            font-style: italic;
        }
        
        .currency {
            text-align: right;
            font-weight: bold;
            color: #059669;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mb-20 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Taller</h1>
        <h2>{{ $reporte->name }}</h2>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell info-label">Tipo de Reporte:</div>
            <div class="info-cell">{{ ucfirst($reporte->type) }}</div>
            <div class="info-cell info-label">Fecha de Generación:</div>
            <div class="info-cell">{{ $reporte->generated_at->format('d/m/Y H:i:s') }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Generado por:</div>
            <div class="info-cell">{{ $reporte->user->name ?? 'Sistema' }}</div>
            <div class="info-cell info-label">Total de Registros:</div>
            <div class="info-cell">{{ is_countable($data) ? count($data) : 'N/A' }}</div>
        </div>
    </div>

    @if(isset($filtros) && !empty($filtros))
    <div class="section-title">Filtros Aplicados</div>
    <div class="info-grid mb-20">
        @foreach($filtros as $key => $value)
            @if($value)
            <div class="info-row">
                <div class="info-cell info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</div>
                <div class="info-cell">{{ is_array($value) ? implode(', ', $value) : $value }}</div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    @if(isset($estadisticas) && !empty($estadisticas))
    <div class="section-title">Resumen Estadístico</div>
    <div class="summary-stats mb-20">
        <div class="stat-row">
            @foreach($estadisticas as $label => $value)
            <div class="stat-cell">
                <span class="stat-number">{{ is_numeric($value) ? number_format($value, is_float($value) ? 2 : 0) : $value }}</span>
                <span class="stat-label">{{ $label }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($reporte->type === 'ordenes')
        <div class="section-title">Órdenes de Trabajo</div>
        @if(!empty($data))
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Servicio</th>
                    <th>Empleado</th>
                    <th>Estado</th>
                    <th>Monto</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $orden)
                <tr>
                    <td>{{ $orden->id }}</td>
                    <td>{{ $orden->cliente->name ?? 'N/A' }}</td>
                    <td>{{ $orden->vehiculo->brand ?? 'N/A' }} {{ $orden->vehiculo->model ?? '' }}</td>
                    <td>{{ $orden->servicio->name ?? 'N/A' }}</td>
                    <td>{{ $orden->empleado->name ?? 'N/A' }}</td>
                    <td>
                        <span class="status-badge status-{{ $orden->status }}">
                            {{ match($orden->status) {
                                'pending' => 'Pendiente',
                                'in_progress' => 'En Proceso',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                                default => $orden->status
                            } }}
                        </span>
                    </td>
                    <td class="currency">${{ number_format($orden->total_amount, 2) }}</td>
                    <td>{{ $orden->start_date ? $orden->start_date->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $orden->end_date ? $orden->end_date->format('d/m/Y') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">No se encontraron órdenes para los filtros seleccionados.</div>
        @endif

    @elseif($reporte->type === 'clientes')
        <div class="section-title">Clientes</div>
        @if(!empty($data))
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Documento</th>
                    <th>Estado</th>
                    <th>Vehículos</th>
                    <th>Órdenes</th>
                    <th>Último Servicio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $cliente)
                <tr>
                    <td>{{ $cliente->id }}</td>
                    <td>{{ $cliente->name }}</td>
                    <td>{{ $cliente->email }}</td>
                    <td>{{ $cliente->phone }}</td>
                    <td>{{ $cliente->document_number }}</td>
                    <td>
                        <span class="status-badge status-{{ $cliente->status ? 'active' : 'inactive' }}">
                            {{ $cliente->status ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="text-center">{{ $cliente->vehiculos_count ?? 0 }}</td>
                    <td class="text-center">{{ $cliente->ordenes_count ?? 0 }}</td>
                    <td>{{ $cliente->ultima_orden ? $cliente->ultima_orden->format('d/m/Y') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">No se encontraron clientes para los filtros seleccionados.</div>
        @endif

    @elseif($reporte->type === 'vehiculos')
        <div class="section-title">Vehículos</div>
        @if(!empty($data))
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Propietario</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Año</th>
                    <th>Placa</th>
                    <th>VIN</th>
                    <th>Color</th>
                    <th>Estado</th>
                    <th>Órdenes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $vehiculo)
                <tr>
                    <td>{{ $vehiculo->id }}</td>
                    <td>{{ $vehiculo->cliente->name ?? 'N/A' }}</td>
                    <td>{{ $vehiculo->brand }}</td>
                    <td>{{ $vehiculo->model }}</td>
                    <td>{{ $vehiculo->year }}</td>
                    <td>{{ $vehiculo->license_plate }}</td>
                    <td>{{ $vehiculo->vin }}</td>
                    <td>{{ $vehiculo->color }}</td>
                    <td>
                        <span class="status-badge status-{{ $vehiculo->status ? 'active' : 'inactive' }}">
                            {{ $vehiculo->status ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="text-center">{{ $vehiculo->ordenes_count ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">No se encontraron vehículos para los filtros seleccionados.</div>
        @endif

    @elseif($reporte->type === 'empleados')
        <div class="section-title">Empleados</div>
        @if(!empty($data))
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Posición</th>
                    <th>Salario</th>
                    <th>Fecha Contratación</th>
                    <th>Estado</th>
                    <th>Órdenes Asignadas</th>
                    <th>Órdenes Completadas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $empleado)
                <tr>
                    <td>{{ $empleado->id }}</td>
                    <td>{{ $empleado->name }}</td>
                    <td>{{ $empleado->email }}</td>
                    <td>{{ $empleado->position }}</td>
                    <td class="currency">${{ number_format($empleado->salary, 2) }}</td>
                    <td>{{ $empleado->hire_date->format('d/m/Y') }}</td>
                    <td>
                        <span class="status-badge status-{{ $empleado->status ? 'active' : 'inactive' }}">
                            {{ $empleado->status ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="text-center">{{ $empleado->ordenes_count ?? 0 }}</td>
                    <td class="text-center">{{ $empleado->ordenes_completadas ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">No se encontraron empleados para los filtros seleccionados.</div>
        @endif

    @elseif($reporte->type === 'servicios')
        <div class="section-title">Servicios</div>
        @if(!empty($data))
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Duración (hrs)</th>
                    <th>Estado</th>
                    <th>Veces Solicitado</th>
                    <th>Ingresos Generados</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $servicio)
                <tr>
                    <td>{{ $servicio->id }}</td>
                    <td>{{ $servicio->name }}</td>
                    <td>{{ Str::limit($servicio->description, 50) }}</td>
                    <td class="currency">${{ number_format($servicio->price, 2) }}</td>
                    <td class="text-center">{{ $servicio->duration_hours }}</td>
                    <td>
                        <span class="status-badge status-{{ $servicio->status ? 'active' : 'inactive' }}">
                            {{ $servicio->status ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="text-center">{{ $servicio->ordenes_count ?? 0 }}</td>
                    <td class="currency">${{ number_format($servicio->ingresos_generados ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">No se encontraron servicios para los filtros seleccionados.</div>
        @endif

    @elseif($reporte->type === 'ingresos')
        <div class="section-title">Reporte de Ingresos</div>
        @if(!empty($data))
        <table class="data-table">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Órdenes Completadas</th>
                    <th>Ingresos Totales</th>
                    <th>Promedio por Orden</th>
                    <th>Servicio Más Solicitado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $periodo)
                <tr>
                    <td>{{ $periodo['periodo'] }}</td>
                    <td class="text-center">{{ $periodo['ordenes'] }}</td>
                    <td class="currency">${{ number_format($periodo['ingresos'], 2) }}</td>
                    <td class="currency">${{ number_format($periodo['promedio'], 2) }}</td>
                    <td>{{ $periodo['servicio_popular'] ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">No se encontraron datos de ingresos para el período seleccionado.</div>
        @endif
    @endif

    <div class="footer">
        <p>Sistema de Taller - Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Este reporte contiene información confidencial y está destinado únicamente para uso interno.</p>
    </div>
</body>
</html>