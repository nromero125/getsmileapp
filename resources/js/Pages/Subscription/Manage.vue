<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  subscription: Object,
  clinic: Object,
  seatsIncluded: Number,
  activeUsers: Number,
  extraSeats: Number,
  onTrial: Boolean,
  trialDaysLeft: Number,
  subscribed: Boolean,
  waPlan: String,
  waMessagesQuota: Number,
  waMessagesUsed: Number,
  waMessagesResetAt: String,
  waPlans: Object,
})

const statusLabel = computed(() => {
  if (props.onTrial) return 'Período de prueba'
  if (!props.subscribed) return 'Sin suscripción'
  const map = {
    active:   'Activa',
    trialing: 'En prueba',
    past_due: 'Pago vencido',
    paused:   'Pausada',
    canceled: 'Cancelada',
  }
  return map[props.subscription?.status] ?? props.subscription?.status
})

const statusColor = computed(() => {
  if (props.onTrial) return 'badge-teal'
  if (!props.subscribed) return 'badge-red'
  const map = {
    active:   'badge-teal',
    trialing: 'badge-teal',
    past_due: 'badge-red',
    paused:   'badge-gray',
    canceled: 'badge-red',
  }
  return map[props.subscription?.status] ?? 'badge-gray'
})

const waUsagePercent = computed(() => props.waMessagesQuota > 0 ? Math.min(100, Math.round((props.waMessagesUsed / props.waMessagesQuota) * 100)) : 0)
const waUsageColor = computed(() => {
  if (waUsagePercent.value >= 90) return 'bg-red-500'
  if (waUsagePercent.value >= 70) return 'bg-amber-500'
  return 'bg-teal-500'
})

const changingPlan = ref(false)
const activatePlan = (plan) => {
  if (changingPlan.value) return
  changingPlan.value = true
  router.patch(route('subscription.whatsapp-plan'), { plan }, {
    onFinish: () => changingPlan.value = false,
  })
}
</script>

<template>
  <AppLayout title="Suscripción">
    <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">

      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-navy-900 dark:text-white">Suscripción</h1>
        <p class="text-navy-500 dark:text-navy-400 text-sm mt-1">Gestiona el plan y la facturación de tu clínica.</p>
      </div>

      <!-- Status card -->
      <div class="card p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
          <div>
            <p class="text-xs font-semibold text-navy-500 dark:text-navy-400 uppercase tracking-wide mb-1">Estado</p>
            <div class="flex items-center gap-2">
              <span :class="['badge', statusColor]">{{ statusLabel }}</span>
            </div>
            <div v-if="onTrial" class="mt-3 text-sm text-navy-600 dark:text-navy-400">
              Tu período de prueba vence en <strong>{{ trialDaysLeft }} días</strong>.
            </div>
            <div v-else-if="subscription?.trial_ends_at" class="mt-3 text-sm text-navy-600 dark:text-navy-400">
              Período de prueba finalizado el {{ subscription.trial_ends_at }}.
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <Link v-if="!subscribed" :href="route('subscription.checkout')"
              class="btn-primary text-sm px-4 py-2">
              Activar suscripción
            </Link>
            <a v-else :href="route('subscription.billing-portal')"
              class="btn-outline text-sm px-4 py-2 text-center">
              Portal de facturación
            </a>
          </div>
        </div>
      </div>

      <!-- Plan details -->
      <div class="card p-6">
        <h2 class="font-semibold text-navy-900 dark:text-white mb-4">Plan Dentaris</h2>
        <div class="grid grid-cols-3 gap-4 text-center">
          <div class="rounded-xl bg-navy-50 dark:bg-navy-800 p-4">
            <p class="text-2xl font-bold text-navy-900 dark:text-white">{{ activeUsers }}</p>
            <p class="text-xs text-navy-500 dark:text-navy-400 mt-1">Usuarios activos</p>
          </div>
          <div class="rounded-xl bg-navy-50 dark:bg-navy-800 p-4">
            <p class="text-2xl font-bold text-navy-900 dark:text-white">{{ seatsIncluded }}</p>
            <p class="text-xs text-navy-500 dark:text-navy-400 mt-1">Incluidos en plan</p>
          </div>
          <div class="rounded-xl p-4"
            :class="extraSeats > 0 ? 'bg-teal-50 dark:bg-teal-900/20' : 'bg-navy-50 dark:bg-navy-800'">
            <p class="text-2xl font-bold"
              :class="extraSeats > 0 ? 'text-teal-600' : 'text-navy-900 dark:text-white'">
              {{ extraSeats }}
            </p>
            <p class="text-xs text-navy-500 dark:text-navy-400 mt-1">
              Seats extra {{ extraSeats > 0 ? '(+$3/mes c/u)' : '' }}
            </p>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-navy-100 dark:border-navy-700 text-sm text-navy-600 dark:text-navy-400">
          <div class="flex justify-between py-1">
            <span>Plan base ({{ seatsIncluded }} usuarios)</span>
            <span class="font-semibold text-navy-900 dark:text-white">$25/mes</span>
          </div>
          <div v-if="extraSeats > 0" class="flex justify-between py-1">
            <span>{{ extraSeats }} usuario{{ extraSeats > 1 ? 's' : '' }} adicional{{ extraSeats > 1 ? 'es' : '' }}</span>
            <span class="font-semibold text-navy-900 dark:text-white">${{ extraSeats * 3 }}/mes</span>
          </div>
          <div class="flex justify-between py-1 pt-2 border-t border-navy-100 dark:border-navy-700 font-semibold text-navy-900 dark:text-white">
            <span>Total estimado</span>
            <span>${{ 25 + extraSeats * 3 }}/mes</span>
          </div>
        </div>
      </div>

      <!-- WhatsApp Message Plans -->
      <div class="card p-6">
        <div class="flex items-center justify-between mb-1">
          <h2 class="font-semibold text-navy-900 dark:text-white">Mensajes WhatsApp</h2>
          <span v-if="waPlan" class="badge badge-teal capitalize">{{ waPlans[waPlan]?.label }}</span>
          <span v-else class="badge badge-gray">Sin plan</span>
        </div>
        <p class="text-xs text-navy-500 dark:text-navy-400 mb-4">
          Confirmaciones, recordatorios y notificaciones de facturas para tus pacientes.
        </p>

        <!-- Usage bar -->
        <div v-if="waPlan" class="mb-5">
          <div class="flex justify-between text-xs text-navy-500 dark:text-navy-400 mb-1">
            <span>{{ waMessagesUsed.toLocaleString('es-DO') }} / {{ waMessagesQuota.toLocaleString('es-DO') }} mensajes usados</span>
            <span>{{ waUsagePercent }}%</span>
          </div>
          <div class="h-2 rounded-full bg-navy-100 dark:bg-navy-700 overflow-hidden">
            <div :class="['h-full rounded-full transition-all', waUsageColor]" :style="`width: ${waUsagePercent}%`"></div>
          </div>
          <div class="flex justify-between text-xs mt-1">
            <span class="text-navy-400">
              Reinicio:
              {{ waMessagesResetAt ? new Date(waMessagesResetAt).toLocaleDateString('es-MX', { day: 'numeric', month: 'long' }) : '—' }}
            </span>
            <span :class="waUsagePercent >= 90 ? 'text-red-500 font-semibold' : 'text-navy-400'">
              {{ (waMessagesQuota - waMessagesUsed).toLocaleString('es-DO') }} disponibles
            </span>
          </div>
        </div>

        <!-- Plan selection -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
          <div v-for="(plan, key) in waPlans" :key="key"
            :class="[
              'rounded-xl border-2 p-4 text-center transition-all',
              waPlan === key
                ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20'
                : 'border-navy-200 dark:border-navy-700 hover:border-teal-400',
            ]">
            <p class="font-semibold text-navy-900 dark:text-white">{{ plan.label }}</p>
            <p class="text-2xl font-bold text-teal-600 my-1">${{ plan.price }}<span class="text-sm font-normal text-navy-400">/mes</span></p>
            <p class="text-xs text-navy-500 dark:text-navy-400 mb-3">{{ plan.msgs.toLocaleString('es-DO') }} mensajes</p>
            <button
              @click="activatePlan(key)"
              :disabled="changingPlan || waPlan === key"
              :class="[
                'w-full text-xs py-1.5 rounded-lg font-medium transition-colors',
                waPlan === key
                  ? 'bg-teal-500 text-white cursor-default'
                  : 'btn-outline',
              ]">
              {{ waPlan === key ? 'Plan actual' : (changingPlan ? 'Procesando…' : 'Activar') }}
            </button>
          </div>
        </div>

        <!-- Cancel plan -->
        <div v-if="waPlan" class="flex items-center justify-between pt-3 border-t border-navy-100 dark:border-navy-700">
          <span class="text-xs text-navy-500 dark:text-navy-400">¿Ya no necesitas WhatsApp?</span>
          <button @click="activatePlan('none')" :disabled="changingPlan" class="text-xs text-red-500 hover:text-red-600 disabled:opacity-50">
            Cancelar plan
          </button>
        </div>
      </div>

      <!-- Subscription ID -->
      <div v-if="subscription?.paddle_id" class="card p-4">
        <p class="text-xs text-navy-500 dark:text-navy-400">
          ID de suscripción: <span class="font-mono text-navy-700 dark:text-navy-300">{{ subscription.paddle_id }}</span>
        </p>
      </div>

    </div>
  </AppLayout>
</template>
