# API Base - Authentication System

Una API REST moderna construida con **Laravel 13** que proporciona un sistema completo de autenticación con tokens JWT usando **Laravel Sanctum**.

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.0-FF2D20?style=flat-square&logo=laravel" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/SQLite-003B57?style=flat-square&logo=sqlite" alt="SQLite">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="MIT License">
</p>

## 📋 Tabla de Contenidos

- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Endpoints](#endpoints)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Desarrollo](#desarrollo)
- [Testing](#testing)
- [Stack Tecnológico](#stack-tecnológico)

## ✨ Características

- ✅ **Autenticación Segura**: Implementada con Laravel Sanctum y tokens API
- ✅ **Registro de Usuarios**: Validación robusta y encriptación de contraseñas
- ✅ **Login/Logout**: Gestión completa de sesiones
- ✅ **Refresh Token**: Renovación automática de tokens
- ✅ **API RESTful**: Respuestas JSON consistentes
- ✅ **Validación**: Form Requests con reglas personalizadas
- ✅ **Rate Limiting**: Control de límite de solicitudes
- ✅ **CORS**: Configurado para múltiples orígenes
- ✅ **Database Migrations**: Control de versiones de esquema
- ✅ **Testing**: Suite de tests con Pest PHP

## 🔧 Requisitos

- **PHP**: >= 8.3
- **Composer**: >= 2.0
- **Node.js**: >= 18.0
- **npm**: >= 9.0

## 📦 Instalación

### 1. Clonar el repositorio
```bash
git clone <url-del-repositorio>
cd api-base
```

### 2. Instalación con setup
```bash
composer run setup
```

O manualmente:
```bash
# Instalar dependencias PHP
composer install

# Crear archivo .env
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate --force

# Instalar dependencias Node
npm install

# Compilar assets
npm run build
```

## ⚙️ Configuración

### Variables de Entorno (`.env`)

```env
APP_NAME="API Base"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

APP_KEY=base64:xxxxx...

SANCTUM_EXPIRATION=300
```

### Sanctum

El tiempo de expiración de tokens está configurado en `SANCTUM_EXPIRATION` (en minutos). Por defecto: **24 horas (1440 minutos)**.

### CORS

Los orígenes permitidos se configuran en `config/cors.php`. Por defecto permite todos los orígenes en desarrollo.

## 🔌 Endpoints

### Autenticación Pública

#### Registro
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Respuesta (201)**:
```json
{
  "status": true,
  "message": "Usuario registrado exitosamente.",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "created_at": "2026-03-20T10:30:00Z",
      "updated_at": "2026-03-20T10:30:00Z"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5..."
  }
}
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "juan@example.com",
  "password": "password123"
}
```

**Respuesta (200)**:
```json
{
  "status": true,
  "message": "Login exitoso.",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "created_at": "2026-03-20T10:30:00Z",
      "updated_at": "2026-03-20T10:30:00Z"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5..."
  }
}
```

### Autenticación Protegida

Requieren header: `Authorization: Bearer {token}`

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer token_aqui
```

**Respuesta (200)**:
```json
{
  "status": true,
  "message": "Sesión cerrada correctamente.",
  "data": null
}
```

#### Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer token_aqui
```

**Respuesta (200)**:
```json
{
  "status": true,
  "message": "Token renovado exitosamente.",
  "data": {
    "user": { ... },
    "token": "nuevo_token_aqui"
  }
}
```

## 📂 Estructura del Proyecto

```
api-base/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── LogoutController.php
│   │   │   ├── RegisterController.php
│   │   │   └── RefreshTokenController.php
│   │   ├── Requests/Api/V1/Auth/
│   │   │   ├── LoginRequest.php
│   │   │   └── RegisterRequest.php
│   │   ├── Resources/Api/V1/
│   │   │   └── UserResource.php
│   │   └── Helpers/
│   │       └── ApiResponse.php
│   └── Models/
│       └── User.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cors.php
│   ├── database.php
│   └── sanctum.php
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Feature/Api/
│   └── Unit/
├── .env.example
├── composer.json
├── package.json
└── vite.config.js
```

### Componentes Principales

- **Controllers**: Manejan la lógica de autenticación
- **Requests**: Validan los datos de entrada
- **Resources**: Transforman modelos a JSON
- **ApiResponse Helper**: Respuestas consistentes en toda la API
- **Models**: Modelos Eloquent

## 🚀 Desarrollo

### Iniciar servidor de desarrollo
```bash
composer run dev
```

Este comando ejecuta en paralelo:
- PHP development server
- Queue listener
- Pail logs
- Vite dev server

### Comandos disponibles

```bash
# Servir la aplicación
php artisan serve

# Ejecutar migraciones
php artisan migrate

# Revertir migraciones
php artisan migrate:rollback

# Seed de base de datos
php artisan db:seed

# Tinker (REPL interactivo)
php artisan tinker
```

## 🧪 Testing

### Ejecutar tests
```bash
composer run test
```

### Tests disponibles

- ✅ Tests de autenticación
- ✅ Tests de validación
- ✅ Tests de endpoints

Usa **Pest PHP** para testing. Los tests se encuentran en `tests/Feature/Api/`.

## 📊 Stack Tecnológico

### Backend
- **Laravel** 13.0 - Framework PHP
- **PHP** 8.3 - Lenguaje
- **SQLite** - Base de datos (desarrollo)
- **Laravel Sanctum** - Autenticación API
- **Pest** - Testing

### Frontend Build Tools
- **Vite** - Build tool moderno
- **Tailwind CSS** - Framework CSS
- **Axios** - Cliente HTTP

### Herramientas de Desarrollo
- **Composer** - Gestor de dependencias PHP
- **npm** - Gestor de dependencias JS
- **Pint** - Formateador de código PHP
- **PhpUnit** - Testing framework

## 🔐 Seguridad

- Contraseñas hasheadas con bcrypt
- CSRF protection
- Rate limiting en endpoints
- Tokens con tiempo de expiración
- Validación de entrada en todos los endpoints
- CORS configurado

## 📝 Validación

### Register
- `name`: Requerido, máx 255 caracteres
- `email`: Requerido, email válido, único
- `password`: Requerido, mínimo 8 caracteres, confirmado

### Login
- `email`: Requerido, email válido
- `password`: Requerido

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para reportar bugs o sugerencias, abre un issue en el repositorio.

---

<p align="center">
  Hecho con ❤️ usando Laravel 13
</p>
