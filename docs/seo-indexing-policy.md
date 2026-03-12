# SEO Indexing Policy

## Production domains and indexing

- `portfolio.labarcaministerio.com`: index public pages.
- `shop.labarcaministerio.com`: index public storefront and content pages.
- `delivery.labarcaministerio.com`: index public pages and login pages.
- `dunamis.labarcaministerio.com`: index public pages and login pages.

## Never index

- API domains: `apiecommerce.*`, `apidelivery.*`, `apidunamis.*`.
- Admin-only internal routes where possible.
- Staging or preview hosts.

## Canonical policy

- Every public route must set canonical to its production host.
- Do not canonicalize to localhost or preview URLs.

## Robots and sitemap

- Each public frontend must expose `robots.txt` and `sitemap.xml`.
- `robots.txt` must include the sitemap URL.
- APIs keep `Disallow:` default but are not submitted to search engines.
