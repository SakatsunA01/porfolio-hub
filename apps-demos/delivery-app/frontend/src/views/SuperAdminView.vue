<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Building2, CircleDollarSign, Plus, RefreshCw, Users } from 'lucide-vue-next'
import ProfileMenu from '../components/common/ProfileMenu.vue'

type BillingStatus = 'paid' | 'pending' | 'overdue' | 'trial'
type PlanKey = 'takeaway' | 'full' | 'bi'

interface TenantRow {
  id: number
  name: string
  slug: string
  plan_key: PlanKey
  billing_status: BillingStatus
  monthly_fee_ars: number
  next_billing_at: string | null
  is_active: boolean
  users_count: number
  active_users_count: number
}

interface TenantUser {
  id: number
  name: string
  email: string
  role: string
  role_label: string
  is_active: boolean
}

interface RoleItem {
  id: number
  name: string
  label: string
}

const AUTH_KEY = 'delivery-vue-auth-v2'
const API_BASE = import.meta.env.VITE_BACKEND_API_URL || 'http://127.0.0.1:8010/api'

const loading = ref(false)
const saving = ref(false)
const statusMessage = ref('')
const tenants = ref<TenantRow[]>([])
const roles = ref<RoleItem[]>([])
const selectedTenantId = ref<number | null>(null)
const tenantUsers = ref<TenantUser[]>([])

const createTenantForm = reactive({
  name: '',
  slug: '',
  plan_key: 'full' as PlanKey,
  billing_status: 'paid' as BillingStatus,
  monthly_fee_ars: 160000,
  next_billing_at: '',
  is_active: true,
  owner_name: '',
  owner_email: '',
  owner_password: 'demo1234',
})

const createUserForm = reactive({
  name: '',
  email: '',
  password: 'demo1234',
  role_id: 0,
  is_active: true,
})

const selectedTenant = computed(() => tenants.value.find((item) => item.id === selectedTenantId.value) || null)
const adminsCount = computed(() => tenants.value.length)
const activeBusinesses = computed(() => tenants.value.filter((item) => item.is_active).length)
const mrrEstimate = computed(() => tenants.value.filter((item) => item.billing_status !== 'overdue').reduce((acc, item) => acc + Number(item.monthly_fee_ars || 0), 0))

const parseToken = () => {
  try {
    const raw = localStorage.getItem(AUTH_KEY)
    if (!raw) return ''
    const parsed = JSON.parse(raw) as { token?: string }
    return parsed.token || ''
  } catch {
    return ''
  }
}

const apiRequest = async <T>(path: string, init?: RequestInit): Promise<T> => {
  const token = parseToken()
  const headers = new Headers(init?.headers || {})
  headers.set('Accept', 'application/json')
  headers.set('X-Requested-With', 'XMLHttpRequest')
  if (init?.body && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }
  if (token) headers.set('Authorization', `Bearer ${token}`)

  const response = await fetch(`${API_BASE.replace(/\/$/, '')}${path}`, { ...init, headers })
  if (!response.ok) {
    let message = ''
    try {
      const payload = (await response.json()) as { message?: string }
      message = payload.message || ''
    } catch {
      // noop
    }
    throw new Error(message || `HTTP ${response.status}`)
  }
  return (await response.json()) as T
}

const formatMoney = (value: number) => `$${Number(value || 0).toLocaleString('es-AR')}`
const formatDate = (value: string | null) => (value ? new Date(value).toLocaleDateString('es-AR') : 'Sin fecha')

const billingBadgeClass = (status: BillingStatus) => {
  if (status === 'paid') return 'bg-emerald-100 text-emerald-700'
  if (status === 'pending') return 'bg-amber-100 text-amber-700'
  if (status === 'trial') return 'bg-sky-100 text-sky-700'
  return 'bg-rose-100 text-rose-700'
}

const billingLabel = (status: BillingStatus) => {
  if (status === 'paid') return 'Pagado'
  if (status === 'pending') return 'Pendiente'
  if (status === 'trial') return 'Prueba'
  return 'Vencido'
}

const loadRoles = async () => {
  roles.value = await apiRequest<RoleItem[]>('/roles')
  if (!createUserForm.role_id) {
    const defaultRole = roles.value.find((role) => role.name === 'admin') || roles.value[0]
    createUserForm.role_id = defaultRole?.id || 0
  }
}

const loadTenants = async () => {
  loading.value = true
  try {
    const payload = await apiRequest<TenantRow[]>('/superadmin/tenants')
    tenants.value = payload.map((tenant) => ({
      ...tenant,
      next_billing_at: tenant.next_billing_at ? String(tenant.next_billing_at).slice(0, 10) : null,
    }))
    if (!selectedTenantId.value && tenants.value.length) {
      const firstTenantId = tenants.value[0]?.id || null
      selectedTenantId.value = firstTenantId
      if (firstTenantId) {
        await loadTenantUsers(firstTenantId)
      }
    }
  } catch (error) {
    statusMessage.value = `Error al cargar negocios: ${error instanceof Error ? error.message : 'desconocido'}`
  } finally {
    loading.value = false
  }
}

const loadTenantUsers = async (tenantId: number) => {
  selectedTenantId.value = tenantId
  try {
    tenantUsers.value = await apiRequest<TenantUser[]>(`/superadmin/tenants/${tenantId}/users`)
  } catch (error) {
    statusMessage.value = `No se pudieron cargar usuarios: ${error instanceof Error ? error.message : 'desconocido'}`
  }
}

const createTenant = async () => {
  if (!createTenantForm.name.trim() || !createTenantForm.slug.trim()) {
    statusMessage.value = 'Completa nombre y slug del negocio.'
    return
  }
  saving.value = true
  try {
    await apiRequest('/superadmin/tenants', {
      method: 'POST',
      body: JSON.stringify({
        ...createTenantForm,
        slug: createTenantForm.slug.trim().toLowerCase(),
        next_billing_at: createTenantForm.next_billing_at || null,
        owner_name: createTenantForm.owner_name || null,
        owner_email: createTenantForm.owner_email || null,
        owner_password: createTenantForm.owner_password || null,
      }),
    })
    statusMessage.value = 'Negocio creado correctamente.'
    createTenantForm.name = ''
    createTenantForm.slug = ''
    createTenantForm.owner_name = ''
    createTenantForm.owner_email = ''
    createTenantForm.owner_password = 'demo1234'
    await loadTenants()
  } catch (error) {
    statusMessage.value = `No se pudo crear negocio: ${error instanceof Error ? error.message : 'desconocido'}`
  } finally {
    saving.value = false
  }
}

const saveTenant = async (tenant: TenantRow) => {
  saving.value = true
  try {
    await apiRequest(`/superadmin/tenants/${tenant.id}`, {
      method: 'PUT',
      body: JSON.stringify({
        name: tenant.name,
        slug: tenant.slug,
        plan_key: tenant.plan_key,
        billing_status: tenant.billing_status,
        monthly_fee_ars: Number(tenant.monthly_fee_ars || 0),
        next_billing_at: tenant.next_billing_at || null,
        is_active: tenant.is_active,
      }),
    })
    statusMessage.value = `Negocio ${tenant.name} actualizado.`
    await loadTenants()
    if (selectedTenantId.value === tenant.id) {
      await loadTenantUsers(tenant.id)
    }
  } catch (error) {
    statusMessage.value = `Error actualizando negocio: ${error instanceof Error ? error.message : 'desconocido'}`
  } finally {
    saving.value = false
  }
}

const createTenantUser = async () => {
  if (!selectedTenantId.value) {
    statusMessage.value = 'Selecciona un negocio para crear usuario.'
    return
  }
  if (!createUserForm.name.trim() || !createUserForm.email.trim() || !createUserForm.role_id) {
    statusMessage.value = 'Completa nombre, email y rol.'
    return
  }
  saving.value = true
  try {
    await apiRequest(`/superadmin/tenants/${selectedTenantId.value}/users`, {
      method: 'POST',
      body: JSON.stringify({
        ...createUserForm,
      }),
    })
    statusMessage.value = 'Usuario creado en el negocio seleccionado.'
    createUserForm.name = ''
    createUserForm.email = ''
    createUserForm.password = 'demo1234'
    await loadTenantUsers(selectedTenantId.value)
    await loadTenants()
  } catch (error) {
    statusMessage.value = `No se pudo crear usuario: ${error instanceof Error ? error.message : 'desconocido'}`
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadRoles()
  await loadTenants()
})
</script>

<template>
  <section class="space-y-4 rounded-[24px] bg-[#F8F9FA] p-3 md:p-4">
    <div v-if="statusMessage" class="rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-700">
      {{ statusMessage }}
    </div>

    <header class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Admin General SaaS</h2>
          <p class="text-sm text-slate-500">Negocios, estado de pago y usuarios globales.</p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 active:scale-[0.98]"
          :disabled="loading"
          @click="loadTenants"
        >
          <RefreshCw class="h-4 w-4" />
          Recargar
        </button>
        <ProfileMenu />
      </div>
      <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl bg-slate-50 p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">Negocios</p>
          <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ adminsCount }}</p>
        </article>
        <article class="rounded-2xl bg-slate-50 p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">Negocios activos</p>
          <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ activeBusinesses }}</p>
        </article>
        <article class="rounded-2xl bg-slate-50 p-3">
          <p class="text-xs uppercase tracking-wide text-slate-500">MRR estimado</p>
          <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ formatMoney(mrrEstimate) }}</p>
        </article>
      </div>
    </header>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,0.9fr)]">
      <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-900">Negocios registrados</h3>
          <span class="text-xs text-slate-500">{{ tenants.length }} resultados</span>
        </div>

        <div class="space-y-3">
          <div
            v-for="tenant in tenants"
            :key="tenant.id"
            class="rounded-2xl border border-slate-200 bg-slate-50 p-3"
          >
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
              <div class="grid gap-2 sm:grid-cols-2">
                <label class="text-xs font-semibold text-slate-500">
                  Nombre
                  <input v-model="tenant.name" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700" type="text" />
                </label>
                <label class="text-xs font-semibold text-slate-500">
                  Slug
                  <input v-model="tenant.slug" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700" type="text" />
                </label>
                <label class="text-xs font-semibold text-slate-500">
                  Plan
                  <select v-model="tenant.plan_key" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                    <option value="takeaway">Takeaway</option>
                    <option value="full">Full Operativo</option>
                    <option value="bi">BI & Marketing</option>
                  </select>
                </label>
                <label class="text-xs font-semibold text-slate-500">
                  Estado de pago
                  <select v-model="tenant.billing_status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                    <option value="paid">Pagado</option>
                    <option value="pending">Pendiente</option>
                    <option value="overdue">Vencido</option>
                    <option value="trial">Prueba</option>
                  </select>
                </label>
                <label class="text-xs font-semibold text-slate-500">
                  Fee mensual (ARS)
                  <input v-model.number="tenant.monthly_fee_ars" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700" type="number" min="0" />
                </label>
                <label class="text-xs font-semibold text-slate-500">
                  Proximo cobro
                  <input v-model="tenant.next_billing_at" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700" type="date" />
                </label>
              </div>

              <div class="flex flex-col items-start gap-2 lg:items-end">
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="billingBadgeClass(tenant.billing_status)">
                  {{ billingLabel(tenant.billing_status) }}
                </span>
                <label class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                  <input v-model="tenant.is_active" type="checkbox" class="h-4 w-4" />
                  Activo
                </label>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 active:scale-[0.98]"
                  :disabled="saving"
                  @click="saveTenant(tenant)"
                >
                  <CircleDollarSign class="h-4 w-4" />
                  Guardar
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-100 active:scale-[0.98]"
                  @click="loadTenantUsers(tenant.id)"
                >
                  <Users class="h-4 w-4" />
                  Ver usuarios ({{ tenant.users_count }})
                </button>
              </div>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
              <span class="rounded-full bg-white px-2 py-1 ring-1 ring-slate-200">ID: {{ tenant.id }}</span>
              <span class="rounded-full bg-white px-2 py-1 ring-1 ring-slate-200">Activos: {{ tenant.active_users_count }}</span>
              <span class="rounded-full bg-white px-2 py-1 ring-1 ring-slate-200">Cobro: {{ formatDate(tenant.next_billing_at) }}</span>
            </div>
          </div>
        </div>
      </article>

      <aside class="space-y-4">
        <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          <div class="mb-3 flex items-center gap-2">
            <Building2 class="h-4 w-4 text-emerald-600" />
            <h3 class="text-sm font-semibold text-slate-900">Crear negocio</h3>
          </div>
          <div class="grid gap-2">
            <input v-model="createTenantForm.name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="text" placeholder="Nombre del negocio" />
            <input v-model="createTenantForm.slug" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="text" placeholder="slug-negocio" />
            <div class="grid grid-cols-2 gap-2">
              <select v-model="createTenantForm.plan_key" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                <option value="takeaway">Takeaway</option>
                <option value="full">Full</option>
                <option value="bi">BI</option>
              </select>
              <select v-model="createTenantForm.billing_status" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                <option value="paid">Pagado</option>
                <option value="pending">Pendiente</option>
                <option value="overdue">Vencido</option>
                <option value="trial">Prueba</option>
              </select>
            </div>
            <input v-model.number="createTenantForm.monthly_fee_ars" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="number" min="0" placeholder="Fee mensual ARS" />
            <input v-model="createTenantForm.next_billing_at" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="date" />
            <input v-model="createTenantForm.owner_name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="text" placeholder="Admin inicial (opcional)" />
            <input v-model="createTenantForm.owner_email" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="email" placeholder="Email admin inicial (opcional)" />
            <input v-model="createTenantForm.owner_password" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="text" placeholder="Password admin inicial" />
            <label class="inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
              <input v-model="createTenantForm.is_active" type="checkbox" class="h-4 w-4" />
              Negocio activo al crear
            </label>
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 active:scale-[0.98]"
              :disabled="saving"
              @click="createTenant"
            >
              <Plus class="h-4 w-4" />
              Crear negocio
            </button>
          </div>
        </article>

        <article class="rounded-[24px] bg-white p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          <h3 class="text-sm font-semibold text-slate-900">Usuarios del negocio</h3>
          <p class="mt-1 text-xs text-slate-500">
            {{ selectedTenant ? `${selectedTenant.name} (${selectedTenant.slug})` : 'Selecciona un negocio para ver usuarios' }}
          </p>
          <div class="mt-3 max-h-[240px] space-y-2 overflow-y-auto">
            <div v-for="user in tenantUsers" :key="`tenant-user-${user.id}`" class="rounded-xl bg-slate-50 px-3 py-2">
              <p class="text-sm font-semibold text-slate-900">{{ user.name }}</p>
              <p class="text-xs text-slate-500">{{ user.email }}</p>
              <p class="mt-1 text-[11px] text-slate-600">{{ user.role_label }} · {{ user.is_active ? 'Activo' : 'Inactivo' }}</p>
            </div>
          </div>

          <div class="mt-4 grid gap-2">
            <input v-model="createUserForm.name" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="text" placeholder="Nombre usuario" />
            <input v-model="createUserForm.email" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="email" placeholder="Email usuario" />
            <input v-model="createUserForm.password" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="text" placeholder="Password" />
            <select v-model.number="createUserForm.role_id" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
              <option v-for="role in roles.filter((role) => role.name !== 'superadmin')" :key="`role-option-${role.id}`" :value="role.id">
                {{ role.label }}
              </option>
            </select>
            <label class="inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
              <input v-model="createUserForm.is_active" type="checkbox" class="h-4 w-4" />
              Usuario activo
            </label>
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 active:scale-[0.98]"
              :disabled="saving || !selectedTenantId"
              @click="createTenantUser"
            >
              <Plus class="h-4 w-4" />
              Crear usuario en negocio
            </button>
          </div>
        </article>
      </aside>
    </div>
  </section>
</template>
