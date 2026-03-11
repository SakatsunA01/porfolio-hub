# ADR-002 — Pagos event-driven con webhook e idempotencia

## Contexto

En ecommerce se requiere confirmar pagos de Mercado Pago sin duplicar side effects (estado/stock).

## Decisión

Adoptar flujo:

1. crear orden `pending`,
2. confirmar pago por webhook,
3. aplicar cambios finales con idempotencia.

Idempotencia mínima: si `payment_status=paid`, no reprocesar.

## Consecuencias

- Evita doble descuento de stock.
- Mejora tolerancia a retries de pasarela.
- Requiere trazabilidad de eventos de webhook.

## Estado

Aceptado.
