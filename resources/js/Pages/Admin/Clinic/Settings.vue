<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { BuildingOfficeIcon, ArrowUpTrayIcon, TrashIcon, PlusIcon, PencilIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  clinic: Object,
  ncfSequences: Array,
})

// ── Clinic form ────────────────────────────────────────────────────────────
const logoInput = ref(null)
const logoPreview = ref(props.clinic.logo_url || null)

const form = useForm({
  _method: 'put',
  clinic_name: props.clinic.name || '',
  email: props.clinic.email || '',
  phone: props.clinic.phone || '',
  address: props.clinic.address || '',
  website: props.clinic.website || '',
  tax_id: props.clinic.tax_id || '',
  logo: null,
})

const onLogoChange = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  form.logo = file
  logoPreview.value = URL.createObjectURL(file)
}

const removeLogo = () => {
  form.logo = null
  logoPreview.value = null
  if (logoInput.value) logoInput.value.value = ''
}

const submit = () => form.post(route('clinic.settings.update'))

// ── NCF helpers ────────────────────────────────────────────────────────────
const NCF_TYPES = [
  { value: 'B01', label: 'B01 — Crédito Fiscal' },
  { value: 'B02', label: 'B02 — Consumo' },
  { value: 'B04', label: 'B04 — Nota de Crédito (Anulación)' },
]

const seqStatus = (seq) => {
  if (seq.is_expired)         return { label: 'Vencida',   color: 'red' }
  if (seq.is_exhausted)       return { label: 'Agotada',   color: 'red' }
  if (!seq.is_active)         return { label: 'Inactiva',  color: 'gray' }
  if (seq.is_nearly_exhausted || seq.is_nearly_expired)
                              return { label: 'Atención',  color: 'yellow' }
  return                             { label: 'Activa',    color: 'green' }
}

const statusClass = {
  green:  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  yellow: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  red:    'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
  gray:   'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
}

const barColor = (seq) => {
  if (seq.is_expired || seq.is_exhausted) return '#EF4444'
  if (seq.is_nearly_exhausted || seq.is_nearly_expired) return '#F59E0B'
  return '#00BFA6'
}

const formatNum = (n) => String(n).padStart(8, '0')

// ── Add sequence modal ─────────────────────────────────────────────────────
const showAddModal = ref(false)

const addForm = useForm({
  type:        'B02',
  from_number: '',
  to_number:   '',
  expires_at:  '',
  is_active:   false,
})

const addSequence = () => {
  addForm.post(route('clinic.ncf-sequences.store'), {
    onSuccess: () => { showAddModal.value = false; addForm.reset() }
  })
}

// ── Edit sequence modal ────────────────────────────────────────────────────
const showEditModal  = ref(false)
const editingSeq     = ref(null)
const editForm       = useForm({})

const openEdit = (seq) => {
  editingSeq.value = seq
  editForm.defaults({
    from_number:    seq.from_number,
    to_number:      seq.to_number,
    current_number: seq.current_number,
    expires_at:     seq.expires_at ?? '',
    is_active:      seq.is_active,
  }).reset()
  showEditModal.value = true
}

const saveEdit = () => {
  editForm.put(route('clinic.ncf-sequences.update', editingSeq.value.id), {
    onSuccess: () => { showEditModal.value = false }
  })
}

// ── Delete sequence ────────────────────────────────────────────────────────
const confirmDeleteSeq = ref(null)

const deleteSequence = (seq) => {
  router.delete(route('clinic.ncf-sequences.destroy', seq.id), {
    onFinish: () => { confirmDeleteSeq.value = null }
  })
}

// ── Computed warnings for edit form ───────────────────────────────────────
const editCurrentWarning = computed(() => {
  if (!editingSeq.value) return null
  const c = editForm.current_number
  const from = editForm.from_number ?? editingSeq.value.from_number
  const to   = editForm.to_number   ?? editingSeq.value.to_number
  if (c < from - 1)  return `El número actual no puede ser menor a ${from - 1}.`
  if (c > to)        return `El número actual no puede superar el rango final (${to}).`
  return null
})
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Configuración de Clínica</h1>
    </template>
    <Head title="Configuración de Clínica" />

    <div class="max-w-2xl space-y-6">

      <!-- ── Clinic settings form ──────────────────────────────────────── -->
      <form @submit.prevent="submit" class="space-y-4">
        <div class="card p-6">
          <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Logo de la clínica</h2>
          <div class="flex items-center gap-5">
            <div class="w-20 h-20 rounded-2xl border-2 border-dashed border-navy-200 dark:border-navy-700 flex items-center justify-center overflow-hidden flex-shrink-0 bg-navy-50 dark:bg-navy-800">
              <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-1" alt="Logo" />
              <BuildingOfficeIcon v-else class="w-8 h-8 text-navy-300" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-navy-900 dark:text-white mb-1">{{ clinic.name }}</p>
              <p class="text-xs text-navy-400 mb-3">PNG, JPG o SVG · Máx. 2 MB · Se mostrará en facturas PDF</p>
              <div class="flex gap-2">
                <button type="button" @click="logoInput.click()" class="btn-outline text-xs gap-1.5">
                  <ArrowUpTrayIcon class="w-3.5 h-3.5" />
                  {{ logoPreview ? 'Cambiar logo' : 'Subir logo' }}
                </button>
                <button v-if="logoPreview" type="button" @click="removeLogo" class="btn-ghost text-xs text-red-400 hover:text-red-600 gap-1.5">
                  <TrashIcon class="w-3.5 h-3.5" /> Quitar
                </button>
              </div>
              <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoChange" />
              <p v-if="form.errors.logo" class="text-xs text-red-500 mt-1">{{ form.errors.logo }}</p>
            </div>
          </div>
        </div>

        <div class="card p-6">
          <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Información de la Clínica</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2"><label class="label">Nombre *</label><input v-model="form.clinic_name" type="text" class="input" required /></div>
            <div><label class="label">Correo electrónico</label><input v-model="form.email" type="email" class="input" /></div>
            <div><label class="label">Teléfono</label><input v-model="form.phone" type="tel" class="input" /></div>
            <div class="sm:col-span-2"><label class="label">Dirección</label><textarea v-model="form.address" rows="2" class="input" /></div>
            <div><label class="label">Sitio web</label><input v-model="form.website" type="url" class="input" placeholder="https://…" /></div>
            <div><label class="label">RNC / ID Fiscal</label><input v-model="form.tax_id" type="text" class="input" placeholder="101-XXXXX-X" /></div>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="btn-primary" :disabled="form.processing">
            {{ form.processing ? 'Guardando…' : 'Guardar Configuración' }}
          </button>
        </div>
      </form>

      <!-- ── NCF Sequences ─────────────────────────────────────────────── -->
      <div class="card p-6">
        <div class="flex items-center justify-between mb-2">
          <div>
            <h2 class="font-display font-semibold text-navy-900 dark:text-white">Secuencias NCF</h2>
            <p class="text-xs text-navy-400 mt-0.5">
              Rangos autorizados por la DGII. El sistema genera los NCF automáticamente en orden.
            </p>
          </div>
          <button @click="showAddModal = true" class="btn-primary text-sm gap-1.5">
            <PlusIcon class="w-4 h-4" /> Nueva secuencia
          </button>
        </div>

        <div v-if="!ncfSequences?.length" class="text-center py-10 text-navy-400 text-sm">
          No hay secuencias configuradas. Agrega una para poder generar NCF en tus facturas.
        </div>

        <div v-else class="mt-4 space-y-3">
          <div v-for="seq in ncfSequences" :key="seq.id"
            class="rounded-xl border p-4 transition-all"
            :class="[
              seq.is_expired || seq.is_exhausted ? 'border-red-200 dark:border-red-800 bg-red-50/40 dark:bg-red-900/10' :
              seq.is_nearly_exhausted || seq.is_nearly_expired ? 'border-amber-200 dark:border-amber-800 bg-amber-50/40 dark:bg-amber-900/10' :
              seq.is_active ? 'border-teal-200 dark:border-teal-800 bg-teal-50/30 dark:bg-teal-900/10' :
              'border-navy-200 dark:border-navy-700'
            ]">

            <!-- Header row -->
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono font-bold text-sm px-2 py-0.5 rounded bg-navy-900 dark:bg-navy-700 text-white">{{ seq.type }}</span>
                <span class="text-sm font-medium text-navy-800 dark:text-navy-200">
                  {{ NCF_TYPES.find(t => t.value === seq.type)?.label.split('—')[1].trim() }}
                </span>
                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', statusClass[seqStatus(seq).color]]">
                  {{ seqStatus(seq).label }}
                </span>
              </div>
              <div class="flex gap-1 flex-shrink-0">
                <button @click="openEdit(seq)" class="btn-ghost p-1.5" title="Editar">
                  <PencilIcon class="w-4 h-4 text-navy-400" />
                </button>
                <button v-if="!seq.is_locked" @click="confirmDeleteSeq = seq" class="btn-ghost p-1.5" title="Eliminar">
                  <TrashIcon class="w-4 h-4 text-red-400" />
                </button>
              </div>
            </div>

            <!-- Range info -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3 text-xs">
              <div>
                <p class="text-navy-400 mb-0.5">Rango autorizado</p>
                <p class="font-mono font-semibold text-navy-800 dark:text-navy-200">
                  {{ formatNum(seq.from_number) }} → {{ formatNum(seq.to_number) }}
                </p>
              </div>
              <div>
                <p class="text-navy-400 mb-0.5">Último emitido</p>
                <p class="font-mono font-semibold text-navy-800 dark:text-navy-200">
                  {{ seq.current_number >= seq.from_number ? formatNum(seq.current_number) : '—' }}
                </p>
              </div>
              <div>
                <p class="text-navy-400 mb-0.5">Próximo NCF</p>
                <p class="font-mono font-semibold" :class="seq.is_exhausted || seq.is_expired ? 'text-red-500' : 'text-teal-600 dark:text-teal-400'">
                  {{ seq.is_exhausted || seq.is_expired ? 'N/A' : seq.next_ncf }}
                </p>
              </div>
              <div>
                <p class="text-navy-400 mb-0.5">Disponibles</p>
                <p class="font-semibold" :class="seq.is_exhausted ? 'text-red-500' : seq.is_nearly_exhausted ? 'text-amber-600' : 'text-navy-800 dark:text-navy-200'">
                  {{ seq.remaining }} de {{ seq.total }}
                </p>
              </div>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-navy-100 dark:bg-navy-700 rounded-full h-2 mb-2 overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :style="`width: ${Math.min(100, seq.usage_pct)}%; background: ${barColor(seq)}`">
              </div>
            </div>
            <div class="flex items-center justify-between text-xs text-navy-400">
              <span>{{ seq.usage_pct }}% usado</span>
              <span v-if="seq.expires_at" :class="seq.is_expired ? 'text-red-500' : seq.is_nearly_expired ? 'text-amber-600' : ''">
                Vence: {{ new Date(seq.expires_at).toLocaleDateString('es-DO', { day:'2-digit', month:'short', year:'numeric' }) }}
              </span>
              <span v-else class="text-navy-300">Sin fecha de vencimiento</span>
            </div>

            <!-- Warnings -->
            <div v-if="seq.is_nearly_exhausted && !seq.is_exhausted" class="mt-2 flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 dark:bg-amber-900/20 px-3 py-1.5 rounded-lg">
              <ExclamationTriangleIcon class="w-3.5 h-3.5 flex-shrink-0" />
              Quedan pocos comprobantes. Solicita una nueva serie a la DGII.
            </div>
            <div v-if="seq.is_nearly_expired && !seq.is_expired" class="mt-2 flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 dark:bg-amber-900/20 px-3 py-1.5 rounded-lg">
              <ExclamationTriangleIcon class="w-3.5 h-3.5 flex-shrink-0" />
              Esta secuencia vence pronto. Renuévala en la DGII.
            </div>
            <div v-if="seq.is_locked" class="mt-2 text-xs text-navy-400 italic">
              🔒 En uso — el rango no puede modificarse.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Add sequence modal ─────────────────────────────────────────── -->
    <Modal :show="showAddModal" title="Nueva secuencia NCF" size="md" @close="showAddModal = false; addForm.reset()">
      <form @submit.prevent="addSequence" class="space-y-4">
        <div>
          <label class="label">Tipo de comprobante *</label>
          <select v-model="addForm.type" class="input" required>
            <option v-for="t in NCF_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
          <p v-if="addForm.errors.type" class="text-xs text-red-500 mt-1">{{ addForm.errors.type }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Número inicial (DGII) *</label>
            <input v-model.number="addForm.from_number" type="number" min="1" class="input" placeholder="1" required />
            <p v-if="addForm.errors.from_number" class="text-xs text-red-500 mt-1">{{ addForm.errors.from_number }}</p>
          </div>
          <div>
            <label class="label">Número final (DGII) *</label>
            <input v-model.number="addForm.to_number" type="number" min="1" class="input" placeholder="500" required />
            <p v-if="addForm.errors.to_number" class="text-xs text-red-500 mt-1">{{ addForm.errors.to_number }}</p>
          </div>
        </div>

        <div v-if="addForm.from_number && addForm.to_number && addForm.to_number >= addForm.from_number"
          class="text-xs text-navy-500 bg-navy-50 dark:bg-navy-800 px-3 py-2 rounded-lg font-mono">
          Primer NCF: {{ addForm.type }}{{ String(addForm.from_number).padStart(8, '0') }}
          · Último: {{ addForm.type }}{{ String(addForm.to_number).padStart(8, '0') }}
          · Total: {{ addForm.to_number - addForm.from_number + 1 }} comprobantes
        </div>

        <div>
          <label class="label">Fecha de vencimiento</label>
          <input v-model="addForm.expires_at" type="date" class="input" />
          <p v-if="addForm.errors.expires_at" class="text-xs text-red-500 mt-1">{{ addForm.errors.expires_at }}</p>
        </div>

        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="addForm.is_active" class="rounded text-teal-500 focus:ring-teal-500" />
          <span class="text-sm text-navy-700 dark:text-navy-300">Activar inmediatamente</span>
        </label>
        <p v-if="addForm.errors.is_active" class="text-xs text-red-500">{{ addForm.errors.is_active }}</p>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showAddModal = false; addForm.reset()" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="addForm.processing">Crear secuencia</button>
        </div>
      </form>
    </Modal>

    <!-- ── Edit sequence modal ────────────────────────────────────────── -->
    <Modal :show="showEditModal" :title="`Editar secuencia ${editingSeq?.type}`" size="md" @close="showEditModal = false">
      <form v-if="editingSeq" @submit.prevent="saveEdit" class="space-y-4">

        <div v-if="editingSeq.is_locked"
          class="flex items-start gap-2 text-xs text-amber-700 bg-amber-50 dark:bg-amber-900/20 px-3 py-2.5 rounded-lg">
          <ExclamationTriangleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
          Esta secuencia ya ha emitido comprobantes. El rango y el número actual están bloqueados para evitar duplicados fiscales.
        </div>

        <!-- Locked: show range read-only -->
        <div v-if="editingSeq.is_locked" class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Número inicial</label>
            <input :value="editingSeq.from_number" type="number" class="input opacity-60" disabled />
          </div>
          <div>
            <label class="label">Número final</label>
            <input :value="editingSeq.to_number" type="number" class="input opacity-60" disabled />
          </div>
          <div class="col-span-2">
            <label class="label">Número actual (último emitido)</label>
            <input :value="editingSeq.current_number" type="number" class="input opacity-60" disabled />
            <p class="text-xs text-navy-400 mt-1">Bloqueado tras el primer uso. Próximo: <strong class="font-mono">{{ editingSeq.next_ncf }}</strong></p>
          </div>
        </div>

        <!-- Not locked: fully editable -->
        <div v-else class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Número inicial (DGII) *</label>
            <input v-model.number="editForm.from_number" type="number" min="1" class="input" required />
          </div>
          <div>
            <label class="label">Número final (DGII) *</label>
            <input v-model.number="editForm.to_number" type="number" min="1" class="input" required />
          </div>
          <div class="col-span-2">
            <label class="label">Número actual (último emitido)</label>
            <input v-model.number="editForm.current_number" type="number" min="0" class="input" />
            <p v-if="editCurrentWarning" class="text-xs text-red-500 mt-1">{{ editCurrentWarning }}</p>
            <p v-else class="text-xs text-amber-600 mt-1">⚠ Modifica solo si estás migrando datos de un sistema anterior.</p>
          </div>
        </div>

        <div>
          <label class="label">Fecha de vencimiento</label>
          <input v-model="editForm.expires_at" type="date" class="input" />
        </div>

        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="editForm.is_active" class="rounded text-teal-500 focus:ring-teal-500" />
          <span class="text-sm text-navy-700 dark:text-navy-300">Activa</span>
        </label>
        <p v-if="editForm.errors.is_active" class="text-xs text-red-500">{{ editForm.errors.is_active }}</p>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showEditModal = false" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="editForm.processing || !!editCurrentWarning">
            Guardar cambios
          </button>
        </div>
      </form>
    </Modal>

    <!-- ── Delete confirm ─────────────────────────────────────────────── -->
    <ConfirmModal
      :show="!!confirmDeleteSeq"
      title="Eliminar secuencia NCF"
      :message="`¿Eliminar la secuencia ${confirmDeleteSeq?.type} (${confirmDeleteSeq?.from_number}-${confirmDeleteSeq?.to_number})? Esta acción no se puede deshacer.`"
      confirm-text="Eliminar"
      @confirm="deleteSequence(confirmDeleteSeq)"
      @cancel="confirmDeleteSeq = null"
    />
  </AppLayout>
</template>
