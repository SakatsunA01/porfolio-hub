# Optimization Baseline Matrix

## Scope and objective

This matrix defines the quick-wins baseline for SEO, security, and load performance across the monorepo.

## KPI thresholds

- Lighthouse mobile: Performance >= 80, SEO >= 95, Best Practices >= 95, Accessibility >= 90.
- API latency: p95 <= 500ms on critical endpoints under nominal load.
- Security: 0 open high/critical findings before release.

## App matrix

| App | Public SEO | Security focus | Load focus | Validation |
| --- | --- | --- | --- | --- |
| `portfolio-home` | Canonical, OG/Twitter, JSON-LD, sitemap, robots | CSP report-only + browser security headers via host | Bundle split, static cache headers, image optimization | `npm run build`, Lighthouse |
| `sak-commerce` | Route titles/meta + canonical + sitemap + robots | Session/CORS/Sanctum strict origins, request-id, rate limits | Catalog/checkout critical path latency | `npm run build`, `npm run type-check` |
| `dunamis frontend` | Route titles/meta + canonical + sitemap + robots | Auth route hardening and CSP compatibility | Dashboard/list views response time | `npm run build` |
| `delivery frontend` | Route titles/meta + canonical + sitemap + robots | Role route hardening and CSP compatibility | Order/admin views and API throughput | `npm run build`, `npm run type-check`, `npm run lint` |
| `commerce-back` | Product/share metadata preserved | Security headers, webhook signature validation, error contract, request-id | Checkout + webhook + storefront p95 | `php artisan test` |
| `dunamis backend` | API-only public health endpoint | Security headers, error contract, request-id, rate limits | `/api/v1/*` read/write p95 | `php artisan test` |
| `delivery backend` | API-only public storefront endpoints | Security headers, error contract, request-id, role/tenant hardening | auth + orders + storefront p95 | `php artisan test` |

## Critical endpoint set

- Ecommerce: `GET /api/store/products`, `POST /api/orders/preview`, `POST /api/orders`, `POST /api/webhooks/mercado-pago`.
- Delivery: `POST /api/auth/login`, `GET /api/storefront/{slug}`, `POST /api/orders`.
- Dunamis: `POST /api/v1/auth/login`, `GET /api/v1/dashboard`, `GET /api/v1/products`.

## Release gate

1. All app build/test checks pass.
2. Security headers present in production responses.
3. Smoke tests and latency checks pass.
4. SEO files and canonical links verified for public domains.
