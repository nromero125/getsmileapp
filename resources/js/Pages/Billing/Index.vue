<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import StatCard from '@/Components/StatCard.vue'
import { PlusIcon, MagnifyingGlassIcon, DocumentArrowDownIcon, CurrencyDollarIcon, ClockIcon, DocumentTextIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  invoices: Object,
  filters: Object,
  stats: Object,
})

const can = usePage().props.can
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const doSearch = () => {
  router.get(route('invoices.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true })
}

const formatCurrency = (v) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP' }).format(v || 0)
const formatDate = (d) => d ? new Date(d).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Facturación</h1>
    </template>
    <Head title="Facturación" />

    <!-- Billing Nav -->
    <div class="flex gap-1 mb-5 border-b border-navy-200 dark:border-navy-700">
      <Link :href="route('invoices.index')" class="px-4 py-2 text-sm font-medium border-b-2 border-teal-500 text-teal-600 dark:text-teal-400 -mb-px">Facturas</Link>
      <Link :href="route('quotes.index')" class="px-4 py-2 text-sm font-medium text-navy-500 hover:text-navy-700 dark:hover:text-navy-300 border-b-2 border-transparent -mb-px">Cotizaciones</Link>
      <Link :href="route('receivables.index')" class="px-4 py-2 text-sm font-medium text-navy-500 hover:text-navy-700 dark:hover:text-navy-300 border-b-2 border-transparent -mb-px">Cuentas por cobrar</Link>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-4 mb-6">
      <StatCard title="Ingresos Totales" :value="formatCurrency(stats?.total_revenue)" :icon="CurrencyDollarIcon" color="green" />
      <StatCard title="Pagos Pendientes" :value="formatCurrency(stats?.pending)" :icon="ClockIcon" color="amber" />
    </div>

    <div class="card">
      <!-- Toolbar -->
      <div class="p-4 border-b border-navy-100 dark:border-navy-800 flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-navy-400" />
          <input v-model="search" @keyup.enter="doSearch" type="text" placeholder="Buscar facturas…" class="input pl-9" />
        </div>
        <select v-model="status" @change="doSearch" class="input w-full sm:w-40">
          <option value="">Todos los estados</option>
          <option value="pending">Pendiente</option>
          <option value="partial">Pago parcial</option>
          <option value="paid">Pagada</option>
          <option value="cancelled">Cancelada</option>
        </select>
        <Link v-if="can.billing" :href="route('invoices.create')" class="btn-primary flex-shrink-0">
          <PlusIcon class="w-4 h-4" /> Nueva Factura
        </Link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-navy-100 dark:border-navy-800">
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Factura #</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Paciente</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500 hidden md:table-cell">Fecha</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Estado</th>
              <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Total</th>
              <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500 hidden sm:table-cell">Pagado</th>
              <th class="w-20"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!invoices?.data?.length">
              <td colspan="7" class="px-4 py-12 text-center text-navy-400 text-sm">No se encontraron facturas</td>
            </tr>
            <tr v-for="invoice in invoices?.data" :key="invoice.id" class="table-row">
              <td class="px-4 py-3">
                <Link :href="route('invoices.show', invoice.id)" class="font-mono font-semibold text-teal-600 hover:text-teal-700">
                  {{ invoice.invoice_number }}
                </Link>
              </td>
              <td class="px-4 py-3">
                <p class="font-medium text-navy-900 dark:text-white">{{ invoice.patient?.first_name }} {{ invoice.patient?.last_name }}</p>
              </td>
              <td class="px-4 py-3 text-navy-500 hidden md:table-cell">{{ formatDate(invoice.invoice_date) }}</td>
              <td class="px-4 py-3"><StatusBadge :status="invoice.status" type="invoice" /></td>
              <td class="px-4 py-3 text-right font-semibold text-navy-900 dark:text-white">{{ formatCurrency(invoice.total) }}</td>
              <td class="px-4 py-3 text-right text-navy-600 dark:text-navy-400 hidden sm:table-cell">{{ formatCurrency(invoice.amount_paid) }}</td>
              <td class="px-4 py-3 text-right">
                <Link :href="route('invoices.show', invoice.id)" class="btn-ghost p-1.5">Ver</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4"><Pagination :links="invoices?.links" /></div>
    </div>
  </AppLayout>
</template>
