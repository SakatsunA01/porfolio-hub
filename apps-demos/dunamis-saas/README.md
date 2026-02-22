# Dunamis SaaS - Estructura Separada

El proyecto quedo separado en dos carpetas independientes:

- `frontend/`: Vue 3 + Vite (SPA)
- `backend/`: Laravel (API)

No queda codigo monolitico suelto en la raiz.

## Estado actual
- `backend/` ahora restaura el flujo web completo de Dunamis (Inertia + Vue) para modo demo real.
- `frontend/` mantiene la SPA API-base separada para evolucionar una migracion progresiva.

## Codigo heredado (para migracion)
- Front heredado: `frontend/legacy/`
- Back heredado: `backend/legacy/`

## Arranque rapido

### 1) Backend
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

### 2) Frontend
```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

## Integracion
- Frontend llama a: `VITE_API_URL` (por defecto `http://127.0.0.1:8000/api`)
- Backend permite CORS desde: `FRONTEND_URL` (por defecto `http://127.0.0.1:5173`)

## Credenciales demo (backend web)
- Usuario: `demo@dunamis.local`
- Password: `demo1234`
