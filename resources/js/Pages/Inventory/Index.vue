<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import StatCard from '@/Components/StatCard.vue'
import {
  PlusIcon, PencilIcon, TrashIcon, ArrowUpIcon, ArrowDownIcon,
  AdjustmentsHorizontalIcon, ExclamationTriangleIcon, TagIcon,
  MagnifyingGlassIcon, CubeIcon, CurrencyDollarIcon, LinkIcon,
} from '@heroicons/vue/24/outline'

const props  = defineProps({ categories: Array, items: Array, treatments: Array, stats: Object })
const can    = usePage().props.can
const search = ref('')
const selectedCategory = ref(null)
const activeTab = ref('all') // 'all' | 'low'

// ── Filters ──────────────────────────────────────────────────────────────────
const filtered = computed(() => {
  let list = props.items
  if (activeTab.value === 'low') list = list.filter(i => i.is_low_stock)
  if (selectedCategory.value)   list = list.filter(i => i.inventory_category_id === selectedCategory.value)
  if (search.value.trim())      list = list.filter(i => i.name.toLowerCase().includes(search.value.toLowerCase()) || (i.sku || '').toLowerCase().includes(search.value.toLowerCase()))
  return list
})

// ── Item modal ────────────────────────────────────────────────────────────────
const showItemModal = ref(false)
const editingId     = ref(null)
const modalTab      = ref('info') // 'info' | 'treatments'

const itemForm = useForm({
  item_name:              '',
  inventory_category_id:  null,
  description:            '',
  unit:                   'pieza',
  stock:                  0,
  min_stock:              5,
  cost_per_unit:          0,
  supplier:               '',
  sku:                    '',
  is_active:              true,
  treatment_links:        [], // [{ treatment_id, quantity_used }]
})

const units = ['pieza', 'caja', 'par', 'ml', 'mg', 'rollo', 'sobre', 'tubo', 'jeringa', 'hoja', 'frasco', 'unidad']

const openAdd = () => {
  editingId.value = null
  modalTab.value  = 'info'
  itemForm.reset()
  itemForm.item_name             = ''
  itemForm.inventory_category_id = null
  itemForm.unit                  = 'pieza'
  itemForm.stock                 = 0
  itemForm.min_stock             = 5
  itemForm.cost_per_unit         = 0
  itemForm.is_active             = true
  itemForm.treatment_links       = []
  showItemModal.value = true
}

const openEdit = (item) => {
  editingId.value                  = item.id
  modalTab.value                   = 'info'
  itemForm.item_name               = item.name
  itemForm.inventory_category_id   = item.inventory_category_id
  itemForm.description             = item.description || ''
  itemForm.unit                    = item.unit
  itemForm.stock                   = item.stock
  itemForm.min_stock               = item.min_stock
  itemForm.cost_per_unit           = item.cost_per_unit
  itemForm.supplier                = item.supplier || ''
  itemForm.sku                     = item.sku || ''
  itemForm.is_active               = item.is_active
  itemForm.treatment_links         = (item.treatments || []).map(t => ({
    treatment_id:  t.id,
    quantity_used: t.pivot?.quantity_used ?? 1,
    name:          t.name,
  }))
  showItemModal.value = true
}

const submitItem = () => {
  const data = {
    name:                   itemForm.item_name,
    inventory_category_id:  itemForm.inventory_category_id,
    description:            itemForm.description,
    unit:                   itemForm.unit,
    stock:                  itemForm.stock,
    min_stock:              itemForm.min_stock,
    cost_per_unit:          itemForm.cost_per_unit,
    supplier:               itemForm.supplier,
    sku:                    itemForm.sku,
    is_active:              itemForm.is_active,
    treatment_links:        itemForm.treatment_links,
  }
  if (editingId.value) {
    itemForm.transform(() => data).put(route('inventory.update', editingId.value), {
      onSuccess: () => { showItemModal.value = false; itemForm.reset() }
    })
  } else {
    itemForm.transform(() => data).post(route('inventory.store'), {
      onSuccess: () => { showItemModal.value = false; itemForm.reset() }
    })
  }
}

// Treatment links within item modal
const addTreatmentLink = () => {
  itemForm.treatment_links.push({ treatment_id: null, quantity_used: 1, name: '' })
}
const removeTreatmentLink = (i) => itemForm.treatment_links.splice(i, 1)
const onTreatmentSelect   = (i, id) => {
  const t = props.treatments.find(t => t.id == id)
  if (t) itemForm.treatment_links[i].name = t.name
}
const availableTreatments = (currentIdx) => {
  const used = itemForm.treatment_links
    .filter((_, i) => i !== currentIdx)
    .map(l => l.treatment_id)
  return props.treatments.filter(t => !used.includes(t.id))
}

// ── Delete item ───────────────────────────────────────────────────────────────
const confirmDelete = ref({ show: false, item: null })
const doDelete      = () => {
  router.delete(route('inventory.destroy', confirmDelete.value.item.id), {
    onFinish: () => confirmDelete.value = { show: false, item: null }
  })
}

// ── Movement modal ────────────────────────────────────────────────────────────
const showMovModal = ref(false)
const movItem      = ref(null)

const movForm = useForm({
  mov_type: 'in',
  quantity:  0,
  reason:    '',
})

const openMovement = (item) => {
  movItem.value     = item
  movForm.mov_type  = 'in'
  movForm.quantity  = 0
  movForm.reason    = ''
  showMovModal.value = true
}

const submitMovement = () => {
  movForm.transform(() => ({
    type:     movForm.mov_type,
    quantity: movForm.quantity,
    reason:   movForm.reason,
  })).post(route('inventory.movements.store', movItem.value.id), {
    onSuccess: () => { showMovModal.value = false; movForm.reset() }
  })
}

const movLabel = { in: 'Entrada', out: 'Salida / Consumo', adjustment: 'Ajuste de inventario' }
const movHelp  = computed(() => ({
  in:         `Stock actual: ${movItem.value?.stock} → ${(parseFloat(movItem.value?.stock || 0) + parseFloat(movForm.quantity || 0)).toFixed(2)}`,
  out:        `Stock actual: ${movItem.value?.stock} → ${Math.max(0, parseFloat(movItem.value?.stock || 0) - parseFloat(movForm.quantity || 0)).toFixed(2)}`,
  adjustment: `Stock actual: ${movItem.value?.stock} → ${parseFloat(movForm.quantity || 0).toFixed(2)} (nuevo total)`,
})[movForm.mov_type])

// ── Category modal ────────────────────────────────────────────────────────────
const showCatModal = ref(false)
const catForm      = useForm({ cat_name: '', color: '#6B7280' })

const submitCategory = () => {
  catForm.transform(() => ({ name: catForm.cat_name, color: catForm.color }))
    .post(route('inventory.categories.store'), {
      onSuccess: () => { showCatModal.value = false; catForm.reset() }
    })
}

const confirmDeleteCat = ref({ show: false, cat: null })
const doDeleteCat      = () => {
  router.delete(route('inventory.categories.destroy', confirmDeleteCat.value.cat.id), {
    onFinish: () => confirmDeleteCat.value = { show: false, cat: null }
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const fmt     = (v) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP' }).format(v || 0)

const stockClass = (item) => {
  const s = parseFloat(item.stock), m = parseFloat(item.min_stock)
  if (s <= 0)           return 'text-red-600 font-bold'
  if (s <= m)           return 'text-orange-500 font-semibold'
  if (s <= m * 1.5)     return 'text-yellow-600'
  return 'text-green-600'
}

const stockBarPct = (item) => {
  const s = parseFloat(item.stock), m = parseFloat(item.min_stock) || 1
  return Math.min(100, Math.round((s / (m * 3)) * 100))
}

const stockBarColor = (item) => {
  const s = parseFloat(item.stock), m = parseFloat(item.min_stock)
  if (s <= 0)       return 'bg-red-500'
  if (s <= m)       return 'bg-orange-400'
  if (s <= m * 1.5) return 'bg-yellow-400'
  return 'bg-green-500'
}

const lowStockItems = computed(() => props.items.filter(i => i.is_low_stock))
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Inventario</h1>
    </template>
    <Head title="Inventario" />

    <div class="space-y-5">

      <!-- Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <StatCard title="Total artículos" :value="stats?.total" :icon="CubeIcon" color="teal" />
        <StatCard title="Bajo stock" :value="stats?.low_stock" :icon="ExclamationTriangleIcon"
          :color="stats?.low_stock > 0 ? 'amber' : 'green'" />
        <StatCard title="Valor estimado" :value="fmt(stats?.total_value)" :icon="CurrencyDollarIcon" color="navy" />
      </div>

      <!-- Low stock alerts -->
      <div v-if="lowStockItems.length" class="card p-4 border-l-4 border-orange-400">
        <div class="flex items-center gap-2 mb-3">
          <ExclamationTriangleIcon class="w-5 h-5 text-orange-500" />
          <h3 class="font-semibold text-navy-900 dark:text-white text-sm">
            Alertas de reposición ({{ lowStockItems.length }})
          </h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
          <div v-for="item in lowStockItems" :key="item.id"
            class="flex items-center justify-between px-3 py-2 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
            <div class="min-w-0">
              <p class="text-sm font-medium text-navy-900 dark:text-white truncate">{{ item.name }}</p>
              <p class="text-xs text-orange-600 dark:text-orange-400">
                Stock: <strong>{{ item.stock }}</strong> {{ item.unit }} — Mín: {{ item.min_stock }}
              </p>
            </div>
            <button v-if="!can.admin" @click="openMovement(item)"
              class="btn-outline text-xs py-1 ml-2 flex-shrink-0">Reponer</button>
            <button v-else @click="openMovement(item)"
              class="btn-outline text-xs py-1 ml-2 flex-shrink-0">Reponer</button>
          </div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- Tab: Todos / Bajo stock -->
        <div class="flex rounded-xl border border-navy-200 dark:border-navy-700 overflow-hidden text-sm">
          <button @click="activeTab = 'all'"
            :class="['px-4 py-2 transition-colors', activeTab === 'all' ? 'bg-navy-900 text-white dark:bg-white dark:text-navy-900' : 'text-navy-500 hover:bg-navy-50 dark:hover:bg-navy-800']">
            Todos ({{ items.length }})
          </button>
          <button @click="activeTab = 'low'"
            :class="['px-4 py-2 transition-colors flex items-center gap-1', activeTab === 'low' ? 'bg-orange-500 text-white' : 'text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/20']">
            <ExclamationTriangleIcon class="w-3.5 h-3.5" />
            Bajo stock ({{ lowStockItems.length }})
          </button>
        </div>

        <!-- Category filter chips -->
        <button @click="selectedCategory = null"
          :class="['badge cursor-pointer transition-colors', selectedCategory === null ? 'bg-navy-800 text-white dark:bg-white dark:text-navy-900' : 'badge-gray hover:bg-navy-100 dark:hover:bg-navy-800']">
          Todas
        </button>
        <button v-for="cat in categories" :key="cat.id"
          @click="selectedCategory = selectedCategory === cat.id ? null : cat.id"
          :class="['badge cursor-pointer transition-colors', selectedCategory === cat.id ? 'text-white' : 'opacity-75 hover:opacity-100']"
          :style="selectedCategory === cat.id ? `background:${cat.color}` : `background:${cat.color}22;color:${cat.color}`">
          {{ cat.name }}
        </button>

        <!-- Search -->
        <div class="relative ml-auto">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-navy-400" />
          <input v-model="search" type="text" placeholder="Buscar artículo o código…" class="input pl-9 w-64" />
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <button v-if="can.admin" @click="showCatModal = true" class="btn-outline text-xs gap-1.5">
            <TagIcon class="w-3.5 h-3.5" /> Categoría
          </button>
          <button v-if="can.admin" @click="openAdd" class="btn-primary gap-1.5">
            <PlusIcon class="w-4 h-4" /> Nuevo artículo
          </button>
        </div>
      </div>

      <!-- Items table -->
      <div class="card">
        <div v-if="!filtered.length" class="py-16 text-center text-navy-400 text-sm">
          <CubeIcon class="w-10 h-10 mx-auto mb-3 opacity-30" />
          No se encontraron artículos.
          <button v-if="can.admin" @click="openAdd" class="text-teal-500 underline ml-1">Agregar uno</button>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-navy-100 dark:border-navy-800 text-xs text-navy-400 uppercase tracking-wide">
                <th class="px-4 py-3 text-left">Artículo</th>
                <th class="px-4 py-3 text-left hidden sm:table-cell">Categoría</th>
                <th class="px-4 py-3 text-left hidden md:table-cell">Proveedor / SKU</th>
                <th class="px-4 py-3 text-center">Stock</th>
                <th class="px-4 py-3 text-right hidden sm:table-cell">Costo unit.</th>
                <th class="px-4 py-3 text-center hidden md:table-cell">Tratamientos</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-navy-100 dark:divide-navy-800">
              <tr v-for="item in filtered" :key="item.id"
                :class="['hover:bg-navy-50/40 dark:hover:bg-navy-800/30 transition-colors', !item.is_active ? 'opacity-50' : '']">

                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <ExclamationTriangleIcon v-if="item.is_low_stock" class="w-4 h-4 text-orange-500 flex-shrink-0" />
                    <div>
                      <p class="font-medium text-navy-900 dark:text-white">{{ item.name }}</p>
                      <p v-if="item.description" class="text-xs text-navy-400 truncate max-w-[200px]">{{ item.description }}</p>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3 hidden sm:table-cell">
                  <span v-if="item.category" class="badge text-xs"
                    :style="`background:${item.category.color}22;color:${item.category.color}`">
                    {{ item.category.name }}
                  </span>
                  <span v-else class="text-navy-300 text-xs">—</span>
                </td>

                <td class="px-4 py-3 hidden md:table-cell">
                  <p class="text-navy-600 dark:text-navy-400 text-xs">{{ item.supplier || '—' }}</p>
                  <p v-if="item.sku" class="text-navy-400 text-xs font-mono">{{ item.sku }}</p>
                </td>

                <td class="px-4 py-3">
                  <div class="flex flex-col items-center gap-1 min-w-[80px]">
                    <span :class="['text-sm', stockClass(item)]">
                      {{ item.stock }} <span class="text-navy-400 font-normal text-xs">{{ item.unit }}</span>
                    </span>
                    <div class="w-full bg-navy-100 dark:bg-navy-700 rounded-full h-1.5">
                      <div :class="['h-1.5 rounded-full transition-all', stockBarColor(item)]"
                        :style="`width:${stockBarPct(item)}%`"></div>
                    </div>
                    <span class="text-xs text-navy-400">Mín: {{ item.min_stock }}</span>
                  </div>
                </td>

                <td class="px-4 py-3 text-right text-navy-700 dark:text-navy-300 hidden sm:table-cell">
                  {{ fmt(item.cost_per_unit) }}
                </td>

                <td class="px-4 py-3 text-center hidden md:table-cell">
                  <span v-if="item.treatments?.length"
                    class="badge badge-blue text-xs cursor-pointer"
                    @click="openEdit(item); modalTab = 'treatments'"
                    title="Ver tratamientos vinculados">
                    <LinkIcon class="w-3 h-3 mr-1" />{{ item.treatments.length }}
                  </span>
                  <span v-else class="text-navy-300 text-xs">—</span>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openMovement(item)"
                      class="btn-ghost p-1.5 text-teal-500 hover:text-teal-600" title="Registrar movimiento">
                      <AdjustmentsHorizontalIcon class="w-4 h-4" />
                    </button>
                    <button v-if="can.admin" @click="openEdit(item)" class="btn-ghost p-1.5" title="Editar">
                      <PencilIcon class="w-4 h-4" />
                    </button>
                    <button v-if="can.admin" @click="confirmDelete = { show: true, item }"
                      class="btn-ghost p-1.5 text-red-400 hover:text-red-600" title="Eliminar">
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Category management strip -->
      <div v-if="can.admin && categories.length" class="card p-4">
        <h3 class="text-xs text-navy-400 uppercase tracking-wider mb-3">Gestionar categorías</h3>
        <div class="flex flex-wrap gap-2">
          <div v-for="cat in categories" :key="cat.id"
            class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm border border-navy-100 dark:border-navy-700">
            <span class="w-3 h-3 rounded-full" :style="`background:${cat.color}`"></span>
            <span class="text-navy-700 dark:text-navy-200">{{ cat.name }}</span>
            <span class="text-navy-400 text-xs">({{ cat.items_count }})</span>
            <button v-if="cat.items_count === 0"
              @click="confirmDeleteCat = { show: true, cat }"
              class="text-red-400 hover:text-red-600 ml-1">
              <TrashIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Item modal ─────────────────────────────────────────────────────── -->
    <Modal :show="showItemModal" :title="editingId ? 'Editar artículo' : 'Nuevo artículo'" size="lg"
      @close="showItemModal = false; itemForm.reset()">

      <!-- Tabs -->
      <div class="flex border-b border-navy-200 dark:border-navy-700 mb-5 -mt-1">
        <button @click="modalTab = 'info'"
          :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
            modalTab === 'info' ? 'border-teal-500 text-teal-600' : 'border-transparent text-navy-500 hover:text-navy-700']">
          Información
        </button>
        <button @click="modalTab = 'treatments'"
          :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors flex items-center gap-1.5',
            modalTab === 'treatments' ? 'border-teal-500 text-teal-600' : 'border-transparent text-navy-500 hover:text-navy-700']">
          <LinkIcon class="w-3.5 h-3.5" /> Tratamientos vinculados
          <span v-if="itemForm.treatment_links.length" class="badge-teal badge text-xs ml-1">{{ itemForm.treatment_links.length }}</span>
        </button>
      </div>

      <form @submit.prevent="submitItem">

        <!-- Info tab -->
        <div v-show="modalTab === 'info'" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="label">Nombre *</label>
              <input v-model="itemForm.item_name" type="text" class="input" required />
            </div>
            <div>
              <label class="label">Categoría</label>
              <select v-model="itemForm.inventory_category_id" class="input">
                <option :value="null">Sin categoría</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Unidad *</label>
              <select v-model="itemForm.unit" class="input">
                <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
              </select>
            </div>
            <div>
              <label class="label">Stock actual *</label>
              <input v-model="itemForm.stock" type="number" min="0" step="0.01" class="input" required />
            </div>
            <div>
              <label class="label">Stock mínimo (alerta) *</label>
              <input v-model="itemForm.min_stock" type="number" min="0" step="0.01" class="input" required />
            </div>
            <div>
              <label class="label">Costo por unidad (MXN)</label>
              <input v-model="itemForm.cost_per_unit" type="number" min="0" step="0.01" class="input" />
            </div>
            <div>
              <label class="label">Proveedor</label>
              <input v-model="itemForm.supplier" type="text" class="input" placeholder="Nombre del proveedor…" />
            </div>
            <div>
              <label class="label">SKU / Código</label>
              <input v-model="itemForm.sku" type="text" class="input" placeholder="Código interno…" />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Descripción</label>
              <textarea v-model="itemForm.description" rows="2" class="input" />
            </div>
            <div class="sm:col-span-2 flex items-center gap-3">
              <input id="item_active" v-model="itemForm.is_active" type="checkbox" class="w-4 h-4 rounded text-teal-500" />
              <label for="item_active" class="text-sm text-navy-700 dark:text-navy-200 cursor-pointer">Artículo activo</label>
            </div>
          </div>
        </div>

        <!-- Treatments tab -->
        <div v-show="modalTab === 'treatments'" class="space-y-3">
          <p class="text-sm text-navy-500 dark:text-navy-400">
            Define cuántas unidades de este insumo se consumen por cada tratamiento.
          </p>

          <div v-if="!itemForm.treatment_links.length" class="py-6 text-center text-navy-400 text-sm">
            Sin tratamientos vinculados.
          </div>

          <div v-for="(link, i) in itemForm.treatment_links" :key="i"
            class="flex items-center gap-3 p-3 bg-navy-50 dark:bg-navy-800 rounded-xl">
            <div class="flex-1">
              <label class="label">Tratamiento</label>
              <select v-model="link.treatment_id" @change="onTreatmentSelect(i, $event.target.value)" class="input text-sm">
                <option :value="null">Seleccionar…</option>
                <option v-for="t in availableTreatments(i)" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
            </div>
            <div class="w-36">
              <label class="label">Cant. por uso</label>
              <div class="flex items-center gap-1">
                <input v-model="link.quantity_used" type="number" min="0.01" step="0.01" class="input" />
                <span class="text-xs text-navy-400 flex-shrink-0">{{ itemForm.unit }}</span>
              </div>
            </div>
            <button type="button" @click="removeTreatmentLink(i)"
              class="btn-ghost p-1.5 text-red-400 hover:text-red-600 mt-5">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>

          <button type="button" @click="addTreatmentLink" class="btn-outline w-full gap-1.5 text-sm">
            <PlusIcon class="w-4 h-4" /> Vincular tratamiento
          </button>
        </div>

        <div v-if="Object.keys(itemForm.errors).length" class="p-3 bg-red-50 rounded-xl text-sm text-red-600 mt-4">
          <p v-for="(err, key) in itemForm.errors" :key="key">{{ err }}</p>
        </div>

        <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-navy-100 dark:border-navy-800">
          <button type="button" @click="showItemModal = false; itemForm.reset()" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="itemForm.processing">
            {{ editingId ? 'Guardar cambios' : 'Crear artículo' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- ── Movement modal ────────────────────────────────────────────────── -->
    <Modal :show="showMovModal" title="Registrar movimiento" size="sm"
      @close="showMovModal = false; movForm.reset()">
      <form @submit.prevent="submitMovement" class="space-y-4">
        <div class="p-3 bg-navy-50 dark:bg-navy-800 rounded-xl text-sm">
          <p class="font-medium text-navy-900 dark:text-white">{{ movItem?.name }}</p>
          <p class="text-navy-500">Stock actual: <strong>{{ movItem?.stock }} {{ movItem?.unit }}</strong></p>
        </div>

        <div>
          <label class="label">Tipo de movimiento *</label>
          <div class="grid grid-cols-3 gap-2">
            <button v-for="(label, type) in movLabel" :key="type" type="button"
              @click="movForm.mov_type = type"
              :class="['py-2 px-3 rounded-xl text-xs font-medium border-2 transition-colors text-center',
                movForm.mov_type === type
                  ? type === 'in' ? 'border-green-500 bg-green-50 text-green-700'
                    : type === 'out' ? 'border-red-400 bg-red-50 text-red-600'
                    : 'border-blue-400 bg-blue-50 text-blue-600'
                  : 'border-navy-200 dark:border-navy-600 text-navy-500 hover:border-navy-400']">
              <component :is="type === 'in' ? ArrowUpIcon : type === 'out' ? ArrowDownIcon : AdjustmentsHorizontalIcon"
                class="w-4 h-4 mx-auto mb-1" />
              {{ label }}
            </button>
          </div>
        </div>

        <div>
          <label class="label">
            {{ movForm.mov_type === 'adjustment' ? 'Nuevo stock total' : 'Cantidad' }} *
          </label>
          <div class="flex items-center gap-2">
            <input v-model="movForm.quantity" type="number" min="0" step="0.01" class="input flex-1" required />
            <span class="text-sm text-navy-500 flex-shrink-0">{{ movItem?.unit }}</span>
          </div>
          <p class="text-xs text-navy-400 mt-1">{{ movHelp }}</p>
        </div>

        <div>
          <label class="label">Razón / Nota</label>
          <input v-model="movForm.reason" type="text" class="input"
            :placeholder="movForm.mov_type === 'in' ? 'Ej. Compra a proveedor…' : movForm.mov_type === 'out' ? 'Ej. Uso en tratamiento…' : 'Ej. Conteo físico…'" />
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showMovModal = false" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="movForm.processing">Registrar</button>
        </div>
      </form>
    </Modal>

    <!-- ── Category modal ────────────────────────────────────────────────── -->
    <Modal :show="showCatModal" title="Nueva categoría" size="sm" @close="showCatModal = false; catForm.reset()">
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
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showCatModal = false" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="catForm.processing">Crear</button>
        </div>
      </form>
    </Modal>

    <!-- Confirm delete item -->
    <ConfirmModal :show="confirmDelete.show" title="Eliminar artículo"
      :message="`¿Eliminar '${confirmDelete.item?.name}'? Se eliminará también su historial de movimientos.`"
      confirm-text="Eliminar" @confirm="doDelete" @cancel="confirmDelete.show = false" />

    <!-- Confirm delete category -->
    <ConfirmModal :show="confirmDeleteCat.show" title="Eliminar categoría"
      :message="`¿Eliminar la categoría '${confirmDeleteCat.cat?.name}'?`"
      confirm-text="Eliminar" @confirm="doDeleteCat" @cancel="confirmDeleteCat.show = false" />
  </AppLayout>
</template>
