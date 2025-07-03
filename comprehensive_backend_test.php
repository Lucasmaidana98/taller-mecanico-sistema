<?php

/**
 * Comprehensive Backend Testing for Laravel Application
 * Tests Models, Database Operations, Relationships, and Data Integrity
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Servicio;
use App\Models\Empleado;
use App\Models\OrdenTrabajo;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class ComprehensiveBackendTest
{
    private $results = [];
    private $testCount = 0;
    private $passedTests = 0;
    private $failedTests = 0;

    public function __construct()
    {
        echo "=== COMPREHENSIVE BACKEND TESTING ===\n";
        echo "Testing Laravel Application at http://localhost:8002\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
    }

    private function test($testName, $testFunction)
    {
        $this->testCount++;
        try {
            $result = $testFunction();
            if ($result === true) {
                $this->passedTests++;
                $this->results[] = "✅ PASS: $testName";
                echo "✅ PASS: $testName\n";
            } else {
                $this->failedTests++;
                $this->results[] = "❌ FAIL: $testName - $result";
                echo "❌ FAIL: $testName - $result\n";
            }
        } catch (Exception $e) {
            $this->failedTests++;
            $this->results[] = "❌ ERROR: $testName - " . $e->getMessage();
            echo "❌ ERROR: $testName - " . $e->getMessage() . "\n";
        }
    }

    // ========== MIGRATION AND SCHEMA TESTING ==========
    public function testMigrationsAndSchema()
    {
        echo "\n--- MIGRATION AND SCHEMA TESTING ---\n";

        // Test table existence
        $this->test("Clientes table exists", function() {
            return Schema::hasTable('clientes');
        });

        $this->test("Vehiculos table exists", function() {
            return Schema::hasTable('vehiculos');
        });

        $this->test("Servicios table exists", function() {
            return Schema::hasTable('servicios');
        });

        $this->test("Empleados table exists", function() {
            return Schema::hasTable('empleados');
        });

        $this->test("Orden_trabajos table exists", function() {
            return Schema::hasTable('orden_trabajos');
        });

        $this->test("Users table exists", function() {
            return Schema::hasTable('users');
        });

        // Test column structure
        $this->test("Clientes table has correct columns", function() {
            $columns = ['id', 'name', 'email', 'phone', 'address', 'document_number', 'status'];
            foreach ($columns as $column) {
                if (!Schema::hasColumn('clientes', $column)) {
                    return "Missing column: $column";
                }
            }
            return true;
        });

        $this->test("Vehiculos table has correct columns", function() {
            $columns = ['id', 'cliente_id', 'brand', 'model', 'year', 'license_plate', 'vin', 'color', 'status'];
            foreach ($columns as $column) {
                if (!Schema::hasColumn('vehiculos', $column)) {
                    return "Missing column: $column";
                }
            }
            return true;
        });

        $this->test("OrdenTrabajo table has correct foreign keys", function() {
            $columns = ['cliente_id', 'vehiculo_id', 'empleado_id', 'servicio_id'];
            foreach ($columns as $column) {
                if (!Schema::hasColumn('orden_trabajos', $column)) {
                    return "Missing foreign key: $column";
                }
            }
            return true;
        });

        // Test unique constraints
        $this->test("Clientes email unique constraint", function() {
            try {
                $cliente1 = Cliente::create([
                    'name' => 'Test Unique 1',
                    'email' => 'unique@test.com',
                    'phone' => '123456789',
                    'address' => 'Test Address',
                    'document_number' => 'UNIQUE001'
                ]);

                $cliente2 = Cliente::create([
                    'name' => 'Test Unique 2',
                    'email' => 'unique@test.com', // Same email
                    'phone' => '987654321',
                    'address' => 'Test Address 2',
                    'document_number' => 'UNIQUE002'
                ]);

                // Clean up
                $cliente1->delete();
                $cliente2->delete();

                return "Unique constraint not enforced";
            } catch (Exception $e) {
                // Clean up any created records
                Cliente::where('email', 'unique@test.com')->delete();
                return true; // Exception expected
            }
        });
    }

    // ========== MODEL TESTING ==========
    public function testModels()
    {
        echo "\n--- MODEL TESTING ---\n";

        // Test Cliente model
        $this->test("Cliente model fillable fields", function() {
            $cliente = new Cliente();
            $fillable = $cliente->getFillable();
            $expected = ['name', 'email', 'phone', 'address', 'document_number', 'status'];
            
            foreach ($expected as $field) {
                if (!in_array($field, $fillable)) {
                    return "Missing fillable field: $field";
                }
            }
            return true;
        });

        $this->test("Cliente model casts", function() {
            $cliente = new Cliente();
            $casts = $cliente->getCasts();
            return isset($casts['status']) && $casts['status'] === 'boolean';
        });

        // Test Vehiculo model
        $this->test("Vehiculo model fillable fields", function() {
            $vehiculo = new Vehiculo();
            $fillable = $vehiculo->getFillable();
            $expected = ['cliente_id', 'brand', 'model', 'year', 'license_plate', 'vin', 'color', 'status'];
            
            foreach ($expected as $field) {
                if (!in_array($field, $fillable)) {
                    return "Missing fillable field: $field";
                }
            }
            return true;
        });

        $this->test("Vehiculo model casts", function() {
            $vehiculo = new Vehiculo();
            $casts = $vehiculo->getCasts();
            return isset($casts['year']) && $casts['year'] === 'integer' && 
                   isset($casts['status']) && $casts['status'] === 'boolean';
        });

        // Test Servicio model
        $this->test("Servicio model fillable fields", function() {
            $servicio = new Servicio();
            $fillable = $servicio->getFillable();
            $expected = ['name', 'description', 'price', 'duration_hours', 'status'];
            
            foreach ($expected as $field) {
                if (!in_array($field, $fillable)) {
                    return "Missing fillable field: $field";
                }
            }
            return true;
        });

        $this->test("Servicio model casts", function() {
            $servicio = new Servicio();
            $casts = $servicio->getCasts();
            return isset($casts['price']) && $casts['price'] === 'decimal:2' && 
                   isset($casts['duration_hours']) && $casts['duration_hours'] === 'decimal:2';
        });

        // Test Empleado model
        $this->test("Empleado model fillable fields", function() {
            $empleado = new Empleado();
            $fillable = $empleado->getFillable();
            $expected = ['name', 'email', 'phone', 'position', 'salary', 'hire_date', 'status'];
            
            foreach ($expected as $field) {
                if (!in_array($field, $fillable)) {
                    return "Missing fillable field: $field";
                }
            }
            return true;
        });

        $this->test("Empleado model casts", function() {
            $empleado = new Empleado();
            $casts = $empleado->getCasts();
            return isset($casts['salary']) && $casts['salary'] === 'decimal:2' && 
                   isset($casts['hire_date']) && $casts['hire_date'] === 'date';
        });

        // Test OrdenTrabajo model
        $this->test("OrdenTrabajo model fillable fields", function() {
            $orden = new OrdenTrabajo();
            $fillable = $orden->getFillable();
            $expected = ['cliente_id', 'vehiculo_id', 'empleado_id', 'servicio_id', 'description', 'status', 'total_amount', 'start_date', 'end_date'];
            
            foreach ($expected as $field) {
                if (!in_array($field, $fillable)) {
                    return "Missing fillable field: $field";
                }
            }
            return true;
        });

        // Test User model
        $this->test("User model has HasRoles trait", function() {
            $user = new User();
            return method_exists($user, 'assignRole') && method_exists($user, 'hasRole');
        });
    }

    // ========== RELATIONSHIP TESTING ==========
    public function testRelationships()
    {
        echo "\n--- RELATIONSHIP TESTING ---\n";

        // Create test data
        DB::beginTransaction();
        
        try {
            $cliente = Cliente::create([
                'name' => 'Test Cliente Relationship',
                'email' => 'test_rel@example.com',
                'phone' => '123456789',
                'address' => 'Test Address',
                'document_number' => 'REL001'
            ]);

            $vehiculo = Vehiculo::create([
                'cliente_id' => $cliente->id,
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2020,
                'license_plate' => 'REL-123',
                'vin' => 'REL12345678901234567',
                'color' => 'Blue'
            ]);

            $servicio = Servicio::create([
                'name' => 'Test Service',
                'description' => 'Test service description',
                'price' => 100.00,
                'duration_hours' => 2.5
            ]);

            $empleado = Empleado::create([
                'name' => 'Test Employee',
                'email' => 'emp_rel@example.com',
                'phone' => '987654321',
                'position' => 'Mechanic',
                'salary' => 50000.00,
                'hire_date' => '2023-01-01'
            ]);

            $orden = OrdenTrabajo::create([
                'cliente_id' => $cliente->id,
                'vehiculo_id' => $vehiculo->id,
                'empleado_id' => $empleado->id,
                'servicio_id' => $servicio->id,
                'description' => 'Test order',
                'status' => 'pending',
                'total_amount' => 100.00,
                'start_date' => now()
            ]);

            // Test hasMany relationships
            $this->test("Cliente hasMany Vehiculos relationship", function() use ($cliente) {
                $vehiculos = $cliente->vehiculos;
                return $vehiculos->count() > 0 && $vehiculos->first() instanceof Vehiculo;
            });

            $this->test("Cliente hasMany OrdenTrabajo relationship", function() use ($cliente) {
                $ordenes = $cliente->ordenesTrabajo;
                return $ordenes->count() > 0 && $ordenes->first() instanceof OrdenTrabajo;
            });

            $this->test("Vehiculo hasMany OrdenTrabajo relationship", function() use ($vehiculo) {
                $ordenes = $vehiculo->ordenesTrabajo;
                return $ordenes->count() > 0 && $ordenes->first() instanceof OrdenTrabajo;
            });

            $this->test("Servicio hasMany OrdenTrabajo relationship", function() use ($servicio) {
                $ordenes = $servicio->ordenesTrabajo;
                return $ordenes->count() > 0 && $ordenes->first() instanceof OrdenTrabajo;
            });

            $this->test("Empleado hasMany OrdenTrabajo relationship", function() use ($empleado) {
                $ordenes = $empleado->ordenesTrabajo;
                return $ordenes->count() > 0 && $ordenes->first() instanceof OrdenTrabajo;
            });

            // Test belongsTo relationships
            $this->test("Vehiculo belongsTo Cliente relationship", function() use ($vehiculo) {
                $cliente = $vehiculo->cliente;
                return $cliente instanceof Cliente;
            });

            $this->test("OrdenTrabajo belongsTo Cliente relationship", function() use ($orden) {
                $cliente = $orden->cliente;
                return $cliente instanceof Cliente;
            });

            $this->test("OrdenTrabajo belongsTo Vehiculo relationship", function() use ($orden) {
                $vehiculo = $orden->vehiculo;
                return $vehiculo instanceof Vehiculo;
            });

            $this->test("OrdenTrabajo belongsTo Empleado relationship", function() use ($orden) {
                $empleado = $orden->empleado;
                return $empleado instanceof Empleado;
            });

            $this->test("OrdenTrabajo belongsTo Servicio relationship", function() use ($orden) {
                $servicio = $orden->servicio;
                return $servicio instanceof Servicio;
            });

            // Test eager loading
            $this->test("Eager loading with ->with()", function() use ($cliente) {
                $clienteWithRelations = Cliente::with(['vehiculos', 'ordenesTrabajo'])
                    ->find($cliente->id);
                
                return $clienteWithRelations->relationLoaded('vehiculos') && 
                       $clienteWithRelations->relationLoaded('ordenesTrabajo');
            });

            // Test relationship counting
            $this->test("Relationship counting", function() use ($cliente) {
                $clienteWithCounts = Cliente::withCount(['vehiculos', 'ordenesTrabajo'])
                    ->find($cliente->id);
                
                return isset($clienteWithCounts->vehiculos_count) && 
                       isset($clienteWithCounts->ordenes_trabajo_count);
            });

            // Test whereHas queries
            $this->test("whereHas relationship queries", function() use ($cliente) {
                $clientesWithVehiculos = Cliente::whereHas('vehiculos')->get();
                return $clientesWithVehiculos->contains('id', $cliente->id);
            });

            DB::rollback();

        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // ========== DATABASE OPERATIONS TESTING ==========
    public function testDatabaseOperations()
    {
        echo "\n--- DATABASE OPERATIONS TESTING ---\n";

        DB::beginTransaction();

        try {
            // Test CREATE operations
            $this->test("CREATE Cliente operation", function() {
                $cliente = Cliente::create([
                    'name' => 'Test CREATE Cliente',
                    'email' => 'create_test@example.com',
                    'phone' => '123456789',
                    'address' => 'Test Address',
                    'document_number' => 'CREATE001'
                ]);
                
                return $cliente->exists && $cliente->id > 0;
            });

            $this->test("CREATE Servicio operation", function() {
                $servicio = Servicio::create([
                    'name' => 'Test CREATE Service',
                    'description' => 'Test service for CREATE operation',
                    'price' => 150.00,
                    'duration_hours' => 3.0
                ]);
                
                return $servicio->exists && $servicio->id > 0;
            });

            // Test READ operations
            $this->test("READ operations with various queries", function() {
                $clientes = Cliente::all();
                $activeClientes = Cliente::where('status', true)->get();
                $clienteByEmail = Cliente::where('email', 'create_test@example.com')->first();
                
                return $clientes->count() > 0 && 
                       $activeClientes->count() >= 0 && 
                       $clienteByEmail !== null;
            });

            // Test UPDATE operations
            $this->test("UPDATE operations and data persistence", function() {
                $cliente = Cliente::where('email', 'create_test@example.com')->first();
                if (!$cliente) return "Cliente not found for update test";
                
                $originalName = $cliente->name;
                $newName = 'Updated Cliente Name';
                
                $cliente->update(['name' => $newName]);
                $cliente->refresh();
                
                $success = $cliente->name === $newName;
                
                // Restore original name
                $cliente->update(['name' => $originalName]);
                
                return $success;
            });

            // Test DELETE operations
            $this->test("DELETE operations", function() {
                $servicio = Servicio::create([
                    'name' => 'Test DELETE Service',
                    'description' => 'Service to be deleted',
                    'price' => 50.00,
                    'duration_hours' => 1.0
                ]);
                
                $servicioId = $servicio->id;
                $servicio->delete();
                
                $deletedServicio = Servicio::find($servicioId);
                return $deletedServicio === null;
            });

            // Test database transactions
            $this->test("Database transactions", function() {
                DB::beginTransaction();
                
                try {
                    $cliente = Cliente::create([
                        'name' => 'Transaction Test Cliente',
                        'email' => 'transaction@test.com',
                        'phone' => '123456789',
                        'address' => 'Test Address',
                        'document_number' => 'TRANS001'
                    ]);
                    
                    // Simulate an error
                    throw new Exception('Simulated error');
                    
                } catch (Exception $e) {
                    DB::rollback();
                    
                    // Check that the cliente was not created
                    $cliente = Cliente::where('email', 'transaction@test.com')->first();
                    return $cliente === null;
                }
            });

            DB::rollback();

        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // ========== SEEDER TESTING ==========
    public function testSeeders()
    {
        echo "\n--- SEEDER TESTING ---\n";

        $this->test("Roles and Permissions seeded correctly", function() {
            $adminRole = Role::where('name', 'Administrador')->first();
            $mecanicoRole = Role::where('name', 'Mecánico')->first();
            $recepcionistaRole = Role::where('name', 'Recepcionista')->first();
            
            return $adminRole !== null && $mecanicoRole !== null && $recepcionistaRole !== null;
        });

        $this->test("Permissions seeded correctly", function() {
            $permissions = ['ver-clientes', 'crear-clientes', 'editar-clientes', 'eliminar-clientes'];
            
            foreach ($permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if (!$permission) {
                    return "Permission not found: $permissionName";
                }
            }
            return true;
        });

        $this->test("Admin user created with correct role", function() {
            $admin = User::where('email', 'admin@taller.com')->first();
            return $admin !== null && $admin->hasRole('Administrador');
        });

        $this->test("Sample users have correct permissions", function() {
            $mecanico = User::where('email', 'mecanico@taller.com')->first();
            $recepcionista = User::where('email', 'recepcion@taller.com')->first();
            
            return $mecanico !== null && $mecanico->hasRole('Mecánico') &&
                   $recepcionista !== null && $recepcionista->hasRole('Recepcionista');
        });
    }

    // ========== DATA CONSISTENCY TESTING ==========
    public function testDataConsistency()
    {
        echo "\n--- DATA CONSISTENCY TESTING ---\n";

        $this->test("Foreign key constraints enforcement", function() {
            try {
                // Try to create a vehiculo with non-existent cliente_id
                $vehiculo = Vehiculo::create([
                    'cliente_id' => 99999, // Non-existent ID
                    'brand' => 'Test Brand',
                    'model' => 'Test Model',
                    'year' => 2020,
                    'license_plate' => 'FK-TEST',
                    'vin' => 'FK12345678901234567',
                    'color' => 'Red'
                ]);
                
                return "Foreign key constraint not enforced";
            } catch (Exception $e) {
                return true; // Exception expected
            }
        });

        $this->test("Data type validation", function() {
            try {
                // Try to create a vehiculo with invalid year
                $cliente = Cliente::create([
                    'name' => 'FK Test Cliente',
                    'email' => 'fk_test@example.com',
                    'phone' => '123456789',
                    'address' => 'Test Address',
                    'document_number' => 'FK001'
                ]);

                $vehiculo = Vehiculo::create([
                    'cliente_id' => $cliente->id,
                    'brand' => 'Test Brand',
                    'model' => 'Test Model',
                    'year' => 'invalid_year', // Invalid data type
                    'license_plate' => 'TYPE-TEST',
                    'vin' => 'TYPE12345678901234567',
                    'color' => 'Blue'
                ]);

                // Clean up
                $cliente->delete();
                return "Data type validation not working";
                
            } catch (Exception $e) {
                Cliente::where('email', 'fk_test@example.com')->delete();
                return true; // Exception expected
            }
        });

        $this->test("Decimal precision handling", function() {
            $servicio = Servicio::create([
                'name' => 'Precision Test',
                'description' => 'Testing decimal precision',
                'price' => 123.456789, // More than 2 decimal places
                'duration_hours' => 2.556789 // More than 2 decimal places
            ]);

            $servicio->refresh();
            
            // Check if values are properly rounded
            $priceCorrect = number_format($servicio->price, 2) === '123.46';
            $durationCorrect = number_format($servicio->duration_hours, 2) === '2.56';
            
            $servicio->delete();
            
            return $priceCorrect && $durationCorrect;
        });
    }

    public function runAllTests()
    {
        $this->testMigrationsAndSchema();
        $this->testModels();
        $this->testRelationships();
        $this->testDatabaseOperations();
        $this->testSeeders();
        $this->testDataConsistency();

        echo "\n=== TEST SUMMARY ===\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: {$this->failedTests}\n";
        echo "Success Rate: " . round(($this->passedTests / $this->testCount) * 100, 2) . "%\n\n";

        if ($this->failedTests > 0) {
            echo "FAILED TESTS:\n";
            foreach ($this->results as $result) {
                if (strpos($result, '❌') !== false) {
                    echo $result . "\n";
                }
            }
        }

        return [
            'total' => $this->testCount,
            'passed' => $this->passedTests,
            'failed' => $this->failedTests,
            'success_rate' => round(($this->passedTests / $this->testCount) * 100, 2),
            'results' => $this->results
        ];
    }
}

// Run the comprehensive test suite
$tester = new ComprehensiveBackendTest();
$results = $tester->runAllTests();

// Save results to JSON file
file_put_contents('backend_test_results.json', json_encode($results, JSON_PRETTY_PRINT));
echo "\nTest results saved to backend_test_results.json\n";