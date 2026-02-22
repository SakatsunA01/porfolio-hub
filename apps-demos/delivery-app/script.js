const STORAGE_KEY = 'delivery-demo-v1'

const seedState = {
  role: 'admin',
  products: [
    { id: 1, name: 'Combo Hamburguesa', price: 9500, prepMin: 18, enabled: true },
    { id: 2, name: 'Pizza Muzzarella', price: 12000, prepMin: 24, enabled: true },
    { id: 3, name: 'Empanadas x12', price: 9800, prepMin: 15, enabled: true },
  ],
  employees: [
    { id: 1, name: 'Camila', active: true },
    { id: 2, name: 'Lucas', active: true },
  ],
  drivers: [
    { id: 1, name: 'Mateo', active: true },
    { id: 2, name: 'Sofia', active: true },
  ],
  orders: [
    {
      id: 1001,
      customer: 'Florencia',
      address: 'Av. Corrientes 1324',
      items: [{ productId: 1, qty: 1 }, { productId: 3, qty: 1 }],
      status: 'received',
      employeeId: null,
      driverId: null,
      createdAt: Date.now() - 1000 * 60 * 8,
      etaMin: 34,
    },
  ],
  shiftEmployeeId: 1,
  shiftDriverId: 1,
  clientLookup: '',
}

const deepClone = (obj) => JSON.parse(JSON.stringify(obj))

const state = loadState()

const roleTabs = document.getElementById('roleTabs')
const kpiGrid = document.getElementById('kpiGrid')
const resetDemoBtn = document.getElementById('resetDemoBtn')
const toastHost = document.getElementById('toastHost')

const views = {
  admin: document.getElementById('adminView'),
  employee: document.getElementById('employeeView'),
  driver: document.getElementById('driverView'),
  client: document.getElementById('clientView'),
}

const templates = {
  admin: document.getElementById('adminTemplate'),
  employee: document.getElementById('employeeTemplate'),
  driver: document.getElementById('driverTemplate'),
  client: document.getElementById('clientTemplate'),
}

const statusLabel = {
  received: 'Recibido',
  preparing: 'En preparacion',
  ready: 'Listo para despacho',
  onroute: 'En camino',
  delivered: 'Entregado',
}

const roleViews = ['admin', 'employee', 'driver', 'client']

function loadState() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return deepClone(seedState)
    const parsed = JSON.parse(raw)
    if (!parsed || !Array.isArray(parsed.orders) || !Array.isArray(parsed.products)) {
      return deepClone(seedState)
    }
    return {
      ...deepClone(seedState),
      ...parsed,
    }
  } catch {
    return deepClone(seedState)
  }
}

function persistState() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
}

function toast(message) {
  const node = document.createElement('div')
  node.className = 'toast'
  node.textContent = message
  toastHost.appendChild(node)
  setTimeout(() => {
    node.remove()
  }, 2400)
}

function formatMoney(value) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
  }).format(value)
}

function formatDate(ts) {
  return new Intl.DateTimeFormat('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
  }).format(new Date(ts))
}

function getProduct(id) {
  return state.products.find((p) => p.id === id)
}

function getEmployee(id) {
  return state.employees.find((e) => e.id === id)
}

function getDriver(id) {
  return state.drivers.find((d) => d.id === id)
}

function nextId(collection) {
  return collection.length ? Math.max(...collection.map((x) => x.id)) + 1 : 1
}

function estimateOrderEta(items) {
  const prep = items.reduce((acc, item) => acc + (getProduct(item.productId)?.prepMin || 0), 0)
  const dispatch = 18
  return Math.max(22, Math.round(prep / Math.max(items.length, 1)) + dispatch)
}

function setActiveRoleUI() {
  roleTabs.querySelectorAll('.role-tab').forEach((tab) => {
    tab.classList.toggle('active', tab.dataset.role === state.role)
  })
  roleViews.forEach((role) => {
    views[role].classList.toggle('active', role === state.role)
  })
}

function renderKpis() {
  const activeOrders = state.orders.filter((o) => o.status !== 'delivered').length
  const pendingKitchen = state.orders.filter((o) => ['received', 'preparing'].includes(o.status)).length
  const onRoute = state.orders.filter((o) => o.status === 'onroute').length
  const avgEta =
    state.orders.length > 0
      ? Math.round(state.orders.reduce((acc, o) => acc + o.etaMin, 0) / state.orders.length)
      : 0

  kpiGrid.innerHTML = `
    <article class="kpi-card"><span class="kpi-label">Pedidos activos</span><div class="kpi-value">${activeOrders}</div></article>
    <article class="kpi-card"><span class="kpi-label">En cocina</span><div class="kpi-value">${pendingKitchen}</div></article>
    <article class="kpi-card"><span class="kpi-label">En reparto</span><div class="kpi-value">${onRoute}</div></article>
    <article class="kpi-card"><span class="kpi-label">ETA promedio</span><div class="kpi-value">${avgEta} min</div></article>
  `
}

function renderAdmin() {
  const view = views.admin
  view.innerHTML = ''
  view.appendChild(templates.admin.content.cloneNode(true))

  const productList = view.querySelector('#productList')
  const employeeList = view.querySelector('#employeeList')
  const driverList = view.querySelector('#driverList')
  const adminOrders = view.querySelector('#adminOrders')

  const addProductForm = view.querySelector('#addProductForm')
  addProductForm.addEventListener('submit', (event) => {
    event.preventDefault()
    const form = new FormData(addProductForm)
    const name = String(form.get('name') || '').trim()
    const price = Number(form.get('price'))
    const prep = Number(form.get('prep'))
    if (!name || price <= 0 || prep <= 0) {
      toast('Completa datos validos de producto.')
      return
    }
    state.products.push({ id: nextId(state.products), name, price, prepMin: prep, enabled: true })
    toast('Producto agregado.')
    render()
  })

  const addEmployeeForm = view.querySelector('#addEmployeeForm')
  addEmployeeForm.addEventListener('submit', (event) => {
    event.preventDefault()
    const name = String(new FormData(addEmployeeForm).get('name') || '').trim()
    if (!name) return
    state.employees.push({ id: nextId(state.employees), name, active: true })
    toast('Empleado agregado.')
    render()
  })

  const addDriverForm = view.querySelector('#addDriverForm')
  addDriverForm.addEventListener('submit', (event) => {
    event.preventDefault()
    const name = String(new FormData(addDriverForm).get('name') || '').trim()
    if (!name) return
    state.drivers.push({ id: nextId(state.drivers), name, active: true })
    toast('Repartidor agregado.')
    render()
  })

  productList.innerHTML = `<div class="list">${state.products
    .map(
      (p) => `<div class="list-row">
          <div>
            <strong>${p.name}</strong><br />
            <span class="order-meta">${formatMoney(p.price)} | ${p.prepMin} min</span>
          </div>
          <button class="secondary" data-product="${p.id}">${p.enabled ? 'Activo' : 'Pausado'}</button>
        </div>`,
    )
    .join('')}</div>`

  productList.querySelectorAll('button[data-product]').forEach((button) => {
    button.addEventListener('click', () => {
      const product = state.products.find((p) => p.id === Number(button.dataset.product))
      if (!product) return
      product.enabled = !product.enabled
      toast(`Producto ${product.enabled ? 'activado' : 'pausado'}.`)
      render()
    })
  })

  employeeList.innerHTML = `<div class="list">${state.employees
    .map(
      (e) => `<div class="list-row">
          <span>${e.name}</span>
          <button class="secondary" data-employee="${e.id}">${e.active ? 'En turno' : 'Pausa'}</button>
        </div>`,
    )
    .join('')}</div>`

  employeeList.querySelectorAll('button[data-employee]').forEach((button) => {
    button.addEventListener('click', () => {
      const employee = state.employees.find((e) => e.id === Number(button.dataset.employee))
      if (!employee) return
      employee.active = !employee.active
      render()
    })
  })

  driverList.innerHTML = `<div class="list">${state.drivers
    .map(
      (d) => `<div class="list-row">
          <span>${d.name}</span>
          <button class="secondary" data-driver="${d.id}">${d.active ? 'En turno' : 'Pausa'}</button>
        </div>`,
    )
    .join('')}</div>`

  driverList.querySelectorAll('button[data-driver]').forEach((button) => {
    button.addEventListener('click', () => {
      const driver = state.drivers.find((d) => d.id === Number(button.dataset.driver))
      if (!driver) return
      driver.active = !driver.active
      render()
    })
  })

  adminOrders.innerHTML = state.orders
    .sort((a, b) => b.createdAt - a.createdAt)
    .map((order) => {
      const employeeOptions = state.employees
        .map((e) => `<option value="${e.id}" ${order.employeeId === e.id ? 'selected' : ''}>${e.name}</option>`)
        .join('')
      const driverOptions = state.drivers
        .map((d) => `<option value="${d.id}" ${order.driverId === d.id ? 'selected' : ''}>${d.name}</option>`)
        .join('')
      const items = order.items.map((i) => `${i.qty}x ${getProduct(i.productId)?.name || 'Producto'}`).join(' | ')

      return `<div class="order-card">
        <div class="order-head">
          <strong>#${order.id} | ${order.customer}</strong>
          <span class="badge ${order.status}">${statusLabel[order.status]}</span>
        </div>
        <div class="order-meta">${items}</div>
        <div class="order-meta">${order.address} | ETA ${order.etaMin} min | ${formatDate(order.createdAt)}</div>
        <div class="order-actions">
          <label class="order-meta">Empleado:</label>
          <select data-assign-employee="${order.id}"><option value="">Sin asignar</option>${employeeOptions}</select>
          <label class="order-meta">Repartidor:</label>
          <select data-assign-driver="${order.id}"><option value="">Sin asignar</option>${driverOptions}</select>
        </div>
      </div>`
    })
    .join('')

  adminOrders.querySelectorAll('select[data-assign-employee]').forEach((select) => {
    select.addEventListener('change', () => {
      const order = state.orders.find((o) => o.id === Number(select.dataset.assignEmployee))
      if (!order) return
      order.employeeId = select.value ? Number(select.value) : null
      render()
    })
  })

  adminOrders.querySelectorAll('select[data-assign-driver]').forEach((select) => {
    select.addEventListener('change', () => {
      const order = state.orders.find((o) => o.id === Number(select.dataset.assignDriver))
      if (!order) return
      order.driverId = select.value ? Number(select.value) : null
      render()
    })
  })
}

function renderEmployee() {
  const view = views.employee
  view.innerHTML = ''
  view.appendChild(templates.employee.content.cloneNode(true))

  const shiftSelect = view.querySelector('#employeeShift')
  const employeeOrders = view.querySelector('#employeeOrders')
  const activeEmployees = state.employees.filter((e) => e.active)

  shiftSelect.innerHTML = activeEmployees.length
    ? activeEmployees
        .map((e) => `<option value="${e.id}" ${state.shiftEmployeeId === e.id ? 'selected' : ''}>${e.name}</option>`)
        .join('')
    : '<option value="">Sin empleados en turno</option>'

  shiftSelect.addEventListener('change', () => {
    state.shiftEmployeeId = Number(shiftSelect.value)
    render()
  })

  const candidates = state.orders.filter((o) => ['received', 'preparing'].includes(o.status))
  if (!candidates.length) {
    employeeOrders.innerHTML = '<p class="muted">No hay pedidos pendientes de cocina.</p>'
    return
  }

  employeeOrders.innerHTML = candidates
    .map((order) => {
      const mine = order.employeeId === state.shiftEmployeeId
      const canTake = order.employeeId === null || mine
      const items = order.items.map((i) => `${i.qty}x ${getProduct(i.productId)?.name || 'Producto'}`).join(' | ')

      return `<div class="order-card">
        <div class="order-head">
          <strong>#${order.id} | ${order.customer}</strong>
          <span class="badge ${order.status}">${statusLabel[order.status]}</span>
        </div>
        <div class="order-meta">${items}</div>
        <div class="order-meta">Asignado: ${order.employeeId ? getEmployee(order.employeeId)?.name : 'Sin asignar'}</div>
        <div class="order-actions">
          <button data-start="${order.id}" ${canTake && state.shiftEmployeeId ? '' : 'disabled'}>Tomar / Iniciar</button>
          <button class="secondary" data-ready="${order.id}" ${mine && order.status !== 'ready' ? '' : 'disabled'}>Marcar listo</button>
        </div>
      </div>`
    })
    .join('')

  employeeOrders.querySelectorAll('button[data-start]').forEach((button) => {
    button.addEventListener('click', () => {
      const order = state.orders.find((o) => o.id === Number(button.dataset.start))
      if (!order || !state.shiftEmployeeId) return
      order.employeeId = state.shiftEmployeeId
      order.status = 'preparing'
      toast(`Pedido #${order.id} tomado por cocina.`)
      render()
    })
  })

  employeeOrders.querySelectorAll('button[data-ready]').forEach((button) => {
    button.addEventListener('click', () => {
      const order = state.orders.find((o) => o.id === Number(button.dataset.ready))
      if (!order) return
      order.status = 'ready'
      toast(`Pedido #${order.id} listo para despacho.`)
      render()
    })
  })
}

function renderDriver() {
  const view = views.driver
  view.innerHTML = ''
  view.appendChild(templates.driver.content.cloneNode(true))

  const shiftSelect = view.querySelector('#driverShift')
  const driverOrders = view.querySelector('#driverOrders')
  const takeRouteBtn = view.querySelector('#takeRouteBtn')
  const activeDrivers = state.drivers.filter((d) => d.active)

  shiftSelect.innerHTML = activeDrivers.length
    ? activeDrivers
        .map((d) => `<option value="${d.id}" ${state.shiftDriverId === d.id ? 'selected' : ''}>${d.name}</option>`)
        .join('')
    : '<option value="">Sin repartidores en turno</option>'

  shiftSelect.addEventListener('change', () => {
    state.shiftDriverId = Number(shiftSelect.value)
    render()
  })

  takeRouteBtn.addEventListener('click', () => {
    if (!state.shiftDriverId) return
    const readyOrders = state.orders.filter((o) => o.status === 'ready' && (!o.driverId || o.driverId === state.shiftDriverId))
    readyOrders.forEach((o) => {
      o.driverId = state.shiftDriverId
      o.status = 'onroute'
    })
    if (readyOrders.length) toast(`Ruta cargada con ${readyOrders.length} pedido(s).`)
    render()
  })

  const mine = state.orders.filter((o) => o.driverId === state.shiftDriverId || (o.status === 'ready' && !o.driverId))
  if (!mine.length) {
    driverOrders.innerHTML = '<p class="muted">No hay pedidos asignados a este repartidor.</p>'
    return
  }

  driverOrders.innerHTML = mine
    .map((order) => `<div class="order-card">
        <div class="order-head">
          <strong>#${order.id} | ${order.customer}</strong>
          <span class="badge ${order.status}">${statusLabel[order.status]}</span>
        </div>
        <div class="order-meta">${order.address}</div>
        <div class="order-meta">ETA cliente: ${order.etaMin} min</div>
        <div class="order-actions">
          <button data-route="${order.id}" ${order.status === 'ready' ? '' : 'disabled'}>Salir a entregar</button>
          <button class="secondary" data-done="${order.id}" ${order.status === 'onroute' ? '' : 'disabled'}>Marcar entregado</button>
        </div>
      </div>`)
    .join('')

  driverOrders.querySelectorAll('button[data-route]').forEach((button) => {
    button.addEventListener('click', () => {
      const order = state.orders.find((o) => o.id === Number(button.dataset.route))
      if (!order || !state.shiftDriverId) return
      order.driverId = state.shiftDriverId
      order.status = 'onroute'
      render()
    })
  })

  driverOrders.querySelectorAll('button[data-done]').forEach((button) => {
    button.addEventListener('click', () => {
      const order = state.orders.find((o) => o.id === Number(button.dataset.done))
      if (!order) return
      order.status = 'delivered'
      order.etaMin = 0
      toast(`Pedido #${order.id} entregado.`)
      render()
    })
  })
}

function renderClient() {
  const view = views.client
  view.innerHTML = ''
  view.appendChild(templates.client.content.cloneNode(true))

  const createOrderForm = view.querySelector('#createOrderForm')
  const productPicker = view.querySelector('#clientProductPicker')
  const lookupInput = view.querySelector('#clientLookup')
  const clientOrders = view.querySelector('#clientOrders')
  const activeProducts = state.products.filter((p) => p.enabled)

  productPicker.innerHTML = activeProducts.length
    ? activeProducts
        .map(
          (p) => `<div class="picker-item">
            <div>
              <strong>${p.name}</strong><br />
              <span class="order-meta">${formatMoney(p.price)} | ${p.prepMin} min</span>
            </div>
            <input type="number" min="0" value="0" data-product-qty="${p.id}" />
          </div>`,
        )
        .join('')
    : '<p class="muted">No hay productos disponibles en este momento.</p>'

  createOrderForm.addEventListener('submit', (event) => {
    event.preventDefault()
    const form = new FormData(createOrderForm)
    const customer = String(form.get('customer') || '').trim()
    const address = String(form.get('address') || '').trim()
    const items = []

    productPicker.querySelectorAll('input[data-product-qty]').forEach((input) => {
      const qty = Number(input.value)
      if (qty > 0) items.push({ productId: Number(input.dataset.productQty), qty })
    })

    if (!customer || !address) {
      toast('Completa nombre y direccion.')
      return
    }
    if (!items.length) {
      toast('Selecciona al menos un producto.')
      return
    }

    const etaMin = estimateOrderEta(items)
    state.orders.unshift({
      id: nextId(state.orders),
      customer,
      address,
      items,
      status: 'received',
      employeeId: null,
      driverId: null,
      createdAt: Date.now(),
      etaMin,
    })

    state.clientLookup = customer
    toast('Pedido creado correctamente.')
    render()
  })

  lookupInput.value = state.clientLookup
  lookupInput.addEventListener('input', () => {
    state.clientLookup = lookupInput.value.trim()
    persistState()
    renderClientOrders(clientOrders)
  })

  renderClientOrders(clientOrders)
}

function renderClientOrders(container) {
  const filter = state.clientLookup.toLowerCase()
  const rows = state.orders.filter((o) => (filter ? o.customer.toLowerCase().includes(filter) : true))

  if (!rows.length) {
    container.innerHTML = '<p class="muted">No hay pedidos para ese nombre.</p>'
    return
  }

  container.innerHTML = rows
    .sort((a, b) => b.createdAt - a.createdAt)
    .map((order) => {
      const items = order.items.map((i) => `${i.qty}x ${getProduct(i.productId)?.name || 'Producto'}`).join(' | ')
      const progressText =
        order.status === 'received'
          ? 'Pedido recibido, pronto comienza la preparacion.'
          : order.status === 'preparing'
            ? 'Tu pedido se esta preparando.'
            : order.status === 'ready'
              ? 'Pedido preparado, esperando repartidor.'
              : order.status === 'onroute'
                ? 'Pedido en camino.'
                : 'Pedido entregado.'

      return `<div class="order-card">
        <div class="order-head">
          <strong>#${order.id}</strong>
          <span class="badge ${order.status}">${statusLabel[order.status]}</span>
        </div>
        <div class="order-meta">${items}</div>
        <div class="order-meta">${progressText}</div>
        <div class="order-meta">Tiempo estimado: ${order.etaMin} min</div>
      </div>`
    })
    .join('')
}

function tickEta() {
  let changed = false
  state.orders.forEach((order) => {
    if (['delivered'].includes(order.status)) return
    if (order.etaMin > 0) {
      order.etaMin -= 1
      changed = true
    }
  })
  if (changed) {
    persistState()
    if (state.role === 'client') renderClient()
    renderKpis()
  }
}

function render() {
  setActiveRoleUI()
  renderKpis()
  renderAdmin()
  renderEmployee()
  renderDriver()
  renderClient()
  persistState()
}

roleTabs.addEventListener('click', (event) => {
  const button = event.target.closest('.role-tab')
  if (!button) return
  state.role = button.dataset.role
  render()
})

resetDemoBtn.addEventListener('click', () => {
  const ok = window.confirm('Esto reiniciara productos, equipo y pedidos demo. Continuar?')
  if (!ok) return
  Object.assign(state, deepClone(seedState))
  localStorage.removeItem(STORAGE_KEY)
  toast('Demo reiniciada.')
  render()
})

setInterval(tickEta, 60 * 1000)

render()
