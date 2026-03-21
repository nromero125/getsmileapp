<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { ExclamationCircleIcon, ClockIcon, CheckCircleIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ invoices: Array, summary: Object })

const selectedBucket = ref('all')

const filtered = computed(() =>
  selectedBucket.value === 'all'
    ? props.invoices
    : props.invoices.filter(i => i.bucket === selectedBucket.value)
)

const fmt     = (v) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP' }).format(v || 0)
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'

const buckets = [
  { key: 'all',   label: 'Todas', color: 'bg-navy-100 text-navy-700 dark:bg-navy-700 dark:text-white' },
  { key: 'current', label: 'Al corriente', color: 'bg-green-100 text-green-700' },
  { key: '1-30',  label: '1–30 días',  color: 'bg-yellow-100 text-yellow-700' },
  { key: '31-60', label: '31–60 días', color: 'bg-orange-100 text-orange-700' },
  { key: '61-90', label: '61–90 días', color: 'bg-red-100 text-red-600' },
  { key: '90+',   label: '+90 días',   color: 'bg-red-200 text-red-800 font-semibold' },
]

const bucketColor = (b) => ({
  current: 'text-green-600', '1-30': 'text-yellow-600',
  '31-60': 'text-orange-500', '61-90': 'text-red-500', '90+': 'text-red-700 font-bold',
})[b] || ''
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Facturación</h1>
    </template>
    <Head title="Cuentas por cobrar" />

    <!-- Billing Nav -->
    <div class="flex gap-1 mb-5 border-b border-navy-200 dark:border-navy-700">
      <Link :href="route('invoices.index')" class="px-4 py-2 text-sm font-medium text-navy-500 hover:text-navy-700 dark:hover:text-navy-300 border-b-2 border-transparent -mb-px">Facturas</Link>
      <Link :href="route('quotes.index')" class="px-4 py-2 text-sm font-medium text-navy-500 hover:text-navy-700 dark:hover:text-navy-300 border-b-2 border-transparent -mb-px">Cotizaciones</Link>
      <Link :href="route('receivables.index')" class="px-4 py-2 text-sm font-medium border-b-2 border-teal-500 text-teal-600 dark:text-teal-400 -mb-px">Cuentas por cobrar</Link>
    </div>

    <!-- Aging summary cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
      <div class="card p-4 text-center">
        <p class="text-xs text-navy-400 uppercase tracking-wide mb-1">Total</p>
        <p class="font-bold text-navy-900 dark:text-white text-lg">{{ fmt(summary?.total) }}</p>
      </div>
      <div v-for="b in buckets.slice(1)" :key="b.key" class="card p-4 text-center">
        <p class="text-xs text-navy-400 uppercase tracking-wide mb-1">{{ b.label }}</p>
        <p :class="['font-bold text-lg', bucketColor(b.key)]">{{ fmt(summary?.[b.key]) }}</p>
        <p class="text-xs text-navy-400 mt-0.5">{{ invoices.filter(i => i.bucket === b.key).length }} facturas</p>
      </div>
    </div>

    <!-- Bucket filter -->
    <div class="flex flex-wrap gap-2 mb-4">
      <button v-for="b in buckets" :key="b.key"
        @click="selectedBucket = b.key"
        :class="['badge cursor-pointer transition-all', selectedBucket === b.key ? b.color + ' ring-2 ring-offset-1 ring-current' : 'badge-gray opacity-70 hover:opacity-100']">
        {{ b.label }}
        <span class="ml-1 opacity-70">({{ b.key === 'all' ? invoices.length : invoices.filter(i => i.bucket === b.key).length }})</span>
      </button>
    </div>

    <div class="card">
      <div v-if="!filtered.length" class="py-12 text-center text-navy-400 text-sm">
        No hay facturas pendientes de cobro
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-navy-100 dark:border-navy-800">
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Paciente</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Factura #</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500 hidden md:table-cell">Emisión</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500 hidden sm:table-cell">Vencimiento</th>
              <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Total</th>
              <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500 hidden sm:table-cell">Pagado</th>
              <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Saldo</th>
              <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500 hidden md:table-cell">Vencido</th>
              <th class="w-16"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inv in filtered" :key="inv.id"
              class="border-b border-navy-100 dark:border-navy-800 hover:bg-navy-50/40 dark:hover:bg-navy-800/30 transition-colors">
              <td class="px-4 py-3 font-medium text-navy-900 dark:text-white">{{ inv.patient?.first_name }} {{ inv.patient?.last_name }}</td>
              <td class="px-4 py-3">
                <Link :href="route('invoices.show', inv.id)" class="font-mono text-teal-600 hover:text-teal-700 font-semibold">
                  {{ inv.invoice_number }}
                </Link>
              </td>
              <td class="px-4 py-3 text-navy-500 hidden md:table-cell">{{ fmtDate(inv.invoice_date) }}</td>
              <td class="px-4 py-3 text-navy-500 hidden sm:table-cell">{{ fmtDate(inv.due_date) }}</td>
              <td class="px-4 py-3 text-right text-navy-700 dark:text-navy-300">{{ fmt(inv.total) }}</td>
              <td class="px-4 py-3 text-right text-green-600 hidden sm:table-cell">{{ fmt(inv.amount_paid) }}</td>
              <td class="px-4 py-3 text-right font-bold text-red-500">{{ fmt(inv.balance_due) }}</td>
              <td class="px-4 py-3 text-center hidden md:table-cell">
                <span v-if="inv.days_late !== null && inv.days_late > 0" :class="['text-xs font-semibold', bucketColor(inv.bucket)]">
                  {{ Math.round(inv.days_late) }} días
                </span>
                <span v-else-if="inv.bucket === 'current'" class="text-xs text-green-600">Al día</span>
                <span v-else class="text-xs text-navy-400">—</span>
              </td>
              <td class="px-4 py-3">
                <Link :href="route('invoices.show', inv.id)" class="btn-ghost p-1.5 text-xs">Ver</Link>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bg-navy-50 dark:bg-navy-800 font-semibold">
              <td colspan="6" class="px-4 py-3 text-right text-navy-700 dark:text-navy-300 hidden sm:table-cell">Total saldo pendiente:</td>
              <td colspan="6" class="px-4 py-3 text-right text-navy-700 dark:text-navy-300 sm:hidden">Total:</td>
              <td class="px-4 py-3 text-right text-red-500 font-bold">{{ fmt(filtered.reduce((s, i) => s + i.balance_due, 0)) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
