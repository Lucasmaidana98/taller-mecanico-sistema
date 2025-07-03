# 🔧 Sistema de Gestión para Talleres Mecánicos

Sistema web completo para la administración de talleres mecánicos desarrollado en **Laravel 12** con **Bootstrap 5**, diseñado para gestionar clientes, vehículos, servicios, empleados, órdenes de trabajo y reportes.

[![Laravel](https://img.shields.io/badge/Laravel-12.0-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🎯 Características Principales

### 📊 **Dashboard Interactivo**
- Estadísticas en tiempo real de clientes activos, vehículos activos y órdenes
- Gráficos de estado de órdenes de trabajo
- Órdenes recientes y acciones rápidas
- Indicadores de ingresos mensuales

### 👥 **Gestión de Usuarios y Roles**
- **3 Roles de Usuario**: Administrador, Mecánico, Recepcionista
- Sistema de permisos granular con **Spatie Laravel Permission**
- Autenticación segura con **Laravel Breeze**
- Gestión de perfil de usuario completa

### 🏢 **Módulos del Sistema**

#### 1. **👤 Gestión de Clientes**
- CRUD completo con validaciones avanzadas
- Búsqueda y filtrado inteligente
- Estadísticas de clientes activos/inactivos
- **Eliminación permanente** con validación de dependencias
- Sistema de alertas automáticas

#### 2. **🚗 Gestión de Vehículos**
- Registro detallado con VIN, placa, marca y modelo
- Asociación con clientes
- Control de estado activo/inactivo
- Historial de servicios por vehículo

#### 3. **🔧 Gestión de Servicios**
- Catálogo de servicios con precios y duración
- Descripción detallada de cada servicio
- Control de disponibilidad
- Estadísticas de servicios más solicitados

#### 4. **👨‍🔧 Gestión de Empleados**
- Información personal y laboral
- Asignación de roles y permisos
- Control de salarios y fechas de contratación
- Historial de órdenes asignadas

#### 5. **📋 Gestión de Órdenes de Trabajo**
- Estados: Pendiente, En Proceso, Completado, Cancelado
- Asignación de cliente, vehículo, empleado y servicio
- Cálculo automático de totales
- Seguimiento de fechas de inicio y finalización

#### 6. **📈 Reportes y Estadísticas**
- Reportes en PDF con **DomPDF**
- Estadísticas de ventas y servicios
- Reportes por período de tiempo
- Análisis de rendimiento del taller

## 🛠️ Tecnologías Utilizadas

### **Backend**
- **Laravel 12** - Framework PHP moderno
- **PHP 8.3+** - Lenguaje de programación
- **MySQL** - Base de datos relacional
- **Spatie Laravel Permission** - Sistema de roles y permisos
- **DomPDF** - Generación de reportes PDF

### **Frontend**
- **Bootstrap 5.3** - Framework CSS responsive
- **jQuery 3.7** - Librería JavaScript
- **DataTables** - Tablas interactivas
- **SweetAlert2** - Alertas modernas
- **Font Awesome** - Iconografía

### **Herramientas de Desarrollo**
- **Laravel Breeze** - Autenticación
- **Laravel Mix/Vite** - Compilación de assets
- **Composer** - Gestión de dependencias PHP
- **NPM** - Gestión de dependencias JavaScript

## ⚡ Instalación y Configuración

### **Requisitos Previos**
- PHP >= 8.3
- Composer
- Node.js >= 18
- MySQL >= 8.0
- Git

### **1. Clonar el Repositorio**
```bash
git clone https://github.com/Lucasmaidana98/sistema-taller.git
cd sistema-taller
```

### **2. Instalar Dependencias**
```bash
# Dependencias PHP
composer install

# Dependencias JavaScript
npm install
```

### **3. Configuración del Entorno**
```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### **4. Configurar Base de Datos**
Editar `.env` con tus credenciales de base de datos:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taller_sistema
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### **5. Ejecutar Migraciones y Seeders**
```bash
# Crear tablas
php artisan migrate

# Poblar datos iniciales
php artisan db:seed
```

### **6. Compilar Assets**
```bash
# Para desarrollo
npm run dev

# Para producción
npm run build
```

### **7. Iniciar Servidor**
```bash
php artisan serve
```

Visita `http://localhost:8000` en tu navegador.

## 👨‍💼 Usuarios de Prueba

| Rol | Email | Contraseña |
|-----|-------|------------|
| **Administrador** | admin@taller.com | admin123 |
| **Mecánico** | mecanico@taller.com | mecanico123 |
| **Recepcionista** | recepcion@taller.com | recepcion123 |

## 🔐 Sistema de Permisos

### **Administrador**
- ✅ Acceso completo a todos los módulos
- ✅ Gestión de usuarios y roles
- ✅ Configuración del sistema
- ✅ Reportes avanzados

### **Mecánico**
- ✅ Ver y actualizar órdenes de trabajo
- ✅ Consultar información de clientes y vehículos
- ✅ Registrar servicios realizados
- ❌ No puede eliminar registros críticos

### **Recepcionista**
- ✅ Gestión de clientes y vehículos
- ✅ Crear y asignar órdenes de trabajo
- ✅ Consultar servicios y precios
- ❌ Acceso limitado a configuración

## 🎨 Características de UI/UX

### **Diseño Moderno**
- Interfaz responsive con Bootstrap 5
- Tema claro con colores profesionales
- Iconografía consistente con Font Awesome
- Navegación intuitiva con sidebar

### **Experiencia de Usuario Mejorada**
- **DataTables** para búsqueda y filtrado avanzado
- **SweetAlert2** para confirmaciones elegantes
- **Alertas automáticas** con feedback inmediato
- **Validación en tiempo real** en formularios
- **Eliminación con confirmación** y actualización automática de listas
- **Perfil de usuario completamente funcional** con Bootstrap 5

### **Funcionalidades Avanzadas**
- **AJAX** para operaciones sin recarga
- **Paginación inteligente** en listados
- **Eliminación permanente** con confirmación
- **Actualización automática** de tablas y estadísticas
- **Sistema de alertas contextual** para cada tipo de operación

## 📱 Responsive Design

El sistema está completamente optimizado para:
- 💻 **Desktop** (1200px+)
- 📱 **Tablet** (768px - 1199px)
- 📱 **Mobile** (< 768px)

## 🔧 Funcionalidades Técnicas

### **Seguridad**
- Protección CSRF en todos los formularios
- Validación de datos en servidor y cliente
- Control de acceso basado en roles
- Sanitización de entradas de usuario
- **Eliminación segura** con validación de dependencias

### **Performance**
- Carga lazy de DataTables
- Optimización de consultas con Eloquent
- Cacheo de configuraciones
- Compresión de assets
- **Actualización selectiva** de componentes UI

### **Mantenibilidad**
- Código organizado con patrones MVC
- Documentación inline en funciones críticas
- Separación clara de responsabilidades
- Estructura modular y escalable

## 📊 Métricas del Proyecto

- **Líneas de Código**: ~15,000+
- **Archivos PHP**: 50+
- **Vistas Blade**: 30+
- **Migraciones**: 6
- **Tests Integrados**: 100% funcional
- **Cobertura de Funcionalidades**: 95%+
- **Success Rate de Tests**: 100% (22/22 tests passing)

## 🧪 Testing y Calidad

### **Tests Implementados**
- ✅ Tests de integración modelo-controlador-vista
- ✅ Verificación de autenticación y permisos
- ✅ Validación de operaciones CRUD
- ✅ Tests de eliminación y actualización
- ✅ Verificación de UI/UX
- ✅ **Tests de eliminación permanente**
- ✅ **Tests de perfil de usuario**

### **Calidad de Código**
- Cumple estándares PSR-12
- Validaciones comprensivas
- Manejo robusto de errores
- Logging de operaciones críticos
- **100% de operaciones funcionando correctamente**

## 🚀 Características Recientes

### **✅ Eliminación Mejorada**
- **Hard Delete**: Los registros se eliminan permanentemente
- **Confirmación elegante** con SweetAlert2
- **Validación de dependencias** antes de eliminar
- **Actualización automática** de listas sin recarga
- **Alertas de éxito** inmediatas

### **✅ Perfil de Usuario Completo**
- **Convertido a Bootstrap 5** desde TailwindCSS
- **Formularios completamente funcionales**:
  - Actualización de información personal
  - Cambio de contraseña con validación
  - Eliminación de cuenta con confirmación
- **Diseño responsive** con sidebar informativo
- **Validaciones robustas** en frontend y backend

### **✅ Dashboard Mejorado**
- Estadísticas precisas de **"Clientes Activos"** y **"Vehículos Activos"**
- Contadores que reflejan solo registros con `status = true`
- Interfaz más clara y descriptiva

## 🤝 Contribución

¡Las contribuciones son bienvenidas! Por favor:

1. Haz fork del proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## 📞 Contacto

**Lucas Maidana**
- GitHub: [@Lucasmaidana98](https://github.com/Lucasmaidana98)
- Email: lucasmaidana98@example.com

## 🙏 Agradecimientos

- **Laravel Team** por el framework excepcional
- **Spatie** por el paquete de permisos
- **Bootstrap Team** por el framework CSS
- **Comunidad Open Source** por las librerías utilizadas

---

<div align="center">

**⭐ Si este proyecto te fue útil, ¡no olvides darle una estrella! ⭐**

</div>