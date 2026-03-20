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
    <div class="w-full max-w-md rounded-2xl p-8 shadow-2xl text-center" style="background:#fff">

      <!-- Icon -->
      <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
        style="background:#FEF3C7">
        <svg class="w-8 h-8" style="color:#D97706" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
      </div>

      <h1 class="text-2xl font-bold mb-3" style="color:#0F1F3D">Período de prueba finalizado</h1>
      <p class="text-sm mb-8" style="color:#64748b">
        Tu período de prueba gratuita ha terminado. Activa tu suscripción para continuar usando Dentaris.
      </p>

      <!-- Price reminder -->
      <div class="rounded-xl border p-4 mb-6 text-left" style="border-color:#E2E8F0; background:#F8FAFC">
        <div class="flex justify-between items-center mb-2">
          <span class="font-semibold text-sm" style="color:#0F1F3D">Plan Dentaris</span>
          <span class="font-bold" style="color:#0F1F3D">$25<span class="text-xs font-normal" style="color:#64748b">/mes</span></span>
        </div>
        <p class="text-xs" style="color:#64748b">3 usuarios incluidos · +$3/usuario adicional · Sin límite de pacientes</p>
      </div>

      <button
        @click="openCheckout"
        :disabled="loading"
        class="w-full py-3.5 rounded-xl font-bold text-white text-sm mb-4"
        style="background:#00BFA6"
        :style="loading ? 'opacity:0.7' : ''">
        <span v-if="!loading">Activar suscripción →</span>
        <span v-else class="flex items-center justify-center gap-2">
          <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Preparando checkout...
        </span>
      </button>

      <div class="text-center mt-2">
        <button @click="logout" class="text-xs underline" style="color:#94a3b8">
          Cerrar sesión
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  clientToken: String,
  priceId: String,
  isSandbox: Boolean,
})

const loading = ref(false)

function logout() {
  router.post(route('logout'))
}

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

    const { data } = await axios.post(route('subscription.checkout-url'))

    window.Paddle.Checkout.open({
      ...data,
      settings: { ...data.settings, displayMode: 'overlay', frameStyle: undefined },
    })

    window.Paddle.Update({
      eventCallback(event) {
        if (event.name === 'checkout.completed') {
          window.location.href = route('dashboard')
        }
      },
    })

    loading.value = false
  } catch (e) {
    console.error(e)
    loading.value = false
  }
}
</script>
