# API Base - Plantilla Base para APIs con Autenticación y Autorización

Plantilla base para la construcción de APIs REST con **Laravel 13**. Incluye autenticación mediante login y sistema de autorización basado en roles y permisos.

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.0-FF2D20?style=flat-square&logo=laravel" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/SQLite-003B57?style=flat-square&logo=sqlite" alt="SQLite">
</p>

## 📋 Tabla de Contenidos

- [Propósito](#propósito)
- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Desarrollo](#desarrollo)
- [Testing](#testing)

## 🎯 Propósito

Proporciona una estructura base y lista para usar en la construcción de nuevas APIs con autenticación segura mediante tokens API (**Laravel Sanctum**) y un sistema flexible de roles y permisos para control de acceso.

## ✨ Características

- ✅ **Autenticación con Tokens**: Laravel Sanctum
- ✅ **Registro y Login**: Validación robusta de usuarios
- ✅ **Sistema de Roles y Permisos**: Control granular de acceso
- ✅ **API RESTful**: Respuestas JSON consistentes
- ✅ **Migraciones**: Control de versiones de esquema
- ✅ **Testing**: Suite de tests con Pest PHP

## 🔧 Requisitos

- **PHP**: >= 8.3
- **Composer**: >= 2.0
- **Node.js**: >= 18.0
- **npm**: >= 9.0

## 📦 Instalación

### 1. Instalación rápida
```bash
composer run setup
```

### 2. Manual
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

##  Desarrollo

### Iniciar servidor de desarrollo
```bash
composer run dev
```

Este comando ejecuta en paralelo:
- PHP development server
- Queue listener
- Pail logs
- Vite dev server

### Comandos útiles

```bash
# Servir la aplicación
php artisan serve

# Ejecutar migraciones
php artisan migrate

# Revertir migraciones
php artisan migrate:rollback

# Tinker (REPL interactivo)
php artisan tinker
```

## 🧪 Testing

### Ejecutar tests
```bash
composer run test
```

Los tests se encuentran en `tests/Feature/Api/` y utilizan **Pest PHP**.
