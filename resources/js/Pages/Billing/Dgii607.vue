<script setup>
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ArrowDownTrayIcon, ArrowLeftIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ clinic: Object })
const page  = usePage()

const now        = new Date()
const year       = ref(now.getFullYear())
const month      = ref(now.getMonth() + 1)
const downloading = ref(false)

const exportError = ref(null)

const months = [
  { v: 1, l: 'Enero' }, { v: 2, l: 'Febrero' }, { v: 3, l: 'Marzo' },
  { v: 4, l: 'Abril' }, { v: 5, l: 'Mayo' },     { v: 6, l: 'Junio' },
  { v: 7, l: 'Julio' }, { v: 8, l: 'Agosto' },   { v: 9, l: 'Septiembre' },
  { v: 10, l: 'Octubre' }, { v: 11, l: 'Noviembre' }, { v: 12, l: 'Diciembre' },
]

const years = computed(() => {
  const list = []
  for (let y = now.getFullYear(); y >= 2020; y--) list.push(y)
  return list
})

const monthLabel = computed(() => months.find(m => m.v === month.value)?.l ?? '')

const rnc = computed(() => (props.clinic?.tax_id ?? '').replace(/\D/g, ''))

const filename = computed(() =>
  `607${rnc.value}${String(year.value)}${String(month.value).padStart(2, '0')}.txt`
)

const download = async () => {
  exportError.value = null
  downloading.value = true

  try {
    const params = new URLSearchParams({ year: year.value, month: month.value })
    const response = await fetch(route('dgii.607.download') + '?' + params.toString(), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })

    if (!response.ok) {
      const data = await response.json().catch(() => null)
      exportError.value = data?.error ?? 'Error al generar el archivo.'
      return
    }

    // Trigger file download from blob
    const blob = await response.blob()
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href     = url
    a.download = filename.value
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    exportError.value = 'Error de conexión. Intenta de nuevo.'
  } finally {
    downloading.value = false
  }
}

const fields = [
  { name: 'RNC / Cédula / Pasaporte',         example: '40212345678' },
  { name: 'Tipo identificación',              example: '1=Cédula, 2=RNC, 3=Extran.' },
  { name: 'NCF',                              example: 'B0200000450' },
  { name: 'NCF modificado',                   example: 'B0200000450 (solo B04)' },
  { name: 'Tipo de ingreso',                  example: '01' },
  { name: 'Fecha comprobante',                example: '20260321' },
  { name: 'Fecha retención',                  example: '(vacío)' },
  { name: 'Monto facturado',                  example: '5000.00' },
  { name: 'ITBIS facturado',                  example: '0.00' },
  { name: 'ITBIS retenido por terceros',      example: '0.00' },
  { name: 'ITBIS percibido',                  example: '0.00' },
  { name: 'Retención renta por terceros',     example: '0.00' },
  { name: 'ISR percibido',                    example: '0.00' },
  { name: 'Impuesto Selectivo al Consumo',    example: '0.00' },
  { name: 'Otros impuestos / tasas',          example: '0.00' },
  { name: 'Propina legal',                    example: '0.00' },
  { name: 'Efectivo',                         example: '5000.00' },
  { name: 'Cheque / Transferencia',           example: '0.00' },
  { name: 'Tarjeta débito / crédito',         example: '0.00' },
  { name: 'Venta a crédito',                  example: '0.00' },
  { name: 'Bonos / certificados de regalo',   example: '0.00' },
  { name: 'Permuta',                          example: '0.00' },
  { name: 'Otras formas de venta',            example: '0.00' },
  { name: 'Estatus',                          example: '1=válido, 0=anulado' },
]
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('reports.index')" class="btn-ghost p-1.5">
          <ArrowLeftIcon class="w-4 h-4" />
        </Link>
        <div>
          <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">
            Exportar 607 — Reporte de Ventas DGII
          </h1>
          <p class="text-sm text-navy-400">Archivo plano para envío a la DGII</p>
        </div>
      </div>
    </template>
    <Head title="Exportar 607" />

    <div class="max-w-xl space-y-5">

      <!-- Clinic info -->
      <div class="card p-5">
        <h2 class="font-semibold text-navy-900 dark:text-white mb-3">Datos de la empresa</h2>
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
          <dt class="text-navy-400">Nombre</dt>
          <dd class="text-navy-700 dark:text-navy-200 font-medium">{{ clinic.name }}</dd>
          <dt class="text-navy-400">RNC</dt>
          <dd class="text-navy-700 dark:text-navy-200 font-medium font-mono">
            {{ clinic.tax_id || '—' }}
          </dd>
        </dl>
        <div v-if="!clinic.tax_id" class="mt-3 flex items-start gap-2 text-xs text-amber-600 bg-amber-50 dark:bg-amber-900/20 p-3 rounded-xl">
          <ExclamationTriangleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
          El RNC de la clínica no está configurado. El nombre del archivo no incluirá el RNC.
          Ve a <Link :href="route('clinic.settings')" class="underline font-medium ml-1">Ajustes de Clínica</Link> para configurarlo.
        </div>
      </div>

      <!-- Period selector -->
      <div class="card p-5">
        <h2 class="font-semibold text-navy-900 dark:text-white mb-4">Período a exportar</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Mes</label>
            <select v-model="month" class="input">
              <option v-for="m in months" :key="m.v" :value="m.v">{{ m.l }}</option>
            </select>
          </div>
          <div>
            <label class="label">Año</label>
            <select v-model="year" class="input">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Preview & download -->
      <div class="card p-5">
        <h2 class="font-semibold text-navy-900 dark:text-white mb-3">Archivo a generar</h2>

        <div class="bg-navy-50 dark:bg-navy-800 rounded-xl p-4 font-mono text-sm text-navy-700 dark:text-navy-200 mb-4">
          {{ filename }}
        </div>

        <div class="text-xs text-navy-400 space-y-1 mb-5">
          <p>• Formato: TXT separado por pipes ( | )</p>
          <p>• Incluye: comprobantes B01, B02 y notas de crédito B04</p>
          <p>• Excluye: facturas sin NCF y en estado borrador</p>
          <p>• Montos con 2 decimales · NCF sin guiones</p>
        </div>

        <div v-if="exportError" class="flex items-start gap-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 p-3 rounded-xl mb-4">
          <ExclamationTriangleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
          {{ exportError }}
        </div>

        <button @click="download" :disabled="downloading" class="btn-primary w-full flex items-center justify-center gap-2 disabled:opacity-60">
          <ArrowDownTrayIcon class="w-4 h-4" />
          {{ downloading ? 'Generando archivo…' : `Descargar 607 — ${monthLabel} ${year}` }}
        </button>
      </div>

      <!-- Format reference -->
      <div class="card p-5">
        <h2 class="font-semibold text-navy-900 dark:text-white mb-3">Estructura del archivo</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="border-b border-navy-100 dark:border-navy-700">
                <th class="text-left py-1.5 pr-3 text-navy-400 font-medium">#</th>
                <th class="text-left py-1.5 pr-3 text-navy-400 font-medium">Campo</th>
                <th class="text-left py-1.5 text-navy-400 font-medium">Ejemplo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-navy-50 dark:divide-navy-800">
              <tr v-for="(f, i) in fields" :key="i" class="table-row">
                <td class="py-1.5 pr-3 text-navy-400 font-mono">{{ i + 1 }}</td>
                <td class="py-1.5 pr-3 text-navy-700 dark:text-navy-200">{{ f.name }}</td>
                <td class="py-1.5 font-mono text-navy-500 dark:text-navy-400">{{ f.example }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-4 p-3 bg-navy-50 dark:bg-navy-800 rounded-xl font-mono text-xs text-navy-500 break-all">
          <p class="text-navy-400 mb-1">Factura normal (efectivo):</p>
          <p>40212345678|1|B0200000450||01|20260321||5000.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|5000.00|0.00|0.00|0.00|0.00|0.00|0.00|1</p>
          <p class="text-navy-400 mt-3 mb-1">Nota de crédito (B04):</p>
          <p>40212345678|1|B0400000001|B0200000450|01|20260325||-5000.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0</p>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

