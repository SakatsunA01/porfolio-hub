# Pagos (seguridad, idempotencia, validación server-side)

Foco actual: ecommerce + Mercado Pago.

## 1. Principios obligatorios

- Backend recalcula totales y valida disponibilidad.
- No confiar en precio/total enviado por cliente.
- Orden previa al pago en estado `pending`.
- Webhook idempotente: no reprocesar órdenes ya pagadas.
- Descuento de stock solo tras confirmación aprobada.

## 2. Contratos actuales (ecommerce)

- `POST /api/orders/preview` → validación previa + totales recalculados.
- `POST /api/orders` → crea orden pending.
- `POST /api/webhooks/mercado-pago` → procesa confirmación.

Referencia de implementación:

- `StorefrontOrderController`
- `MercadoPagoWebhookController`
- `ProductStockService`

## 3. Flujo operativo

1. Cliente arma carrito y pide `preview`.
2. Backend valida items, variantes y stock real.
3. Se crea orden pending.
4. MP notifica pago.
5. Backend consulta API oficial de MP para verificar estado.
6. Si aprobado: actualiza orden y descuenta stock cuando corresponde.

## 4. Checklist de seguridad

- [ ] Revalidar payload en cada endpoint de pago.
- [ ] Registrar auditoría de cambios de estado de orden/pago.
- [ ] Manejar retries sin duplicar efectos.
- [ ] Alertar cuando webhook llegue sin orden asociada.
- [ ] No exponer secretos/tokens en frontend.

## 5. Observabilidad mínima

- Log por evento de webhook: `payment_id`, estado, resultado.
- Métrica de pagos aprobados/fallidos/pendientes.
- Trazabilidad de excepción de stock en confirmación.

## 6. TODO explícitos

- TODO: formalizar verificación de firma/origen de webhook MP.
- TODO: definir retry/backoff estandarizado en fallas transitorias de pasarela.
- TODO: homologar política de pagos para Dunamis/Delivery si se integra pasarela.
