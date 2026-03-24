<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const mobileOpen = ref(false)

const navLinks = [
  { label: 'Características', href: '#caracteristicas' },
  { label: 'Módulos',         href: '#modulos' },
  { label: 'Precios',         href: '#precios' },
  { label: 'Testimonios',     href: '#testimonios' },
]

// Feature icons are inline SVG paths (Heroicons 24 outline)
const features = [
  {
    title: 'Gestión de Pacientes',
    desc: 'Expedientes clínicos completos, historial médico, archivos y seguimiento en un solo lugar.',
    icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
  },
  {
    title: 'Agenda Inteligente',
    desc: 'Calendario visual, confirmaciones automáticas por email y recordatorios para reducir ausencias.',
    icon: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z',
  },
  {
    title: 'Facturación y Cobros',
    desc: 'Facturas, cotizaciones, pagos parciales y control de cuentas por cobrar sin esfuerzo.',
    icon: 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
  },
  {
    title: 'Odontograma Digital',
    desc: 'Diagnósticos por pieza dental desde un catálogo personalizado, con trazabilidad completa.',
    icon: 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5',
  },
  {
    title: 'Inventario de Materiales',
    desc: 'Control de insumos, alertas de reposición automática y relación insumo → tratamiento.',
    icon: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
  },
  {
    title: 'Reportes y KPIs',
    desc: 'Dashboard con ingresos, citas, productividad por dentista y métricas clave de tu clínica.',
    icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
  },
]

const modules = [
  {
    tag: 'Clínico',
    title: 'Odontograma interactivo',
    desc: 'Selecciona una o varias piezas dentales y asigna diagnósticos desde un catálogo propio de tu clínica. Cada diagnóstico queda registrado con fecha, dentista y notas clínicas.',
    bullets: ['Catálogo de diagnósticos personalizable', 'Selección múltiple de piezas', 'Tratamientos sugeridos por diagnóstico', 'Historial completo por paciente'],
    color: 'teal',
  },
  {
    tag: 'Facturación',
    title: 'Cobros sin fricción',
    desc: 'Desde la cotización hasta el pago, todo en un solo flujo. Convierte presupuestos en facturas con un clic y ofrece planes de cuotas a tus pacientes.',
    bullets: ['Cotizaciones → Facturas en un clic', 'Pagos parciales e instalamentos', 'Cuentas por cobrar con aging', 'PDF de facturas con tu logo'],
    color: 'navy',
  },
  {
    tag: 'Administración',
    title: 'Multi-clínica y multi-rol',
    desc: 'Diseñado desde el inicio para equipos. Define qué puede hacer cada miembro de tu staff según su rol: administrador, dentista o recepcionista.',
    bullets: ['Roles: Admin, Dentista, Recepcionista', 'Cada clínica con sus propios datos', 'Control de acceso granular', 'Auditoría de acciones'],
    color: 'teal',
  },
]

const whatsappPacks = [
  { price: '$10', messages: '300',   label: 'Básico' },
  { price: '$20', messages: '800',   label: 'Estándar' },
  { price: '$30', messages: '1,500', label: 'Pro' },
]

const testimonials = [
  {
    name: 'Dr. Ricardo Méndez',
    role: 'Director, Clínica Dental Méndez',
    text: 'Dentarix transformó cómo gestionamos nuestra clínica. Antes usábamos hojas de cálculo y ahora todo está centralizado. El odontograma digital fue un cambio de juego para nuestro equipo clínico.',
    avatar: 'RM',
  },
  {
    name: 'Lic. Carolina Vega',
    role: 'Administradora, Centro Dental Integral',
    text: 'La facturación y el control de cuentas por cobrar me ahorran horas cada semana. Los reportes de ingresos son exactamente lo que necesitaba para presentarle resultados al director médico.',
    avatar: 'CV',
  },
  {
    name: 'Dra. Patricia Solano',
    role: 'Propietaria, SmilePro Dental',
    text: 'Probé varios sistemas antes de Dentarix. La diferencia es la velocidad y la simplicidad. Mi recepcionista aprendió a usarlo en un día. La confirmación automática de citas redujo los no-shows un 40%.',
    avatar: 'PS',
  },
]

const activeTestimonial = ref(0)

const stats = [
  { value: '500+', label: 'Clínicas activas' },
  { value: '120k+', label: 'Pacientes gestionados' },
  { value: '99.9%', label: 'Uptime garantizado' },
  { value: '40%',   label: 'Menos no-shows' },
]
</script>

<template>
  <div class="min-h-screen bg-white" style="font-family: system-ui, -apple-system, sans-serif;">

    <!-- ═══ NAVBAR ══════════════════════════════════════════════════════════ -->
    <header class="fixed top-0 inset-x-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-100 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">

        <a href="#" class="flex items-center gap-2.5 flex-shrink-0">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center shadow-md" style="background:#00BFA6">
            <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z" />
            </svg>
          </div>
          <span class="text-xl font-bold tracking-tight" style="color:#0F1F3D">Den<span style="color:#00BFA6">tarix</span></span>
        </a>

        <nav class="hidden md:flex items-center gap-1">
          <a v-for="link in navLinks" :key="link.label" :href="link.href"
            class="px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors">
            {{ link.label }}
          </a>
        </nav>

        <div class="flex items-center gap-3">
          <Link :href="route('login')" class="hidden sm:block text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
            Iniciar sesión
          </Link>
          <Link :href="route('register')"
            class="px-4 py-2 text-white text-sm font-semibold rounded-xl shadow-sm transition-all hover:opacity-90"
            style="background:#00BFA6">
            Prueba gratis
          </Link>
          <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <div v-if="mobileOpen" class="md:hidden bg-white border-t border-gray-100 px-4 pb-4 pt-2 space-y-1">
        <a v-for="link in navLinks" :key="link.label" :href="link.href"
          @click="mobileOpen = false"
          class="block px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100">
          {{ link.label }}
        </a>
      </div>
    </header>

    <!-- ═══ HERO ═════════════════════════════════════════════════════════════ -->
    <section class="relative min-h-screen flex items-end overflow-hidden pt-16">

      <!-- Background image -->
      <div class="absolute inset-0">
        <img src="/getsmile-hero-image.jpg" alt=""
          class="w-full h-full object-cover object-center" />
        <!-- Dark overlay: lighter on right so image shows, heavier on left for text legibility -->
        <div class="absolute inset-0" style="background:linear-gradient(105deg, rgba(10,24,40,0.93) 0%, rgba(10,24,40,0.88) 42%, rgba(10,24,40,0.45) 70%, rgba(10,24,40,0.2) 100%)"></div>
      </div>

      <!-- Content -->
      <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24 pt-28 lg:pb-32">
        <div class="max-w-xl">

          <p class="text-sm font-semibold uppercase tracking-widest mb-5" style="color:#00BFA6">
            Software de gestión dental
          </p>

          <h1 class="text-5xl lg:text-6xl font-extrabold text-white leading-[1.08] mb-6">
            Tu clínica,<br>bajo control.<br>
            <span style="color:#00BFA6">Desde el día uno.</span>
          </h1>

          <p class="text-lg mb-10 max-w-md" style="color:#c0cfe0; line-height:1.7">
            Pacientes, agenda, facturación y odontograma en una sola plataforma. Sin hojas de cálculo, sin papel, sin dolores de cabeza.
          </p>

          <div class="flex flex-wrap gap-3 mb-12">
            <Link :href="route('register')"
              class="inline-flex items-center gap-2 px-7 py-3.5 text-white font-bold rounded-xl text-base transition-all hover:opacity-90"
              style="background:#00BFA6; box-shadow:0 4px 20px rgba(0,191,166,0.4)">
              Prueba gratis 14 días
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
              </svg>
            </Link>
            <a href="#caracteristicas"
              class="inline-flex items-center gap-2 px-7 py-3.5 font-semibold rounded-xl text-base text-white border transition-all hover:bg-white/10"
              style="border-color:rgba(255,255,255,0.25)">
              Ver características
            </a>
          </div>

          <!-- Trust row -->
          <div class="flex flex-wrap gap-x-6 gap-y-2">
            <span v-for="item in ['Sin cobro durante el período de prueba', 'Cancela cuando quieras', 'Configuración en 15 min']" :key="item"
              class="flex items-center gap-1.5 text-sm" style="color:#94a3b8">
              <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:#00BFA6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
              </svg>
              {{ item }}
            </span>
          </div>
        </div>
      </div>

      <!-- Bottom fade to white -->
      <div class="absolute bottom-0 left-0 right-0 h-24 pointer-events-none"
        style="background:linear-gradient(to top, white, transparent)"></div>
    </section>

    <!-- ═══ STATS ═════════════════════════════════════════════════════════════ -->
   <!--  <section class="bg-white py-12 border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
          <div v-for="stat in stats" :key="stat.value" class="p-4">
            <p class="text-4xl font-extrabold" style="color:#00BFA6">{{ stat.value }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </section> -->

    <!-- ═══ FEATURES ══════════════════════════════════════════════════════════ -->
    <section id="caracteristicas" class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <p class="text-sm font-semibold uppercase tracking-widest mb-3" style="color:#00BFA6">Características</p>
          <h2 class="text-4xl font-extrabold" style="color:#0F1F3D">
            Todo lo que necesita tu clínica,<br>
            <span style="color:#00BFA6">en una sola plataforma</span>
          </h2>
          <p class="text-gray-500 mt-4 max-w-xl mx-auto">
            Diseñado específicamente para clínicas dentales. No es un software genérico adaptado — es dental desde el día uno.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="feat in features" :key="feat.title"
            class="group p-7 rounded-3xl border border-gray-100 hover:border-teal-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5 transition-all duration-300 group-hover:scale-110"
              style="background:#f0fdfb">
              <svg class="w-7 h-7" style="color:#00BFA6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" :d="feat.icon" />
              </svg>
            </div>
            <h3 class="text-lg font-bold mb-2" style="color:#0F1F3D">{{ feat.title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ feat.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ MODULES DEEP DIVES ════════════════════════════════════════════════ -->
    <section id="modulos" class="py-8 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div v-for="(mod, i) in modules" :key="mod.title"
          class="rounded-3xl overflow-hidden border border-gray-100 bg-white shadow-sm">
          <div class="grid lg:grid-cols-2 gap-0" :class="i % 2 !== 0 ? 'lg:grid-flow-dense' : ''">

            <!-- Copy -->
            <div class="p-10 lg:p-14 flex flex-col justify-center" :class="i % 2 !== 0 ? 'lg:col-start-2' : ''">
              <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest mb-4 w-fit"
                :style="mod.color === 'teal' ? 'background:#f0fdfb; color:#00BFA6' : 'background:#f0f4ff; color:#0F1F3D'">
                {{ mod.tag }}
              </span>
              <h3 class="text-3xl font-extrabold mb-4" style="color:#0F1F3D">{{ mod.title }}</h3>
              <p class="text-gray-500 leading-relaxed mb-6">{{ mod.desc }}</p>
              <ul class="space-y-2.5">
                <li v-for="b in mod.bullets" :key="b" class="flex items-center gap-3 text-sm text-gray-700">
                  <svg class="w-4 h-4 flex-shrink-0" style="color:#00BFA6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                  </svg>
                  {{ b }}
                </li>
              </ul>
            </div>

            <!-- Visual panel -->
            <div class="p-10 lg:p-14 flex items-center justify-center min-h-64"
              :class="i % 2 !== 0 ? 'lg:col-start-1 lg:row-start-1' : ''"
              :style="mod.color === 'teal'
                ? 'background:linear-gradient(135deg,#0F1F3D,#162B52)'
                : 'background:linear-gradient(135deg,#f0fdfb,#ccfbf1)'">

              <!-- Odontogram mockup -->
              <div v-if="i === 0" class="w-full max-w-xs">
                <div class="rounded-2xl p-5 border" style="background:rgba(255,255,255,0.07); border-color:rgba(255,255,255,0.12)">
                  <p class="text-xs font-semibold uppercase tracking-widest text-center mb-4" style="color:#94a3b8">Arcada Superior</p>
                  <div class="flex justify-center gap-1 mb-3">
                    <div v-for="(col, idx) in ['#E5E7EB','#EF4444','#E5E7EB','#F59E0B','#E5E7EB','#99F6E4','#99F6E4','#E5E7EB']" :key="idx"
                      class="w-6 h-8 rounded-t-full border"
                      :style="`background:${col}; border-color:rgba(255,255,255,0.2)`"></div>
                  </div>
                  <div class="h-px mb-3" style="background:rgba(255,255,255,0.15)"></div>
                  <div class="flex justify-center gap-1 mb-4">
                    <div v-for="(col, idx) in ['#E5E7EB','#E5E7EB','#8B5CF6','#E5E7EB','#EF4444','#E5E7EB','#99F6E4','#99F6E4']" :key="idx"
                      class="w-6 h-8 rounded-b-full border"
                      :style="`background:${col}; border-color:rgba(255,255,255,0.2)`"></div>
                  </div>
                  <div class="space-y-1.5 mt-3">
                    <div v-for="(diag, idx) in [{c:'#EF4444',t:'Diente #2 — Caries'},{c:'#F59E0B',t:'Diente #4 — Corona'},{c:'#8B5CF6',t:'Diente #19 — Conducto'}]" :key="idx"
                      class="flex items-center gap-2 px-3 py-1.5 rounded-lg" style="background:rgba(255,255,255,0.08)">
                      <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="`background:${diag.c}`"></div>
                      <span class="text-xs" style="color:#e2e8f0">{{ diag.t }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Billing mockup -->
              <div v-else-if="i === 1" class="w-full max-w-xs space-y-3">
                <div class="rounded-2xl p-4 border border-teal-100" style="background:white">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold" style="color:#0F1F3D">COT-00042</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold" style="background:#f0fdfb; color:#00BFA6">Aceptada</span>
                  </div>
                  <div class="space-y-1.5 text-xs text-gray-500 mb-3">
                    <div class="flex justify-between"><span>Ortodoncia completa</span><span class="font-semibold text-gray-800">$2,400</span></div>
                    <div class="flex justify-between"><span>Limpieza dental</span><span class="font-semibold text-gray-800">$80</span></div>
                  </div>
                  <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                    <span class="text-xs text-gray-400">Total</span>
                    <span class="font-extrabold" style="color:#0F1F3D">$2,480</span>
                  </div>
                  <button class="w-full mt-3 py-2 rounded-xl text-xs font-bold text-white" style="background:#00BFA6">
                    → Convertir a Factura
                  </button>
                </div>
                <div class="rounded-2xl p-4 border border-gray-100 bg-white">
                  <p class="text-xs font-semibold text-gray-500 mb-2">Cuentas por cobrar</p>
                  <div class="space-y-1.5">
                    <div v-for="row in [['Corriente','$1,200','#10B981'],['1-30 días','$890','#F59E0B'],['31-60 días','$340','#EF4444']]" :key="row[0]"
                      class="flex items-center justify-between text-xs">
                      <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full" :style="`background:${row[2]}`"></div>
                        <span class="text-gray-500">{{ row[0] }}</span>
                      </div>
                      <span class="font-bold" style="color:#0F1F3D">{{ row[1] }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Multi-role mockup -->
              <div v-else class="w-full max-w-xs space-y-3">
                <div v-for="role in [
                  { label:'Administrador', color:'#00BFA6', perms:['Todo el sistema','Config. clínica','Reportes'] },
                  { label:'Dentista',      color:'#0F1F3D', perms:['Pacientes','Odontograma','Citas'] },
                  { label:'Recepcionista', color:'#6366f1', perms:['Agenda','Facturación','Archivos'] },
                ]" :key="role.label"
                  class="rounded-2xl p-4 border" style="background:white; border-color:#e5e7eb">
                  <div class="flex items-center gap-2 mb-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-extrabold text-white"
                      :style="`background:${role.color}`">
                      {{ role.label[0] }}
                    </div>
                    <span class="text-sm font-bold" style="color:#0F1F3D">{{ role.label }}</span>
                  </div>
                  <div class="flex flex-wrap gap-1">
                    <span v-for="p in role.perms" :key="p"
                      class="text-xs px-2 py-0.5 rounded-full border" style="color:#64748b; border-color:#e5e7eb">
                      {{ p }}
                    </span>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ PRICING ═══════════════════════════════════════════════════════════ -->
    <section id="precios" class="py-24 bg-white">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
          <p class="text-sm font-semibold uppercase tracking-widest mb-3" style="color:#00BFA6">Precios</p>
          <h2 class="text-4xl font-extrabold" style="color:#0F1F3D">
            Un plan, sin sorpresas.<br>
            <span style="color:#00BFA6">Crece a tu ritmo.</span>
          </h2>
          <p class="text-gray-500 mt-4 max-w-md mx-auto">
            Paga solo por lo que usas. Empieza con lo esencial y agrega lo que necesites.
          </p>
        </div>

        <div class="grid lg:grid-cols-2 gap-6 items-start">

          <!-- Base plan -->
          <div class="rounded-3xl p-8 border-2 flex flex-col"
            style="background:linear-gradient(135deg,#0F1F3D,#162B52); border-color:#00BFA6; box-shadow:0 20px 60px rgba(0,191,166,0.15)">

            <div class="mb-2">
              <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest"
                style="background:rgba(0,191,166,0.15); color:#00BFA6">Plan único</span>
            </div>

            <div class="flex items-end gap-2 my-5">
              <span class="text-7xl font-extrabold text-white leading-none">$25</span>
              <div class="pb-2" style="color:#94a3b8">
                <p class="text-sm font-medium">/mes</p>
                <p class="text-xs">por clínica</p>
              </div>
            </div>

            <p class="text-sm mb-6" style="color:#94a3b8">
              Acceso completo a todos los módulos. Sin límite de pacientes ni de citas.
            </p>

            <ul class="space-y-3 mb-8">
              <li v-for="f in [
                'Todos los módulos incluidos',
                'Pacientes y citas ilimitados',
                '3 usuarios incluidos',
                'Facturación, cotizaciones y cuotas',
                'Odontograma + catálogo de diagnósticos',
                'Confirmaciones de cita por correo al paciente',
                'Inventario de materiales',
                'Reportes y dashboard',
                'Soporte por email',
              ]" :key="f" class="flex items-center gap-2.5 text-sm" style="color:#e2e8f0">
                <svg class="w-4 h-4 flex-shrink-0" style="color:#00BFA6" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                {{ f }}
              </li>
            </ul>

            <Link :href="route('register')"
              class="w-full py-4 rounded-2xl font-bold text-center text-base transition-all hover:opacity-90 block mt-auto"
              style="background:#00BFA6; color:white">
              Empezar gratis — 14 días
            </Link>
            <p class="text-center text-xs mt-3" style="color:#475569">Sin cobro hasta terminar el período de prueba</p>
          </div>

          <!-- Add-ons -->
          <div class="space-y-4">
            <p class="text-xs font-bold uppercase tracking-widest mb-5" style="color:#64748b">Add-ons opcionales</p>

            <!-- Extra users -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 hover:border-teal-200 hover:shadow-md transition-all">
              <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0" style="background:#f0fdfb">
                    👤
                  </div>
                  <div>
                    <p class="font-bold text-sm" style="color:#0F1F3D">Usuario adicional</p>
                    <p class="text-xs text-gray-400 mt-0.5">Agrega dentistas, recepcionistas o admins a tu equipo.</p>
                  </div>
                </div>
                <div class="text-right flex-shrink-0">
                  <span class="text-2xl font-extrabold" style="color:#0F1F3D">$3</span>
                  <p class="text-xs text-gray-400">/usuario/mes</p>
                </div>
              </div>
            </div>

            <!-- WhatsApp packs -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 hover:border-teal-200 hover:shadow-md transition-all">
              <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0" style="background:#f0fdf4">
                  💬
                </div>
                <div>
                  <p class="font-bold text-sm" style="color:#0F1F3D">Mensajes WhatsApp</p>
                  <p class="text-xs text-gray-400 mt-0.5">Confirmaciones, recordatorios y notificaciones a pacientes.</p>
                </div>
              </div>

              <div class="grid grid-cols-3 gap-2">
                <div v-for="pack in whatsappPacks" :key="pack.price"
                  class="rounded-xl border border-gray-100 p-3 text-center hover:border-teal-300 cursor-default transition-colors group">
                  <p class="text-xs font-semibold text-gray-400 mb-1.5">{{ pack.label }}</p>
                  <p class="text-xl font-extrabold" style="color:#0F1F3D">{{ pack.price }}</p>
                  <p class="text-xs text-gray-400 mt-1 leading-tight">{{ pack.messages }} mensajes</p>
                </div>
              </div>
            </div>

            <!-- Total example -->
            <div class="rounded-2xl p-5 border" style="background:#f8fafc; border-color:#e2e8f0">
              <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Ejemplo: clínica con 5 usuarios</p>
              <div class="space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-600">
                  <span>Plan base (3 usuarios)</span>
                  <span class="font-semibold" style="color:#0F1F3D">$20</span>
                </div>
                <div class="flex justify-between text-gray-600">
                  <span>2 usuarios adicionales</span>
                  <span class="font-semibold" style="color:#0F1F3D">$6</span>
                </div>
                <div class="flex justify-between text-gray-600">
                  <span>WhatsApp 800 mensajes</span>
                  <span class="font-semibold" style="color:#0F1F3D">$20</span>
                </div>
                <div class="flex justify-between items-center pt-2 mt-1 border-t border-gray-200">
                  <span class="font-bold" style="color:#0F1F3D">Total / mes</span>
                  <span class="text-xl font-extrabold" style="color:#00BFA6">$46</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ TESTIMONIALS ══════════════════════════════════════════════════════ -->
    <section id="testimonios" class="py-24 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
          <p class="text-sm font-semibold uppercase tracking-widest mb-3" style="color:#00BFA6">Testimonios</p>
          <h2 class="text-4xl font-extrabold" style="color:#0F1F3D">
            Clínicas que confían en <span style="color:#00BFA6">Dentarix</span>
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="(t, i) in testimonials" :key="t.name"
            class="bg-white rounded-3xl p-7 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex gap-1 mb-4">
              <svg v-for="s in 5" :key="s" class="w-4 h-4" style="fill:#facc15" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
            <blockquote class="text-gray-600 text-sm leading-relaxed mb-5">"{{ t.text }}"</blockquote>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                :style="`background:${i % 2 === 0 ? '#00BFA6' : '#0F1F3D'}`">
                {{ t.avatar }}
              </div>
              <div>
                <p class="text-sm font-bold" style="color:#0F1F3D">{{ t.name }}</p>
                <p class="text-xs text-gray-400">{{ t.role }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ FINAL CTA ══════════════════════════════════════════════════════════ -->
    <section class="py-24 relative overflow-hidden" style="background:linear-gradient(135deg,#0F1F3D,#162B52)">
      <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background:rgba(0,191,166,0.12)"></div>
        <div class="absolute -bottom-20 -left-20 w-80 h-80 rounded-full blur-3xl" style="background:rgba(0,191,166,0.08)"></div>
      </div>
      <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center relative z-10">
        <h2 class="text-4xl lg:text-5xl font-extrabold text-white mb-4 leading-tight">
          Tu clínica merece<br>
          <span style="color:#00BFA6">un software a su altura</span>
        </h2>
        <p class="text-lg mb-10 max-w-xl mx-auto" style="color:#94a3b8">
          Únete a más de 500 clínicas dentales que ya gestionan su práctica con Dentarix. Pruébalo gratis por 14 días.
        </p>
        <div class="flex flex-wrap gap-4 justify-center">
          <Link :href="route('register')"
            class="inline-flex items-center gap-2 px-8 py-4 text-white font-bold rounded-2xl text-lg transition-all hover:opacity-90 hover:scale-105 shadow-xl"
            style="background:#00BFA6; box-shadow:0 8px 32px rgba(0,191,166,0.3)">
            Empezar prueba gratuita
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
          </Link>
          <Link :href="route('login')"
            class="inline-flex items-center gap-2 px-8 py-4 font-bold rounded-2xl text-lg text-white border-2 transition-all hover:bg-white/10"
            style="border-color:rgba(255,255,255,0.2)">
            Iniciar sesión
          </Link>
        </div>
        <p class="mt-6 text-sm" style="color:#475569">14 días gratis · Cancela cuando quieras · Soporte incluido</p>
      </div>
    </section>

    <!-- ═══ FOOTER ════════════════════════════════════════════════════════════ -->
    <footer style="background:#0a1828" class="text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">

          <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2.5 mb-4">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#00BFA6">
                <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z" />
                </svg>
              </div>
              <span class="text-lg font-bold">Den<span style="color:#00BFA6">taris</span></span>
            </div>
            <p class="text-sm leading-relaxed" style="color:#64748b">Software de gestión dental todo-en-uno. Diseñado para clínicas modernas.</p>
          </div>

          <div>
            <p class="text-xs font-semibold uppercase tracking-widest mb-4" style="color:#475569">Producto</p>
            <ul class="space-y-2">
              <li v-for="link in navLinks" :key="link.label">
                <a :href="link.href" class="text-sm transition-colors hover:text-teal-400" style="color:#64748b">{{ link.label }}</a>
              </li>
            </ul>
          </div>

          <div>
            <p class="text-xs font-semibold uppercase tracking-widest mb-4" style="color:#475569">Módulos</p>
            <ul class="space-y-2">
              <li v-for="f in features.slice(0,4)" :key="f.title">
                <a href="#modulos" class="text-sm transition-colors hover:text-teal-400" style="color:#64748b">{{ f.title }}</a>
              </li>
            </ul>
          </div>

          <div>
            <p class="text-xs font-semibold uppercase tracking-widest mb-4" style="color:#475569">Empresa</p>
            <ul class="space-y-2">
              <li v-for="item in ['Blog','Soporte','Documentación','Contacto']" :key="item">
                <a href="#" class="text-sm transition-colors hover:text-teal-400" style="color:#64748b">{{ item }}</a>
              </li>
            </ul>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs pt-6"
          style="border-top:1px solid rgba(255,255,255,0.06); color:#334155">
          <p>© 2026 Dentarix. Todos los derechos reservados.</p>
          <div class="flex gap-4">
            <a href="#" class="hover:text-teal-400 transition-colors">Términos de servicio</a>
            <a href="#" class="hover:text-teal-400 transition-colors">Política de privacidad</a>
          </div>
        </div>
      </div>
    </footer>

  </div>
</template>
