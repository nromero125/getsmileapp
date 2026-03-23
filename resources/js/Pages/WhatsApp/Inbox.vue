<script setup>
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChatBubbleLeftRightIcon, UserCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  conversations: Array,
})

const page = usePage()

const selectedPhone = ref(null)
const messages = ref([])
const loadingThread = ref(false)
const patient = ref(null)

const selected = computed(() =>
  props.conversations.find(c => c.phone === selectedPhone.value)
)

const templateLabels = {
  appointment_confirmation: 'Confirmación de cita enviada',
  appointment_reminder:     'Recordatorio de cita enviado',
  invoice_ready:            'Notificación de factura enviada',
}

const formatTime = (ts) => {
  if (!ts) return ''
  const d = new Date(ts)
  const now = new Date()
  const isToday = d.toDateString() === now.toDateString()
  return isToday
    ? d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
    : d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' })
}

const formatFull = (ts) => {
  if (!ts) return ''
  return new Date(ts).toLocaleString('es-MX', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const lastMessagePreview = (c) => {
  if (c.last_direction === 'out') {
    return templateLabels[c.last_template] ?? 'Mensaje enviado'
  }
  return c.last_body ?? 'Mensaje recibido'
}

const openThread = async (phone) => {
  if (selectedPhone.value === phone) return
  selectedPhone.value = phone
  messages.value = []
  patient.value = null
  loadingThread.value = true

  try {
    const encodedPhone = encodeURIComponent(phone)
    const res = await fetch(route('whatsapp.thread', encodedPhone), {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      credentials: 'same-origin',
    })
    const data = await res.json()
    messages.value = data.messages ?? []
    patient.value = data.patient ?? null

    // Update unread count locally
    const conv = props.conversations.find(c => c.phone === phone)
    if (conv) conv.unread_count = 0
  } finally {
    loadingThread.value = false
  }
}
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Bandeja WhatsApp</h1>
    </template>
    <Head title="Bandeja WhatsApp" />

    <div class="flex h-[calc(100vh-10rem)] rounded-2xl overflow-hidden border border-navy-100 dark:border-navy-800 bg-white dark:bg-navy-900 shadow-sm">

      <!-- Conversation list -->
      <div class="w-full sm:w-80 flex-shrink-0 border-r border-navy-100 dark:border-navy-800 flex flex-col"
        :class="selectedPhone ? 'hidden sm:flex' : 'flex'">

        <div class="px-4 py-3 border-b border-navy-100 dark:border-navy-800">
          <p class="text-xs font-semibold text-navy-500 dark:text-navy-400 uppercase tracking-wide">
            {{ conversations.length }} conversación{{ conversations.length !== 1 ? 'es' : '' }}
          </p>
        </div>

        <div v-if="conversations.length === 0" class="flex-1 flex flex-col items-center justify-center gap-3 text-center p-8">
          <ChatBubbleLeftRightIcon class="w-10 h-10 text-navy-200 dark:text-navy-700" />
          <p class="text-sm text-navy-400">Aún no hay mensajes recibidos.</p>
          <p class="text-xs text-navy-400">Los pacientes que respondan tus notificaciones aparecerán aquí.</p>
        </div>

        <ul class="flex-1 overflow-y-auto divide-y divide-navy-50 dark:divide-navy-800">
          <li v-for="c in conversations" :key="c.phone">
            <button
              @click="openThread(c.phone)"
              :class="[
                'w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-navy-50 dark:hover:bg-navy-800 transition-colors',
                selectedPhone === c.phone ? 'bg-teal-50 dark:bg-teal-900/20' : '',
              ]">
              <div class="relative flex-shrink-0">
                <UserCircleIcon class="w-10 h-10 text-navy-200 dark:text-navy-700" />
                <span v-if="c.unread_count > 0"
                  class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-teal-500 text-white text-[10px] font-bold flex items-center justify-center">
                  {{ c.unread_count > 9 ? '9+' : c.unread_count }}
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-sm font-semibold text-navy-900 dark:text-white truncate">
                    {{ c.patient_name ?? c.phone }}
                  </p>
                  <span class="text-xs text-navy-400 flex-shrink-0">{{ formatTime(c.last_message_at) }}</span>
                </div>
                <p :class="['text-xs truncate mt-0.5', c.unread_count > 0 ? 'text-navy-700 dark:text-navy-200 font-medium' : 'text-navy-400']">
                  <span v-if="c.last_direction === 'out'" class="text-navy-300 dark:text-navy-500">↗ </span>
                  {{ lastMessagePreview(c) }}
                </p>
              </div>
            </button>
          </li>
        </ul>
      </div>

      <!-- Thread panel -->
      <div class="flex-1 flex flex-col min-w-0">

        <!-- Empty state -->
        <div v-if="!selectedPhone" class="flex-1 flex flex-col items-center justify-center gap-3 text-center p-8">
          <ChatBubbleLeftRightIcon class="w-12 h-12 text-navy-200 dark:text-navy-700" />
          <p class="text-sm text-navy-400">Selecciona una conversación para verla</p>
        </div>

        <template v-else>
          <!-- Thread header -->
          <div class="flex items-center gap-3 px-4 py-3 border-b border-navy-100 dark:border-navy-800">
            <button @click="selectedPhone = null" class="sm:hidden p-1 text-navy-400 hover:text-navy-900">
              ←
            </button>
            <UserCircleIcon class="w-9 h-9 text-navy-300 flex-shrink-0" />
            <div>
              <p class="font-semibold text-navy-900 dark:text-white text-sm">
                {{ selected?.patient_name ?? selected?.phone }}
              </p>
              <p v-if="selected?.patient_name" class="text-xs text-navy-400">{{ selected.phone }}</p>
              <Link v-if="selected?.patient_id" :href="route('patients.show', selected.patient_id)"
                class="text-xs text-teal-600 hover:underline">
                Ver perfil del paciente →
              </Link>
            </div>
          </div>

          <!-- Messages -->
          <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <div v-if="loadingThread" class="flex justify-center py-8">
              <div class="w-5 h-5 border-2 border-teal-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <template v-else>
              <div v-for="msg in messages" :key="msg.id"
                :class="['flex', msg.direction === 'out' ? 'justify-end' : 'justify-start']">
                <div :class="[
                  'max-w-xs lg:max-w-sm px-3 py-2 rounded-2xl text-sm',
                  msg.direction === 'out'
                    ? 'bg-teal-500 text-white rounded-br-sm'
                    : 'bg-navy-100 dark:bg-navy-800 text-navy-900 dark:text-white rounded-bl-sm',
                ]">
                  <p v-if="msg.direction === 'in'">{{ msg.body }}</p>
                  <p v-else class="italic opacity-90">{{ templateLabels[msg.template] ?? 'Mensaje enviado' }}</p>
                  <p :class="['text-xs mt-1', msg.direction === 'out' ? 'text-teal-100' : 'text-navy-400']">
                    {{ formatFull(msg.created_at) }}
                  </p>
                </div>
              </div>
            </template>
          </div>

          <!-- Footer note -->
          <div class="px-4 py-2 border-t border-navy-100 dark:border-navy-800">
            <p class="text-xs text-navy-400 text-center">
              Las respuestas se envían desde el panel de citas. Solo se pueden enviar plantillas aprobadas por Meta.
            </p>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>
</template>
