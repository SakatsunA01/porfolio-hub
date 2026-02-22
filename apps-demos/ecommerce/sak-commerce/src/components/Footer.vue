<template>
  <footer class="border-t border-slate-200 bg-axis-primary">
    <div class="axis-container py-12">
      <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
        <div class="space-y-4">
          <h2 class="text-2xl font-display font-bold text-axis-secondary">Axis Tech</h2>
          <p class="text-axis-tertiary">Diseno que simplifica tu dia.</p>
        </div>

        <div class="md:col-span-2 grid grid-cols-2 gap-8 md:grid-cols-3">
          <div>
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Categorias</h3>
            <ul class="mt-4 space-y-3">
              <li><router-link to="/category/Audio" class="text-sm text-axis-tertiary transition hover:text-axis-secondary">Audio</router-link></li>
              <li><router-link to="/category/Carga" class="text-sm text-axis-tertiary transition hover:text-axis-secondary">Carga</router-link></li>
              <li><router-link to="/category/Accesorios" class="text-sm text-axis-tertiary transition hover:text-axis-secondary">Accesorios</router-link></li>
            </ul>
          </div>

          <div>
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Empresa</h3>
            <ul class="mt-4 space-y-3">
              <li><router-link to="/about" class="text-sm text-axis-tertiary transition hover:text-axis-secondary">Sobre nosotros</router-link></li>
              <li><router-link to="/press" class="text-sm text-axis-tertiary transition hover:text-axis-secondary">Prensa</router-link></li>
              <li><router-link to="/contact" class="text-sm text-axis-tertiary transition hover:text-axis-secondary">Contacto</router-link></li>
            </ul>
          </div>

          <div>
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Legal</h3>
            <ul class="mt-4 space-y-3">
              <li><router-link to="/privacy" class="text-sm text-axis-tertiary transition hover:text-axis-secondary">Privacidad</router-link></li>
              <li><router-link to="/terms" class="text-sm text-axis-tertiary transition hover:text-axis-secondary">Terminos</router-link></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-slate-200 pt-6 md:flex-row">
        <p class="text-sm text-axis-tertiary">&copy; 2026 Axis Tech. Todos los derechos reservados.</p>
        <div class="flex items-center gap-4">
          <a href="#" class="text-axis-tertiary transition hover:text-axis-secondary">
            <span class="sr-only">Twitter</span>
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.71v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
            </svg>
          </a>
          <a href="#" class="text-axis-tertiary transition hover:text-axis-secondary">
            <span class="sr-only">Instagram</span>
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.024.06 1.378.06 3.808s-.012 2.784-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.024.048-1.378.06-3.808.06s-2.784-.013-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.024-.06-1.378-.06-3.808s.012-2.784.06-3.808c.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 016.08 2.525c.636-.247 1.363-.416 2.427-.465C9.53 2.013 9.884 2 12.315 2zm-1.04 2.54a4.971 4.971 0 00-2.822 1.199 4.971 4.971 0 00-1.199 2.822c-.051.99-.06 1.327-.06 3.44s.009 2.45.06 3.44a4.971 4.971 0 001.199 2.822 4.971 4.971 0 002.822 1.199c.99.051 1.327.06 3.44.06s2.45-.009 3.44-.06a4.971 4.971 0 002.822-1.199 4.971 4.971 0 001.199-2.822c.051-.99.06-1.327.06-3.44s-.009-2.45-.06-3.44a4.971 4.971 0 00-1.199-2.822 4.971 4.971 0 00-2.822-1.199c-.99-.051-1.327-.06-3.44-.06s-2.45.009-3.44.06zM12 8.468a3.532 3.532 0 100 7.064 3.532 3.532 0 000-7.064zm-4.75 3.532a4.75 4.75 0 119.5 0 4.75 4.75 0 01-9.5 0z" clip-rule="evenodd" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts"></script>
