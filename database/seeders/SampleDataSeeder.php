<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Servicio;
use App\Models\Empleado;
use App\Models\OrdenTrabajo;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample clientes
        $clientes = [
            [
                'name' => 'Carlos Rodríguez',
                'email' => 'carlos.rodriguez@email.com',
                'phone' => '+595-21-123456',
                'address' => 'Av. España 1234, Asunción',
                'document_number' => '12345678',
                'status' => true
            ],
            [
                'name' => 'María González',
                'email' => 'maria.gonzalez@email.com',
                'phone' => '+595-21-234567',
                'address' => 'Calle Palma 567, Asunción',
                'document_number' => '23456789',
                'status' => true
            ],
            [
                'name' => 'Pedro Benítez',
                'email' => 'pedro.benitez@email.com',
                'phone' => '+595-21-345678',
                'address' => 'Av. Mariscal López 890, Asunción',
                'document_number' => '34567890',
                'status' => true
            ],
            [
                'name' => 'Ana Martínez',
                'email' => 'ana.martinez@email.com',
                'phone' => '+595-21-456789',
                'address' => 'Calle Independencia 123, Luque',
                'document_number' => '45678901',
                'status' => true
            ],
            [
                'name' => 'José López',
                'email' => 'jose.lopez@email.com',
                'phone' => '+595-21-567890',
                'address' => 'Av. Eusebio Ayala 456, San Lorenzo',
                'document_number' => '56789012',
                'status' => false
            ],
        ];

        foreach ($clientes as $clienteData) {
            Cliente::create($clienteData);
        }

        // Create sample servicios
        $servicios = [
            [
                'name' => 'Cambio de Aceite',
                'description' => 'Cambio de aceite de motor y filtro',
                'price' => 150000.00,
                'duration_hours' => 1.0,
                'status' => true
            ],
            [
                'name' => 'Alineación y Balanceo',
                'description' => 'Alineación de ruedas y balanceo de neumáticos',
                'price' => 250000.00,
                'duration_hours' => 2.0,
                'status' => true
            ],
            [
                'name' => 'Frenos Completos',
                'description' => 'Cambio de pastillas y discos de freno',
                'price' => 800000.00,
                'duration_hours' => 4.0,
                'status' => true
            ],
            [
                'name' => 'Diagnóstico Computarizado',
                'description' => 'Diagnóstico completo del vehículo con scanner',
                'price' => 100000.00,
                'duration_hours' => 0.5,
                'status' => true
            ],
            [
                'name' => 'Cambio de Batería',
                'description' => 'Reemplazo de batería del vehículo',
                'price' => 350000.00,
                'duration_hours' => 0.5,
                'status' => true
            ],
            [
                'name' => 'Lavado Completo',
                'description' => 'Lavado exterior e interior del vehículo',
                'price' => 80000.00,
                'duration_hours' => 1.5,
                'status' => true
            ],
        ];

        foreach ($servicios as $servicioData) {
            Servicio::create($servicioData);
        }

        // Create sample empleados
        $empleados = [
            [
                'name' => 'Roberto Silva',
                'email' => 'roberto.silva@taller.com',
                'phone' => '+595-21-111111',
                'position' => 'Mecánico Senior',
                'salary' => 4500000.00,
                'hire_date' => Carbon::parse('2020-01-15'),
                'status' => true
            ],
            [
                'name' => 'Luis Fernández',
                'email' => 'luis.fernandez@taller.com',
                'phone' => '+595-21-222222',
                'position' => 'Mecánico Junior',
                'salary' => 3200000.00,
                'hire_date' => Carbon::parse('2021-06-20'),
                'status' => true
            ],
            [
                'name' => 'Carmen Vega',
                'email' => 'carmen.vega@taller.com',
                'phone' => '+595-21-333333',
                'position' => 'Electricista Automotriz',
                'salary' => 4000000.00,
                'hire_date' => Carbon::parse('2019-03-10'),
                'status' => true
            ],
            [
                'name' => 'Miguel Torres',
                'email' => 'miguel.torres@taller.com',
                'phone' => '+595-21-444444',
                'position' => 'Supervisor de Taller',
                'salary' => 5500000.00,
                'hire_date' => Carbon::parse('2018-09-01'),
                'status' => true
            ],
        ];

        foreach ($empleados as $empleadoData) {
            Empleado::create($empleadoData);
        }

        // Create sample vehiculos
        $vehiculos = [
            [
                'cliente_id' => 1,
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2020,
                'license_plate' => 'ABC-123',
                'vin' => '1HGBH41JXMN109186',
                'color' => 'Blanco',
                'status' => true
            ],
            [
                'cliente_id' => 1,
                'brand' => 'Honda',
                'model' => 'Civic',
                'year' => 2019,
                'license_plate' => 'DEF-456',
                'vin' => '2HGBH41JXMN109187',
                'color' => 'Negro',
                'status' => true
            ],
            [
                'cliente_id' => 2,
                'brand' => 'Chevrolet',
                'model' => 'Onix',
                'year' => 2021,
                'license_plate' => 'GHI-789',
                'vin' => '3HGBH41JXMN109188',
                'color' => 'Rojo',
                'status' => true
            ],
            [
                'cliente_id' => 3,
                'brand' => 'Ford',
                'model' => 'Focus',
                'year' => 2018,
                'license_plate' => 'JKL-012',
                'vin' => '4HGBH41JXMN109189',
                'color' => 'Azul',
                'status' => true
            ],
            [
                'cliente_id' => 4,
                'brand' => 'Volkswagen',
                'model' => 'Gol',
                'year' => 2022,
                'license_plate' => 'MNO-345',
                'vin' => '5HGBH41JXMN109190',
                'color' => 'Gris',
                'status' => true
            ],
            [
                'cliente_id' => 5,
                'brand' => 'Nissan',
                'model' => 'Sentra',
                'year' => 2017,
                'license_plate' => 'PQR-678',
                'vin' => '6HGBH41JXMN109191',
                'color' => 'Blanco',
                'status' => false
            ],
        ];

        foreach ($vehiculos as $vehiculoData) {
            Vehiculo::create($vehiculoData);
        }

        // Create sample ordenes de trabajo
        $ordenes = [
            [
                'cliente_id' => 1,
                'vehiculo_id' => 1,
                'empleado_id' => 1,
                'servicio_id' => 1,
                'description' => 'Cambio de aceite programado - 15,000 km',
                'status' => 'completed',
                'total_amount' => 150000.00,
                'start_date' => Carbon::now()->subDays(15),
                'end_date' => Carbon::now()->subDays(15)->addHours(1)
            ],
            [
                'cliente_id' => 2,
                'vehiculo_id' => 3,
                'empleado_id' => 2,
                'servicio_id' => 3,
                'description' => 'Frenos delanteros haciendo ruido',
                'status' => 'in_progress',
                'total_amount' => 800000.00,
                'start_date' => Carbon::now()->subDays(2),
                'end_date' => null
            ],
            [
                'cliente_id' => 3,
                'vehiculo_id' => 4,
                'empleado_id' => 3,
                'servicio_id' => 4,
                'description' => 'Check engine encendido',
                'status' => 'pending',
                'total_amount' => 100000.00,
                'start_date' => Carbon::now()->addDays(1),
                'end_date' => null
            ],
            [
                'cliente_id' => 1,
                'vehiculo_id' => 2,
                'empleado_id' => 1,
                'servicio_id' => 2,
                'description' => 'Vehículo tirando hacia la izquierda',
                'status' => 'completed',
                'total_amount' => 250000.00,
                'start_date' => Carbon::now()->subDays(30),
                'end_date' => Carbon::now()->subDays(30)->addHours(2)
            ],
            [
                'cliente_id' => 4,
                'vehiculo_id' => 5,
                'empleado_id' => 4,
                'servicio_id' => 5,
                'description' => 'Batería no arranca en las mañanas',
                'status' => 'completed',
                'total_amount' => 350000.00,
                'start_date' => Carbon::now()->subDays(7),
                'end_date' => Carbon::now()->subDays(7)->addMinutes(30)
            ],
            [
                'cliente_id' => 2,
                'vehiculo_id' => 3,
                'empleado_id' => 2,
                'servicio_id' => 6,
                'description' => 'Lavado antes de entrega',
                'status' => 'pending',
                'total_amount' => 80000.00,
                'start_date' => Carbon::now()->addDays(3),
                'end_date' => null
            ],
            [
                'cliente_id' => 5,
                'vehiculo_id' => 6,
                'empleado_id' => 3,
                'servicio_id' => 1,
                'description' => 'Mantenimiento preventivo',
                'status' => 'cancelled',
                'total_amount' => 150000.00,
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => null
            ],
            [
                'cliente_id' => 3,
                'vehiculo_id' => 4,
                'empleado_id' => 1,
                'servicio_id' => 3,
                'description' => 'Cambio de pastillas de freno traseras',
                'status' => 'completed',
                'total_amount' => 400000.00,
                'start_date' => Carbon::now()->subMonths(2),
                'end_date' => Carbon::now()->subMonths(2)->addHours(3)
            ],
        ];

        foreach ($ordenes as $ordenData) {
            OrdenTrabajo::create($ordenData);
        }

        $this->command->info('✅ Datos de ejemplo creados exitosamente:');
        $this->command->info('   - ' . Cliente::count() . ' clientes');
        $this->command->info('   - ' . Vehiculo::count() . ' vehículos');
        $this->command->info('   - ' . Servicio::count() . ' servicios');
        $this->command->info('   - ' . Empleado::count() . ' empleados');
        $this->command->info('   - ' . OrdenTrabajo::count() . ' órdenes de trabajo');
    }
}