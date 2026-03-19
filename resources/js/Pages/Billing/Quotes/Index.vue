<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import StatCard from '@/Components/StatCard.vue'
import { PlusIcon, MagnifyingGlassIcon, CurrencyDollarIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ quotes: Object, filters: Object, stats: Object })
const can = usePage().props.can
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const doSearch = () => {
  router.get(route('quotes.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true })
}

const fmt = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0)
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
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Facturación</h1>
    </template>
    <Head title="Cotizaciones" />

    <!-- Billing Nav -->
    <div class="flex gap-1 mb-5 border-b border-navy-200 dark:border-navy-700">
      <Link :href="route('invoices.index')" class="px-4 py-2 text-sm font-medium text-navy-500 hover:text-navy-700 dark:hover:text-navy-300 border-b-2 border-transparent -mb-px">Facturas</Link>
      <Link :href="route('quotes.index')" class="px-4 py-2 text-sm font-medium border-b-2 border-teal-500 text-teal-600 dark:text-teal-400 -mb-px">Cotizaciones</Link>
      <Link :href="route('receivables.index')" class="px-4 py-2 text-sm font-medium text-navy-500 hover:text-navy-700 dark:hover:text-navy-300 border-b-2 border-transparent -mb-px">Cuentas por cobrar</Link>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
      <StatCard title="Cotizaciones activas" :value="fmt(stats?.total)" :icon="CurrencyDollarIcon" color="blue" />
      <StatCard title="Convertidas a factura" :value="fmt(stats?.accepted)" :icon="CheckCircleIcon" color="green" />
    </div>

    <div class="card">
      <div class="p-4 border-b border-navy-100 dark:border-navy-800 flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-navy-400" />
          <input v-model="search" @keyup.enter="doSearch" type="text" placeholder="Buscar cotizaciones…" class="input pl-9" />
        </div>
        <select v-model="status" @change="doSearch" class="input w-full sm:w-44">
          <option value="">Todos los estados</option>
          <option value="draft">Borrador</option>
          <option value="sent">Enviada</option>
          <option value="accepted">Aceptada</option>
          <option value="rejected">Rechazada</option>
          <option value="expired">Expirada</option>
        </select>
        <Link v-if="can.billing" :href="route('quotes.create')" class="btn-primary flex-shrink-0">
          <PlusIcon class="w-4 h-4" /> Nueva Cotización
        </Link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-navy-100 dark:border-navy-800">
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Cotización #</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Paciente</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500 hidden md:table-cell">Fecha</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500 hidden md:table-cell">Válida hasta</th>
              <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Estado</th>
              <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide text-navy-500">Total</th>
              <th class="w-20"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!quotes?.data?.length">
              <td colspan="7" class="px-4 py-12 text-center text-navy-400 text-sm">No se encontraron cotizaciones</td>
            </tr>
            <tr v-for="quote in quotes?.data" :key="quote.id" class="table-row">
              <td class="px-4 py-3">
                <Link :href="route('quotes.show', quote.id)" class="font-mono font-semibold text-teal-600 hover:text-teal-700">
                  {{ quote.quote_number }}
                </Link>
              </td>
              <td class="px-4 py-3 font-medium text-navy-900 dark:text-white">{{ quote.patient?.first_name }} {{ quote.patient?.last_name }}</td>
              <td class="px-4 py-3 text-navy-500 hidden md:table-cell">{{ fmtDate(quote.quote_date) }}</td>
              <td class="px-4 py-3 text-navy-500 hidden md:table-cell">{{ fmtDate(quote.valid_until) }}</td>
              <td class="px-4 py-3">
                <span :class="['badge text-xs', statusColors[quote.status]]">{{ statusLabels[quote.status] }}</span>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-navy-900 dark:text-white">{{ fmt(quote.total) }}</td>
              <td class="px-4 py-3 text-right">
                <Link :href="route('quotes.show', quote.id)" class="btn-ghost p-1.5">Ver</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4"><Pagination :links="quotes?.links" /></div>
    </div>
  </AppLayout>
</template>
