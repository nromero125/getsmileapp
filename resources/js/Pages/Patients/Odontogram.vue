<script setup>
import { ref, computed } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import {
  ArrowLeftIcon, PlusIcon, TrashIcon, PencilSquareIcon,
  BeakerIcon, XMarkIcon, CheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  patient:   Object,
  catalog:   Array,   // DiagnosisCatalog[] (active)
  diagnoses: Object,  // keyed by tooth_number → ToothDiagnosis[]
})

// ─── Tooth grid ───────────────────────────────────────────────────────────────
const upperTeeth = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16]
const lowerTeeth = [32,31,30,29,28,27,26,25,24,23,22,21,20,19,18,17]

// ─── Multi-select ─────────────────────────────────────────────────────────────
const selectedTeeth = ref(new Set())

const toggleTooth = (num) => {
  const s = new Set(selectedTeeth.value)
  if (s.has(num)) s.delete(num)
  else s.add(num)
  selectedTeeth.value = s
}

const clearSelection = () => { selectedTeeth.value = new Set() }

const isSelected = (num) => selectedTeeth.value.has(num)

// ─── Tooth visual helpers ─────────────────────────────────────────────────────
const toothDiagnoses = (num) => props.diagnoses?.[num] || []

const toothPrimaryColor = (num) => {
  const list = toothDiagnoses(num)
  if (!list.length) return '#E5E7EB'
  return list[0].catalog?.color || '#E5E7EB'
}

const toothColors = (num) => {
  const seen = new Set()
  return toothDiagnoses(num).reduce((acc, d) => {
    const c = d.catalog?.color || '#9CA3AF'
    if (!seen.has(c)) { seen.add(c); acc.push(c) }
    return acc
  }, [])
}

// ─── Add Diagnosis Form ───────────────────────────────────────────────────────
const addForm = useForm({
  diagnosis_catalog_id: '',
  diag_notes:           '',
  diagnosed_at:         new Date().toISOString().slice(0, 10),
})

const selectedCatalog = computed(() =>
  props.catalog.find(c => c.id === Number(addForm.diagnosis_catalog_id)) || null
)

const saveDiagnosis = () => {
  addForm.transform(() => ({
    tooth_numbers:        Array.from(selectedTeeth.value),
    diagnosis_catalog_id: addForm.diagnosis_catalog_id,
    notes:                addForm.diag_notes,
    diagnosed_at:         addForm.diagnosed_at,
  })).post(route('patients.diagnoses.store', props.patient.id), {
    onSuccess: () => {
      addForm.diagnosis_catalog_id = ''
      addForm.diag_notes           = ''
      clearSelection()
    }
  })
}

// ─── Tooth detail panel (for viewing existing diagnoses) ──────────────────────
const detailTooth   = ref(null)
const showDetail    = ref(false)

const openDetail = (num) => {
  if (toothDiagnoses(num).length === 0) return
  detailTooth.value = num
  showDetail.value  = true
}

// Right-click or long-press to view detail vs left-click to toggle
const handleToothClick = (num) => { toggleTooth(num) }
const handleToothDblClick = (num) => {
  if (toothDiagnoses(num).length) {
    detailTooth.value = num
    showDetail.value  = true
  }
}

// ─── Delete Diagnosis ─────────────────────────────────────────────────────────
const deletingDiag = ref(null)
const doDelete = () => {
  router.delete(route('patients.diagnoses.destroy', [props.patient.id, deletingDiag.value.id]), {
    onSuccess: () => { deletingDiag.value = null }
  })
}

// ─── Edit notes ───────────────────────────────────────────────────────────────
const editingDiag = ref(null)
const editForm = useForm({ edit_notes: '', diagnosed_at: '' })

const openEdit = (diag) => {
  editingDiag.value   = diag
  editForm.edit_notes = diag.notes || ''
  editForm.diagnosed_at = diag.diagnosed_at
    ? new Date(diag.diagnosed_at).toISOString().slice(0, 10)
    : ''
}

const saveEdit = () => {
  editForm.transform(() => ({
    notes:        editForm.edit_notes,
    diagnosed_at: editForm.diagnosed_at,
  })).put(route('patients.diagnoses.update', [props.patient.id, editingDiag.value.id]), {
    onSuccess: () => { editingDiag.value = null }
  })
}

// ─── Severity helpers ─────────────────────────────────────────────────────────
const severityClass = {
  low:      'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  medium:   'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  high:     'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  critical: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
}
const severityLabel = { low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica' }

// ─── All diagnoses flat for table ─────────────────────────────────────────────
const allDiagnoses = computed(() =>
  Object.entries(props.diagnoses || {})
    .flatMap(([tooth, list]) => list.map(d => ({ ...d, tooth_number: Number(tooth) })))
    .sort((a, b) => a.tooth_number - b.tooth_number || new Date(b.diagnosed_at) - new Date(a.diagnosed_at))
)

// Sorted selected teeth for display
const selectedTeethSorted = computed(() =>
  Array.from(selectedTeeth.value).sort((a, b) => a - b)
)
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('patients.show', patient.id)" class="btn-ghost p-1.5">
          <ArrowLeftIcon class="w-4 h-4" />
        </Link>
        <div>
          <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Odontograma</h1>
          <p class="text-xs text-navy-400">{{ patient.first_name }} {{ patient.last_name }}</p>
        </div>
      </div>
    </template>

    <div class="lg:grid lg:grid-cols-3 lg:gap-6">

      <!-- ── Left: Odontogram ─────────────────────────────────────────────── -->
      <div class="lg:col-span-2 space-y-4">
        <div class="card p-6">

          <!-- Instructions -->
          <div class="mb-4 p-3 rounded-xl bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800">
            <p class="text-xs text-teal-700 dark:text-teal-400">
              <strong>Clic</strong> en uno o varios dientes para seleccionarlos, luego elige el diagnóstico en el panel derecho.
              <strong>Doble clic</strong> en un diente con diagnósticos para ver su detalle.
            </p>
          </div>

          <!-- Legend -->
          <div class="flex flex-wrap gap-2 mb-5">
            <div v-for="entry in catalog" :key="entry.id" class="flex items-center gap-1.5">
              <div class="w-3 h-3 rounded-full border border-gray-200" :style="`background: ${entry.color}`"></div>
              <span class="text-xs text-navy-600 dark:text-navy-400">{{ entry.name }}</span>
            </div>
          </div>

          <div class="overflow-x-auto">
            <div class="min-w-[640px]">
              <p class="text-xs font-semibold text-navy-400 uppercase tracking-widest mb-2 text-center">Arcada Superior (Maxilar)</p>

              <!-- Upper teeth -->
              <div class="flex justify-center gap-1 mb-1">
                <button v-for="num in upperTeeth" :key="num"
                  @click="handleToothClick(num)"
                  @dblclick.prevent="handleToothDblClick(num)"
                  class="group flex flex-col items-center gap-0.5 select-none"
                  :title="isSelected(num) ? `Diente #${num} — seleccionado` : `Diente #${num}`">
                  <span class="text-[10px] font-medium transition-colors"
                    :class="isSelected(num) ? 'text-teal-600 dark:text-teal-400 font-bold' : 'text-navy-400 group-hover:text-navy-700'">
                    {{ num }}
                  </span>
                  <div class="relative">
                    <svg viewBox="0 0 30 42" class="w-7 h-10 cursor-pointer">
                      <path d="M15 2 C10 2 6 5.5 4.5 10 C3 14.5 3.5 20 4.5 25 C5.5 30 7 36 10 38 C13 40 15 36 15 36 C15 36 17 40 20 38 C23 36 24.5 30 25.5 25 C26.5 20 27 14.5 25.5 10 C24 5.5 20 2 15 2Z"
                        :fill="isSelected(num) ? '#99F6E4' : toothPrimaryColor(num)"
                        :stroke="isSelected(num) ? '#00BFA6' : '#D1D5DB'"
                        :stroke-width="isSelected(num) ? 2.5 : 1"
                        class="transition-all duration-150 group-hover:opacity-80"
                      />
                    </svg>
                    <!-- Selected checkmark -->
                    <div v-if="isSelected(num)"
                      class="absolute inset-0 flex items-center justify-center pointer-events-none">
                      <CheckIcon class="w-4 h-4 text-teal-600 drop-shadow" />
                    </div>
                    <!-- Multi-diagnosis dots -->
                    <div v-else-if="toothColors(num).length > 0"
                      class="absolute -bottom-1 left-0 right-0 flex justify-center gap-0.5">
                      <div v-for="(c, i) in toothColors(num).slice(0, 4)" :key="i"
                        class="w-1.5 h-1.5 rounded-full border border-white"
                        :style="`background:${c}`"></div>
                    </div>
                  </div>
                </button>
              </div>

              <!-- Midline -->
              <div class="flex items-center gap-2 my-3">
                <div class="flex-1 h-px bg-navy-200 dark:bg-navy-700"></div>
                <span class="text-xs text-navy-400 font-medium px-2">LÍNEA MEDIA</span>
                <div class="flex-1 h-px bg-navy-200 dark:bg-navy-700"></div>
              </div>

              <!-- Lower teeth -->
              <div class="flex justify-center gap-1 mb-1">
                <button v-for="num in lowerTeeth" :key="num"
                  @click="handleToothClick(num)"
                  @dblclick.prevent="handleToothDblClick(num)"
                  class="group flex flex-col items-center gap-0.5 select-none"
                  :title="isSelected(num) ? `Diente #${num} — seleccionado` : `Diente #${num}`">
                  <div class="relative">
                    <svg viewBox="0 0 30 42" class="w-7 h-10 cursor-pointer" style="transform: scaleY(-1)">
                      <path d="M15 2 C10 2 6 5.5 4.5 10 C3 14.5 3.5 20 4.5 25 C5.5 30 7 36 10 38 C13 40 15 36 15 36 C15 36 17 40 20 38 C23 36 24.5 30 25.5 25 C26.5 20 27 14.5 25.5 10 C24 5.5 20 2 15 2Z"
                        :fill="isSelected(num) ? '#99F6E4' : toothPrimaryColor(num)"
                        :stroke="isSelected(num) ? '#00BFA6' : '#D1D5DB'"
                        :stroke-width="isSelected(num) ? 2.5 : 1"
                        class="transition-all duration-150 group-hover:opacity-80"
                      />
                    </svg>
                    <!-- Selected checkmark (flip back) -->
                    <div v-if="isSelected(num)"
                      class="absolute inset-0 flex items-center justify-center pointer-events-none"
                      style="transform: scaleY(-1)">
                      <CheckIcon class="w-4 h-4 text-teal-600 drop-shadow" />
                    </div>
                    <!-- Multi-diagnosis dots -->
                    <div v-else-if="toothColors(num).length > 0"
                      class="absolute -top-1 left-0 right-0 flex justify-center gap-0.5">
                      <div v-for="(c, i) in toothColors(num).slice(0, 4)" :key="i"
                        class="w-1.5 h-1.5 rounded-full border border-white"
                        :style="`background:${c}`"></div>
                    </div>
                  </div>
                  <span class="text-[10px] font-medium transition-colors"
                    :class="isSelected(num) ? 'text-teal-600 dark:text-teal-400 font-bold' : 'text-navy-400 group-hover:text-navy-700'">
                    {{ num }}
                  </span>
                </button>
              </div>

              <p class="text-xs font-semibold text-navy-400 uppercase tracking-widest mt-2 text-center">Arcada Inferior (Mandibular)</p>
            </div>
          </div>
        </div>

        <!-- Diagnoses Table -->
        <div v-if="allDiagnoses.length" class="card">
          <div class="p-4 border-b border-navy-100 dark:border-navy-800">
            <h3 class="font-semibold text-navy-900 dark:text-white">Historial de Diagnósticos</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-navy-100 dark:border-navy-800">
                  <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Diente</th>
                  <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Diagnóstico</th>
                  <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Severidad</th>
                  <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Notas</th>
                  <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Dentista</th>
                  <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Fecha</th>
                  <th class="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in allDiagnoses" :key="d.id" class="table-row">
                  <td class="px-4 py-3 font-bold text-navy-900 dark:text-white">#{{ d.tooth_number }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <div class="w-3 h-3 rounded-full flex-shrink-0" :style="`background:${d.catalog?.color}`"></div>
                      <span class="font-medium">{{ d.catalog?.name }}</span>
                      <span class="font-mono text-xs text-navy-400">{{ d.catalog?.code }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span :class="['badge text-xs', severityClass[d.catalog?.severity] || '']">
                      {{ severityLabel[d.catalog?.severity] || d.catalog?.severity }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-navy-600 dark:text-navy-400 max-w-[180px] truncate">{{ d.notes || '—' }}</td>
                  <td class="px-4 py-3 text-navy-600 dark:text-navy-400 text-xs">{{ d.dentist?.name || '—' }}</td>
                  <td class="px-4 py-3 text-navy-400 text-xs whitespace-nowrap">
                    {{ d.diagnosed_at ? new Date(d.diagnosed_at).toLocaleDateString('es-ES') : '—' }}
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-1 justify-end">
                      <button @click="openEdit(d)" class="p-1 rounded text-navy-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20">
                        <PencilSquareIcon class="w-4 h-4" />
                      </button>
                      <button @click="deletingDiag = d" class="p-1 rounded text-navy-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── Right Panel ───────────────────────────────────────────────────── -->
      <div class="mt-6 lg:mt-0 space-y-4">

        <!-- Add diagnosis form -->
        <div class="card">
          <div class="p-4 border-b border-navy-100 dark:border-navy-800">
            <h3 class="font-semibold text-navy-900 dark:text-white">Agregar Diagnóstico</h3>
          </div>
          <div class="p-4">

            <!-- Selected teeth chips -->
            <div class="mb-4">
              <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-navy-500 uppercase tracking-wider">
                  Piezas seleccionadas
                  <span v-if="selectedTeeth.size" class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-teal-500 text-white text-[10px] font-bold">
                    {{ selectedTeeth.size }}
                  </span>
                </p>
                <button v-if="selectedTeeth.size" @click="clearSelection"
                  class="text-xs text-navy-400 hover:text-red-500 transition-colors">
                  Limpiar
                </button>
              </div>

              <div v-if="selectedTeeth.size" class="flex flex-wrap gap-1.5">
                <span v-for="num in selectedTeethSorted" :key="num"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 text-xs font-medium">
                  #{{ num }}
                  <button @click="toggleTooth(num)" class="hover:text-red-500 transition-colors">
                    <XMarkIcon class="w-3 h-3" />
                  </button>
                </span>
              </div>
              <div v-else class="flex items-center justify-center h-10 rounded-xl border-2 border-dashed border-navy-200 dark:border-navy-700">
                <p class="text-xs text-navy-400">Selecciona piezas en el odontograma</p>
              </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="saveDiagnosis" class="space-y-3">
              <div>
                <label class="label">Diagnóstico *</label>
                <select v-model="addForm.diagnosis_catalog_id" class="input" required>
                  <option value="" disabled>Selecciona diagnóstico…</option>
                  <option v-for="entry in catalog" :key="entry.id" :value="entry.id">
                    [{{ entry.code }}] {{ entry.name }}
                  </option>
                </select>
              </div>

              <!-- Selected catalog info -->
              <div v-if="selectedCatalog" class="p-2.5 rounded-xl bg-navy-50 dark:bg-navy-800 space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                  <div class="w-3 h-3 rounded-full flex-shrink-0" :style="`background:${selectedCatalog.color}`"></div>
                  <span class="text-sm font-medium text-navy-800 dark:text-white">{{ selectedCatalog.name }}</span>
                  <span :class="['badge text-xs', severityClass[selectedCatalog.severity] || '']">
                    {{ severityLabel[selectedCatalog.severity] }}
                  </span>
                </div>
                <p v-if="selectedCatalog.description" class="text-xs text-navy-500">{{ selectedCatalog.description }}</p>
                <div v-if="selectedCatalog.treatments?.length" class="flex flex-wrap gap-1 items-center">
                  <span class="text-xs text-navy-400">Sugiere:</span>
                  <span v-for="t in selectedCatalog.treatments" :key="t.id"
                    class="text-xs px-1.5 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400">
                    {{ t.name }}
                  </span>
                </div>
              </div>

              <div>
                <label class="label">Fecha de diagnóstico</label>
                <input v-model="addForm.diagnosed_at" type="date" class="input" />
              </div>

              <div>
                <label class="label">Notas clínicas</label>
                <textarea v-model="addForm.diag_notes" rows="2" class="input" placeholder="Observaciones…" />
              </div>

              <button type="submit" class="btn-primary w-full"
                :disabled="addForm.processing || !addForm.diagnosis_catalog_id || selectedTeeth.size === 0">
                <PlusIcon class="w-4 h-4" />
                <span v-if="selectedTeeth.size === 0">Selecciona piezas primero</span>
                <span v-else-if="selectedTeeth.size === 1">Registrar diagnóstico</span>
                <span v-else>Registrar en {{ selectedTeeth.size }} piezas</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Catalog quick ref -->
        <div v-if="catalog.length" class="card">
          <div class="p-3 border-b border-navy-100 dark:border-navy-800">
            <p class="text-xs font-semibold text-navy-500 uppercase tracking-wider">Catálogo de Diagnósticos</p>
          </div>
          <div class="p-3 space-y-1.5 max-h-64 overflow-y-auto">
            <div v-for="entry in catalog" :key="entry.id" class="flex items-center gap-2 text-xs text-navy-700 dark:text-navy-300">
              <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="`background:${entry.color}`"></div>
              <span class="font-mono font-bold text-navy-400 w-12 flex-shrink-0">{{ entry.code }}</span>
              <span class="truncate">{{ entry.name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tooth Detail Modal (double-click) -->
    <Modal :show="showDetail && !!detailTooth" :title="`Diente #${detailTooth} — Diagnósticos`" size="sm" @close="showDetail = false">
      <div class="space-y-2">
        <div v-for="d in toothDiagnoses(detailTooth)" :key="d.id"
          class="p-3 rounded-xl bg-navy-50 dark:bg-navy-800">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2 flex-1 min-w-0">
              <div class="w-3 h-3 rounded-full flex-shrink-0" :style="`background:${d.catalog?.color}`"></div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-navy-900 dark:text-white">{{ d.catalog?.name }}</p>
                <p v-if="d.notes" class="text-xs text-navy-500 dark:text-navy-400 mt-0.5">{{ d.notes }}</p>
                <p class="text-xs text-navy-400 mt-0.5">
                  {{ d.dentist?.name }} ·
                  {{ d.diagnosed_at ? new Date(d.diagnosed_at).toLocaleDateString('es-ES') : '' }}
                </p>
                <div v-if="d.catalog?.treatments?.length" class="mt-1.5 flex flex-wrap gap-1">
                  <span v-for="t in d.catalog.treatments" :key="t.id"
                    class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400">
                    <BeakerIcon class="w-3 h-3" />{{ t.name }}
                  </span>
                </div>
              </div>
            </div>
            <div class="flex gap-1 flex-shrink-0">
              <button @click="showDetail = false; openEdit(d)" class="p-1 rounded text-navy-400 hover:text-teal-600">
                <PencilSquareIcon class="w-4 h-4" />
              </button>
              <button @click="showDetail = false; deletingDiag = d" class="p-1 rounded text-navy-400 hover:text-red-600">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 flex justify-end">
        <button @click="showDetail = false" class="btn-outline">Cerrar</button>
      </div>
    </Modal>

    <!-- Edit Notes Modal -->
    <Modal :show="!!editingDiag" title="Editar Diagnóstico" size="sm" @close="editingDiag = null">
      <form @submit.prevent="saveEdit" class="space-y-4">
        <p class="text-sm font-medium text-navy-800 dark:text-white">
          Diente #{{ editingDiag?.tooth_number }} — {{ editingDiag?.catalog?.name }}
        </p>
        <div>
          <label class="label">Fecha de diagnóstico</label>
          <input v-model="editForm.diagnosed_at" type="date" class="input" />
        </div>
        <div>
          <label class="label">Notas clínicas</label>
          <textarea v-model="editForm.edit_notes" rows="3" class="input" placeholder="Observaciones clínicas…" />
        </div>
        <div class="flex gap-3 justify-end pt-2">
          <button type="button" @click="editingDiag = null" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="editForm.processing">Guardar</button>
        </div>
      </form>
    </Modal>

    <!-- Delete Confirm -->
    <Modal :show="!!deletingDiag" title="Eliminar Diagnóstico" size="sm" @close="deletingDiag = null">
      <p class="text-navy-600 dark:text-navy-400 mb-4">
        ¿Eliminar <strong>{{ deletingDiag?.catalog?.name }}</strong> del diente #{{ deletingDiag?.tooth_number }}?
      </p>
      <div class="flex gap-3 justify-end">
        <button @click="deletingDiag = null" class="btn-outline">Cancelar</button>
        <button @click="doDelete" class="btn-danger">Eliminar</button>
      </div>
    </Modal>
  </AppLayout>
</template>
