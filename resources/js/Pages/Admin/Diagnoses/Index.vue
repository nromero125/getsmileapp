<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import {
  PlusIcon, PencilSquareIcon, TrashIcon, BeakerIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  catalog: Array,
  treatments: Array,
  severities: Object,
})

// ─── Catalog Modal ────────────────────────────────────────────────────────────
const showModal = ref(false)
const editing  = ref(null)

const form = useForm({
  code:          '',
  diag_name:     '',
  description:   '',
  color:         '#6B7280',
  severity:      'medium',
  is_active:     true,
  treatment_ids: [],
})

const openCreate = () => {
  editing.value = null
  form.reset()
  form.color    = '#6B7280'
  form.severity = 'medium'
  form.is_active = true
  form.treatment_ids = []
  showModal.value = true
}

const openEdit = (entry) => {
  editing.value = entry
  form.code          = entry.code
  form.diag_name     = entry.name
  form.description   = entry.description || ''
  form.color         = entry.color
  form.severity      = entry.severity
  form.is_active     = entry.is_active
  form.treatment_ids = entry.treatments?.map(t => t.id) || []
  showModal.value = true
}

const save = () => {
  const data = form.transform(() => ({
    code:          form.code,
    name:          form.diag_name,
    description:   form.description,
    color:         form.color,
    severity:      form.severity,
    is_active:     form.is_active,
    treatment_ids: form.treatment_ids,
  }))

  if (editing.value) {
    data.put(route('diagnosis-catalog.update', editing.value.id), {
      onSuccess: () => { showModal.value = false }
    })
  } else {
    data.post(route('diagnosis-catalog.store'), {
      onSuccess: () => { showModal.value = false }
    })
  }
}

// ─── Delete ───────────────────────────────────────────────────────────────────
const deleting = ref(null)
const doDelete = () => {
  router.delete(route('diagnosis-catalog.destroy', deleting.value.id), {
    onSuccess: () => { deleting.value = null }
  })
}

// ─── Severity badge ───────────────────────────────────────────────────────────
const severityClass = {
  low:      'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  medium:   'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  high:     'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  critical: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
}
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Catálogo de Diagnósticos</h1>
        <button @click="openCreate" class="btn-primary">
          <PlusIcon class="w-4 h-4" />
          Nuevo Diagnóstico
        </button>
      </div>
    </template>

    <div class="card">
      <div class="p-4 border-b border-navy-100 dark:border-navy-800">
        <p class="text-sm text-navy-500 dark:text-navy-400">
          Define los diagnósticos que los dentistas pueden asignar a las piezas dentales de los pacientes.
          Cada diagnóstico puede vincularse a tratamientos sugeridos.
        </p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-navy-100 dark:border-navy-800">
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Código</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Diagnóstico</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Color</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Severidad</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Tratamientos</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase">Estado</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in catalog" :key="entry.id" class="table-row">
              <td class="px-4 py-3">
                <span class="font-mono text-xs font-bold text-navy-700 dark:text-navy-300">{{ entry.code }}</span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-4 h-4 rounded-full flex-shrink-0 border border-white/20" :style="`background:${entry.color}`"></div>
                  <div>
                    <p class="font-medium text-navy-900 dark:text-white">{{ entry.name }}</p>
                    <p v-if="entry.description" class="text-xs text-navy-400">{{ entry.description }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <input type="color" :value="entry.color" disabled class="w-8 h-8 rounded cursor-default border border-navy-200 dark:border-navy-700" />
              </td>
              <td class="px-4 py-3">
                <span :class="['badge', severityClass[entry.severity]]">
                  {{ severities[entry.severity]?.label || entry.severity }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div v-if="entry.treatments?.length" class="flex flex-wrap gap-1">
                  <span v-for="t in entry.treatments" :key="t.id"
                    class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400">
                    <BeakerIcon class="w-3 h-3" />
                    {{ t.name }}
                  </span>
                </div>
                <span v-else class="text-navy-400 text-xs">—</span>
              </td>
              <td class="px-4 py-3">
                <span :class="['badge', entry.is_active ? 'badge-success' : 'badge-neutral']">
                  {{ entry.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2 justify-end">
                  <button @click="openEdit(entry)" class="p-1.5 rounded-lg text-navy-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-colors">
                    <PencilSquareIcon class="w-4 h-4" />
                  </button>
                  <button @click="deleting = entry" class="p-1.5 rounded-lg text-navy-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!catalog.length">
              <td colspan="7" class="px-4 py-12 text-center text-navy-400">
                No hay diagnósticos en el catálogo. Agrega el primero.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal :show="showModal" :title="editing ? 'Editar Diagnóstico' : 'Nuevo Diagnóstico'" size="md" @close="showModal = false">
      <form @submit.prevent="save" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Código *</label>
            <input v-model="form.code" type="text" class="input font-mono" placeholder="CAR" maxlength="20" :class="{ 'border-red-400': form.errors.code }" />
            <p v-if="form.errors.code" class="text-xs text-red-500 mt-1">{{ form.errors.code }}</p>
          </div>
          <div>
            <label class="label">Severidad *</label>
            <select v-model="form.severity" class="input">
              <option v-for="(info, key) in severities" :key="key" :value="key">{{ info.label }}</option>
            </select>
          </div>
        </div>

        <div>
          <label class="label">Nombre *</label>
          <input v-model="form.diag_name" type="text" class="input" placeholder="Caries" :class="{ 'border-red-400': form.errors.name }" />
          <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="label">Descripción</label>
          <input v-model="form.description" type="text" class="input" placeholder="Descripción breve…" />
        </div>

        <div>
          <label class="label">Color del indicador</label>
          <div class="flex items-center gap-3">
            <input v-model="form.color" type="color" class="w-10 h-10 rounded-lg cursor-pointer border border-navy-200 dark:border-navy-700" />
            <input v-model="form.color" type="text" class="input w-32 font-mono text-sm" placeholder="#6B7280" maxlength="7" />
          </div>
        </div>

        <div>
          <label class="label">Tratamientos sugeridos</label>
          <div class="grid grid-cols-2 gap-1.5 max-h-40 overflow-y-auto border border-navy-200 dark:border-navy-700 rounded-xl p-2">
            <label v-for="t in treatments" :key="t.id"
              class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-navy-50 dark:hover:bg-navy-800 cursor-pointer text-sm">
              <input type="checkbox" :value="t.id" v-model="form.treatment_ids" class="rounded" />
              <span class="text-navy-700 dark:text-navy-300">{{ t.name }}</span>
            </label>
            <p v-if="!treatments.length" class="text-xs text-navy-400 col-span-2 text-center py-2">
              No hay tratamientos configurados.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <input id="is_active" type="checkbox" v-model="form.is_active" class="rounded" />
          <label for="is_active" class="text-sm text-navy-700 dark:text-navy-300">Diagnóstico activo</label>
        </div>

        <div class="flex gap-3 justify-end pt-2">
          <button type="button" @click="showModal = false" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="form.processing">
            {{ editing ? 'Actualizar' : 'Crear' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Delete Confirm -->
    <Modal :show="!!deleting" title="Eliminar Diagnóstico" size="sm" @close="deleting = null">
      <p class="text-navy-600 dark:text-navy-400 mb-4">
        ¿Eliminar <strong>{{ deleting?.name }}</strong> del catálogo? Se eliminarán también los registros de diagnóstico asociados a pacientes.
      </p>
      <div class="flex gap-3 justify-end">
        <button @click="deleting = null" class="btn-outline">Cancelar</button>
        <button @click="doDelete" class="btn-danger">Eliminar</button>
      </div>
    </Modal>
  </AppLayout>
</template>
