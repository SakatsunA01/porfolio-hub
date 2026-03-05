@php
    $productDraft = [
        'name' => 'Auriculares Pulse ANC',
        'category' => 'Audio',
        'price' => '$189.000',
        'cost' => '$82.400',
        'margin' => '56%',
        'description' => 'Cancelacion adaptativa, bateria de 40 horas y perfil premium para retail moderno.',
    ];
    $categories = [
        ['name' => 'Audio', 'count' => 18, 'icon' => 'Ondas', 'visible' => true],
        ['name' => 'Carga', 'count' => 12, 'icon' => 'Rayo', 'visible' => true],
        ['name' => 'Accesorios', 'count' => 26, 'icon' => 'Grid', 'visible' => true],
        ['name' => 'Ofertas de Verano', 'count' => 8, 'icon' => 'Sol', 'visible' => false],
    ];
    $sales = [
        ['day' => 'Lun', 'amount' => 420000, 'net' => 201000],
        ['day' => 'Mar', 'amount' => 610000, 'net' => 294000],
        ['day' => 'Mie', 'amount' => 560000, 'net' => 270000],
        ['day' => 'Jue', 'amount' => 740000, 'net' => 356000],
        ['day' => 'Vie', 'amount' => 930000, 'net' => 462000],
        ['day' => 'Sab', 'amount' => 1210000, 'net' => 589000],
    ];
    $auditLogs = [
        ['event' => 'Pedido #421 cambiado a Enviado', 'user' => 'Camila · Owner', 'time' => 'Hoy 14:42'],
        ['event' => 'Categoria "Ofertas de Verano" ocultada', 'user' => 'Bruno · Editor', 'time' => 'Hoy 12:18'],
        ['event' => 'Costo de adquisicion actualizado', 'user' => 'Camila · Owner', 'time' => 'Ayer 19:04'],
    ];
    $roles = [
        ['name' => 'Owner', 'access' => 'Metricas, margenes, auditoria y pagos', 'tone' => 'bg-emerald-50 text-emerald-700'],
        ['name' => 'Editor de contenido', 'access' => 'Productos, media, categorias y preview', 'tone' => 'bg-sky-50 text-sky-700'],
        ['name' => 'Operaciones', 'access' => 'Pedidos, estados y filtros de venta', 'tone' => 'bg-amber-50 text-amber-700'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Admin SaaS</p>
                <h2 class="text-2xl font-semibold tracking-[-0.04em] text-[#171717]">
                    Centro de negocio multi-tenant
                </h2>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-[#171717]/10 bg-white px-3 py-2 text-xs font-semibold text-[#61574d]">
                Tenant activo: {{ Auth::user()->currentTeam->name ?? 'Orbix Store' }}
            </div>
        </div>
    </x-slot>

    <div class="bg-[#f5f1ea] py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 lg:grid-cols-4">
                <article class="rounded-[32px] border border-white/70 bg-white/85 p-5 shadow-[0_18px_50px_rgba(23,23,23,0.05)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Ingresos netos</p>
                    <p class="mt-3 text-3xl font-semibold tracking-[-0.05em] text-[#171717]">$2.172.000</p>
                    <p class="mt-2 text-sm text-emerald-700">+14.8% vs. semana anterior</p>
                </article>
                <article class="rounded-[32px] border border-white/70 bg-white/85 p-5 shadow-[0_18px_50px_rgba(23,23,23,0.05)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Ticket promedio</p>
                    <p class="mt-3 text-3xl font-semibold tracking-[-0.05em] text-[#171717]">$94.300</p>
                    <p class="mt-2 text-sm text-[#61574d]">Carritos con 2.8 productos promedio</p>
                </article>
                <article class="rounded-[32px] border border-white/70 bg-white/85 p-5 shadow-[0_18px_50px_rgba(23,23,23,0.05)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Conversion</p>
                    <p class="mt-3 text-3xl font-semibold tracking-[-0.05em] text-[#171717]">3.4%</p>
                    <p class="mt-2 text-sm text-[#61574d]">Checkout express con 68% de completitud</p>
                </article>
                <article class="rounded-[32px] border border-[#171717]/10 bg-[#171717] p-5 text-white shadow-[0_20px_50px_rgba(23,23,23,0.12)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-white/55">RBAC</p>
                    <p class="mt-3 text-2xl font-semibold tracking-[-0.04em]">Datos aislados por tenant</p>
                    <p class="mt-2 text-sm leading-7 text-white/70">Solo el owner accede a metricas, margenes y auditoria financiera.</p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(340px,0.8fr)]">
                <article class="rounded-[32px] border border-white/70 bg-white/88 p-6 shadow-[0_18px_55px_rgba(23,23,23,0.05)]">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Gestion de productos</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#171717]">Editor de valor con preview en vivo</h3>
                        </div>
                        <div class="rounded-full border border-[#171717]/10 bg-[#f8f4ee] px-4 py-2 text-xs font-semibold text-[#61574d]">
                            Drag & drop media
                        </div>
                    </div>

                    <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
                        <div class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="checkout-field is-valid">
                                    <span>Nombre del producto</span>
                                    <input type="text" class="checkout-input" value="{{ $productDraft['name'] }}">
                                    <span class="checkout-check">&#10003;</span>
                                </label>
                                <label class="checkout-field is-valid">
                                    <span>Categoria</span>
                                    <input type="text" class="checkout-input" value="{{ $productDraft['category'] }}">
                                    <span class="checkout-check">&#10003;</span>
                                </label>
                                <label class="checkout-field is-valid">
                                    <span>Precio de venta</span>
                                    <input type="text" class="checkout-input" value="{{ $productDraft['price'] }}">
                                    <span class="checkout-check">&#10003;</span>
                                </label>
                                <label class="checkout-field is-valid">
                                    <span>Costo de adquisicion</span>
                                    <input type="text" class="checkout-input" value="{{ $productDraft['cost'] }}">
                                    <span class="checkout-check">&#10003;</span>
                                </label>
                            </div>

                            <label class="checkout-field is-valid">
                                <span>Descripcion</span>
                                <textarea class="checkout-input min-h-[110px]">{{ $productDraft['description'] }}</textarea>
                                <span class="checkout-check">&#10003;</span>
                            </label>

                            <div class="rounded-[28px] border border-dashed border-[#171717]/12 bg-[#f8f4ee] p-5">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#171717]">Media drag & drop</p>
                                        <p class="mt-1 text-xs text-[#7a7066]">Subida limpia con miniaturas optimizadas y reordenamiento rapido.</p>
                                    </div>
                                    <button type="button" class="checkout-pill is-active">Subir imagenes</button>
                                </div>
                                <div class="mt-4 grid grid-cols-3 gap-3">
                                    <div class="rounded-[24px] bg-white p-2 shadow-[0_10px_22px_rgba(23,23,23,0.04)]"><img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80" alt="" class="h-24 w-full rounded-[18px] object-cover"></div>
                                    <div class="rounded-[24px] bg-white p-2 shadow-[0_10px_22px_rgba(23,23,23,0.04)]"><img src="https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=600&q=80" alt="" class="h-24 w-full rounded-[18px] object-cover"></div>
                                    <div class="grid place-items-center rounded-[24px] border border-dashed border-[#171717]/12 bg-white text-xs font-semibold uppercase tracking-[0.2em] text-[#8a8179]">+</div>
                                </div>
                            </div>
                        </div>

                        <aside class="rounded-[32px] border border-[#171717]/10 bg-[#171717] p-5 text-white shadow-[0_20px_50px_rgba(23,23,23,0.12)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-white/55">Preview storefront</p>
                            <div class="mt-4 rounded-[28px] bg-white p-4 text-[#171717]">
                                <div class="overflow-hidden rounded-[24px] bg-[#ece6de]">
                                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80" alt="" class="h-52 w-full object-cover">
                                </div>
                                <div class="mt-4">
                                    <span class="rounded-full bg-[#f8f4ee] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-[#61574d]">{{ $productDraft['category'] }}</span>
                                    <h4 class="mt-4 text-2xl font-semibold tracking-[-0.03em]">{{ $productDraft['name'] }}</h4>
                                    <p class="mt-2 text-sm leading-7 text-[#61574d]">{{ $productDraft['description'] }}</p>
                                    <div class="mt-5 flex items-center justify-between">
                                        <p class="text-2xl font-semibold">{{ $productDraft['price'] }}</p>
                                        <button type="button" class="rounded-full bg-[#171717] px-5 py-3 text-sm font-semibold text-white">Agregar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 rounded-[24px] bg-white/8 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/55">Margen bruto estimado</p>
                                <p class="mt-2 text-2xl font-semibold">{{ $productDraft['margin'] }}</p>
                                <p class="mt-1 text-sm text-white/70">El costo interno no se expone al cliente y alimenta net revenue.</p>
                            </div>
                        </aside>
                    </div>
                </article>

                <article class="rounded-[32px] border border-white/70 bg-white/88 p-6 shadow-[0_18px_55px_rgba(23,23,23,0.05)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Categorias</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#171717]">Arquitectura flexible del catalogo</h3>
                    <div class="mt-5 space-y-3">
                        @foreach ($categories as $category)
                            <div class="rounded-[24px] border border-[#171717]/8 bg-[#f8f4ee] p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-11 w-11 place-items-center rounded-[18px] bg-white text-xs font-semibold uppercase tracking-[0.18em] text-[#61574d]">{{ $category['icon'] }}</div>
                                        <div>
                                            <p class="text-sm font-semibold text-[#171717]">{{ $category['name'] }}</p>
                                            <p class="mt-1 text-xs text-[#7a7066]">{{ $category['count'] }} productos</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full px-3 py-1 text-[11px] font-semibold {{ $category['visible'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                            {{ $category['visible'] ? 'Visible' : 'Oculta' }}
                                        </span>
                                        <button type="button" class="checkout-pill">{{ $category['visible'] ? 'Mover' : 'Activar' }}</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(360px,0.9fr)]">
                <article class="rounded-[32px] border border-white/70 bg-white/88 p-6 shadow-[0_18px_55px_rgba(23,23,23,0.05)]">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Monitor de ventas</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#171717]">Salud del negocio y trazabilidad operativa</h3>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="checkout-pill is-active">Ultimos 7 dias</button>
                            <button type="button" class="checkout-pill">Mercado Pago</button>
                            <button type="button" class="checkout-pill">Pendientes</button>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                        <div class="rounded-[28px] border border-[#171717]/8 bg-[#f8f4ee] p-5">
                            <div class="flex h-64 items-end gap-3">
                                @foreach ($sales as $entry)
                                    @php($height = max(18, round(($entry['amount'] / 1210000) * 100)))
                                    <div class="flex-1 text-center">
                                        <div class="mx-auto flex h-56 items-end justify-center rounded-[24px] bg-white px-2 py-3">
                                            <div class="w-full rounded-[18px] bg-[#171717]" style="height: {{ $height }}%;"></div>
                                        </div>
                                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#7a7066]">{{ $entry['day'] }}</p>
                                        <p class="mt-1 text-sm font-semibold text-[#171717]">${{ number_format($entry['net'], 0, ',', '.') }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-4 text-sm text-[#61574d]">Ingresos netos descontando costo de adquisicion. Ideal para detectar categoria rentable y no solo volumen bruto.</p>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-[28px] border border-[#171717]/8 bg-[#f8f4ee] p-5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#8a8179]">Filtros inteligentes</p>
                                <div class="mt-4 space-y-3">
                                    <button type="button" class="checkout-pill is-active w-full justify-center">Hoy</button>
                                    <button type="button" class="checkout-pill w-full justify-center">Tarjeta</button>
                                    <button type="button" class="checkout-pill w-full justify-center">Enviados</button>
                                </div>
                            </div>
                            <div class="rounded-[28px] border border-[#171717]/8 bg-[#171717] p-5 text-white">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/55">Auditoria</p>
                                <div class="mt-4 space-y-3">
                                    @foreach ($auditLogs as $log)
                                        <div class="rounded-[22px] bg-white/8 p-4">
                                            <p class="text-sm font-semibold">{{ $log['event'] }}</p>
                                            <p class="mt-1 text-xs text-white/62">{{ $log['user'] }} · {{ $log['time'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[32px] border border-white/70 bg-white/88 p-6 shadow-[0_18px_55px_rgba(23,23,23,0.05)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Matriz RBAC</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#171717]">Permisos por rol y tenant</h3>
                    <div class="mt-5 space-y-4">
                        @foreach ($roles as $role)
                            <div class="rounded-[24px] border border-[#171717]/8 bg-[#f8f4ee] p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#171717]">{{ $role['name'] }}</p>
                                        <p class="mt-1 text-xs text-[#7a7066]">{{ $role['access'] }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-semibold {{ $role['tone'] }}">
                                        Aislado por tenant
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 rounded-[28px] border border-dashed border-[#171717]/12 bg-white p-5">
                        <p class="text-sm font-semibold text-[#171717]">Criterio enterprise</p>
                        <p class="mt-2 text-sm leading-7 text-[#61574d]">El editor crea productos y mueve categorias. Operaciones cambia estados y filtra pedidos. Solo el owner ve metricas de dinero, margenes y logs sensibles.</p>
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
