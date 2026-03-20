<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12"
    style="background: linear-gradient(135deg, #0F1F3D 0%, #162B52 100%)">

    <!-- Logo -->
    <div class="flex items-center gap-2 mb-10">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#00BFA6">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z"/>
        </svg>
      </div>
      <span class="text-xl font-bold text-white">Den<span style="color:#00BFA6">taris</span></span>
    </div>

    <!-- Card -->
    <div class="w-full max-w-md rounded-2xl p-8 shadow-2xl"
      style="background:#fff">

      <!-- Trial badge -->
      <div class="flex justify-center mb-6">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold"
          style="background:rgba(0,191,166,0.12); color:#00BFA6">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ trialDaysLeft }} días de prueba gratis restantes
        </span>
      </div>

      <h1 class="text-2xl font-bold text-center mb-2" style="color:#0F1F3D">
        Activa tu suscripción
      </h1>
      <p class="text-sm text-center mb-8" style="color:#64748b">
        Ingresa tus datos de pago para <strong>{{ clinicName }}</strong>.<br/>
        No se realizará ningún cobro hasta que termine el período de prueba.
      </p>

      <!-- Plan summary -->
      <div class="rounded-xl border p-4 mb-6" style="border-color:#E2E8F0; background:#F8FAFC">
        <div class="flex justify-between items-center mb-3">
          <span class="font-semibold text-sm" style="color:#0F1F3D">Plan Dentaris</span>
          <span class="font-bold" style="color:#0F1F3D">$25<span class="text-xs font-normal" style="color:#64748b">/mes</span></span>
        </div>
        <ul class="space-y-1.5">
          <li v-for="item in planFeatures" :key="item" class="flex items-center gap-2 text-xs" style="color:#475569">
            <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:#00BFA6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ item }}
          </li>
        </ul>
      </div>

      <!-- CTA -->
      <button
        @click="openCheckout"
        :disabled="loading"
        class="w-full py-3.5 rounded-xl font-bold text-white text-sm transition-all"
        style="background:#00BFA6"
        :style="loading ? 'opacity:0.7; cursor:not-allowed' : 'cursor:pointer'">
        <span v-if="!loading">Agregar método de pago →</span>
        <span v-else class="flex items-center justify-center gap-2">
          <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Preparando checkout...
        </span>
      </button>

      <p class="text-center mt-4 text-xs" style="color:#94a3b8">
        Puedes cancelar en cualquier momento. Sin compromisos.
      </p>

      <!-- Skip for now -->
      <div class="text-center mt-4">
        <Link :href="route('dashboard')" class="text-xs underline" style="color:#94a3b8">
          Continuar con la prueba gratuita →
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  clientToken: String,
  priceId: String,
  isSandbox: Boolean,
  trialDaysLeft: Number,
  clinicName: String,
})

const loading = ref(false)

const planFeatures = [
  'Pacientes y expedientes ilimitados',
  'Agenda y citas',
  'Odontograma digital',
  'Facturación y cotizaciones',
  'Inventario',
  '3 usuarios incluidos (+$3/usuario adicional)',
]

function loadPaddleScript() {
  return new Promise((resolve) => {
    if (window.Paddle) return resolve()
    const script = document.createElement('script')
    script.src = props.isSandbox
      ? 'https://sandbox-cdn.paddle.com/paddle/v2/paddle.js'
      : 'https://cdn.paddle.com/paddle/v2/paddle.js'
    script.onload = resolve
    document.head.appendChild(script)
  })
}

async function openCheckout() {
  loading.value = true
  try {
    await loadPaddleScript()

    if (props.isSandbox) {
      window.Paddle.Environment.set('sandbox')
    }
    window.Paddle.Initialize({ token: props.clientToken })

    // Get checkout options from backend
    const { data } = await axios.post(route('subscription.checkout-url'))

    // Open Paddle inline/overlay checkout
    window.Paddle.Checkout.open(data)
  } catch (e) {
    console.error(e)
    loading.value = false
  }
}
</script>
