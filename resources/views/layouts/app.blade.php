<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistema de Taller') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }
        
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8fafc;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.25rem;
            margin: 0.25rem 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .main-content {
            min-height: 100vh;
            padding: 2rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            border: none;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            border: none;
        }
        
        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .table th {
            background: linear-gradient(135deg, var(--dark-color), #334155);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.1);
        }
        
        .badge {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-radius: 12px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-tools me-2"></i>
                        Taller Sistema
                    </h4>
                    
                    <ul class="nav flex-column">
                        @can('ver-dashboard')
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        @endcan
                        
                        @can('ver-clientes')
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('clientes*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">
                                <i class="fas fa-users me-2"></i>
                                Clientes
                            </a>
                        </li>
                        @endcan
                        
                        @can('ver-vehiculos')
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('vehiculos*') ? 'active' : '' }}" href="{{ route('vehiculos.index') }}">
                                <i class="fas fa-car me-2"></i>
                                Vehículos
                            </a>
                        </li>
                        @endcan
                        
                        @can('ver-servicios')
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('servicios*') ? 'active' : '' }}" href="{{ route('servicios.index') }}">
                                <i class="fas fa-cogs me-2"></i>
                                Servicios
                            </a>
                        </li>
                        @endcan
                        
                        @can('ver-empleados')
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('empleados*') ? 'active' : '' }}" href="{{ route('empleados.index') }}">
                                <i class="fas fa-user-tie me-2"></i>
                                Empleados
                            </a>
                        </li>
                        @endcan
                        
                        @can('ver-ordenes')
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('ordenes*') ? 'active' : '' }}" href="{{ route('ordenes.index') }}">
                                <i class="fas fa-clipboard-list me-2"></i>
                                Órdenes de Trabajo
                            </a>
                        </li>
                        @endcan
                        
                        @can('ver-reportes')
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('reportes*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                                <i class="fas fa-chart-bar me-2"></i>
                                Reportes
                            </a>
                        </li>
                        @endcan
                    </ul>
                    
                    <hr class="my-4 text-white-50">
                    
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i>
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="fas fa-user-edit me-2"></i>Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Por favor corrige los siguientes errores:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
        
        // Confirm delete actions
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            let form = $(this).closest('form');
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
        
        // Mobile sidebar toggle
        $('.sidebar-toggle').on('click', function() {
            $('.sidebar').toggleClass('show');
        });
    </script>
    
    @stack('scripts')
</body>
</html>
