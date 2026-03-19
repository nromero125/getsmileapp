<script setup>
import { ref } from 'vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { ArrowLeftIcon, ArrowDownTrayIcon, PlusIcon, CalendarDaysIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ invoice: Object })
const can = usePage().props.can

const showPaymentModal = ref(false)
const paymentForm = useForm({
  amount: '',
  payment_date: new Date().toISOString().slice(0, 10),
  method: 'card',
  reference: '',
  notes: '',
})

const recordPayment = () => {
  paymentForm.post(route('invoices.payment', props.invoice.id), {
    onSuccess: () => { showPaymentModal.value = false; paymentForm.reset() }
  })
}

const formatCurrency = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0)
const formatDate = (d) => d ? new Date(d).toLocaleDateString('es-MX', { year:'numeric', month:'short', day:'numeric' }) : '—'

const balanceDue = () => (parseFloat(props.invoice.total) - parseFloat(props.invoice.amount_paid)).toFixed(2)

// Installment plan
const showInstallmentModal  = ref(false)
const confirmDeletePlan     = ref(false)
const showPayInstallModal   = ref(false)
const payingInstallment     = ref(null)

const installmentForm = useForm({
  installments_count: 3,
  first_due_date: new Date().toISOString().slice(0, 10),
  frequency: 'monthly',
})

const payInstallForm = useForm({
  payment_date: new Date().toISOString().slice(0, 10),
  method: 'card',
  reference: '',
})

const createPlan = () => {
  installmentForm.post(route('invoices.installments.store', props.invoice.id), {
    onSuccess: () => { showInstallmentModal.value = false; installmentForm.reset() }
  })
}

const openPayInstall = (installment) => {
  payingInstallment.value = installment
  payInstallForm.payment_date = new Date().toISOString().slice(0, 10)
  payInstallForm.method = 'card'
  payInstallForm.reference = ''
  showPayInstallModal.value = true
}

const doPayInstall = () => {
  payInstallForm.post(
    route('invoices.installments.pay', { invoice: props.invoice.id, installment: payingInstallment.value.id }),
    { onSuccess: () => { showPayInstallModal.value = false; payingInstallment.value = null } }
  )
}

const doDeletePlan = () => {
  router.delete(route('invoices.installments.destroy', props.invoice.id), {
    onFinish: () => { confirmDeletePlan.value = false }
  })
}

const installmentStatusLabel = { paid: 'Pagada', overdue: 'Vencida', pending: 'Pendiente' }
const installmentStatusClass = { paid: 'badge-teal', overdue: 'bg-red-100 text-red-600', pending: 'badge-gray' }
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('invoices.index')" class="btn-ghost p-1.5"><ArrowLeftIcon class="w-4 h-4" /></Link>
        <div>
          <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">{{ invoice.invoice_number }}</h1>
          <p class="text-xs text-navy-400">Factura</p>
        </div>
      </div>
    </template>
    <Head :title="invoice.invoice_number" />

    <div class="max-w-3xl space-y-4">
      <!-- Header Card -->
      <div class="card p-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div>
            <p class="text-sm text-navy-500 mb-1">Facturar a</p>
            <p class="text-lg font-semibold text-navy-900 dark:text-white">{{ invoice.patient?.first_name }} {{ invoice.patient?.last_name }}</p>
            <p class="text-sm text-navy-500 mt-0.5">{{ invoice.patient?.phone }}</p>
          </div>
          <div class="text-right">
            <StatusBadge :status="invoice.status" type="invoice" />
            <p class="text-sm text-navy-500 mt-2">Fecha: <span class="text-navy-700 dark:text-navy-300">{{ formatDate(invoice.invoice_date) }}</span></p>
            <p v-if="invoice.due_date" class="text-sm text-navy-500">Vencimiento: <span class="text-navy-700 dark:text-navy-300">{{ formatDate(invoice.due_date) }}</span></p>
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
            <tr v-for="item in invoice.items" :key="item.id" class="border-b border-navy-100 dark:border-navy-800">
              <td class="px-4 py-3 text-navy-900 dark:text-white">{{ item.description }}</td>
              <td class="px-4 py-3 text-right text-navy-600 dark:text-navy-400">{{ item.quantity }}</td>
              <td class="px-4 py-3 text-right text-navy-600 dark:text-navy-400">{{ formatCurrency(item.unit_price) }}</td>
              <td class="px-4 py-3 text-right font-medium text-navy-900 dark:text-white">{{ formatCurrency(item.total) }}</td>
            </tr>
          </tbody>
          <tfoot class="border-t-2 border-navy-200 dark:border-navy-700">
            <tr><td colspan="3" class="px-4 py-2 text-right text-sm text-navy-500">Subtotal</td><td class="px-4 py-2 text-right font-medium">{{ formatCurrency(invoice.subtotal) }}</td></tr>
            <tr v-if="invoice.discount_amount > 0"><td colspan="3" class="px-4 py-2 text-right text-sm text-green-600">Descuento ({{ invoice.discount_percent }}%)</td><td class="px-4 py-2 text-right text-green-600">-{{ formatCurrency(invoice.discount_amount) }}</td></tr>
            <tr v-if="invoice.tax_amount > 0"><td colspan="3" class="px-4 py-2 text-right text-sm text-navy-500">Impuesto ({{ invoice.tax_percent }}%)</td><td class="px-4 py-2 text-right">{{ formatCurrency(invoice.tax_amount) }}</td></tr>
            <tr class="bg-navy-50 dark:bg-navy-800"><td colspan="3" class="px-4 py-3 text-right font-bold text-navy-900 dark:text-white">Total</td><td class="px-4 py-3 text-right font-bold text-xl text-navy-900 dark:text-white">{{ formatCurrency(invoice.total) }}</td></tr>
          </tfoot>
        </table>
      </div>

      <!-- Payment Summary -->
      <div class="card p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="grid grid-cols-3 gap-6">
            <div><p class="text-xs text-navy-400 uppercase tracking-wide mb-1">Total</p><p class="text-lg font-bold text-navy-900 dark:text-white">{{ formatCurrency(invoice.total) }}</p></div>
            <div><p class="text-xs text-navy-400 uppercase tracking-wide mb-1">Pagado</p><p class="text-lg font-bold text-green-600">{{ formatCurrency(invoice.amount_paid) }}</p></div>
            <div><p class="text-xs text-navy-400 uppercase tracking-wide mb-1">Saldo</p><p :class="['text-lg font-bold', balanceDue() > 0 ? 'text-red-500' : 'text-green-600']">{{ formatCurrency(balanceDue()) }}</p></div>
          </div>
          <div class="flex gap-2">
            <a :href="route('invoices.pdf', invoice.id)" target="_blank" class="btn-outline">
              <ArrowDownTrayIcon class="w-4 h-4" /> PDF
            </a>
            <button v-if="can.billing && invoice.status !== 'paid'" @click="showPaymentModal = true" class="btn-primary">
              <PlusIcon class="w-4 h-4" /> Registrar Pago
            </button>
          </div>
        </div>
      </div>

      <!-- Payment History -->
      <div v-if="invoice.payments?.length" class="card p-6">
        <h3 class="font-semibold text-navy-900 dark:text-white mb-3">Historial de Pagos</h3>
        <div class="space-y-2">
          <div v-for="payment in invoice.payments" :key="payment.id"
            class="flex items-center justify-between py-2 border-b border-navy-100 dark:border-navy-800 last:border-0 text-sm">
            <div>
              <p class="font-medium text-navy-900 dark:text-white capitalize">{{ payment.method }}</p>
              <p class="text-xs text-navy-400">{{ formatDate(payment.payment_date) }}</p>
            </div>
            <p class="font-semibold text-green-600">{{ formatCurrency(payment.amount) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Installment Plan -->
    <div class="card p-6 max-w-3xl" v-if="can.billing || invoice.installments?.length">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-navy-900 dark:text-white flex items-center gap-2">
          <CalendarDaysIcon class="w-5 h-5 text-teal-500" /> Plan de pagos
        </h3>
        <div class="flex gap-2">
          <button v-if="!invoice.installments?.length && can.billing && invoice.status !== 'paid'"
            @click="showInstallmentModal = true" class="btn-outline text-xs gap-1">
            <PlusIcon class="w-3.5 h-3.5" /> Crear plan
          </button>
          <button v-if="invoice.installments?.length && can.billing"
            @click="confirmDeletePlan = true" class="btn-ghost p-1.5 text-red-400 hover:text-red-600" title="Eliminar plan">
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div v-if="!invoice.installments?.length" class="text-sm text-navy-400 py-2">
        No hay plan de pagos. {{ invoice.status !== 'paid' ? 'Crea uno para dividir el saldo en cuotas.' : '' }}
      </div>

      <div v-else class="space-y-2">
        <div v-for="inst in invoice.installments" :key="inst.id"
          class="flex items-center justify-between p-3 rounded-xl border border-navy-100 dark:border-navy-700">
          <div class="flex items-center gap-3">
            <span class="w-7 h-7 rounded-full bg-navy-100 dark:bg-navy-700 flex items-center justify-center text-xs font-bold text-navy-600 dark:text-navy-300">
              {{ inst.installment_number }}
            </span>
            <div>
              <p class="text-sm font-medium text-navy-900 dark:text-white">{{ formatCurrency(inst.amount) }}</p>
              <p class="text-xs text-navy-400">{{ formatDate(inst.due_date) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span :class="['badge text-xs', installmentStatusClass[inst.status]]">
              {{ installmentStatusLabel[inst.status] }}
            </span>
            <button v-if="inst.status !== 'paid' && can.billing"
              @click="openPayInstall(inst)" class="btn-outline text-xs py-1">
              Pagar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create installment plan modal -->
    <Modal :show="showInstallmentModal" title="Crear plan de pagos" size="sm" @close="showInstallmentModal = false">
      <form @submit.prevent="createPlan" class="space-y-4">
        <div>
          <label class="label">Número de cuotas *</label>
          <input v-model="installmentForm.installments_count" type="number" min="2" max="60" class="input" required />
          <p class="text-xs text-navy-400 mt-1">
            Cada cuota: {{ formatCurrency(balanceDue() / installmentForm.installments_count) }}
          </p>
        </div>
        <div>
          <label class="label">Primera cuota *</label>
          <input v-model="installmentForm.first_due_date" type="date" class="input" required />
        </div>
        <div>
          <label class="label">Periodicidad *</label>
          <select v-model="installmentForm.frequency" class="input">
            <option value="weekly">Semanal</option>
            <option value="biweekly">Quincenal</option>
            <option value="monthly">Mensual</option>
          </select>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showInstallmentModal = false" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="installmentForm.processing">Crear plan</button>
        </div>
      </form>
    </Modal>

    <!-- Pay installment modal -->
    <Modal :show="showPayInstallModal" :title="`Pagar cuota #${payingInstallment?.installment_number}`" size="sm"
      @close="showPayInstallModal = false">
      <form @submit.prevent="doPayInstall" class="space-y-4">
        <p class="text-sm text-navy-600 dark:text-navy-300">
          Monto: <strong>{{ formatCurrency(payingInstallment?.amount) }}</strong>
        </p>
        <div>
          <label class="label">Fecha *</label>
          <input v-model="payInstallForm.payment_date" type="date" class="input" required />
        </div>
        <div>
          <label class="label">Método *</label>
          <select v-model="payInstallForm.method" class="input">
            <option value="cash">Efectivo</option>
            <option value="card">Tarjeta</option>
            <option value="transfer">Transferencia</option>
            <option value="insurance">Seguro</option>
            <option value="other">Otro</option>
          </select>
        </div>
        <div>
          <label class="label">Referencia</label>
          <input v-model="payInstallForm.reference" type="text" class="input" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showPayInstallModal = false" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="payInstallForm.processing">Registrar pago</button>
        </div>
      </form>
    </Modal>

    <ConfirmModal
      :show="confirmDeletePlan"
      title="Eliminar plan de pagos"
      message="¿Eliminar el plan de pagos? Las cuotas pendientes serán eliminadas."
      confirm-text="Eliminar"
      @confirm="doDeletePlan"
      @cancel="confirmDeletePlan = false"
    />

    <!-- Payment Modal -->
    <Modal :show="showPaymentModal" title="Registrar Pago" size="sm" @close="showPaymentModal = false">
      <form @submit.prevent="recordPayment" class="space-y-4">
        <div>
          <label class="label">Monto *</label>
          <input v-model="paymentForm.amount" type="number" step="0.01" :max="balanceDue()" class="input" required />
        </div>
        <div>
          <label class="label">Fecha *</label>
          <input v-model="paymentForm.payment_date" type="date" class="input" required />
        </div>
        <div>
          <label class="label">Método *</label>
          <select v-model="paymentForm.method" class="input">
            <option value="cash">Efectivo</option>
            <option value="card">Tarjeta</option>
            <option value="transfer">Transferencia bancaria</option>
            <option value="insurance">Seguro</option>
            <option value="other">Otro</option>
          </select>
        </div>
        <div>
          <label class="label">Referencia</label>
          <input v-model="paymentForm.reference" type="text" class="input" placeholder="# de transacción, # de cheque…" />
        </div>
        <div class="flex gap-3 justify-end pt-2">
          <button type="button" @click="showPaymentModal = false" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="paymentForm.processing">Registrar Pago</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>
