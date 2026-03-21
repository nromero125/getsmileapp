<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  period:          String,
  startDate:       String,
  endDate:         String,
  revenue:         Object,
  appointments:    Object,
  new_patients:    Number,
  trend:           Array,
  top_treatments:  Array,
  dentist_stats:   Array,
  payment_methods: Array,
})

const fmt = (v) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP', maximumFractionDigits: 0 }).format(v || 0)
const pct = (v) => v === null ? '—' : (v >= 0 ? '+' : '') + v + '%'

const selectedPeriod = ref(props.period)
const customStart    = ref(props.startDate)
const customEnd      = ref(props.endDate)

const periods = [
  { key: 'this_month',  label: 'Este mes' },
  { key: 'last_month',  label: 'Mes pasado' },
  { key: 'quarter',     label: 'Trimestre' },
  { key: 'year',        label: 'Año' },
  { key: 'custom',      label: 'Personalizado' },
]

function navigate() {
  const params = { period: selectedPeriod.value }
  if (selectedPeriod.value === 'custom') {
    params.start = customStart.value
    params.end   = customEnd.value
  }
  router.get(route('reports.index'), params, { preserveState: true, preserveScroll: true })
}

watch(selectedPeriod, (v) => { if (v !== 'custom') navigate() })

// ── Status labels ──────────────────────────────────────────────────────────
const statusLabels = {
  scheduled:   'Programadas',
  confirmed:   'Confirmadas',
  in_progress: 'En curso',
  completed:   'Completadas',
  cancelled:   'Canceladas',
  no_show:     'No se presentó',
}
const statusColors = {
  scheduled:   '#3B82F6',
  confirmed:   '#00BFA6',
  in_progress: '#F59E0B',
  completed:   '#10B981',
  cancelled:   '#EF4444',
  no_show:     '#9CA3AF',
}

// ── Charts ─────────────────────────────────────────────────────────────────
let revenueChart = null
let apptChart    = null

async function initCharts() {
  if (!props.trend?.length) return
  const { Chart, registerables } = await import('chart.js')
  Chart.register(...registerables)

  const isDark = document.documentElement.classList.contains('dark')
  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(15,31,61,0.05)'
  const tickColor = isDark ? '#9CA3AF' : '#6B7280'

  const labels   = props.trend.map(t => t.month)
  const revenues = props.trend.map(t => t.revenue)
  const appts    = props.trend.map(t => t.appointments)

  const revCanvas  = document.getElementById('revenueChart')
  const apptCanvas = document.getElementById('apptChart')
  if (!revCanvas || !apptCanvas) return

  revenueChart = new Chart(revCanvas, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Ingresos cobrados',
        data: revenues,
        borderColor: '#00BFA6',
        backgroundColor: 'rgba(0,191,166,0.08)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#00BFA6',
        pointRadius: 4,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { grid: { color: gridColor }, ticks: { callback: v => 'RD$' + (v/1000).toFixed(0) + 'k', color: tickColor, font: { size: 11 } }, border: { display: false } },
        x: { grid: { display: false }, ticks: { color: tickColor, font: { size: 11 } }, border: { display: false } },
      },
    },
  })

  apptChart = new Chart(apptCanvas, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Citas',
        data: appts,
        backgroundColor: 'rgba(99,102,241,0.7)',
        borderRadius: 6,
        borderSkipped: false,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { grid: { color: gridColor }, ticks: { color: tickColor, font: { size: 11 }, stepSize: 1 }, border: { display: false } },
        x: { grid: { display: false }, ticks: { color: tickColor, font: { size: 11 } }, border: { display: false } },
      },
    },
  })
}

onMounted(() => initCharts())

// KPI change color
const changeClass = (pct) => pct === null ? 'text-navy-400' : pct >= 0 ? 'text-emerald-600' : 'text-red-500'

// Appointment status summary
const statusSummary = computed(() =>
  Object.entries(props.appointments.by_status || {}).map(([status, total]) => ({
    label: statusLabels[status] || status,
    color: statusColors[status] || '#94A3B8',
    total,
  })).sort((a, b) => b.total - a.total)
)

const completionRate = computed(() => {
  const total     = props.appointments.total
  const completed = props.appointments.by_status?.completed || 0
  return total > 0 ? Math.round((completed / total) * 100) : 0
})

const cancelRate = computed(() => {
  const total    = props.appointments.total
  const cancel   = (props.appointments.by_status?.cancelled || 0) + (props.appointments.by_status?.no_show || 0)
  return total > 0 ? Math.round((cancel / total) * 100) : 0
})
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Reportes</h1>
    </template>
    <Head title="Reportes" />

    <!-- Period selector -->
    <div class="flex flex-wrap items-center gap-2 mb-6">
      <button
        v-for="p in periods" :key="p.key"
        @click="selectedPeriod = p.key"
        class="px-4 py-1.5 rounded-full text-sm font-medium transition-all"
        :class="selectedPeriod === p.key
          ? 'text-white shadow-sm'
          : 'text-navy-600 dark:text-navy-300 bg-white dark:bg-navy-800 border border-navy-200 dark:border-navy-700 hover:border-teal-400'"
        :style="selectedPeriod === p.key ? 'background:#00BFA6' : ''"
      >{{ p.label }}</button>

      <!-- Custom range -->
      <template v-if="selectedPeriod === 'custom'">
        <input v-model="customStart" type="date" class="input py-1 text-sm" />
        <span class="text-navy-400 text-sm">—</span>
        <input v-model="customEnd" type="date" class="input py-1 text-sm" />
        <button @click="navigate" class="btn-primary py-1.5 text-sm">Aplicar</button>
      </template>

      <span class="ml-auto text-xs text-navy-400 dark:text-navy-500">
        {{ new Date(startDate).toLocaleDateString('es-DO', { day:'numeric', month:'short', year:'numeric' }) }}
        — {{ new Date(endDate).toLocaleDateString('es-DO', { day:'numeric', month:'short', year:'numeric' }) }}
      </span>
    </div>

    <!-- ── KPI Row ───────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

      <!-- Ingresos cobrados -->
      <div class="card p-5">
        <p class="text-xs uppercase tracking-wide text-navy-400 mb-1">Ingresos cobrados</p>
        <p class="text-2xl font-bold text-navy-900 dark:text-white">{{ fmt(revenue.collected) }}</p>
        <p class="text-xs mt-1" :class="changeClass(revenue.change_pct)">
          {{ pct(revenue.change_pct) }} vs período anterior
        </p>
      </div>

      <!-- Por cobrar -->
      <div class="card p-5">
        <p class="text-xs uppercase tracking-wide text-navy-400 mb-1">Por cobrar</p>
        <p class="text-2xl font-bold text-amber-500">{{ fmt(revenue.pending) }}</p>
        <p class="text-xs mt-1 text-navy-400">Facturas pendientes / parciales</p>
      </div>

      <!-- Total citas -->
      <div class="card p-5">
        <p class="text-xs uppercase tracking-wide text-navy-400 mb-1">Total citas</p>
        <p class="text-2xl font-bold text-navy-900 dark:text-white">{{ appointments.total }}</p>
        <p class="text-xs mt-1" :class="changeClass(appointments.change_pct)">
          {{ pct(appointments.change_pct) }} vs período anterior
        </p>
      </div>

      <!-- Pacientes nuevos -->
      <div class="card p-5">
        <p class="text-xs uppercase tracking-wide text-navy-400 mb-1">Pacientes nuevos</p>
        <p class="text-2xl font-bold text-navy-900 dark:text-white">{{ new_patients }}</p>
        <p class="text-xs mt-1 text-navy-400">Ticket promedio: {{ fmt(revenue.avg_ticket) }}</p>
      </div>
    </div>

    <!-- ── Charts row ────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
      <div class="card p-5">
        <p class="text-sm font-semibold text-navy-800 dark:text-white mb-4">Ingresos cobrados por mes</p>
        <div style="height:220px">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>
      <div class="card p-5">
        <p class="text-sm font-semibold text-navy-800 dark:text-white mb-4">Citas por mes</p>
        <div style="height:220px">
          <canvas id="apptChart"></canvas>
        </div>
      </div>
    </div>

    <!-- ── Appointments breakdown + Payment methods ──────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

      <!-- Citas por estado -->
      <div class="card p-5">
        <p class="text-sm font-semibold text-navy-800 dark:text-white mb-4">Citas por estado</p>
        <div class="flex gap-6 mb-4">
          <div>
            <p class="text-2xl font-bold text-emerald-600">{{ completionRate }}%</p>
            <p class="text-xs text-navy-400">Tasa de completadas</p>
          </div>
          <div>
            <p class="text-2xl font-bold text-red-500">{{ cancelRate }}%</p>
            <p class="text-xs text-navy-400">Cancelaciones / no shows</p>
          </div>
        </div>
        <div class="space-y-2">
          <div v-for="s in statusSummary" :key="s.label" class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="`background:${s.color}`"></span>
            <span class="text-sm text-navy-700 dark:text-navy-300 flex-1">{{ s.label }}</span>
            <span class="text-sm font-semibold text-navy-900 dark:text-white">{{ s.total }}</span>
            <div class="w-24 bg-navy-100 dark:bg-navy-700 rounded-full h-1.5 overflow-hidden">
              <div class="h-full rounded-full" :style="`width:${appointments.total ? Math.round(s.total/appointments.total*100) : 0}%; background:${s.color}`"></div>
            </div>
          </div>
          <p v-if="!statusSummary.length" class="text-sm text-navy-400">Sin citas en este período.</p>
        </div>
      </div>

      <!-- Métodos de pago -->
      <div class="card p-5">
        <p class="text-sm font-semibold text-navy-800 dark:text-white mb-4">Métodos de pago</p>
        <div v-if="payment_methods.length" class="space-y-3">
          <div v-for="m in payment_methods" :key="m.method" class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-sm text-navy-700 dark:text-navy-300">{{ m.method }}</span>
              <span class="text-xs text-navy-400">({{ m.count }} pago{{ m.count !== 1 ? 's' : '' }})</span>
            </div>
            <span class="text-sm font-semibold text-navy-900 dark:text-white">{{ fmt(m.total) }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-navy-400">Sin pagos registrados en este período.</p>
      </div>
    </div>

    <!-- ── Dentist performance + Top treatments ──────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Dentistas -->
      <div class="card p-5">
        <p class="text-sm font-semibold text-navy-800 dark:text-white mb-4">Rendimiento por dentista</p>
        <div v-if="dentist_stats.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs uppercase tracking-wide text-navy-400 border-b border-navy-100 dark:border-navy-700">
                <th class="pb-2 font-medium">Dentista</th>
                <th class="pb-2 font-medium text-center">Citas</th>
                <th class="pb-2 font-medium text-center">Complet.</th>
                <th class="pb-2 font-medium text-center">Cancelac.</th>
                <th class="pb-2 font-medium text-right">Ingresos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in dentist_stats" :key="d.name" class="table-row">
                <td class="py-2 text-navy-800 dark:text-navy-200 font-medium">{{ d.name }}</td>
                <td class="py-2 text-center text-navy-600 dark:text-navy-300">{{ d.appointments }}</td>
                <td class="py-2 text-center text-emerald-600">{{ d.completed }}</td>
                <td class="py-2 text-center text-red-500">{{ d.cancelled }}</td>
                <td class="py-2 text-right text-navy-800 dark:text-navy-200">{{ fmt(d.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-sm text-navy-400">Sin citas en este período.</p>
      </div>

      <!-- Top tratamientos -->
      <div class="card p-5">
        <p class="text-sm font-semibold text-navy-800 dark:text-white mb-4">Tratamientos más realizados</p>
        <div v-if="top_treatments.length" class="space-y-2.5">
          <div v-for="(t, i) in top_treatments" :key="t.name" class="flex items-center gap-3">
            <span class="text-xs font-bold text-navy-300 w-4 text-right">{{ i + 1 }}</span>
            <span class="text-sm text-navy-700 dark:text-navy-300 flex-1 truncate">{{ t.name }}</span>
            <span class="text-xs text-navy-400">×{{ t.count }}</span>
            <span class="text-sm font-semibold text-navy-900 dark:text-white">{{ fmt(t.revenue) }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-navy-400">Sin tratamientos registrados en este período.</p>
      </div>
    </div>
  </AppLayout>
</template>
