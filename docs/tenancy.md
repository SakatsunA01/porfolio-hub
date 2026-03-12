# Tenancy (aislamiento, onboarding y riesgos)

## 1. Modelo actual por app

## Ecommerce

- Aislamiento principal por `commerce_id` en catálogo y configuración visual.
- Branding de tienda en `commerces` (`logo_path`, `brand_palette`, textos editoriales).

## Dunamis SaaS

- Aislamiento por `organization_id` en entidades operativas (`products`, `clients`, `sales`, etc.).
- API v1 y auth con Sanctum.

## Delivery

- Actualmente orientado a una operación de demo.
- TODO: documentar estrategia de multi-tenant real si se amplía a SaaS multi-comercio.

## 2. Onboarding tenant (target operativo)

1. Crear comercio/organización.
2. Crear usuario admin ligado al tenant.
3. Cargar branding base y settings.
4. Seed mínimo de catálogo/clientes según app.
5. Validar scope en endpoints críticos.

## 3. Riesgos multi-tenant

- Queries sin `commerce_id/organization_id` (fuga de datos).
- Caché compartida sin namespace por tenant.
- Exportaciones/reportes sin filtro de tenant.
- Logs/auditoría sin contexto de tenant.
- Seeds/migraciones que mezclan datos por defecto.

## 4. Controles recomendados

- Scope obligatorio por tenant en repositorios/controladores.
- Tests de aislamiento (tenant A no ve tenant B).
- Convención de cache keys con prefijo de tenant.
- Auditoría con `tenant_id`, `user_id`, `action`.
- Revisión de endpoints admin antes de release.

## 5. Checklist rápido de revisión

- [ ] Endpoint protegido por auth y scope tenant.
- [ ] Query con filtro tenant explícito o global scope validado.
- [ ] Response sin datos de otros tenants.
- [ ] Logs con contexto de tenant.
- [ ] Prueba manual cruzada A/B antes de deploy.

## 6. TODO explícitos

- TODO: estandarizar contrato de tenant context entre apps.
- TODO: definir estrategia de tenant provisioning automatizado.

## 7. Tenant context contract (implemented baseline)

Request precedence:

1. Header `X-Tenant-Slug` over query `tenant_slug`.
2. Header `X-Organization-Id` over query `organization_id`.
3. Header `X-Commerce-Id` over query `commerce_id`.

Normalized request attributes set by middleware:

- `tenant_slug`
- `organization_id`
- `commerce_id`

Notes:

- Middleware is now applied to API groups in ecommerce, dunamis, and delivery backends.
- Existing controller rules remain source of truth for authorization and business scope checks.
