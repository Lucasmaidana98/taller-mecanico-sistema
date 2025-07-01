# 🚗 Sistema de Taller - Workshop Management System

Un sistema completo de gestión para talleres automotrices desarrollado en Laravel 12 con Bootstrap 5, que permite administrar clientes, vehículos, servicios, empleados, órdenes de trabajo y generar reportes en PDF.

## 🚀 Características Principales

### 📋 Módulos del Sistema
- **Dashboard** - Panel principal con estadísticas y métricas en tiempo real
- **Clientes** - Gestión completa de clientes con historial de servicios
- **Vehículos** - Registro y seguimiento de vehículos por cliente
- **Servicios** - Catálogo de servicios con precios y duración
- **Empleados** - Administración de personal del taller
- **Órdenes de Trabajo** - Gestión completa del flujo de trabajo
- **Reportes** - Generación de reportes con filtros y exportación a PDF

### 🔐 Sistema de Roles y Permisos
- **Administrador** - Acceso completo a todos los módulos
- **Mecánico** - Acceso a órdenes de trabajo y consulta de información
- **Recepcionista** - Gestión de clientes, vehículos y órdenes

### 🎨 Interfaz de Usuario
- **Diseño Moderno** - Interfaz profesional con Bootstrap 5
- **Responsive Design** - Optimizado para dispositivos móviles y escritorio
- **Tema Elegante** - Colores corporativos azul y gradientes profesionales
- **UX Intuitiva** - Navegación clara con iconos Font Awesome

### 🛡️ Seguridad y Validación
- **Autenticación Laravel Breeze** - Sistema de login seguro
- **Validación Centralizada** - Request classes para todas las operaciones
- **Protección CSRF** - Tokens de seguridad en todos los formularios
- **Control de Acceso** - Middleware de permisos en todas las rutas

## 🛠️ Tecnologías Utilizadas

### Backend
- **Laravel 12** - Framework PHP moderno
- **MySQL/SQLite** - Base de datos relacional
- **Spatie Laravel Permission** - Sistema de roles y permisos
- **Laravel Breeze** - Sistema de autenticación

### Frontend
- **Laravel Blade** - Motor de plantillas
- **Bootstrap 5** - Framework CSS
- **Font Awesome 6** - Iconografía
- **jQuery** - Interactividad JavaScript
- **SweetAlert2** - Alertas elegantes
- **DataTables** - Tablas avanzadas

### Reportes y PDF
- **DomPDF** - Generación de PDF
- **Chart.js** - Gráficos y estadísticas

## 📦 Instalación

### Requisitos del Sistema
- PHP 8.2 o superior
- Composer
- Node.js y NPM
- MySQL o SQLite
- Git

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/tu-usuario/taller-sistema.git
cd taller-sistema
```

2. **Instalar dependencias de PHP**
```bash
composer install
```

3. **Instalar dependencias de Node.js**
```bash
npm install
npm run build
```

4. **Configurar el entorno**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configurar la base de datos**
Editar el archivo `.env` con la configuración de tu base de datos:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taller_sistema
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

6. **Ejecutar migraciones y seeders**
```bash
php artisan migrate
php artisan db:seed
```

7. **Configurar permisos**
```bash
php artisan storage:link
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

8. **Iniciar el servidor**
```bash
php artisan serve
```

El sistema estará disponible en `http://localhost:8000`

## 👥 Usuarios por Defecto

El sistema viene con usuarios de ejemplo ya configurados:

| Rol | Email | Contraseña | Permisos |
|-----|--------|------------|----------|
| Administrador | admin@taller.com | admin123 | Acceso completo |
| Mecánico | mecanico@taller.com | mecanico123 | Órdenes y consultas |
| Recepcionista | recepcion@taller.com | recepcion123 | Clientes y órdenes |

## 📊 Estructura de la Base de Datos

### Tablas Principales
- `users` - Usuarios del sistema
- `roles` / `permissions` - Sistema de roles y permisos
- `clientes` - Información de clientes
- `vehiculos` - Vehículos de los clientes
- `servicios` - Catálogo de servicios
- `empleados` - Personal del taller
- `orden_trabajos` - Órdenes de trabajo
- `reportes` - Historial de reportes generados

### Relaciones
- Cliente → Vehículos (1:N)
- Cliente → Órdenes de Trabajo (1:N)
- Vehículo → Órdenes de Trabajo (1:N)
- Empleado → Órdenes de Trabajo (1:N)
- Servicio → Órdenes de Trabajo (1:N)
- Usuario → Reportes (1:N)

## 🔧 Funcionalidades Principales

### 📈 Dashboard
- Estadísticas en tiempo real
- Órdenes pendientes, en proceso y completadas
- Ingresos del mes actual
- Gráficos de estado de órdenes
- Acciones rápidas

### 👤 Gestión de Clientes
- CRUD completo de clientes
- Búsqueda y filtros avanzados
- Historial de vehículos y servicios
- Estados activo/inactivo
- Validación de datos únicos

### 🚗 Gestión de Vehículos
- Registro detallado de vehículos
- Vinculación con propietarios
- Historial de servicios
- Búsqueda por marca, modelo, placa
- Validación de VIN y placas únicas

### 🔧 Catálogo de Servicios
- Servicios con precios y duración
- Estadísticas de popularidad
- Control de servicios activos/inactivos
- Historial de uso

### 👷 Gestión de Empleados
- Información completa del personal
- Cargos y salarios
- Estadísticas de rendimiento
- Control de empleados activos

### 📋 Órdenes de Trabajo
- Flujo completo de trabajo
- Estados: Pendiente, En Proceso, Completado, Cancelado
- Asignación de empleados
- Cálculo automático de costos
- Seguimiento de fechas

### 📊 Sistema de Reportes
- Reportes por módulo (clientes, vehículos, servicios, empleados, órdenes, ingresos)
- Filtros avanzados por fechas, estados, etc.
- Exportación a PDF profesional
- Estadísticas y gráficos
- Historial de reportes generados

## 🎨 Diseño y UX

### Paleta de Colores
- **Primario**: #2563eb (Azul corporativo)
- **Secundario**: #64748b (Gris)
- **Éxito**: #10b981 (Verde)
- **Peligro**: #ef4444 (Rojo)
- **Advertencia**: #f59e0b (Ámbar)
- **Información**: #06b6d4 (Cian)

### Componentes UI
- Cards con sombras y efectos hover
- Botones con gradientes y animaciones
- Formularios con validación en tiempo real
- Tablas responsivas con DataTables
- Alertas con SweetAlert2
- Sidebar navegable con iconos

## 🔄 Flujo de Trabajo

1. **Recepción del Cliente**
   - Registro/búsqueda de cliente
   - Registro del vehículo (si es nuevo)
   - Creación de orden de trabajo

2. **Asignación y Trabajo**
   - Asignación a empleado
   - Actualización de estado a "En Proceso"
   - Realización del servicio

3. **Finalización**
   - Actualización a "Completado"
   - Registro de fecha de finalización
   - Facturación y entrega

4. **Reportes y Análisis**
   - Generación de reportes periódicos
   - Análisis de rendimiento
   - Estadísticas financieras

## 📁 Estructura del Proyecto

```
taller-sistema/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Controladores CRUD
│   │   └── Requests/            # Validaciones centralizadas
│   └── Models/                  # Modelos Eloquent
├── database/
│   ├── migrations/              # Migraciones de BD
│   └── seeders/                # Datos de ejemplo
├── resources/
│   ├── views/
│   │   ├── layouts/            # Layout principal
│   │   ├── clientes/           # Vistas de clientes
│   │   ├── vehiculos/          # Vistas de vehículos
│   │   ├── servicios/          # Vistas de servicios
│   │   ├── empleados/          # Vistas de empleados
│   │   ├── ordenes/            # Vistas de órdenes
│   │   └── reportes/           # Vistas de reportes
│   └── css/js/                 # Assets compilados
└── routes/
    └── web.php                 # Rutas del sistema
```

## 🧪 Testing

Para ejecutar las pruebas del sistema:

```bash
# Pruebas unitarias
php artisan test

# Pruebas con cobertura
php artisan test --coverage

# Pruebas específicas
php artisan test --filter ClienteTest
```

## 🚀 Despliegue en Producción

### Preparación
```bash
# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
npm run build
```

### Variables de Entorno
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
```

### Servidor Web
- Configurar virtual host apuntando a `/public`
- Configurar SSL/TLS
- Optimizar base de datos
- Configurar backup automático

## 🤝 Contribuir

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Si necesitas ayuda o tienes preguntas:
- 📧 Email: soporte@tallersistema.com
- 🐛 Issues: [GitHub Issues](https://github.com/tu-usuario/taller-sistema/issues)
- 📖 Documentación: [Wiki del proyecto](https://github.com/tu-usuario/taller-sistema/wiki)

## 🔄 Changelog

### v1.0.0 (2025-07-01)
- 🎉 Lanzamiento inicial
- ✅ Todos los módulos CRUD implementados
- ✅ Sistema de roles y permisos
- ✅ Reportes con exportación PDF
- ✅ Dashboard con estadísticas
- ✅ Interfaz moderna y responsive
- ✅ Validación centralizada
- ✅ Datos de ejemplo incluidos

---

**Desarrollado con ❤️ para la comunidad de talleres automotrices**

## 🙏 Agradecimientos

- Laravel Team por el increíble framework
- Spatie por el excelente paquete de permisos
- Bootstrap Team por el framework CSS
- Font Awesome por los iconos
- Todos los contribuidores de librerías open source utilizadas
