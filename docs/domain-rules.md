# Reglas de dominio (estado actual)

Documento operativo de reglas que ya están en código.

## 1. Ecommerce

## 1.1 Tipos de producto y stock

- `preorder`:
  - se permite compra aun con stock 0,
  - requiere `preorder_shipping_date`,
  - no descuenta stock al confirmar pago.
- `permanent` / `limited`:
  - si tiene variantes: stock por `product_variants.stock`,
  - si no tiene variantes: `products.stock_global`.
- Nunca se permite stock negativo (`ProductStockService`).

## 1.2 Checkout y órdenes

- El backend recalcula subtotal/envío/total.
- No se usa total enviado por frontend como fuente de verdad.
- La orden nace en `pending` (`payment_status`, `order_status`).
- Confirmación de pago ocurre por webhook y recién ahí se cambia estado.

## 2. Dunamis SaaS

- Entidades operativas usan `organization_id`.
- Auth API en `/api/v1/auth/*` con Sanctum.
- Módulos activos: dashboard, products, clients, sales, reports, profile.

TODO:

- Formalizar invariantes de precio/costo/margen por organización.
- Definir política única de borrado lógico/físico para entidades de negocio.

## 3. Delivery

- Catálogo compuesto por `products`, `combos`, `ingredients`, `extras`.
- Acciones de escritura restringidas por rol admin.
- Precio final considera extras seleccionados (regla server-side en backend).

TODO:

- Estandarizar reglas de estado de orden y trazabilidad entre front/back.

## 4. Antipatrones prohibidos

- Hardcodear reglas de stock o pago en frontend.
- Permitir cambio de estado de pago sin verificación server-side.
- Operar queries multi-tenant sin `commerce_id` o `organization_id`.
- Exponer datos internos de pasarela (IDs técnicos) al cliente final sin necesidad.
