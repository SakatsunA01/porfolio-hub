# UI Guidelines (Ceramic Glassmorphism + Forest City + WCAG 2.2 AA)

Guía corta para consistencia visual y operativa en frontends Vue.

## 1. Dirección visual

- Base: superficies claras, calma visual, jerarquía fuerte.
- Glassmorphism **selectivo**:
  - permitido en HUD, sheets, popovers.
  - evitar en tablas o bloques de lectura densa.
- Forest City:
  - fondo ambiental sutil,
  - contenido principal siempre prioriza legibilidad.

## 2. Reglas de componentes

- CTAs: un primario claro por vista.
- Tarjetas/listas: tamaños consistentes, no depender del ratio de imagen.
- Estados vacíos/loading/error:
  - skeletons estables (evitar CLS),
  - mensajes accionables.
- Animaciones: 150–250ms, propósito claro, sin ruido ornamental.

## 3. Tokens y consistencia

- Preferir tokens CSS (background/foreground/card/border/primary).
- Evitar colores fuera del sistema de diseño activo.
- Mantener contraste mínimo AA en texto/acciones.

TODO:

- unificar tokens globales compartidos entre `sak-commerce`, `dunamis-frontend` y `portfolio-home`.

## 4. Accesibilidad (WCAG 2.2 AA)

- Target mínimo 24x24 px en acciones táctiles.
- Focus visible y no tapado por overlays/sticky UI.
- Navegación por teclado en login, checkout y vistas admin.
- Labels y errores inline en formularios.
- Evitar depender de color como único indicador de estado.

## 5. Vue 3 Composition API + TypeScript

- Estado de dominio en stores/composables, no en vistas monolíticas.
- Tipar DTOs de API y errores (401/403/422).
- Evitar side-effects dispersos: centralizar en acciones/composables.
- No duplicar lógica server-side (totales, stock, permisos) en frontend.
