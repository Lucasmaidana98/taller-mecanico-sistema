<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orden_trabajos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('empleado_id')->constrained('empleados');
            $table->foreignId('servicio_id')->constrained('servicios');
            $table->text('description');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled']);
            $table->decimal('total_amount', 10, 2);
            $table->datetime('start_date');
            $table->datetime('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_trabajos');
    }
};
