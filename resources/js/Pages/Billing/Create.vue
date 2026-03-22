<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ArrowLeftIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  patients: Array,
  treatments: Array,
  patient_id: [String, Number],
  ncfSequences: Object,
})

const form = useForm({
  patient_id: props.patient_id || '',
  invoice_date: new Date().toISOString().slice(0, 10),
  due_date: '',
  notes: '',
  ncf_type: '',
  discount_percent: 0,
  tax_percent: 0,
  items: [{ description: '', quantity: 1, unit_price: 0, treatment_id: null, discount: 0 }],
})

const ncfTypes = [
  { value: 'B01', label: 'B01 — Crédito Fiscal' },
  { value: 'B02', label: 'B02 — Consumo' },
]

const selectedNcfAvailable = computed(() => {
  if (!form.ncf_type) return true
  const seq = props.ncfSequences?.[form.ncf_type]
  return seq && seq.remaining > 0
})

const ncfRemaining = computed(() => {
  if (!form.ncf_type) return null
  const seq = props.ncfSequences?.[form.ncf_type]
  return seq ? seq.remaining : 0
})

const addItem = () => form.items.push({ description: '', quantity: 1, unit_price: 0, treatment_id: null, discount: 0 })
const removeItem = (i) => form.items.splice(i, 1)

const applyTreatment = (i, treatmentId) => {
  const t = props.treatments.find(t => t.id == treatmentId)
  if (t) {
    form.items[i].description = t.name
    form.items[i].unit_price = t.default_price
    form.items[i].treatment_id = t.id
  }
}

const subtotal = computed(() => form.items.reduce((s, i) => s + (i.unit_price * i.quantity) - (i.discount || 0), 0))
const discountAmt = computed(() => subtotal.value * (form.discount_percent / 100))
const taxAmt = computed(() => (subtotal.value - discountAmt.value) * (form.tax_percent / 100))
const total = computed(() => subtotal.value - discountAmt.value + taxAmt.value)

const formatCurrency = (v) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP' }).format(v || 0)

const submit = () => form.post(route('invoices.store'))
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('invoices.index')" class="btn-ghost p-1.5"><ArrowLeftIcon class="w-4 h-4" /></Link>
        <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Nueva Factura</h1>
      </div>
    </template>
    <Head title="Nueva Factura" />

    <form @submit.prevent="submit" class="max-w-3xl space-y-4">
      <!-- Header -->
      <div class="card p-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="sm:col-span-1">
            <label class="label">Paciente *</label>
            <select v-model="form.patient_id" class="input" required>
              <option value="">Seleccionar paciente…</option>
              <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Fecha de Factura *</label>
            <input v-model="form.invoice_date" type="date" class="input" required />
          </div>
          <div>
            <label class="label">Fecha de Vencimiento</label>
            <input v-model="form.due_date" type="date" class="input" />
          </div>

          <!-- NCF -->
          <div class="sm:col-span-3 border-t border-navy-100 dark:border-navy-700 pt-4">
            <label class="label">Número de Comprobante Fiscal (NCF)</label>
            <div class="flex flex-wrap items-center gap-3">
              <select v-model="form.ncf_type" class="input max-w-xs">
                <option value="">Sin NCF</option>
                <option
                  v-for="t in ncfTypes" :key="t.value" :value="t.value"
                  :disabled="!ncfSequences?.[t.value]">
                  {{ t.label }}{{ !ncfSequences?.[t.value] ? ' (no configurado)' : '' }}
                </option>
              </select>
              <span v-if="form.ncf_type && selectedNcfAvailable" class="text-xs text-navy-400">
                Quedan {{ ncfRemaining }} comprobantes disponibles
              </span>
              <span v-if="form.ncf_type && !selectedNcfAvailable" class="text-xs text-red-500 font-medium">
                ⚠ Secuencia agotada. Configura una nueva en Ajustes de Clínica.
              </span>
            </div>
            <p v-if="form.errors.ncf_type" class="text-xs text-red-500 mt-1">{{ form.errors.ncf_type }}</p>
          </div>
        </div>
      </div>

      <!-- Line Items -->
      <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-navy-900 dark:text-white">Conceptos</h3>
          <button type="button" @click="addItem" class="btn-outline"><PlusIcon class="w-4 h-4" /> Agregar Concepto</button>
        </div>
        <div class="space-y-3">
          <div v-for="(item, i) in form.items" :key="i"
            class="grid grid-cols-12 gap-2 p-3 bg-navy-50 dark:bg-navy-800 rounded-xl">
            <div class="col-span-12 sm:col-span-5">
              <label class="label">Tratamiento / Descripción</label>
              <select @change="applyTreatment(i, $event.target.value)" class="input mb-1 text-xs">
                <option value="">Aplicar del catálogo…</option>
                <option v-for="t in treatments" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
              <input v-model="item.description" type="text" placeholder="Descripción" class="input" required />
            </div>
            <div class="col-span-4 sm:col-span-2">
              <label class="label">Cant.</label>
              <input v-model="item.quantity" type="number" min="1" class="input" />
            </div>
            <div class="col-span-4 sm:col-span-2">
              <label class="label">Precio</label>
              <input v-model="item.unit_price" type="number" step="0.01" min="0" class="input" />
            </div>
            <div class="col-span-4 sm:col-span-2">
              <label class="label">Descuento</label>
              <input v-model="item.discount" type="number" step="0.01" min="0" class="input" />
            </div>
            <div class="col-span-12 sm:col-span-1 flex items-end">
              <button type="button" @click="removeItem(i)" :disabled="form.items.length <= 1" class="btn-ghost p-1.5 text-red-400 hover:text-red-600 disabled:opacity-30">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Totals -->
      <div class="card p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div class="space-y-3">
            <div><label class="label">Descuento %</label><input v-model="form.discount_percent" type="number" min="0" max="100" step="0.5" class="input" /></div>
            <div><label class="label">ITBIS %</label><input v-model="form.tax_percent" type="number" min="0" max="30" step="0.5" class="input" /></div>
            <div><label class="label">Notas</label><textarea v-model="form.notes" rows="2" class="input" /></div>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 border-b border-navy-100 dark:border-navy-800">
              <span class="text-navy-500">Subtotal</span>
              <span class="font-medium">{{ formatCurrency(subtotal) }}</span>
            </div>
            <div v-if="discountAmt > 0" class="flex justify-between py-2 border-b border-navy-100 dark:border-navy-800">
              <span class="text-green-600">Descuento</span>
              <span class="text-green-600">-{{ formatCurrency(discountAmt) }}</span>
            </div>
            <div v-if="taxAmt > 0" class="flex justify-between py-2 border-b border-navy-100 dark:border-navy-800">
              <span class="text-navy-500">ITBIS</span>
              <span>{{ formatCurrency(taxAmt) }}</span>
            </div>
            <div class="flex justify-between py-3 bg-navy-50 dark:bg-navy-800 rounded-xl px-3 mt-2">
              <span class="font-bold text-navy-900 dark:text-white">Total</span>
              <span class="font-bold text-xl text-navy-900 dark:text-white">{{ formatCurrency(total) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3">
        <Link :href="route('invoices.index')" class="btn-outline">Cancelar</Link>
        <button type="submit" class="btn-primary" :disabled="form.processing">{{ form.processing ? 'Creando…' : 'Crear Factura' }}</button>
      </div>
    </form>
  </AppLayout>
</template>
