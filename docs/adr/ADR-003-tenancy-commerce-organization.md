# ADR-003 — Tenancy por `commerce_id` / `organization_id`

## Contexto

El monorepo maneja dos estrategias de aislamiento ya presentes en código:

- ecommerce: `commerce_id`,
- dunamis: `organization_id`.

## Decisión

Mantener estrategia actual por producto y reforzar controles de scope en endpoints y consultas.

## Consecuencias

- Permite continuidad sin migración inmediata de esquema.
- Incrementa riesgo de inconsistencia semántica entre apps.
- Requiere checklist de aislamiento en cambios de dominio.

## Estado

Aceptado con TODO de estandarización futura.
