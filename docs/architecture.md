# Arquitectura (arc42 + C4, resumida)

## 1. Objetivo y alcance

Monorepo con 4 productos:

- Portfolio Home (presentación).
- Ecommerce (storefront + admin + pagos MP).
- Dunamis SaaS (operación comercial vía API v1).
- Delivery App (catálogo/combo/orders con panel admin).

Objetivo: mantener separación front/back, reglas de dominio server-side y despliegue coordinado.

## 2. C4 — Nivel Contexto

Actores principales:

- Cliente final (compra ecommerce / hace pedidos delivery).
- Admin negocio (gestiona catálogo, órdenes, métricas).
- Operador SaaS (Dunamis: productos, clientes, ventas, reportes).

Sistemas externos:

- Mercado Pago API (pagos/webhook ecommerce).
- Open-Meteo (telemetría visual en portfolio).

## 3. C4 — Nivel Contenedores

- **Vue SPA / Vite**: `portfolio-home`, `sak-commerce`, `dunamis-saas/frontend`, `delivery-app/frontend`.
- **Laravel API**: `commerce-back`, `dunamis-saas/backend`, `delivery-app/backend`.
- **MySQL**: persistencia por app.
- **Sanctum**: sesión/cookies en front/back separados por subdominio.
- **Deploy**: `scripts/deploy_all_v2.sh` (sync + migraciones + smoke tests).

## 4. C4 — Componentes clave por dominio

### Ecommerce

- `StorefrontCatalogController`: catálogo, categorías, settings, búsqueda/social-proof.
- `StorefrontOrderController`: preview, validate address, create order (pending).
- `MercadoPagoWebhookController`: confirmación pago + idempotencia + disparo de stock.
- `ProductStockService`: disponibilidad/descarga stock por tipo y variante.

### Dunamis SaaS

- API v1 (`/api/v1/*`): `auth`, `dashboard`, `products`, `clients`, `sales`, `reports`, `profile`.
- Modelo org-aware por `organization_id`.

### Delivery

- API de productos/combos/extras/orders + middleware admin.
- Front TS con flujos operativos y roles.

## 5. Runtime crítico (checkout ecommerce)

1. Front solicita `POST /api/orders/preview`.
2. Backend recalcula totales y valida disponibilidad real.
3. Front crea orden `POST /api/orders` (`payment_status=pending`).
4. Mercado Pago notifica webhook.
5. Backend verifica pago con API oficial.
6. Si aprobado: `payment_status=paid`, `order_status=confirmed`, descuenta stock.

## 6. Restricciones y riesgos

- Versionado real actual: Laravel 10 (no 11 todavía).
- No existe Docker estandarizado en raíz del monorepo.
- Riesgo multi-tenant: consultas sin scope por `commerce_id/organization_id`.

## 7. TODO explícitos

- TODO: upgrade de backends a Laravel 11.
- TODO: docker-compose oficial en raíz (db + apps + utilidades).
- TODO: unificar contrato de errores API (401/403/422) entre apps.
