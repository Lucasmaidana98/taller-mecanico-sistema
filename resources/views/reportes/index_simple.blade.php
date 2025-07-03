@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="container">
    <h1>Centro de Reportes</h1>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Órdenes Completadas</h5>
                    <h3>{{ $stats['ordenes_completadas'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Ingresos Totales</h5>
                    <h3>${{ number_format($stats['ingresos_totales'] ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Clientes Atendidos</h5>
                    <h3>{{ $stats['clientes_atendidos'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Promedio por Orden</h5>
                    <h3>${{ number_format($stats['promedio_orden'] ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection