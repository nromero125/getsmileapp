<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import {
  ArrowLeftIcon, PencilIcon, CalendarIcon, PaperClipIcon,
  CloudArrowUpIcon, TrashIcon, ArrowDownTrayIcon, EyeIcon,
  XMarkIcon, DocumentIcon, PhotoIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  patient: Object,
  recent_appointments: Array,
  tooth_diagnoses: Object,  // keyed by tooth_number
  clinical_notes: Array,
  invoices: Array,
  media: Array,
})

const can = usePage().props.can

const activeTab = ref('overview')
const tabs = computed(() => [
  { id: 'overview',     label: 'Resumen' },
  { id: 'appointments', label: 'Citas' },
  ...(can.clinical ? [{ id: 'odontogram', label: 'Odontograma' }] : []),
  { id: 'billing',      label: 'Facturación' },
  { id: 'files',        label: 'Archivos', count: computed(() => props.media?.length) },
])

// ── File upload ──────────────────────────────────────────────
const isDragging    = ref(false)
const uploadForm    = useForm({ file: null, collection: 'documents', custom_name: '', notes: '' })
const fileInput     = ref(null)
const previewFile   = ref(null)
const deleteTarget  = ref(null)
const activeFilter  = ref('all')

const collectionOptions = [
  { value: 'xrays',     label: 'Radiografía', icon: '🦷', accept: 'image/*,application/pdf' },
  { value: 'photos',    label: 'Foto',        icon: '📷', accept: 'image/*' },
  { value: 'documents', label: 'Documento',   icon: '📄', accept: '.pdf,.doc,.docx,application/pdf' },
  { value: 'other',     label: 'Otro',        icon: '📎', accept: '*' },
]

const collectionColors = {
  xrays:     'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
  photos:    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  documents: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  other:     'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
}

const filteredMedia = computed(() => {
  if (activeFilter.value === 'all') return props.media || []
  return (props.media || []).filter(f => f.collection === activeFilter.value)
})

const onDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) assignFile(file)
}

const onFileChange = (e) => {
  const file = e.target.files?.[0]
  if (file) assignFile(file)
}

const assignFile = (file) => {
  uploadForm.file = file
  // Auto-detect collection from mime
  if (file.type.startsWith('image/')) {
    uploadForm.collection = 'photos'
  } else if (file.type === 'application/pdf' || file.type.includes('word')) {
    uploadForm.collection = 'documents'
  }
  if (!uploadForm.custom_name) {
    uploadForm.custom_name = file.name.replace(/\.[^/.]+$/, '')
  }
}

const submitUpload = () => {
  uploadForm.post(route('patients.files.store', props.patient.id), {
    forceFormData: true,
    onSuccess: () => {
      uploadForm.reset()
      if (fileInput.value) fileInput.value.value = ''
    },
  })
}

const confirmDelete = (item) => deleteTarget.value = item
const doDelete = () => {
  router.delete(route('patients.files.destroy', [props.patient.id, deleteTarget.value.id]), {
    onFinish: () => deleteTarget.value = null,
  })
}

// ── Mini odontogram helpers ───────────────────────────────────
const upperTeeth = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16]
const lowerTeeth = [32,31,30,29,28,27,26,25,24,23,22,21,20,19,18,17]

const toothColor = (num) => {
  const list = props.tooth_diagnoses?.[num]
  if (!list?.length) return '#E5E7EB'
  return list[0].catalog?.color || '#E5E7EB'
}

const toothHasDiagnosis = (num) => !!(props.tooth_diagnoses?.[num]?.length)

const diagnosedTeeth = computed(() =>
  Object.keys(props.tooth_diagnoses || {}).map(Number).sort((a, b) => a - b)
)

const recentDiagnoses = computed(() =>
  Object.values(props.tooth_diagnoses || {})
    .flat()
    .sort((a, b) => new Date(b.diagnosed_at) - new Date(a.diagnosed_at))
    .slice(0, 6)
)

const formatFileSize = (bytes) => {
  if (!bytes) return '—'
  return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : (bytes / 1024).toFixed(0) + ' KB'
}
const formatDate = (d) => d ? new Date(d).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'
const formatCurrency = (v) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP' }).format(v || 0)
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('patients.index')" class="btn-ghost p-1.5"><ArrowLeftIcon class="w-4 h-4" /></Link>
        <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">
          {{ patient.first_name }} {{ patient.last_name }}
        </h1>
      </div>
    </template>
    <Head :title="`${patient.first_name} ${patient.last_name}`" />

    <!-- Patient Header Card -->
    <div class="card p-6 mb-6">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(patient.first_name+' '+patient.last_name)}&background=0F1F3D&color=00BFA6&size=80`"
          class="w-20 h-20 rounded-2xl flex-shrink-0" />
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-display font-bold text-navy-900 dark:text-white">{{ patient.first_name }} {{ patient.last_name }}</h2>
          <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm text-navy-500">
            <span v-if="patient.date_of_birth">{{ formatDate(patient.date_of_birth) }} ({{ patient.age }} años)</span>
            <span v-if="patient.gender" class="capitalize">{{ patient.gender }}</span>
            <span v-if="patient.blood_type" class="text-red-600 font-semibold">{{ patient.blood_type }}</span>
          </div>
          <div class="flex flex-wrap gap-3 mt-2 text-sm text-navy-600 dark:text-navy-400">
            <span v-if="patient.phone">📞 {{ patient.phone }}</span>
            <span v-if="patient.email">✉️ {{ patient.email }}</span>
          </div>
        </div>
        <div class="flex gap-2 flex-shrink-0">
          <Link :href="route('appointments.index')" class="btn-primary">
            <CalendarIcon class="w-4 h-4" /> Agendar
          </Link>
          <Link :href="route('patients.edit', patient.id)" class="btn-outline">
            <PencilIcon class="w-4 h-4" /> Editar
          </Link>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-navy-100 dark:bg-navy-800 rounded-xl p-1 mb-6 overflow-x-auto">
      <button v-for="tab in tabs" :key="tab.id"
        @click="activeTab = tab.id"
        :class="['px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap flex items-center gap-1.5',
          activeTab === tab.id ? 'bg-white dark:bg-navy-900 text-navy-900 dark:text-white shadow-sm' : 'text-navy-500 hover:text-navy-700']">
        {{ tab.label }}
        <span v-if="tab.count?.value" class="text-xs bg-teal-100 text-teal-700 px-1.5 py-0.5 rounded-full font-semibold">
          {{ tab.count.value }}
        </span>
      </button>
    </div>

    <!-- Overview Tab -->
    <div v-show="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="card p-6">
        <h3 class="font-display font-semibold text-navy-900 dark:text-white mb-3">Contacto y dirección</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Teléfono</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.phone || '—' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Teléfono alternativo</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.phone_alt || '—' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Correo</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.email || '—' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Dirección</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.address || '—' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Ciudad</dt><dd class="text-navy-700 dark:text-navy-300">{{ [patient.city, patient.state, patient.zip_code].filter(Boolean).join(', ') || '—' }}</dd></div>
        </dl>
      </div>
      <div class="card p-6">
        <h3 class="font-display font-semibold text-navy-900 dark:text-white mb-3">Información médica</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Tipo de sangre</dt><dd class="font-semibold text-red-600">{{ patient.blood_type || '—' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Alergias</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.allergies || 'Ninguna reportada' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Notas médicas</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.medical_notes || '—' }}</dd></div>
        </dl>
      </div>
      <div class="card p-6">
        <h3 class="font-display font-semibold text-navy-900 dark:text-white mb-3">Seguro médico</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Aseguradora</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.insurance_provider || 'Ninguno' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Póliza #</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.insurance_policy_number || '—' }}</dd></div>
        </dl>
      </div>
      <div class="card p-6">
        <h3 class="font-display font-semibold text-navy-900 dark:text-white mb-3">Contacto de emergencia</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Nombre</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.emergency_contact_name || '—' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Teléfono</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.emergency_contact_phone || '—' }}</dd></div>
          <div class="flex gap-2"><dt class="w-28 text-navy-400 flex-shrink-0">Parentesco</dt><dd class="text-navy-700 dark:text-navy-300">{{ patient.emergency_contact_relation || '—' }}</dd></div>
        </dl>
      </div>
    </div>

    <!-- Appointments Tab -->
    <div v-show="activeTab === 'appointments'" class="card">
      <div class="p-4 border-b border-navy-100 dark:border-navy-800">
        <h3 class="font-semibold text-navy-900 dark:text-white">Historial de citas</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-navy-100 dark:border-navy-800 text-xs uppercase tracking-wide text-navy-500">
              <th class="text-left px-4 py-3 font-semibold">Fecha y hora</th>
              <th class="text-left px-4 py-3 font-semibold">Dentista</th>
              <th class="text-left px-4 py-3 font-semibold">Tratamiento</th>
              <th class="text-left px-4 py-3 font-semibold">Estado</th>
              <th class="text-right px-4 py-3 font-semibold">Costo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!recent_appointments?.length">
              <td colspan="5" class="px-4 py-12 text-center text-navy-400 text-sm">Sin citas aún</td>
            </tr>
            <tr v-for="appt in recent_appointments" :key="appt.id" class="table-row">
              <td class="px-4 py-3">
                <p class="font-medium text-navy-900 dark:text-white">{{ formatDate(appt.appointment_date) }}</p>
                <p class="text-xs text-navy-400">{{ new Date(appt.appointment_date).toLocaleTimeString('es-ES', {hour:'2-digit',minute:'2-digit'}) }}</p>
              </td>
              <td class="px-4 py-3 text-navy-700 dark:text-navy-300">{{ appt.dentist?.name }}</td>
              <td class="px-4 py-3 text-navy-700 dark:text-navy-300">{{ appt.treatments?.[0]?.name || appt.reason || '—' }}</td>
              <td class="px-4 py-3"><StatusBadge :status="appt.status" /></td>
              <td class="px-4 py-3 text-right font-medium text-navy-900 dark:text-white">{{ formatCurrency(appt.total_cost) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Odontogram Tab -->
    <div v-show="activeTab === 'odontogram'" class="space-y-4">
      <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-display font-semibold text-navy-900 dark:text-white">Mapa dental</h3>
            <p class="text-xs text-navy-400 mt-0.5">
              {{ diagnosedTeeth.length ? `${diagnosedTeeth.length} pieza(s) con diagnóstico` : 'Sin diagnósticos registrados' }}
            </p>
          </div>
          <Link :href="route('patients.odontogram', patient.id)" class="btn-primary">
            Abrir odontograma
          </Link>
        </div>

        <!-- Mini odontogram -->
        <div class="overflow-x-auto">
          <div class="min-w-[520px]">
            <p class="text-[10px] font-semibold text-navy-400 uppercase tracking-widest mb-1.5 text-center">Superior</p>
            <div class="flex justify-center gap-0.5 mb-0.5">
              <button v-for="num in upperTeeth" :key="num"
                @click="$inertia.visit(route('patients.odontogram', patient.id))"
                class="group flex flex-col items-center gap-0.5"
                :title="toothHasDiagnosis(num) ? `#${num}: ${tooth_diagnoses[num]?.map(d => d.catalog?.name).join(', ')}` : `#${num}`">
                <span class="text-[9px] text-navy-300 group-hover:text-navy-600">{{ num }}</span>
                <svg viewBox="0 0 30 42" class="w-6 h-8">
                  <path d="M15 2 C10 2 6 5.5 4.5 10 C3 14.5 3.5 20 4.5 25 C5.5 30 7 36 10 38 C13 40 15 36 15 36 C15 36 17 40 20 38 C23 36 24.5 30 25.5 25 C26.5 20 27 14.5 25.5 10 C24 5.5 20 2 15 2Z"
                    :fill="toothColor(num)"
                    :stroke="toothHasDiagnosis(num) ? '#9CA3AF' : '#E5E7EB'"
                    stroke-width="1"
                    class="transition-colors group-hover:opacity-75"
                  />
                </svg>
              </button>
            </div>

            <div class="flex items-center gap-2 my-2">
              <div class="flex-1 h-px bg-navy-100 dark:bg-navy-800"></div>
              <span class="text-[9px] text-navy-300 px-1">LÍNEA MEDIA</span>
              <div class="flex-1 h-px bg-navy-100 dark:bg-navy-800"></div>
            </div>

            <div class="flex justify-center gap-0.5 mb-0.5">
              <button v-for="num in lowerTeeth" :key="num"
                @click="$inertia.visit(route('patients.odontogram', patient.id))"
                class="group flex flex-col items-center gap-0.5"
                :title="toothHasDiagnosis(num) ? `#${num}: ${tooth_diagnoses[num]?.map(d => d.catalog?.name).join(', ')}` : `#${num}`">
                <svg viewBox="0 0 30 42" class="w-6 h-8" style="transform:scaleY(-1)">
                  <path d="M15 2 C10 2 6 5.5 4.5 10 C3 14.5 3.5 20 4.5 25 C5.5 30 7 36 10 38 C13 40 15 36 15 36 C15 36 17 40 20 38 C23 36 24.5 30 25.5 25 C26.5 20 27 14.5 25.5 10 C24 5.5 20 2 15 2Z"
                    :fill="toothColor(num)"
                    :stroke="toothHasDiagnosis(num) ? '#9CA3AF' : '#E5E7EB'"
                    stroke-width="1"
                    class="transition-colors group-hover:opacity-75"
                  />
                </svg>
                <span class="text-[9px] text-navy-300 group-hover:text-navy-600">{{ num }}</span>
              </button>
            </div>
            <p class="text-[10px] font-semibold text-navy-400 uppercase tracking-widest mt-1.5 text-center">Inferior</p>
          </div>
        </div>
      </div>

      <!-- Recent diagnoses summary -->
      <div v-if="recentDiagnoses.length" class="card">
        <div class="p-4 border-b border-navy-100 dark:border-navy-800">
          <h3 class="font-semibold text-navy-900 dark:text-white text-sm">Diagnósticos recientes</h3>
        </div>
        <div class="divide-y divide-navy-100 dark:divide-navy-800">
          <div v-for="d in recentDiagnoses" :key="d.id" class="flex items-center gap-3 px-4 py-3">
            <div class="w-3 h-3 rounded-full flex-shrink-0" :style="`background:${d.catalog?.color}`"></div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-navy-900 dark:text-white">
                Diente #{{ d.tooth_number }} — {{ d.catalog?.name }}
              </p>
              <p v-if="d.notes" class="text-xs text-navy-400 truncate">{{ d.notes }}</p>
            </div>
            <span class="text-xs text-navy-400 whitespace-nowrap flex-shrink-0">
              {{ d.diagnosed_at ? new Date(d.diagnosed_at).toLocaleDateString('es-ES', { day:'numeric', month:'short' }) : '' }}
            </span>
          </div>
        </div>
      </div>

      <div v-else class="card p-8 text-center text-navy-400">
        <p class="text-sm">Sin diagnósticos aún. Abre el odontograma completo para registrarlos.</p>
      </div>
    </div>

    <!-- Billing Tab -->
    <div v-show="activeTab === 'billing'" class="card">
      <div class="p-4 border-b border-navy-100 dark:border-navy-800 flex items-center justify-between">
        <h3 class="font-semibold text-navy-900 dark:text-white">Facturas</h3>
        <Link v-if="can.billing" :href="route('invoices.create', { patient_id: patient.id })" class="btn-primary">+ Nueva Factura</Link>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead><tr class="border-b border-navy-100 dark:border-navy-800">
            <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Factura #</th>
            <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Fecha</th>
            <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Estado</th>
            <th class="text-right px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Total</th>
          </tr></thead>
          <tbody>
            <tr v-if="!invoices?.length"><td colspan="4" class="px-4 py-8 text-center text-navy-400 text-sm">Sin facturas aún</td></tr>
            <tr v-for="inv in invoices" :key="inv.id" class="table-row">
              <td class="px-4 py-3"><Link :href="route('invoices.show', inv.id)" class="font-medium text-teal-600 hover:underline">{{ inv.invoice_number }}</Link></td>
              <td class="px-4 py-3 text-navy-600 dark:text-navy-300">{{ formatDate(inv.invoice_date) }}</td>
              <td class="px-4 py-3"><StatusBadge :status="inv.status" type="invoice" /></td>
              <td class="px-4 py-3 text-right font-semibold text-navy-900 dark:text-white">{{ formatCurrency(inv.total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ─── Files Tab ──────────────────────────────────────────── -->
    <div v-show="activeTab === 'files'" class="space-y-4">

      <!-- Upload Card -->
      <div class="card p-6">
        <h3 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Subir archivo</h3>

        <form @submit.prevent="submitUpload" class="space-y-4">
          <!-- Drop zone -->
          <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
            @click="fileInput.click()"
            :class="['relative border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all duration-200',
              isDragging
                ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20 scale-[1.01]'
                : uploadForm.file
                  ? 'border-teal-400 bg-teal-50/50 dark:bg-teal-900/10'
                  : 'border-navy-200 dark:border-navy-700 hover:border-teal-400 hover:bg-navy-50 dark:hover:bg-navy-800/50']">
            <input ref="fileInput" type="file" class="hidden"
              accept="image/*,application/pdf,.doc,.docx"
              @change="onFileChange" />

            <div v-if="!uploadForm.file" class="flex flex-col items-center gap-3">
              <div class="w-12 h-12 bg-navy-100 dark:bg-navy-800 rounded-2xl flex items-center justify-center">
                <CloudArrowUpIcon class="w-6 h-6 text-navy-400" />
              </div>
              <div>
                <p class="font-medium text-navy-700 dark:text-navy-300">Arrastra un archivo aquí o haz clic para seleccionar</p>
                <p class="text-xs text-navy-400 mt-1">Imágenes, PDF, Word — máx. 20 MB</p>
              </div>
            </div>

            <div v-else class="flex items-center gap-3">
              <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <PhotoIcon v-if="uploadForm.file.type.startsWith('image/')" class="w-5 h-5 text-teal-600" />
                <DocumentIcon v-else class="w-5 h-5 text-teal-600" />
              </div>
              <div class="text-left flex-1 min-w-0">
                <p class="font-medium text-navy-900 dark:text-white truncate">{{ uploadForm.file.name }}</p>
                <p class="text-xs text-navy-400">{{ formatFileSize(uploadForm.file.size) }}</p>
              </div>
              <button type="button" @click.stop="uploadForm.file = null; fileInput.value = ''"
                class="p-1 rounded-lg hover:bg-red-100 text-navy-400 hover:text-red-500">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Upload options -->
          <div v-if="uploadForm.file" class="grid grid-cols-1 sm:grid-cols-3 gap-3 animate-slide-up">
            <!-- Collection -->
            <div>
              <label class="label">Tipo</label>
              <div class="grid grid-cols-2 gap-1.5">
                <label v-for="opt in collectionOptions" :key="opt.value"
                  :class="['flex items-center gap-1.5 px-2.5 py-2 rounded-xl border cursor-pointer text-xs font-medium transition-all',
                    uploadForm.collection === opt.value
                      ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20 text-teal-700'
                      : 'border-navy-200 dark:border-navy-700 text-navy-600 hover:border-navy-400']">
                  <input type="radio" :value="opt.value" v-model="uploadForm.collection" class="sr-only" />
                  <span>{{ opt.icon }}</span>
                  {{ opt.label }}
                </label>
              </div>
            </div>

            <!-- Name -->
            <div>
              <label class="label">Nombre de visualización</label>
              <input v-model="uploadForm.custom_name" type="text" class="input" placeholder="Nombre opcional…" />
            </div>

            <!-- Notes -->
            <div>
              <label class="label">Notas</label>
              <input v-model="uploadForm.notes" type="text" class="input" placeholder="Notas clínicas…" />
            </div>
          </div>

          <div v-if="uploadForm.file" class="flex items-center justify-end gap-3">
            <button type="button" @click="uploadForm.reset(); fileInput.value = ''" class="btn-outline">Cancelar</button>
            <button type="submit" class="btn-primary" :disabled="uploadForm.processing">
              <CloudArrowUpIcon v-if="!uploadForm.processing" class="w-4 h-4" />
              <span v-if="uploadForm.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              {{ uploadForm.processing ? 'Subiendo…' : 'Subir archivo' }}
            </button>
          </div>

          <p v-if="uploadForm.errors.file" class="text-xs text-red-500">{{ uploadForm.errors.file }}</p>
        </form>
      </div>

      <!-- Files List -->
      <div class="card overflow-hidden">
        <!-- Filter bar -->
        <div class="px-4 py-3 border-b border-navy-100 dark:border-navy-800 flex items-center gap-2 flex-wrap">
          <button v-for="f in ['all', 'xrays', 'photos', 'documents', 'other']" :key="f"
            @click="activeFilter = f"
            :class="['px-3 py-1 rounded-lg text-xs font-medium transition-colors capitalize',
              activeFilter === f
                ? 'bg-navy-900 text-white'
                : 'text-navy-500 hover:bg-navy-100 dark:hover:bg-navy-800']">
            {{ f === 'all' ? `Todos (${media?.length || 0})` : f }}
          </button>
        </div>

        <!-- Empty state -->
        <div v-if="!filteredMedia.length" class="flex flex-col items-center justify-center py-16 text-center px-4">
          <div class="w-14 h-14 bg-navy-100 dark:bg-navy-800 rounded-2xl flex items-center justify-center mb-3">
            <PaperClipIcon class="w-7 h-7 text-navy-400" />
          </div>
          <p class="font-medium text-navy-700 dark:text-navy-300">Sin archivos aún</p>
          <p class="text-sm text-navy-400 mt-1">Sube radiografías, fotos o documentos arriba</p>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
          <div v-for="file in filteredMedia" :key="file.id"
            class="group relative border border-navy-100 dark:border-navy-800 rounded-2xl overflow-hidden hover:border-teal-300 hover:shadow-card-md transition-all duration-200">

            <!-- Image thumbnail OR icon -->
            <div class="relative bg-navy-50 dark:bg-navy-800 h-32 flex items-center justify-center overflow-hidden">
              <img v-if="file.is_image" :src="file.thumb_url || file.url"
                class="h-full w-full object-cover" :alt="file.name" />
              <div v-else class="flex flex-col items-center gap-2">
                <DocumentIcon class="w-10 h-10 text-navy-300" />
                <span class="text-xs text-navy-400 font-mono uppercase">
                  {{ file.file_name.split('.').pop() }}
                </span>
              </div>

              <!-- Hover overlay -->
              <div class="absolute inset-0 bg-navy-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <a :href="file.url" target="_blank" class="p-2 bg-white rounded-xl hover:bg-teal-50" title="Ver">
                  <EyeIcon class="w-4 h-4 text-navy-700" />
                </a>
                <a :href="route('patients.files.download', [patient.id, file.id])" class="p-2 bg-white rounded-xl hover:bg-teal-50" title="Descargar">
                  <ArrowDownTrayIcon class="w-4 h-4 text-navy-700" />
                </a>
                <button @click="confirmDelete(file)" class="p-2 bg-white rounded-xl hover:bg-red-50" title="Eliminar">
                  <TrashIcon class="w-4 h-4 text-red-500" />
                </button>
              </div>
            </div>

            <!-- File info -->
            <div class="p-3">
              <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-medium text-navy-900 dark:text-white truncate">{{ file.name }}</p>
                <span :class="['badge text-xs flex-shrink-0 capitalize', collectionColors[file.collection]]">
                  {{ file.collection === 'xrays' ? 'Radiografía' : file.collection }}
                </span>
              </div>
              <p class="text-xs text-navy-400 mt-1">{{ formatFileSize(file.size) }} · {{ file.created_at }}</p>
              <p v-if="file.notes" class="text-xs text-navy-500 mt-1 italic truncate">{{ file.notes }}</p>
              <p v-if="file.uploader_name" class="text-xs text-navy-400 mt-0.5">Por {{ file.uploader_name }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- ──────────────────────────────────────────────────────── -->

  </AppLayout>

  <!-- Delete confirm -->
  <ConfirmModal
    :show="!!deleteTarget"
    title="Eliminar archivo"
    :message="`¿Eliminar &quot;${deleteTarget?.name}&quot;? Esta acción no se puede deshacer.`"
    @confirm="doDelete"
    @cancel="deleteTarget = null"
  />
</template>
