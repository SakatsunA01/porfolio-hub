# Delivery App

Proyecto separado en dos capas:

- `frontend/` -> Vue 3 + TypeScript + Router + Pinia
- `backend/` -> Laravel 10

## Frontend (actualmente funcional en modo demo)

Incluye 4 vistas conectadas por estado:

1. Administrador
2. Empleado (preparacion)
3. Repartidor (despacho)
4. Cliente

### Ejecutar frontend

```powershell
cd apps-demos/delivery-app/frontend
npm install
npm run dev
```

Puerto esperado: `http://127.0.0.1:5176/`

### Acceso por usuario (nuevo)

La app ahora funciona por sesion y rol.

1. Abrir `http://127.0.0.1:5176/login`
2. Ingresar `email` y `password`
3. Seleccionar el perfil en el grid 2x2 (Cliente, Cocina, Admin, Repartidor)
4. Presionar el portal correspondiente
4. Cada usuario solo puede acceder a su modulo

Credenciales demo:

- Administrador: `admin@delivery.local` / `demo1234`
- Empleado: `empleado@delivery.local` / `demo1234`
- Repartidor: `repartidor@delivery.local` / `demo1234`
- Cliente: `cliente@delivery.local` / `demo1234`

### Realtime Firebase (opcional)

Para activar `onSnapshot` en Cliente, Cocina y Repartidor, crea un `.env` en `frontend/` con:

```env
VITE_FIREBASE_API_KEY=
VITE_FIREBASE_AUTH_DOMAIN=
VITE_FIREBASE_PROJECT_ID=
VITE_FIREBASE_STORAGE_BUCKET=
VITE_FIREBASE_MESSAGING_SENDER_ID=
VITE_FIREBASE_APP_ID=
VITE_BACKEND_API_URL=http://127.0.0.1:8010/api
```

Sin estas variables, la app sigue funcionando en modo demo local.

### Lo que ya funciona

- Flujo completo de pedidos entre roles.
- Login con control de acceso por rol (rutas protegidas).
- Asignaciones de empleado y repartidor.
- KPIs operativos.
- Persistencia local (`localStorage`).
- Boton `Reset Demo`.
- Indicador visual de conexion realtime en footer.
- Personalizacion de items (quitar ingredientes y agregar extras).
- Calculo de precio final en backend.
- Soporte de combos con `sub_items`.
- Carga de imagenes en productos/combos via Media Library (upload por archivo).
- Snapshots en `order_items` para historial contable.
- Soft deletes en catalogo (`products`, `ingredients`, `extras`, `combos`).
- Soporte para `bundles` (precio fijo o descuento porcentual).
- Estados de pedido extendidos: `pendiente`, `preparando`, `listo`, `en_camino`, `entregado`, `cancelado`, `rechazado`.
- Centro de Control Operativo para gestion administrativa de incidencias y flujo.
- Snapshot de stock al confirmar pedido (descuento inmediato para evitar sobre-venta).
- Historial de auditoria (cambios de productos, usuarios y pedidos).
- CRM Light de clientes (Top Customer, ultima direccion y bloqueo/desbloqueo).
- Acciones masivas de catalogo (actualizacion de precios por categoria o seleccion).

## Informe funcional (resumen)

### 4) Flujo de estados del pedido

Secuencia principal:

- `pendiente` -> `preparando` -> `listo` -> `en_camino` -> `entregado`

Estados de excepcion:

- `cancelado`
- `rechazado`

### 6) Admin: Centro de Control Operativo

El Admin opera desde **Acciones agrupadas** y usa el **Centro de Control Operativo** para:

- Supervisar pedidos activos.
- Editar pedido (cliente, direccion, estado, pago, asignaciones, ETA).
- Resolver incidencias (cancelar/rechazar cuando corresponde).
- Coordinar cocina y despacho sin depender de una sola vista tecnica.

### 9) Reglas de negocio clave

- El backend valida y calcula datos sensibles (estado, total, pago).
- La escritura de catalogo y gestion interna queda protegida para Admin.
- El cliente consume catalogo y crea pedidos.
- **Snapshot de Stock**: al confirmar pedido se descuenta stock inmediatamente en backend (reserva transaccional) para evitar sobre-venta.

### 10) Valor para portfolio

- Arquitectura por roles con flujo operativo real.
- Personalizacion de productos con precio final en backend.
- Caja y seguimiento operativo.
- **Integridad referencial**:
  - Se usan snapshots en `order_items` para conservar nombre/precio/configuracion historica.
  - Se usan soft deletes en catalogo para no romper ordenes antiguas.
  - Si cambia o se oculta un producto, la historia de ventas sigue consistente para auditoria y analitica.

## Backend Laravel

### Ejecutar backend

```powershell
cd apps-demos/delivery-app/backend
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8010
```

### Endpoints de personalizacion

- `GET /api/products/{id}?extras[]=1&excluded_ingredients[]=2`
- `GET /api/combos/{id}`
- `GET /api/bundles/{id}`
- `POST /api/orders`

Payload de ejemplo (producto):

```json
{
  "customer": "Sergio Quinteros",
  "address": "Buenos Aires Centro",
  "items": [
    {
      "product_id": 10,
      "qty": 1,
      "excluded_ingredients": [3],
      "extras": [7, 8]
    }
  ]
}
```

Payload de ejemplo (combo con sub-items):

```json
{
  "customer": "Sergio Quinteros",
  "address": "Buenos Aires Centro",
  "items": [
    {
      "combo_id": 50,
      "qty": 1,
      "sub_items": [
        {
          "product_id": 10,
          "excluded_ingredients": [3],
          "extras": [7]
        },
        {
          "product_id": 15,
          "excluded_ingredients": [4],
          "extras": []
        }
      ]
    }
  ]
}
```

## Nota

Los archivos `index.html`, `styles.css` y `script.js` en la raiz son un prototipo inicial standalone.
El desarrollo principal sigue en `frontend/` y `backend/`.
