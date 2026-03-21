<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import {
  UsersIcon, CalendarIcon, CurrencyDollarIcon,
  ClockIcon, UserPlusIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  stats: Object,
  today_appointments: Array,
  upcoming_appointments: Array,
  chart_data: Array,
  recent_patients: Array,
})

// Chart.js
let revenueChart = null
let appointmentsChart = null

const formatCurrency = (val) => new Intl.NumberFormat('es-DO', { style: 'currency', currency: 'DOP' }).format(val || 0)
const formatTime = (dateStr) => new Date(dateStr).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
const formatDate = (dateStr) => new Date(dateStr).toLocaleDateString('es-ES', { weekday: 'short', month: 'short', day: 'numeric' })

onMounted(() => {
  if (typeof window !== 'undefined') {
    import('chart.js').then(({ Chart, registerables }) => {
      Chart.register(...registerables)

      const labels = props.chart_data?.map(d => d.month) || []

      // Revenue trend chart
      const revenueCtx = document.getElementById('revenueChart')
      if (revenueCtx) {
        revenueChart = new Chart(revenueCtx, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Revenue',
              data: props.chart_data?.map(d => d.revenue) || [],
              borderColor: '#00BFA6',
              backgroundColor: 'rgba(0,191,166,0.08)',
              borderWidth: 2.5,
              fill: true,
              tension: 0.4,
              pointBackgroundColor: '#00BFA6',
              pointRadius: 4,
              pointHoverRadius: 6,
            }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { grid: { color: 'rgba(15,31,61,0.05)' }, ticks: { callback: v => 'RD$' + (v/1000).toFixed(0) + 'k', color: '#6B7280', font: { size: 11 } }, border: { display: false } },
              x: { grid: { display: false }, ticks: { color: '#6B7280', font: { size: 11 } }, border: { display: false } }
            }
          }
        })
      }

      // Appointments bar chart
      const apptCtx = document.getElementById('appointmentsChart')
      if (apptCtx) {
        appointmentsChart = new Chart(apptCtx, {
          type: 'bar',
          data: {
            labels,
            datasets: [{
              label: 'Appointments',
              data: props.chart_data?.map(d => d.appointments) || [],
              backgroundColor: 'rgba(15,31,61,0.75)',
              borderRadius: 6,
              borderSkipped: false,
            }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { grid: { color: 'rgba(15,31,61,0.05)' }, ticks: { stepSize: 5, color: '#6B7280', font: { size: 11 } }, border: { display: false } },
              x: { grid: { display: false }, ticks: { color: '#6B7280', font: { size: 11 } }, border: { display: false } }
            }
          }
        })
      }
    })
  }
})
</script>

<template>
  <AppLayout>
    <template #header>
      <div>
        <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Dashboard</h1>
        <p class="text-xs text-navy-400 mt-0.5">{{ new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
      </div>
    </template>

    <Head title="Dashboard" />

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard title="Total Pacientes" :value="stats?.total_patients?.toLocaleString()" :icon="UsersIcon" color="navy" subtitle="Todo el tiempo" />
      <StatCard title="Citas" :value="stats?.appointments_month?.toLocaleString()" :icon="CalendarIcon" color="teal" subtitle="Este mes" />
      <StatCard title="Ingresos" :value="formatCurrency(stats?.revenue_month)" :icon="CurrencyDollarIcon" color="green" subtitle="Este mes" />
      <StatCard title="Pendiente" :value="formatCurrency(stats?.pending_payments)" :icon="ClockIcon" color="amber" subtitle="Por cobrar" />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <!-- Revenue Chart -->
      <div class="lg:col-span-2 card p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-display font-semibold text-navy-900 dark:text-white">Tendencia de Ingresos</h3>
            <p class="text-xs text-navy-400 mt-0.5">Últimos 6 meses</p>
          </div>
        </div>
        <div class="h-52">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>

      <!-- Appointments Chart -->
      <div class="card p-6">
        <div class="mb-4">
          <h3 class="font-display font-semibold text-navy-900 dark:text-white">Citas</h3>
          <p class="text-xs text-navy-400 mt-0.5">Volumen mensual</p>
        </div>
        <div class="h-52">
          <canvas id="appointmentsChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Bottom Row: Today's Schedule + Upcoming + Recent Patients -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Today's Schedule -->
      <div class="lg:col-span-2 card p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-display font-semibold text-navy-900 dark:text-white">Agenda de Hoy</h3>
          <Link :href="route('appointments.index')" class="text-xs text-teal-600 hover:text-teal-700 font-medium">Ver calendario →</Link>
        </div>

        <div v-if="today_appointments?.length" class="space-y-2">
          <div v-for="appt in today_appointments" :key="appt.id"
            class="flex items-center gap-3 p-3 rounded-xl bg-navy-50/50 dark:bg-navy-800/50 hover:bg-navy-100/70 transition-colors">
            <div class="text-center min-w-[48px]">
              <p class="text-sm font-bold text-navy-900 dark:text-white">{{ formatTime(appt.appointment_date) }}</p>
            </div>
            <div class="w-px h-8 bg-navy-200 dark:bg-navy-700" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-navy-900 dark:text-white truncate">{{ appt.patient?.first_name }} {{ appt.patient?.last_name }}</p>
              <p class="text-xs text-navy-500 truncate">{{ appt.dentist?.name }} · {{ appt.duration_minutes }} min</p>
            </div>
            <StatusBadge :status="appt.status" />
          </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-12 text-center">
          <div class="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center mb-3">
            <CalendarIcon class="w-6 h-6 text-teal-500" />
          </div>
          <p class="text-sm font-medium text-navy-900 dark:text-white">Sin citas hoy</p>
          <p class="text-xs text-navy-400 mt-1">Programa citas desde el calendario</p>
          <Link :href="route('appointments.index')" class="btn-primary mt-4">Ir al Calendario</Link>
        </div>
      </div>

      <!-- Recent Patients -->
      <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-display font-semibold text-navy-900 dark:text-white">Pacientes Recientes</h3>
          <Link :href="route('patients.index')" class="text-xs text-teal-600 hover:text-teal-700 font-medium">Ver todos →</Link>
        </div>

        <div class="space-y-3">
          <Link v-for="patient in recent_patients" :key="patient.id"
            :href="route('patients.show', patient.id)"
            class="flex items-center gap-3 p-2 rounded-xl hover:bg-navy-50 dark:hover:bg-navy-800 transition-colors">
            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent((patient.first_name||'') + ' ' + (patient.last_name||''))}&background=0F1F3D&color=00BFA6`"
              class="w-9 h-9 rounded-full flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-navy-900 dark:text-white truncate">{{ patient.first_name }} {{ patient.last_name }}</p>
              <p class="text-xs text-navy-400 truncate">{{ patient.phone }}</p>
            </div>
          </Link>
        </div>

        <Link :href="route('patients.create')" class="btn-outline w-full mt-4 justify-center">
          <UserPlusIcon class="w-4 h-4" />
          Agregar Paciente
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
