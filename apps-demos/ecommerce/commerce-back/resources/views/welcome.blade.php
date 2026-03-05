@php
    $tenant = [
        'accent' => '#2f8f65',
        'accentSoft' => '#dff3e8',
        'brandName' => 'Orbix Store',
        'paymentMethods' => ['Mercado Pago', 'Stripe', 'Tarjeta'],
    ];
    $featured = ['id' => 'orbit-qi2', 'name' => 'Base Orbit Qi2', 'price' => 129900, 'priceLabel' => '$129.900', 'variant' => 'Stone Gray', 'image' => 'https://images.unsplash.com/photo-1516724562728-afc824a36e84?auto=format&fit=crop&w=1200&q=80'];
    $colors = [
        ['label' => 'Stone Gray', 'hex' => '#B8BBB4', 'image' => 'https://images.unsplash.com/photo-1516724562728-afc824a36e84?auto=format&fit=crop&w=1200&q=80'],
        ['label' => 'Midnight', 'hex' => '#2A303A', 'image' => 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&w=1200&q=80'],
        ['label' => 'Soft Sand', 'hex' => '#E6D5BC', 'image' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=1200&q=80'],
    ];
    $audio = [
        ['id' => 'pulse-anc', 'name' => 'Auriculares Pulse ANC', 'price' => 189000, 'priceLabel' => '$189.000', 'variant' => 'Color: Negro', 'tag' => 'Nuevo', 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&q=80', 'summary' => 'Cancelacion adaptativa y bateria de 40 horas.'],
        ['id' => 'halo-mini', 'name' => 'Speaker Halo Mini', 'price' => 94500, 'priceLabel' => '$94.500', 'variant' => 'Color: Arena', 'tag' => 'Top seller', 'image' => 'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=1200&q=80', 'summary' => '360 grados de sonido con cuerpo compacto y waterproof.'],
        ['id' => 'arc-one', 'name' => 'Soundbar Arc One', 'price' => 244000, 'priceLabel' => '$244.000', 'variant' => 'Color: Grafito', 'tag' => 'Home', 'image' => 'https://images.unsplash.com/photo-1583225157631-f3b6b4b0d79b?auto=format&fit=crop&w=1200&q=80', 'summary' => 'Perfil ultrafino, Dolby Atmos y calibracion por ambiente.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orbix Storefront</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f5f1ea] text-[#171717] antialiased">
    <header id="store-navbar" class="sticky top-0 z-40 px-4 pt-4 lg:px-8">
        <div class="navbar-shell mx-auto flex max-w-7xl items-center justify-between gap-4 rounded-[32px] border border-white/70 bg-[#f8f9fa]/85 px-4 py-3 shadow-[0_18px_40px_rgba(23,23,23,0.08)] backdrop-blur-[10px] transition-all duration-300 md:px-6">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-[24px] bg-[#171717] text-sm font-extrabold uppercase tracking-[0.28em] text-[#f5f1ea]">OB</div>
                <div><p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-[#7f7468]">Orbix curated store</p><p class="text-lg font-semibold">Orbix Store</p></div>
            </div>
            <nav class="hidden items-center gap-8 text-sm font-medium text-[#61574d] md:flex">
                <a href="#audio" class="transition hover:text-[#171717]">Audio</a>
                <a href="#carga" class="transition hover:text-[#171717]">Carga</a>
                <a href="#accesorios" class="transition hover:text-[#171717]">Accesorios</a>
            </nav>
            <div class="flex items-center gap-2">
                <button type="button" id="cart-open-button" class="relative inline-flex h-12 w-12 items-center justify-center rounded-full border border-[#171717]/10 bg-white/80 text-[#171717] transition hover:border-[#171717]/20 hover:bg-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386a1.5 1.5 0 011.455 1.136L5.67 6.75m0 0h13.185a1.5 1.5 0 011.46 1.845l-1.106 4.423a1.5 1.5 0 01-1.456 1.155H8.239a1.5 1.5 0 01-1.456-1.155L5.67 6.75zm0 0L4.5 16.5m4.5 3a.75.75 0 100-1.5.75.75 0 000 1.5zm9 0a.75.75 0 100-1.5.75.75 0 000 1.5z"/></svg>
                    <span id="cart-count-badge" class="hidden absolute -right-1 -top-1 min-w-[1.4rem] rounded-full bg-[#2f8f65] px-1.5 py-1 text-center text-[10px] font-semibold leading-none text-white">0</span>
                </button>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="hidden rounded-full border border-[#171717]/10 px-4 py-2 text-sm font-semibold sm:inline-flex">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full border border-[#171717]/10 px-4 py-2 text-sm font-semibold">Ingresar</a>
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-5 pb-24 pt-6 lg:px-8">
        <section class="grid gap-8 pb-16 lg:grid-cols-[minmax(0,1fr)_34rem]">
            <div class="space-y-7">
                <div class="inline-flex items-center gap-3 rounded-full border border-white/60 bg-white/65 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-[#7b7065] shadow-[0_10px_30px_rgba(23,23,23,0.06)] backdrop-blur-md"><span class="h-2 w-2 rounded-full bg-[#171717]"></span>Geometria tactica para un setup premium</div>
                <div class="space-y-4">
                    <h1 class="max-w-3xl text-5xl font-semibold leading-[0.95] tracking-[-0.05em] md:text-7xl">Navbar lucido y carrito contextual sin sacar al usuario de la compra.</h1>
                    <p class="max-w-2xl text-base leading-7 text-[#5f574f] md:text-lg">La experiencia mezcla transparencia, radios grandes y acciones siempre visibles. El side sheet mantiene el contexto de compra y el navbar actua como ancla sin volverse pesado.</p>
                </div>
                <nav class="inline-flex items-center gap-2 rounded-full border border-[#171717]/10 bg-white/70 px-4 py-2 text-xs font-medium text-[#6f655c] backdrop-blur-sm" aria-label="Breadcrumb"><a href="#">Home</a><span>/</span><a href="#audio">Audio</a><span>/</span><span class="text-[#171717]">{{ $featured['name'] }}</span></nav>
                <article id="carga" class="rounded-[32px] border border-white/70 bg-white/75 p-6 shadow-[0_24px_70px_rgba(23,23,23,0.08)] backdrop-blur-xl">
                    <div class="flex flex-wrap items-center gap-3"><span class="rounded-full bg-[#171717] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white">Carga</span><span class="text-sm text-[#6d645d]">4.9 · 214 resenas</span></div>
                    <h2 class="mt-5 text-3xl font-semibold tracking-[-0.04em] md:text-4xl">{{ $featured['name'] }}</h2>
                    <p class="mt-3 max-w-xl text-sm leading-7 text-[#60584f] md:text-base">Cargador inalambrico magnetico con cuerpo aluminio, dock doble y lectura de temperatura en tiempo real.</p>
                    <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
                        <div><p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Precio de lanzamiento</p><p id="product-price-anchor" class="mt-2 text-4xl font-semibold tracking-[-0.05em]">{{ $featured['priceLabel'] }}</p></div>
                        <div class="text-sm text-[#6f655d]">Incluye cable textil, adaptador y garantia de 12 meses.</div>
                    </div>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <button type="button" id="primary-add-to-cart" class="add-cart-trigger inline-flex min-h-[58px] items-center justify-center rounded-full bg-[#171717] px-7 text-sm font-semibold text-white shadow-[0_18px_40px_rgba(23,23,23,0.24)] transition hover:-translate-y-0.5" data-id="{{ $featured['id'] }}" data-name="{{ $featured['name'] }}" data-price="{{ $featured['price'] }}" data-image="{{ $featured['image'] }}" data-variant="{{ $featured['variant'] }}">Agregar al carrito</button>
                        <button type="button" class="inline-flex min-h-[58px] items-center justify-center rounded-full border border-[#171717]/10 bg-white px-6 text-sm font-semibold">Comprar ahora</button>
                    </div>
                </article>
            </div>
            <aside class="lg:sticky lg:top-28">
                <article class="overflow-hidden rounded-[36px] border border-white/70 bg-white/70 p-4 shadow-[0_30px_80px_rgba(23,23,23,0.08)] backdrop-blur-xl">
                    <div class="relative overflow-hidden rounded-[32px] bg-[#ebe3d9]">
                        <img id="featured-image" src="{{ $colors[0]['image'] }}" alt="{{ $featured['name'] }}" class="featured-image h-[24rem] w-full object-cover md:h-[34rem]">
                        <div class="pointer-events-none absolute inset-x-4 bottom-4 rounded-[28px] border border-white/55 bg-white/24 p-4 text-white backdrop-blur-xl"><p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-white/70">Setup note</p><p class="mt-1 text-sm font-medium">Pensado para escritorio o punto de carga premium en retail.</p></div>
                    </div>
                    <div class="mt-5 flex items-start justify-between gap-4">
                        <div><p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Acabado seleccionado</p><p id="selected-color-label" class="mt-2 text-lg font-semibold">{{ $colors[0]['label'] }}</p></div>
                        <div class="flex items-center gap-3">@foreach ($colors as $index => $color)<button type="button" class="color-swatch {{ $index === 0 ? 'is-active' : '' }}" data-color-label="{{ $color['label'] }}" data-color-image="{{ $color['image'] }}" style="--swatch-color: {{ $color['hex'] }}"></button>@endforeach</div>
                    </div>
                </article>
            </aside>
        </section>

        <section id="audio" class="space-y-6 pb-16">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div><p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[#8a8179]">Catalogo destacado</p><h2 class="mt-2 text-3xl font-semibold tracking-[-0.04em] md:text-4xl">Audio con micro-interacciones y decision rapida.</h2></div>
                <nav class="inline-flex items-center gap-2 rounded-full border border-[#171717]/10 bg-white/70 px-4 py-2 text-xs font-medium text-[#6f655c] backdrop-blur-sm" aria-label="Breadcrumb"><a href="#">Home</a><span>/</span><span class="text-[#171717]">Audio</span></nav>
            </div>
            <div class="grid gap-5 lg:grid-cols-3">
                <article id="accesorios" class="flex h-full flex-col justify-between rounded-[32px] border border-[#171717]/8 bg-[#171717] p-7 text-white shadow-[0_28px_60px_rgba(23,23,23,0.16)] lg:col-span-1">
                    <div><p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-white/60">Manifiesto de marca</p><h3 class="mt-4 text-3xl font-semibold tracking-[-0.04em]">Menos ruido visual. Mas decision de compra.</h3><p class="mt-4 max-w-md text-sm leading-7 text-white/72">Integramos el manifiesto dentro del modulo de catalogo para reforzar valor y promesa de marca sin romper el flujo de compra.</p></div>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2"><div class="rounded-[24px] border border-white/10 bg-white/8 p-4 backdrop-blur-md"><p class="text-2xl font-semibold">48 hs</p><p class="mt-1 text-xs uppercase tracking-[0.22em] text-white/60">Despacho premium</p></div><div class="rounded-[24px] border border-white/10 bg-white/8 p-4 backdrop-blur-md"><p class="text-2xl font-semibold">4.9/5</p><p class="mt-1 text-xs uppercase tracking-[0.22em] text-white/60">Satisfaccion media</p></div></div>
                </article>
                <div class="grid gap-5 sm:grid-cols-2 lg:col-span-2">
                    @foreach ($audio as $product)
                        <article class="product-card group relative overflow-hidden rounded-[32px] border border-white/60 bg-white/72 p-4 shadow-[0_20px_55px_rgba(23,23,23,0.08)] backdrop-blur-sm">
                            <div class="overflow-hidden rounded-[26px] bg-[#ece6de]"><img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="h-64 w-full object-cover transition duration-500 group-hover:scale-[1.03]"></div>
                            <div class="mt-4 flex items-center justify-between gap-3"><span class="rounded-full bg-[#f0ebe4] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-[#6f655c]">{{ $product['tag'] }}</span><span class="text-xs font-medium text-[#82786d]">Audio</span></div>
                            <div class="mt-4"><h3 class="text-2xl font-semibold tracking-[-0.03em]">{{ $product['name'] }}</h3><p class="mt-2 text-sm leading-7 text-[#60584f]">{{ $product['summary'] }}</p></div>
                            <div class="mt-5 flex items-center justify-between"><p class="text-2xl font-semibold tracking-[-0.04em]">{{ $product['priceLabel'] }}</p><button type="button" class="add-cart-trigger rounded-full border border-[#171717]/10 px-4 py-2 text-sm font-semibold" data-id="{{ $product['id'] }}" data-name="{{ $product['name'] }}" data-price="{{ $product['price'] }}" data-image="{{ $product['image'] }}" data-variant="{{ $product['variant'] }}">Agregar</button></div>
                            <div class="card-frost pointer-events-none absolute inset-x-4 bottom-4 rounded-[28px] border border-white/30 bg-white/18 p-4 opacity-0 shadow-[0_20px_50px_rgba(23,23,23,0.16)] backdrop-blur-xl transition duration-300 group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:opacity-100"><div class="flex items-center justify-between gap-3"><div><p class="text-sm font-semibold text-white">{{ $product['name'] }}</p><p class="mt-1 text-xs text-white/70">{{ $product['summary'] }}</p></div><button type="button" class="quick-view-trigger inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#171717]" data-name="{{ $product['name'] }}" data-price="{{ $product['priceLabel'] }}" data-summary="{{ $product['summary'] }}" data-image="{{ $product['image'] }}">Vista rapida</button></div></div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <div id="mobile-cart-bar" class="pointer-events-none fixed inset-x-4 bottom-4 z-30 translate-y-6 opacity-0 transition duration-300 md:hidden">
        <div class="pointer-events-auto flex items-center justify-between gap-4 rounded-[28px] border border-white/70 bg-white/88 px-4 py-3 shadow-[0_24px_60px_rgba(23,23,23,0.16)] backdrop-blur-xl">
            <div><p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#8a8179]">{{ $featured['name'] }}</p><p class="mt-1 text-lg font-semibold">{{ $featured['priceLabel'] }}</p></div>
            <button type="button" class="add-cart-trigger inline-flex min-h-[52px] items-center justify-center rounded-full bg-[#171717] px-5 text-sm font-semibold text-white shadow-[0_16px_34px_rgba(23,23,23,0.2)]" data-id="{{ $featured['id'] }}" data-name="{{ $featured['name'] }}" data-price="{{ $featured['price'] }}" data-image="{{ $featured['image'] }}" data-variant="{{ $featured['variant'] }}">Agregar al carrito</button>
        </div>
    </div>

    <div id="cart-overlay" class="pointer-events-none fixed inset-0 z-40 bg-black/30 opacity-0 transition duration-300"></div>
    <aside id="cart-sheet" class="pointer-events-none fixed right-0 top-0 z-50 flex h-full w-full max-w-xl translate-x-full flex-col bg-[#f8f9fa]/96 shadow-[-24px_0_60px_rgba(23,23,23,0.18)] backdrop-blur-xl transition duration-300">
        <div class="flex items-center justify-between gap-4 px-4 pb-4 pt-5 md:px-6"><div><p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Carrito contextual</p><h2 id="cart-title" class="mt-2 text-2xl font-semibold tracking-[-0.04em]">Tu Carrito (0 productos)</h2></div><button type="button" id="cart-close-button" class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-[#171717]/10 bg-white text-xl">×</button></div>
        <div class="flex-1 overflow-y-auto px-4 pb-5 md:px-6"><div id="cart-items" class="space-y-4"></div><div id="cart-empty" class="rounded-[28px] border border-dashed border-[#171717]/12 bg-white/70 px-6 py-10 text-center text-sm text-[#61574d]">Todavia no agregaste productos. Usa el catalogo y el side sheet se actualiza sin sacar al usuario de contexto.</div></div>
        <footer class="rounded-t-[32px] border-t border-white/60 bg-white/88 px-4 py-5 shadow-[0_-20px_45px_rgba(23,23,23,0.08)] backdrop-blur-xl md:px-6"><div class="mb-4 flex items-center justify-between text-sm text-[#61574d]"><span>Subtotal</span><span id="cart-subtotal" class="text-lg font-semibold text-[#171717]">$0</span></div><button type="button" id="checkout-open-button" class="inline-flex min-h-[58px] w-full items-center justify-center rounded-full px-6 text-sm font-semibold text-white shadow-[0_20px_42px_rgba(47,143,101,0.28)]" style="background: {{ $tenant['accent'] }};">Ir al Checkout</button></footer>
    </aside>

    <div id="quick-view-modal" class="pointer-events-none fixed inset-0 z-[60] hidden items-end bg-[#171717]/45 px-4 pb-4 pt-10 opacity-0 backdrop-blur-md transition md:items-center md:justify-center">
        <div class="w-full max-w-3xl rounded-[32px] border border-white/45 bg-[#fbf8f3] p-4 shadow-[0_35px_90px_rgba(23,23,23,0.24)] md:p-6">
            <div class="grid gap-5 md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <div class="overflow-hidden rounded-[28px] bg-[#e9e1d6]"><img id="quick-view-image" src="" alt="" class="h-72 w-full object-cover md:h-full"></div>
                <div class="flex flex-col justify-between gap-5"><div><p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Compra rapida</p><h3 id="quick-view-name" class="mt-3 text-3xl font-semibold tracking-[-0.04em]"></h3><p id="quick-view-summary" class="mt-4 text-sm leading-7 text-[#60584f]"></p></div><div class="space-y-4"><p id="quick-view-price" class="text-3xl font-semibold tracking-[-0.04em]"></p><div class="flex flex-wrap gap-3"><button type="button" class="rounded-full bg-[#171717] px-6 py-3 text-sm font-semibold text-white">Comprar ahora</button><button type="button" id="quick-view-close" class="rounded-full border border-[#171717]/12 bg-white px-6 py-3 text-sm font-semibold">Cerrar</button></div></div></div>
            </div>
        </div>
    </div>

    <div id="checkout-modal" class="pointer-events-none fixed inset-0 z-[70] hidden bg-[#171717]/30 opacity-0 backdrop-blur-md transition">
        <div class="mx-auto flex min-h-full max-w-7xl items-end px-4 py-4 md:items-center md:px-6 lg:px-8">
            <div class="w-full rounded-[32px] border border-white/55 bg-[#f8f9fa] shadow-[0_35px_90px_rgba(23,23,23,0.24)]">
                <div class="flex items-center justify-between gap-4 px-5 pb-4 pt-5 md:px-7">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#8a8179]">Checkout express</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#171717]">Compra en un solo paso</h2>
                    </div>
                    <button type="button" id="checkout-close-button" class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-[#171717]/10 bg-white text-xl">&times;</button>
                </div>

                <div class="grid gap-6 border-t border-[#171717]/8 px-5 py-5 md:px-7 lg:grid-cols-[minmax(0,1.15fr)_24rem]">
                    <div class="space-y-5">
                        <article class="rounded-[32px] border border-white/70 bg-white/80 p-5 shadow-[0_16px_35px_rgba(23,23,23,0.05)]">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#8a8179]">Identificacion</p>
                                    <h3 class="mt-2 text-xl font-semibold text-[#171717]">Email y acceso rapido</h3>
                                </div>
                                <button type="button" id="checkout-login-button" class="rounded-full border border-[#171717]/10 bg-[#f8f4ee] px-4 py-2 text-xs font-semibold text-[#171717]">Ya tengo cuenta</button>
                            </div>
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <label class="checkout-field">
                                    <span>Email</span>
                                    <input id="checkout-email" type="email" class="checkout-input" placeholder="tu@email.com">
                                    <span class="checkout-check">✓</span>
                                </label>
                                <label class="checkout-field">
                                    <span>Telefono</span>
                                    <input id="checkout-phone" type="text" class="checkout-input" placeholder="+54 11 1234 5678">
                                    <span class="checkout-check">✓</span>
                                </label>
                            </div>
                        </article>

                        <article class="rounded-[32px] border border-white/70 bg-white/80 p-5 shadow-[0_16px_35px_rgba(23,23,23,0.05)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#8a8179]">Entrega</p>
                            <h3 class="mt-2 text-xl font-semibold text-[#171717]">Metodo y direccion</h3>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <button type="button" class="delivery-mode-trigger checkout-pill is-active" data-mode="delivery">Envio a domicilio</button>
                                <button type="button" class="delivery-mode-trigger checkout-pill" data-mode="pickup">Retiro en local</button>
                            </div>
                            <div id="delivery-fields" class="mt-4 grid gap-3 md:grid-cols-2">
                                <label class="checkout-field md:col-span-2">
                                    <span>Direccion</span>
                                    <input id="checkout-address" type="text" class="checkout-input" placeholder="Av. Corrientes 1234, CABA">
                                    <span class="checkout-check">✓</span>
                                    <small class="text-[11px] text-[#8a8179]">Autocompletado listo para integrar con Google Maps API.</small>
                                </label>
                                <label class="checkout-field">
                                    <span>Piso / depto</span>
                                    <input id="checkout-unit" type="text" class="checkout-input" placeholder="8B">
                                </label>
                                <label class="checkout-field">
                                    <span>Notas de entrega</span>
                                    <input id="checkout-note" type="text" class="checkout-input" placeholder="Porteria, referencia, etc.">
                                </label>
                            </div>
                        </article>

                        <article class="rounded-[32px] border border-white/70 bg-white/80 p-5 shadow-[0_16px_35px_rgba(23,23,23,0.05)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#8a8179]">Pago seguro</p>
                            <h3 class="mt-2 text-xl font-semibold text-[#171717]">Elegi como queres pagar</h3>
                            <div class="mt-4 flex flex-wrap gap-3">
                                @foreach ($tenant['paymentMethods'] as $index => $method)
                                    <button type="button" class="payment-method-trigger checkout-pill {{ $index === 0 ? 'is-active' : '' }}" data-method="{{ $method }}">{{ $method }}</button>
                                @endforeach
                            </div>
                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                <div class="rounded-[24px] border border-[#171717]/8 bg-[#f8f4ee] px-4 py-3 text-sm font-semibold text-[#171717]">Visa</div>
                                <div class="rounded-[24px] border border-[#171717]/8 bg-[#f8f4ee] px-4 py-3 text-sm font-semibold text-[#171717]">Mastercard</div>
                                <div class="rounded-[24px] border border-[#171717]/8 bg-[#f8f4ee] px-4 py-3 text-sm font-semibold text-[#171717]">Amex</div>
                            </div>
                        </article>
                    </div>

                    <aside class="space-y-4">
                        <article class="rounded-[32px] border border-white/70 bg-white/85 p-5 shadow-[0_16px_35px_rgba(23,23,23,0.05)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#8a8179]">Resumen de la compra</p>
                            <div id="checkout-summary-items" class="mt-4 space-y-3"></div>
                            <div class="mt-5 space-y-2 border-t border-[#171717]/8 pt-4 text-sm">
                                <div class="flex items-center justify-between"><span class="text-[#61574d]">Subtotal</span><span id="checkout-subtotal" class="font-semibold text-[#171717]">$0</span></div>
                                <div class="flex items-center justify-between"><span class="text-[#61574d]">Envio</span><span id="checkout-shipping" class="font-semibold text-[#171717]">$0</span></div>
                                <div class="flex items-center justify-between"><span class="text-[#61574d]">Impuestos</span><span id="checkout-tax" class="font-semibold text-[#171717]">$0</span></div>
                            </div>
                        </article>
                        <article class="rounded-[32px] border border-[#171717]/10 bg-[#171717] p-5 text-white shadow-[0_20px_45px_rgba(23,23,23,0.16)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/55">Tenant ready</p>
                            <p class="mt-2 text-sm leading-7 text-white/75">Branding, medios de pago y CTA cargan desde la configuracion del tenant. Los intentos y errores se pueden persistir como audit log cuando conectemos backend.</p>
                        </article>
                    </aside>
                </div>

                <div class="border-t border-[#171717]/8 bg-white/88 px-5 py-4 md:px-7">
                    <button type="button" id="checkout-submit-button" class="inline-flex min-h-[60px] w-full items-center justify-center rounded-full px-6 text-base font-semibold text-white shadow-[0_20px_42px_rgba(47,143,101,0.28)] md:text-lg" style="background: {{ $tenant['accent'] }};">
                        Pagar <span id="checkout-total-button" class="ml-2">{{ '$0' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="checkout-login-overlay" class="pointer-events-none fixed inset-0 z-[80] hidden bg-[#171717]/25 opacity-0 backdrop-blur-md transition">
        <div class="mx-auto flex min-h-full max-w-xl items-center px-4">
            <div class="w-full rounded-[32px] border border-white/60 bg-white/78 p-5 shadow-[0_28px_70px_rgba(23,23,23,0.18)] backdrop-blur-xl">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#8a8179]">Acceso rapido</p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#171717]">Volver al checkout sin salir de contexto</h3>
                    </div>
                    <button type="button" id="checkout-login-close" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#171717]/10 bg-white text-xl">&times;</button>
                </div>
                <div class="mt-4 grid gap-3">
                    <label class="checkout-field">
                        <span>Email</span>
                        <input type="email" class="checkout-input" placeholder="tu@email.com">
                    </label>
                    <label class="checkout-field">
                        <span>Contrasena</span>
                        <input type="password" class="checkout-input" placeholder="••••••••">
                    </label>
                </div>
                <button type="button" class="mt-4 inline-flex min-h-[54px] w-full items-center justify-center rounded-full px-6 text-sm font-semibold text-white" style="background: {{ $tenant['accent'] }};">Ingresar y continuar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const q = (s) => document.querySelector(s), qa = (s) => document.querySelectorAll(s);
            const cart = [];
            qa('.checkout-check').forEach((node) => { node.innerHTML = '&#10003;'; });
            const format = (v) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(v);
            const navbar = q('#store-navbar');
            const overlay = q('#cart-overlay');
            const sheet = q('#cart-sheet');
            const quick = q('#quick-view-modal');
            const checkout = q('#checkout-modal');
            const checkoutLogin = q('#checkout-login-overlay');
            const featuredImage = q('#featured-image');
            const colorLabel = q('#selected-color-label');
            const mobileBar = q('#mobile-cart-bar');
            const priceAnchor = q('#product-price-anchor');
            const countBadge = q('#cart-count-badge');
            const cartItems = q('#cart-items');
            const cartEmpty = q('#cart-empty');
            const cartTitle = q('#cart-title');
            const cartSubtotal = q('#cart-subtotal');
            const checkoutSummaryItems = q('#checkout-summary-items');
            const checkoutSubtotal = q('#checkout-subtotal');
            const checkoutShipping = q('#checkout-shipping');
            const checkoutTax = q('#checkout-tax');
            const checkoutTotalButton = q('#checkout-total-button');
            const checkoutDeliveryFields = q('#delivery-fields');
            const checkoutInputs = ['#checkout-email', '#checkout-phone', '#checkout-address'].map((selector) => q(selector));
            let deliveryMode = 'delivery';
            let paymentMethod = 'Mercado Pago';
            const shippingBase = 5900;
            const taxRate = 0.12;
            const totalItems = () => cart.reduce((sum, item) => sum + item.qty, 0);
            const itemsSubtotal = () => cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            const shippingCost = () => deliveryMode === 'pickup' ? 0 : shippingBase;
            const taxCost = () => Math.round(itemsSubtotal() * taxRate);
            const checkoutTotal = () => itemsSubtotal() + shippingCost() + taxCost();
            const openCart = () => { overlay.classList.remove('pointer-events-none', 'opacity-0'); sheet.classList.remove('pointer-events-none', 'translate-x-full'); };
            const closeCart = () => { overlay.classList.add('pointer-events-none', 'opacity-0'); sheet.classList.add('pointer-events-none', 'translate-x-full'); };
            const openCheckout = () => { closeCart(); checkout.classList.remove('hidden'); requestAnimationFrame(() => checkout.classList.remove('pointer-events-none', 'opacity-0')); renderCheckout(); };
            const closeCheckout = () => { checkout.classList.add('pointer-events-none', 'opacity-0'); setTimeout(() => checkout.classList.add('hidden'), 180); };
            const openCheckoutLogin = () => { checkoutLogin.classList.remove('hidden'); requestAnimationFrame(() => checkoutLogin.classList.remove('pointer-events-none', 'opacity-0')); };
            const closeCheckoutLogin = () => { checkoutLogin.classList.add('pointer-events-none', 'opacity-0'); setTimeout(() => checkoutLogin.classList.add('hidden'), 180); };

            const renderCart = () => {
                cartItems.innerHTML = '';
                const items = totalItems();
                cartTitle.textContent = `Tu Carrito (${items} ${items === 1 ? 'producto' : 'productos'})`;
                cartSubtotal.textContent = format(itemsSubtotal());
                if (!items) {
                    cartEmpty.classList.remove('hidden');
                    countBadge.classList.add('hidden');
                    return;
                }
                cartEmpty.classList.add('hidden');
                countBadge.textContent = items;
                countBadge.classList.remove('hidden');
                cart.forEach((item) => {
                    const el = document.createElement('article');
                    el.className = 'rounded-[24px] border border-white/70 bg-white/82 p-3 shadow-[0_12px_28px_rgba(23,23,23,0.05)]';
                    el.innerHTML = `<div class="flex gap-3"><img src="${item.image}" alt="${item.name}" class="h-20 w-20 rounded-[20px] object-cover"><div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold">${item.name}</p><p class="mt-1 text-xs text-[#7a7066]">${item.variant}</p></div><p class="text-sm font-semibold">${format(item.price * item.qty)}</p></div><div class="mt-4 flex items-center justify-between gap-3"><div class="inline-flex items-center gap-2 rounded-full border border-[#171717]/10 bg-[#f8f4ee] p-1"><button type="button" class="cart-qty-btn" data-action="decrease" data-id="${item.id}">-</button><span class="min-w-[2rem] text-center text-sm font-semibold">${item.qty}</span><button type="button" class="cart-qty-btn" data-action="increase" data-id="${item.id}">+</button></div><button type="button" class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8a8179]" data-action="remove" data-id="${item.id}">Quitar</button></div></div></div>`;
                    cartItems.appendChild(el);
                });
                qa('[data-action]').forEach((btn) => btn.addEventListener('click', () => {
                    const item = cart.find((entry) => entry.id === btn.dataset.id);
                    if (!item) return;
                    if (btn.dataset.action === 'increase') item.qty += 1;
                    if (btn.dataset.action === 'decrease') item.qty -= 1;
                    if (btn.dataset.action === 'remove' || item.qty <= 0) cart.splice(cart.findIndex((entry) => entry.id === btn.dataset.id), 1);
                    renderCart();
                    renderCheckout();
                }));
            };

            const renderCheckout = () => {
                checkoutSummaryItems.innerHTML = '';
                cart.forEach((item) => {
                    const row = document.createElement('div');
                    row.className = 'rounded-[24px] border border-[#171717]/8 bg-[#f8f4ee] px-4 py-3';
                    row.innerHTML = `<div class="flex items-center gap-3"><img src="${item.image}" alt="${item.name}" class="h-14 w-14 rounded-[18px] object-cover"><div class="min-w-0 flex-1"><p class="text-sm font-semibold text-[#171717]">${item.name}</p><p class="mt-1 text-xs text-[#7a7066]">${item.variant}</p></div><div class="text-right"><p class="text-sm font-semibold text-[#171717]">${format(item.price * item.qty)}</p><p class="mt-1 text-xs text-[#7a7066]">${item.qty} u.</p></div></div>`;
                    checkoutSummaryItems.appendChild(row);
                });
                checkoutSubtotal.textContent = format(itemsSubtotal());
                checkoutShipping.textContent = shippingCost() === 0 ? 'Gratis' : format(shippingCost());
                checkoutTax.textContent = format(taxCost());
                checkoutTotalButton.textContent = format(checkoutTotal());
            };

            qa('.add-cart-trigger').forEach((btn) => btn.addEventListener('click', () => {
                const item = cart.find((entry) => entry.id === btn.dataset.id);
                if (item) item.qty += 1;
                else cart.push({ id: btn.dataset.id, name: btn.dataset.name, price: Number(btn.dataset.price || 0), image: btn.dataset.image, variant: btn.dataset.variant, qty: 1 });
                renderCart();
                renderCheckout();
                openCart();
            }));

            q('#cart-open-button')?.addEventListener('click', openCart);
            q('#cart-close-button')?.addEventListener('click', closeCart);
            q('#checkout-open-button')?.addEventListener('click', openCheckout);
            q('#checkout-close-button')?.addEventListener('click', closeCheckout);
            q('#checkout-login-button')?.addEventListener('click', openCheckoutLogin);
            q('#checkout-login-close')?.addEventListener('click', closeCheckoutLogin);
            q('#checkout-submit-button')?.addEventListener('click', () => window.console.info('audit.checkout_attempt', { items: cart, paymentMethod, deliveryMode, total: checkoutTotal() }));
            overlay?.addEventListener('click', closeCart);
            checkout?.addEventListener('click', (event) => { if (event.target === checkout) closeCheckout(); });
            checkoutLogin?.addEventListener('click', (event) => { if (event.target === checkoutLogin) closeCheckoutLogin(); });

            qa('.delivery-mode-trigger').forEach((button) => button.addEventListener('click', () => {
                qa('.delivery-mode-trigger').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                deliveryMode = button.dataset.mode || 'delivery';
                checkoutDeliveryFields.classList.toggle('opacity-60', deliveryMode === 'pickup');
                renderCheckout();
            }));

            qa('.payment-method-trigger').forEach((button) => button.addEventListener('click', () => {
                qa('.payment-method-trigger').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                paymentMethod = button.dataset.method || 'Mercado Pago';
            }));

            checkoutInputs.forEach((input) => input?.addEventListener('input', () => {
                const field = input.closest('.checkout-field');
                if (!field) return;
                field.classList.toggle('is-valid', input.value.trim().length > (input.type === 'email' ? 5 : 2));
            }));

            qa('.color-swatch').forEach((swatch) => swatch.addEventListener('click', () => {
                qa('.color-swatch').forEach((item) => item.classList.remove('is-active'));
                swatch.classList.add('is-active');
                featuredImage.classList.add('is-swapping');
                setTimeout(() => {
                    featuredImage.src = swatch.dataset.colorImage || '';
                    colorLabel.textContent = swatch.dataset.colorLabel || '';
                    featuredImage.classList.remove('is-swapping');
                }, 170);
            }));

            qa('.quick-view-trigger').forEach((btn) => btn.addEventListener('click', () => {
                q('#quick-view-image').src = btn.dataset.image || '';
                q('#quick-view-name').textContent = btn.dataset.name || '';
                q('#quick-view-summary').textContent = btn.dataset.summary || '';
                q('#quick-view-price').textContent = btn.dataset.price || '';
                quick.classList.remove('hidden');
                requestAnimationFrame(() => { quick.classList.remove('pointer-events-none', 'opacity-0'); quick.classList.add('flex', 'opacity-100'); });
            }));

            const closeQuick = () => {
                quick.classList.add('pointer-events-none', 'opacity-0');
                quick.classList.remove('opacity-100');
                setTimeout(() => { quick.classList.remove('flex'); quick.classList.add('hidden'); }, 180);
            };

            q('#quick-view-close')?.addEventListener('click', closeQuick);
            quick?.addEventListener('click', (event) => { if (event.target === quick) closeQuick(); });
            if (priceAnchor && mobileBar) new IntersectionObserver(([entry]) => entry.isIntersecting ? mobileBar.classList.add('pointer-events-none', 'translate-y-6', 'opacity-0') : mobileBar.classList.remove('pointer-events-none', 'translate-y-6', 'opacity-0'), { threshold: 0.15 }).observe(priceAnchor);
            const onScroll = () => window.scrollY > 18 ? navbar.classList.add('is-condensed') : navbar.classList.remove('is-condensed');
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
            renderCart();
            renderCheckout();
        });
    </script>
</body>
</html>
