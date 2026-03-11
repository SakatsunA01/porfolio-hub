# ADR-001 — Monorepo con separación front/back por producto

## Contexto

El repo contiene múltiples productos con despliegues separados por subdominio y necesidades distintas de evolución.

## Decisión

Mantener estructura monorepo con separación explícita por producto y por capa:

- frontends Vue en carpetas propias,
- backends Laravel en carpetas propias.

## Consecuencias

- Permite evolucionar cada app sin romper las demás.
- Facilita deploy coordinado con script único.
- Exige disciplina documental y contratos API claros entre capas.

## Estado

Aceptado.
