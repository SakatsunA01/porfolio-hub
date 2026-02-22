<script setup lang="ts">
import { BriefcaseBusiness, CookingPot, Truck, UserRound, LayoutPanelLeft } from 'lucide-vue-next'
</script>

<template>
  <section class="forest-card relative overflow-hidden rounded-3xl p-5">
    <span class="forest-glow -right-8 -top-8"></span>
    <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">Seleccion de Perfil</h2>
    <p class="mt-1 text-sm text-slate-500">Elegi el frente que quieras validar dentro de la plataforma.</p>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <RouterLink to="/admin" class="role-card from-slate-100 via-white to-rose-50">
        <BriefcaseBusiness class="h-5 w-5 text-slate-700" />
        <h3>Administrador</h3>
        <p>Configura menu, equipo y asignaciones.</p>
      </RouterLink>

      <RouterLink to="/empleado" class="role-card from-slate-100 via-white to-amber-50">
        <CookingPot class="h-5 w-5 text-slate-700" />
        <h3>Empleado</h3>
        <p>Gestion de cocina y preparacion de pedidos.</p>
      </RouterLink>

      <RouterLink to="/repartidor" class="role-card from-slate-100 via-white to-emerald-50">
        <Truck class="h-5 w-5 text-slate-700" />
        <h3>Repartidor</h3>
        <p>Salida de ruta y confirmacion de entrega.</p>
      </RouterLink>

      <RouterLink to="/cliente" class="role-card from-slate-100 via-white to-cyan-50">
        <UserRound class="h-5 w-5 text-slate-700" />
        <h3>Cliente</h3>
        <p>Pedido rapido y estado en pasos.</p>
      </RouterLink>
    </div>

    <RouterLink to="/operador" class="operator-card mt-3">
      <LayoutPanelLeft class="h-5 w-5 text-emerald-700" />
      <div>
        <h3>Modo Operador Unico</h3>
        <p>Vista unificada de cocina, despacho y administracion para negocios chicos.</p>
      </div>
    </RouterLink>
  </section>
</template>

<style scoped>
.role-card {
  border: 1px solid rgb(226 232 240 / 0.9);
  border-radius: 1rem;
  padding: 0.85rem;
  text-decoration: none;
  color: rgb(15 23 42);
  background: linear-gradient(155deg, var(--tw-gradient-from), var(--tw-gradient-via), var(--tw-gradient-to));
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  transition: all 220ms ease;
}

.role-card h3 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 800;
}

.role-card p {
  margin: 0;
  color: rgb(71 85 105);
  font-size: 0.82rem;
}

.role-card:hover {
  transform: scale(1.05);
  border-color: rgb(251 113 133 / 0.8);
}

.operator-card {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  text-decoration: none;
  border: 1px solid rgb(16 185 129 / 0.25);
  border-radius: 1rem;
  background: linear-gradient(135deg, rgb(236 253 245), rgb(255 255 255));
  padding: 0.85rem;
}

.operator-card h3 {
  margin: 0;
  color: rgb(6 78 59);
  font-weight: 800;
  font-size: 0.95rem;
}

.operator-card p {
  margin: 0;
  color: rgb(15 118 110);
  font-size: 0.82rem;
}
</style>
