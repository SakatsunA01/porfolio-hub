# Delivery App Backend (Laravel 10)

Backend API para la demo de Delivery App.

## Setup

```powershell
cd apps-demos/delivery-app/backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8010
```

## Credenciales seed

- Admin: `admin@delivery.local` / `demo1234`
- Client: `cliente@delivery.local` / `demo1234`

## Arquitectura de datos

- `products`: catalogo base (`image_url`, `is_active`, `base_price`)
- `ingredients`: ingredientes base reutilizables
- `extras`: extras por producto con costo adicional
- `combos`: packs con imagen propia (`image_url`, `is_active`)
- `ingredient_product`: pivot de ingredientes por producto
- `combo_product`: pivot de items por combo

## Endpoints API

Lectura publica:

- `GET /api/health`
- `GET /api/products`
- `GET /api/products/{id}?extras[]=1&extras[]=2`
- `GET /api/combos`
- `GET /api/combos/{id}`
- `GET /api/ingredients`
- `GET /api/extras?product_id={id}`
- `POST /api/orders` (checkout cliente)

Escritura protegida (requiere `auth:sanctum` + rol `admin`):

- `POST|PUT|DELETE /api/products`
- `POST|PUT|DELETE /api/combos`
- `POST|PUT|DELETE /api/ingredients`
- `POST|PUT|DELETE /api/extras`
- `GET /api/orders`

## Reglas de negocio

- Precio final de producto:
  - `final_price = base_price + sum(selected_extras.additional_price)`
- El endpoint `GET /api/products/{id}` responde:
  - producto + ingredientes + extras activos
  - desglose de precio (`base_price`, `extras_total`, `final_price`)
  - extras seleccionados por query param

## Seguridad

Middleware custom: `admin`

- Permite escritura solo si `request()->user()->role === 'admin'`
- Perfil `client` solo puede usar endpoints de lectura (GET)
