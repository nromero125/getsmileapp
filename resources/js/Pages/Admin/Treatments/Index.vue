<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { PlusIcon, PencilIcon, TrashIcon, TagIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ categories: Array, treatments: Array })

// ── Filters ──────────────────────────────────────────────────────────────────
const selectedCategory = ref(null)

const filtered = computed(() =>
  selectedCategory.value
    ? props.treatments.filter(t => t.treatment_category_id === selectedCategory.value)
    : props.treatments
)

// ── Treatment modal ───────────────────────────────────────────────────────────
const showModal = ref(false)
const editingId = ref(null)

const form = useForm({
  clinic_name:            '',   // dummy to avoid useForm 'name' conflict
  treatment_name:         '',
  treatment_category_id:  null,
  default_price:          '',
  duration_minutes:       30,
  description:            '',
  color:                  '#00BFA6',
  is_active:              true,
})

const openAdd = () => {
  editingId.value = null
  form.reset()
  form.treatment_name = ''
  form.treatment_category_id = null
  form.default_price = ''
  form.duration_minutes = 30
  form.description = ''
  form.color = '#00BFA6'
  form.is_active = true
  showModal.value = true
}

const openEdit = (t) => {
  editingId.value = t.id
  form.treatment_name        = t.name
  form.treatment_category_id = t.treatment_category_id
  form.default_price         = t.default_price
  form.duration_minutes      = t.duration_minutes
  form.description           = t.description || ''
  form.color                 = t.color || '#00BFA6'
  form.is_active             = t.is_active
  showModal.value = true
}

const submitTreatment = () => {
  const data = {
    name:                   form.treatment_name,
    treatment_category_id:  form.treatment_category_id,
    default_price:          form.default_price,
    duration_minutes:       form.duration_minutes,
    description:            form.description,
    color:                  form.color,
    is_active:              form.is_active,
  }
  if (editingId.value) {
    form.transform(() => data).put(route('treatments.update', editingId.value), {
      onSuccess: () => { showModal.value = false; form.reset() }
    })
  } else {
    form.transform(() => data).post(route('treatments.store'), {
      onSuccess: () => { showModal.value = false; form.reset() }
    })
  }
}

// ── Delete treatment ──────────────────────────────────────────────────────────
const confirmDelete = ref({ show: false, treatment: null })
const doDelete = () => {
  router.delete(route('treatments.destroy', confirmDelete.value.treatment.id), {
    onFinish: () => confirmDelete.value = { show: false, treatment: null }
  })
}

// ── Category modal ────────────────────────────────────────────────────────────
const showCatModal = ref(false)
const catForm = useForm({ cat_name: '', color: '#6B7280' })

const submitCategory = () => {
  catForm.transform(() => ({ name: catForm.cat_name, color: catForm.color }))
    .post(route('treatment-categories.store'), {
      onSuccess: () => { showCatModal.value = false; catForm.reset() }
    })
}

const confirmDeleteCat = ref({ show: false, cat: null })
const doDeleteCat = () => {
  router.delete(route('treatment-categories.destroy', confirmDeleteCat.value.cat.id), {
    onFinish: () => confirmDeleteCat.value = { show: false, cat: null }
  })
}

const fmt = (v) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP' }).format(v)
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Tratamientos y Precios</h1>
    </template>
    <Head title="Tratamientos" />

    <div class="space-y-4">

      <!-- Category chips + actions -->
      <div class="flex flex-wrap items-center gap-2">
        <button @click="selectedCategory = null"
          :class="['badge cursor-pointer transition-colors', selectedCategory === null ? 'bg-navy-800 text-white dark:bg-white dark:text-navy-900' : 'badge-gray hover:bg-navy-100']">
          Todas ({{ treatments.length }})
        </button>
        <button v-for="cat in categories" :key="cat.id"
          @click="selectedCategory = selectedCategory === cat.id ? null : cat.id"
          :class="['badge cursor-pointer transition-colors gap-1', selectedCategory === cat.id ? 'text-white' : 'opacity-80 hover:opacity-100']"
          :style="selectedCategory === cat.id ? `background:${cat.color}` : `background:${cat.color}22;color:${cat.color}`">
          {{ cat.name }} ({{ cat.treatments_count }})
        </button>

        <div class="ml-auto flex gap-2">
          <button @click="showCatModal = true" class="btn-outline text-xs gap-1.5">
            <TagIcon class="w-3.5 h-3.5" /> Nueva categoría
          </button>
          <button @click="openAdd" class="btn-primary text-sm gap-1.5">
            <PlusIcon class="w-4 h-4" /> Nuevo tratamiento
          </button>
        </div>
      </div>

      <!-- Treatments table -->
      <div class="card">
        <div v-if="filtered.length === 0" class="py-16 text-center text-navy-400 text-sm">
          No hay tratamientos{{ selectedCategory ? ' en esta categoría' : '' }}.
          <button @click="openAdd" class="text-teal-500 underline ml-1">Agregar uno</button>
        </div>

        <table v-else class="w-full text-sm">
          <thead>
            <tr class="border-b border-navy-100 dark:border-navy-800 text-xs text-navy-400 uppercase tracking-wide">
              <th class="px-4 py-3 text-left">Tratamiento</th>
              <th class="px-4 py-3 text-left hidden sm:table-cell">Categoría</th>
              <th class="px-4 py-3 text-right">Precio</th>
              <th class="px-4 py-3 text-right hidden md:table-cell">Duración</th>
              <th class="px-4 py-3 text-center hidden sm:table-cell">Estado</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-navy-100 dark:divide-navy-800">
            <tr v-for="t in filtered" :key="t.id"
              class="hover:bg-navy-50/40 dark:hover:bg-navy-800/30 transition-colors">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="`background:${t.color || '#6B7280'}`"></span>
                  <span class="font-medium text-navy-900 dark:text-white">{{ t.name }}</span>
                </div>
                <p v-if="t.description" class="text-xs text-navy-400 mt-0.5 ml-4.5 line-clamp-1">{{ t.description }}</p>
              </td>
              <td class="px-4 py-3 hidden sm:table-cell">
                <span v-if="t.category" class="badge text-xs"
                  :style="`background:${t.category.color}22;color:${t.category.color}`">
                  {{ t.category.name }}
                </span>
                <span v-else class="text-navy-300 text-xs">—</span>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-navy-900 dark:text-white">{{ fmt(t.default_price) }}</td>
              <td class="px-4 py-3 text-right text-navy-500 hidden md:table-cell">{{ t.duration_minutes }} min</td>
              <td class="px-4 py-3 text-center hidden sm:table-cell">
                <span :class="t.is_active ? 'badge-teal' : 'badge-gray'" class="badge text-xs">
                  {{ t.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button @click="openEdit(t)" class="btn-ghost p-1.5" title="Editar">
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button @click="confirmDelete = { show: true, treatment: t }"
                    class="btn-ghost p-1.5 text-red-400 hover:text-red-600" title="Eliminar">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Category management -->
      <div v-if="categories.length" class="card p-4">
        <h3 class="text-xs text-navy-400 uppercase tracking-wider mb-3">Gestionar categorías</h3>
        <div class="flex flex-wrap gap-2">
          <div v-for="cat in categories" :key="cat.id"
            class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm border border-navy-100 dark:border-navy-700">
            <span class="w-3 h-3 rounded-full" :style="`background:${cat.color}`"></span>
            <span class="text-navy-700 dark:text-navy-200">{{ cat.name }}</span>
            <span class="text-navy-400 text-xs">({{ cat.treatments_count }})</span>
            <button v-if="cat.treatments_count === 0"
              @click="confirmDeleteCat = { show: true, cat }"
              class="text-red-400 hover:text-red-600 ml-1" title="Eliminar">
              <TrashIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Treatment modal -->
    <Modal :show="showModal" :title="editingId ? 'Editar Tratamiento' : 'Nuevo Tratamiento'" size="md"
      @close="showModal = false; form.reset()">
      <form @submit.prevent="submitTreatment" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="label">Nombre *</label>
            <input v-model="form.treatment_name" type="text" class="input" required />
          </div>
          <div>
            <label class="label">Categoría</label>
            <select v-model="form.treatment_category_id" class="input">
              <option :value="null">Sin categoría</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Color</label>
            <div class="flex gap-2 items-center">
              <input v-model="form.color" type="color" class="h-9 w-12 rounded-lg border border-navy-200 dark:border-navy-600 cursor-pointer p-0.5" />
              <input v-model="form.color" type="text" class="input flex-1 font-mono text-sm" placeholder="#00BFA6" maxlength="7" />
            </div>
          </div>
          <div>
            <label class="label">Precio (MXN) *</label>
            <input v-model="form.default_price" type="number" min="0" step="0.01" class="input" required />
          </div>
          <div>
            <label class="label">Duración (minutos) *</label>
            <input v-model="form.duration_minutes" type="number" min="1" class="input" required />
          </div>
          <div class="sm:col-span-2">
            <label class="label">Descripción</label>
            <textarea v-model="form.description" rows="2" class="input" placeholder="Descripción opcional…" />
          </div>
          <div class="sm:col-span-2 flex items-center gap-3">
            <input id="is_active" v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded text-teal-500" />
            <label for="is_active" class="text-sm text-navy-700 dark:text-navy-200 cursor-pointer">Tratamiento activo</label>
          </div>
        </div>
        <div v-if="Object.keys(form.errors).length" class="p-3 bg-red-50 rounded-xl text-sm text-red-600">
          <p v-for="(err, key) in form.errors" :key="key">{{ err }}</p>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showModal = false; form.reset()" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="form.processing">
            {{ editingId ? 'Guardar cambios' : 'Crear tratamiento' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Category modal -->
    <Modal :show="showCatModal" title="Nueva Categoría" size="sm" @close="showCatModal = false; catForm.reset()">
      <form @submit.prevent="submitCategory" class="space-y-4">
        <div>
          <label class="label">Nombre *</label>
          <input v-model="catForm.cat_name" type="text" class="input" required />
        </div>
        <div>
          <label class="label">Color</label>
          <div class="flex gap-2 items-center">
            <input v-model="catForm.color" type="color" class="h-9 w-12 rounded-lg border border-navy-200 dark:border-navy-600 cursor-pointer p-0.5" />
            <input v-model="catForm.color" type="text" class="input flex-1 font-mono text-sm" maxlength="7" />
          </div>
        </div>
        <div v-if="Object.keys(catForm.errors).length" class="p-3 bg-red-50 rounded-xl text-sm text-red-600">
          <p v-for="(err, key) in catForm.errors" :key="key">{{ err }}</p>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showCatModal = false; catForm.reset()" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="catForm.processing">Crear categoría</button>
        </div>
      </form>
    </Modal>

    <!-- Confirm delete treatment -->
    <ConfirmModal
      :show="confirmDelete.show"
      title="Eliminar tratamiento"
      :message="`¿Eliminar '${confirmDelete.treatment?.name}'? Esta acción no se puede deshacer.`"
      confirm-text="Eliminar"
      @confirm="doDelete"
      @cancel="confirmDelete.show = false"
    />

    <!-- Confirm delete category -->
    <ConfirmModal
      :show="confirmDeleteCat.show"
      title="Eliminar categoría"
      :message="`¿Eliminar la categoría '${confirmDeleteCat.cat?.name}'?`"
      confirm-text="Eliminar"
      @confirm="doDeleteCat"
      @cancel="confirmDeleteCat.show = false"
    />
  </AppLayout>
</template>
