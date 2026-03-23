<script setup>
import { ref, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { PlusIcon, XMarkIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  appointments: Array,
  dentists: Array,
  patients: Array,
  treatments: Array,
})

const calendar = ref(null)
const showNewModal = ref(false)
const selectedEvent = ref(null)
const showEventModal = ref(false)

const form = useForm({
  patient_id: '',
  dentist_id: '',
  appointment_date: '',
  duration_minutes: 30,
  reason: '',
  notes: '',
  status: 'scheduled',
  treatment_ids: [],
})

let calendarInstance = null

onMounted(async () => {
  const { Calendar } = await import('@fullcalendar/core')
  const dayGridPlugin = (await import('@fullcalendar/daygrid')).default
  const timeGridPlugin = (await import('@fullcalendar/timegrid')).default
  const interactionPlugin = (await import('@fullcalendar/interaction')).default
  const listPlugin = (await import('@fullcalendar/list')).default

  calendarInstance = new Calendar(calendar.value, {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
    initialView: 'timeGridWeek',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
    },
    height: 'auto',
    slotMinTime: '07:00:00',
    slotMaxTime: '20:00:00',
    slotDuration: '00:30:00',
    nowIndicator: true,
    businessHours: {
      daysOfWeek: [1, 2, 3, 4, 5, 6],
      startTime: '08:00',
      endTime: '18:00',
    },
    events: (info, successCallback, failureCallback) => {
      fetch(
        `/api/appointments/calendar?start=${info.startStr}&end=${info.endStr}`,
        { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }
      )
        .then(r => r.json())
        .then(data => successCallback(data))
        .catch(err => failureCallback(err))
    },
    eventClick: (info) => {
      selectedEvent.value = {
        id: info.event.id,
        title: info.event.title,
        start: info.event.start,
        end: info.event.end,
        ...info.event.extendedProps,
      }
      showEventModal.value = true
    },
    dateClick: (info) => {
      form.appointment_date = info.dateStr.includes('T')
        ? info.dateStr.slice(0, 16)
        : info.dateStr + 'T09:00'
      showNewModal.value = true
    },
    eventContent: (arg) => {
      const inactive = arg.event.extendedProps.dentist_inactive
      const dentistLabel = (arg.event.extendedProps.dentist || '') + (inactive ? ' (Inactivo)' : '')
      return {
        html: `<div class="fc-event-custom">
          <div class="font-medium truncate">${arg.event.title}</div>
          <div class="text-xs opacity-80">${dentistLabel}</div>
        </div>`
      }
    },
  })
  calendarInstance.render()
})

const submitAppointment = () => {
  form.post(route('appointments.store'), {
    onSuccess: () => {
      showNewModal.value = false
      form.reset()
      calendarInstance?.refetchEvents()
    }
  })
}

const updateStatus = (id, status) => {
  router.put(route('appointments.update', id), { status }, {
    onSuccess: () => {
      showEventModal.value = false
      calendarInstance?.refetchEvents()
    }
  })
}

const sendingConfirmation = ref(false)
const sendConfirmation = (id) => {
  sendingConfirmation.value = true
  router.post(route('appointments.send-confirmation', id), {}, {
    onFinish: () => {
      sendingConfirmation.value = false
      showEventModal.value = false
      calendarInstance?.refetchEvents()
    }
  })
}

const getTreatmentDuration = () => {
  if (!form.treatment_ids.length) return
  const t = props.treatments.find(t => t.id === form.treatment_ids[0])
  if (t) form.duration_minutes = t.duration_minutes
}
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Citas</h1>
    </template>
    <Head title="Citas" />

    <div class="card p-4">
      <!-- Calendar controls bar -->
      <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-navy-500">Haz clic en una fecha/hora para agregar una cita</p>
        <button @click="showNewModal = true" class="btn-primary">
          <PlusIcon class="w-4 h-4" /> Nueva Cita
        </button>
      </div>

      <!-- Legend -->
      <div class="flex flex-wrap gap-3 mb-4">
        <span v-for="(color, status) in { 'Programada': '#3B82F6', 'Confirmada': '#00BFA6', 'En curso': '#F59E0B', 'Completada': '#10B981', 'Cancelada': '#EF4444' }"
          :key="status" class="flex items-center gap-1.5 text-xs text-navy-600">
          <span class="w-3 h-3 rounded-sm" :style="`background: ${color}`"></span>
          {{ status }}
        </span>
      </div>

      <!-- Calendar -->
      <div ref="calendar" class="fc-dentaris"></div>
    </div>

    <!-- New Appointment Modal -->
    <Modal :show="showNewModal" title="Nueva Cita" size="lg" @close="showNewModal = false; form.reset()">
      <form @submit.prevent="submitAppointment" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Paciente *</label>
            <select v-model="form.patient_id" class="input" required>
              <option value="">Seleccionar paciente…</option>
              <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
            </select>
            <p v-if="form.errors.patient_id" class="text-xs text-red-500 mt-1">{{ form.errors.patient_id }}</p>
          </div>
          <div>
            <label class="label">Dentista *</label>
            <select v-model="form.dentist_id" class="input" required>
              <option value="">Seleccionar dentista…</option>
              <option v-for="d in dentists.filter(d => d.is_active)" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Fecha y hora *</label>
            <input v-model="form.appointment_date" type="datetime-local" class="input" required />
            <p v-if="form.errors.appointment_date" class="text-xs text-red-500 mt-1">{{ form.errors.appointment_date }}</p>
          </div>
          <div>
            <label class="label">Duración (minutos)</label>
            <input v-model="form.duration_minutes" type="number" min="15" step="15" class="input" />
          </div>
          <div>
            <label class="label">Estado</label>
            <select v-model="form.status" class="input">
              <option value="scheduled">Programada</option>
              <option value="confirmed">Confirmada</option>
            </select>
          </div>
          <div>
            <label class="label">Motivo</label>
            <input v-model="form.reason" type="text" class="input" placeholder="Limpieza, revisión…" />
          </div>
          <div class="sm:col-span-2">
            <label class="label">Tratamientos</label>
            <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 border border-navy-200 rounded-xl">
              <label v-for="t in treatments" :key="t.id"
                class="flex items-center gap-2 p-2 rounded-lg hover:bg-navy-50 cursor-pointer">
                <input type="checkbox" :value="t.id" v-model="form.treatment_ids" @change="getTreatmentDuration"
                  class="rounded text-teal-500 focus:ring-teal-500" />
                <span class="text-sm text-navy-700">{{ t.name }}</span>
                <span class="text-xs text-navy-400 ml-auto">RD${{ t.default_price.toLocaleString('es-DO') }}</span>
              </label>
            </div>
          </div>
          <div class="sm:col-span-2">
            <label class="label">Notas</label>
            <textarea v-model="form.notes" rows="2" class="input" placeholder="Notas adicionales…" />
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showNewModal = false; form.reset()" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="form.processing">
            {{ form.processing ? 'Guardando…' : 'Agendar Cita' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Event Detail Modal -->
    <Modal :show="showEventModal" :title="selectedEvent?.patient || 'Cita'" size="md" @close="showEventModal = false">
      <div v-if="selectedEvent" class="space-y-4">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div><p class="text-navy-400 text-xs uppercase tracking-wide mb-1">Paciente</p><p class="font-semibold text-navy-900 dark:text-white">{{ selectedEvent.patient }}</p></div>
          <div>
            <p class="text-navy-400 text-xs uppercase tracking-wide mb-1">Dentista</p>
            <p class="font-semibold text-navy-900 dark:text-white flex items-center gap-2">
              {{ selectedEvent.dentist }}
              <span v-if="selectedEvent.dentist_inactive" class="text-xs font-normal text-amber-600 bg-amber-50 dark:bg-amber-900/20 px-1.5 py-0.5 rounded">Inactivo</span>
            </p>
          </div>
          <div><p class="text-navy-400 text-xs uppercase tracking-wide mb-1">Fecha</p><p class="font-semibold text-navy-900 dark:text-white">{{ selectedEvent.start?.toLocaleDateString('es-MX', { weekday:'short', month:'short', day:'numeric' }) }}</p></div>
          <div><p class="text-navy-400 text-xs uppercase tracking-wide mb-1">Hora</p><p class="font-semibold text-navy-900 dark:text-white">{{ selectedEvent.start?.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' }) }}</p></div>
          <div><p class="text-navy-400 text-xs uppercase tracking-wide mb-1">Estado</p><StatusBadge :status="selectedEvent.status" /></div>
          <div><p class="text-navy-400 text-xs uppercase tracking-wide mb-1">Duración</p><p class="font-semibold text-navy-900 dark:text-white">{{ selectedEvent.duration }} min</p></div>
          <div v-if="selectedEvent.reason" class="col-span-2"><p class="text-navy-400 text-xs uppercase tracking-wide mb-1">Motivo</p><p class="text-navy-700 dark:text-navy-300">{{ selectedEvent.reason }}</p></div>
        </div>

        <!-- Confirmation email -->
        <div v-if="!['completed','cancelled','no_show','confirmed'].includes(selectedEvent.status)"
          class="border-t border-navy-100 dark:border-navy-800 pt-3">
          <p class="text-xs text-navy-400 uppercase tracking-wide mb-2">Confirmación por correo</p>
          <div v-if="selectedEvent.patient_email" class="flex items-center justify-between gap-3">
            <div>
              <p class="text-sm text-navy-700 dark:text-navy-300 truncate">{{ selectedEvent.patient_email }}</p>
              <p v-if="selectedEvent.confirmation_sent_at" class="text-xs text-navy-400 mt-0.5">
                Enviado {{ new Date(selectedEvent.confirmation_sent_at).toLocaleDateString('es-MX', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }) }}
              </p>
            </div>
            <button @click="sendConfirmation(selectedEvent.id)" :disabled="sendingConfirmation"
              class="btn-outline flex-shrink-0 text-xs gap-1.5 disabled:opacity-50">
              <EnvelopeIcon class="w-3.5 h-3.5" />
              {{ selectedEvent.confirmation_sent_at ? 'Reenviar' : 'Enviar' }}
            </button>
          </div>
          <p v-else class="text-xs text-amber-600 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg">
            El paciente no tiene correo electrónico registrado.
          </p>
        </div>

        <!-- Status Actions -->
        <div class="border-t border-navy-100 dark:border-navy-800 pt-3">
          <p class="text-xs text-navy-400 uppercase tracking-wide mb-2">Actualizar Estado</p>
          <div class="flex flex-wrap gap-2">
            <button v-for="(label, s) in { confirmed: 'Confirmada', in_progress: 'En curso', completed: 'Completada', cancelled: 'Cancelada', no_show: 'No se presentó' }" :key="s"
              @click="updateStatus(selectedEvent.id, s)"
              :disabled="selectedEvent.status === s"
              class="btn-outline text-xs py-1 disabled:opacity-40">
              {{ label }}
            </button>
          </div>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<style>
.fc-dentaris .fc-toolbar-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.1rem;
  font-weight: 600;
  color: #0F1F3D;
}
.dark .fc-dentaris .fc-toolbar-title { color: #FAFAF7; }
.fc-dentaris .fc-button {
  background: #0F1F3D !important;
  border-color: #0F1F3D !important;
  font-family: 'DM Sans', sans-serif !important;
  font-size: 0.8rem !important;
  border-radius: 0.5rem !important;
  padding: 0.35rem 0.75rem !important;
}
.fc-dentaris .fc-button:hover { background: #00BFA6 !important; border-color: #00BFA6 !important; }
.fc-dentaris .fc-button-active { background: #00BFA6 !important; border-color: #00BFA6 !important; }
.fc-dentaris .fc-event { border-radius: 6px !important; border: none !important; padding: 2px 6px !important; font-size: 0.78rem !important; }
.fc-dentaris .fc-event-custom { padding: 1px 2px; }
.fc-dentaris .fc-daygrid-day-number,
.fc-dentaris .fc-col-header-cell-cushion { color: #374151; font-family: 'DM Sans', sans-serif; font-size: 0.8rem; }
.dark .fc-dentaris .fc-daygrid-day-number,
.dark .fc-dentaris .fc-col-header-cell-cushion { color: #D1D5DB; }
.fc-dentaris .fc-timegrid-slot-label { font-size: 0.75rem; color: #9CA3AF; }
.fc-dentaris .fc-now-indicator-line { border-color: #00BFA6 !important; }
</style>
