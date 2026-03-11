# AGENTS.md

Guía operativa para agentes en `porfolio-hub` (monorepo).

## 1) Mapa rápido del repo

- `portfolio-home/` → landing/workstation (Vue 3 + Vite).
- `apps-demos/ecommerce/commerce-back` → API Laravel (ecommerce).
- `apps-demos/ecommerce/sak-commerce` → frontend ecommerce (Vue 3 + TS + Pinia).
- `apps-demos/dunamis-saas/backend` → API Laravel (Dunamis, `/api/v1/*`).
- `apps-demos/dunamis-saas/frontend` → frontend Dunamis (Vue 3 + Vite).
- `apps-demos/delivery-app/backend` → API Laravel (Delivery).
- `apps-demos/delivery-app/frontend` → frontend Delivery (Vue 3 + TS).
- `scripts/deploy_all_v2.sh` → deploy consolidado en Hostinger.

## 2) Reglas de trabajo para agentes

- No hardcodear precios, totales, impuestos ni estados de pago en frontend.
- Toda validación de checkout/pago debe quedar server-side.
- No mezclar datos entre tenants/organizations (`commerce_id`, `organization_id`).
- No confiar en payload del cliente para stock y total final.
- Cambios de dominio deben quedar documentados en `docs/` y ADR si impactan arquitectura.

## 3) Setup y comandos por app

## 3.1 Portfolio Home

```bash
cd portfolio-home
npm install
npm run dev
npm run build
npm run preview
```

## 3.2 Ecommerce Front (`sak-commerce`)

```bash
cd apps-demos/ecommerce/sak-commerce
npm install
npm run dev
npm run build
npm run type-check
npm run format
```

## 3.3 Ecommerce Back (`commerce-back`)

```bash
cd apps-demos/ecommerce/commerce-back
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
php artisan test
./vendor/bin/pint
```

## 3.4 Dunamis Front

```bash
cd apps-demos/dunamis-saas/frontend
npm install
npm run dev
npm run build
npm run preview
```

## 3.5 Dunamis Back

```bash
cd apps-demos/dunamis-saas/backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan test
./vendor/bin/pint
```

## 3.6 Delivery Front

```bash
cd apps-demos/delivery-app/frontend
npm install
npm run dev
npm run build
npm run type-check
npm run lint
npm run format
```

## 3.7 Delivery Back

```bash
cd apps-demos/delivery-app/backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan test
./vendor/bin/pint
```

## 4) Deploy operativo

Script principal:

```bash
bash scripts/deploy_all_v2.sh
```

Modo sin build en servidor:

```bash
SKIP_BUILD=1 bash scripts/deploy_all_v2.sh
```

Notas:

- Lockfile: `/home/u863655389/deploy_all_v2.lock`
- Smoke tests integrados en script (shop/api/delivery/dunamis).

## 5) Criterios de calidad mínimos antes de merge

- Front: `build` + (si aplica) `type-check`/`lint` sin errores.
- Back: `php artisan test` y migraciones consistentes.
- Endpoints críticos de pago/stock/tenant sin regresión.
- Docs actualizados si cambia arquitectura o reglas de dominio.

## 6) TODO técnico global

- Migración Laravel 10 → Laravel 11 en las 3 APIs.
- Estandarizar Docker real en raíz del monorepo (hoy no hay `docker-compose` propio de proyecto).
