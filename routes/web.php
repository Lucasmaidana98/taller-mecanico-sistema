<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\OrdenTrabajoController;
use App\Http\Controllers\ReporteController;

Route::get('/', function () {
    return redirect('/login');
});

// Test route
Route::get('/test', function() {
    return 'Test route working!';
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:ver-dashboard')
        ->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Clientes
    Route::resource('clientes', ClienteController::class)
        ->middleware('permission:ver-clientes');

    // Vehiculos  
    Route::resource('vehiculos', VehiculoController::class)
        ->middleware('permission:ver-vehiculos');

    // Servicios
    Route::resource('servicios', ServicioController::class)
        ->middleware('permission:ver-servicios');

    // Empleados (solo para admin)
    Route::resource('empleados', EmpleadoController::class)
        ->middleware('permission:ver-empleados');

    // Ordenes de Trabajo
    Route::resource('ordenes', OrdenTrabajoController::class)
        ->parameters(['ordenes' => 'ordenTrabajo'])
        ->middleware('permission:ver-ordenes');

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])
        ->middleware('permission:ver-reportes')
        ->name('reportes.index');
    Route::post('/reportes/generar', [ReporteController::class, 'generar'])
        ->middleware('permission:generar-reportes')
        ->name('reportes.generar');
    Route::get('/reportes/exportar/{id}', [ReporteController::class, 'exportarPDF'])
        ->middleware('permission:exportar-reportes')
        ->name('reportes.exportar');
});

require __DIR__.'/auth.php';
