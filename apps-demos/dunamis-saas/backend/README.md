# Backend (Laravel)

## Requisitos
- PHP 8.1+
- Composer

## Instalacion
```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Backend API en: `http://127.0.0.1:8000`

Endpoint de prueba:
- `GET /api/health`

## Nota sobre codigo existente
El codigo del monolito original quedo guardado en `legacy/app` y `legacy/views` para migrarlo a API REST por etapas.