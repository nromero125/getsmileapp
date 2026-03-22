<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { ArrowLeftIcon, DocumentDuplicateIcon, CheckIcon, XMarkIcon, ArrowPathIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ quote: Object })
const can   = usePage().props.can

const confirmConvert  = ref(false)
const confirmDelete   = ref(false)
const showStatusModal = ref(false)
const selectedStatus  = ref(props.quote.status)

const setStatus = () => {
  router.patch(route('quotes.status', props.quote.id), { status: selectedStatus.value }, {
    onSuccess: () => { showStatusModal.value = false }
  })
}

const doConvert = () => router.post(route('quotes.convert', props.quote.id))
const doDelete  = () => router.delete(route('quotes.destroy', props.quote.id))

const fmt     = (v) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP' }).format(v || 0)
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'

const statusColors = {
  draft: 'badge-gray', sent: 'badge-blue', accepted: 'badge-teal',
  rejected: 'bg-red-100 text-red-600', expired: 'bg-orange-100 text-orange-600',
}
const statusLabels = {
  draft: 'Borrador', sent: 'Enviada', accepted: 'Aceptada', rejected: 'Rechazada', expired: 'Expirada',
}
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('quotes.index')" class="btn-ghost p-1.5"><ArrowLeftIcon class="w-4 h-4" /></Link>
        <div>
          <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">{{ quote.quote_number }}</h1>
          <p class="text-xs text-navy-400">Cotización</p>
        </div>
      </div>
    </template>
    <Head :title="quote.quote_number" />

    <div class="max-w-3xl space-y-4">
      <!-- Header -->
      <div class="card p-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div>
            <p class="text-sm text-navy-500 mb-1">Paciente</p>
            <p class="text-lg font-semibold text-navy-900 dark:text-white">{{ quote.patient?.first_name }} {{ quote.patient?.last_name }}</p>
            <p class="text-sm text-navy-500 mt-0.5">{{ quote.patient?.phone }}</p>
          </div>
          <div class="text-right space-y-2">
            <div>
              <span :class="['badge text-xs', statusColors[quote.status]]">{{ statusLabels[quote.status] }}</span>
            </div>
            <p class="text-sm text-navy-500">Fecha: <span class="text-navy-700 dark:text-navy-300">{{ fmtDate(quote.quote_date) }}</span></p>
            <p v-if="quote.valid_until" class="text-sm text-navy-500">Válida hasta: <span class="text-navy-700 dark:text-navy-300">{{ fmtDate(quote.valid_until) }}</span></p>
            <div v-if="quote.invoice" class="mt-2">
              <Link :href="route('invoices.show', quote.invoice.id)" class="text-xs text-teal-600 hover:underline">
                → Factura {{ quote.invoice.invoice_number }}
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Items -->
      <div class="card overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-navy-50 dark:bg-navy-800 border-b border-navy-100 dark:border-navy-700">
              <th class="text-left px-4 py-3 font-semibold text-navy-700 dark:text-navy-300">Descripción</th>
              <th class="text-right px-4 py-3 font-semibold text-navy-700 dark:text-navy-300">Cant.</th>
              <th class="text-right px-4 py-3 font-semibold text-navy-700 dark:text-navy-300">Precio unit.</th>
              <th class="text-right px-4 py-3 font-semibold text-navy-700 dark:text-navy-300">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in quote.items" :key="item.id" class="border-b border-navy-100 dark:border-navy-800">
              <td class="px-4 py-3 text-navy-900 dark:text-white">{{ item.description }}</td>
              <td class="px-4 py-3 text-right text-navy-600 dark:text-navy-400">{{ item.quantity }}</td>
              <td class="px-4 py-3 text-right text-navy-600 dark:text-navy-400">{{ fmt(item.unit_price) }}</td>
              <td class="px-4 py-3 text-right font-medium text-navy-900 dark:text-white">{{ fmt(item.total) }}</td>
            </tr>
          </tbody>
          <tfoot class="border-t-2 border-navy-200 dark:border-navy-700">
            <tr><td colspan="3" class="px-4 py-2 text-right text-sm text-navy-500">Subtotal</td><td class="px-4 py-2 text-right font-medium">{{ fmt(quote.subtotal) }}</td></tr>
            <tr v-if="quote.discount_amount > 0"><td colspan="3" class="px-4 py-2 text-right text-sm text-green-600">Descuento ({{ quote.discount_percent }}%)</td><td class="px-4 py-2 text-right text-green-600">-{{ fmt(quote.discount_amount) }}</td></tr>
            <tr v-if="quote.tax_amount > 0"><td colspan="3" class="px-4 py-2 text-right text-sm text-navy-500">ITBIS ({{ quote.tax_percent }}%)</td><td class="px-4 py-2 text-right">{{ fmt(quote.tax_amount) }}</td></tr>
            <tr class="bg-navy-50 dark:bg-navy-800"><td colspan="3" class="px-4 py-3 text-right font-bold text-navy-900 dark:text-white">Total estimado</td><td class="px-4 py-3 text-right font-bold text-xl text-navy-900 dark:text-white">{{ fmt(quote.total) }}</td></tr>
          </tfoot>
        </table>
      </div>

      <div v-if="quote.notes" class="card p-4 text-sm text-navy-600 dark:text-navy-300 italic">{{ quote.notes }}</div>

      <!-- Actions -->
      <div v-if="can.billing" class="flex flex-wrap gap-2 justify-end">
        <button @click="showStatusModal = true" class="btn-outline gap-1.5">
          <ArrowPathIcon class="w-4 h-4" /> Cambiar estado
        </button>
        <button v-if="!quote.converted_to_invoice_id && ['draft','sent','accepted'].includes(quote.status)"
          @click="confirmConvert = true" class="btn-primary gap-1.5">
          <DocumentDuplicateIcon class="w-4 h-4" /> Convertir a Factura
        </button>
        <button v-if="quote.status === 'draft'" @click="confirmDelete = true"
          class="btn-ghost text-red-400 hover:text-red-600 gap-1.5">
          <TrashIcon class="w-4 h-4" /> Eliminar
        </button>
      </div>
    </div>

    <!-- Status modal -->
    <Modal :show="showStatusModal" title="Cambiar estado" size="sm" @close="showStatusModal = false">
      <div class="space-y-4">
        <select v-model="selectedStatus" class="input">
          <option value="draft">Borrador</option>
          <option value="sent">Enviada</option>
          <option value="accepted">Aceptada</option>
          <option value="rejected">Rechazada</option>
          <option value="expired">Expirada</option>
        </select>
        <div class="flex justify-end gap-3">
          <button @click="showStatusModal = false" class="btn-outline">Cancelar</button>
          <button @click="setStatus" class="btn-primary">Guardar</button>
        </div>
      </div>
    </Modal>

    <ConfirmModal
      :show="confirmConvert"
      title="Convertir a Factura"
      message="Se creará una nueva factura con los datos de esta cotización. ¿Continuar?"
      confirm-text="Convertir"
      @confirm="doConvert"
      @cancel="confirmConvert = false"
    />
    <ConfirmModal
      :show="confirmDelete"
      title="Eliminar cotización"
      :message="`¿Eliminar la cotización ${quote.quote_number}?`"
      confirm-text="Eliminar"
      @confirm="doDelete"
      @cancel="confirmDelete = false"
    />
  </AppLayout>
</template>
