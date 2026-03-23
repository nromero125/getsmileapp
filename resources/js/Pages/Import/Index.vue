<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  ArrowUpTrayIcon, ArrowDownTrayIcon, CheckCircleIcon,
  ExclamationTriangleIcon, DocumentTextIcon, XCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  types: Object,
})

const page = usePage()

// Steps: 'select' → 'preview' → 'done'
const step        = ref('select')
const selectedType = ref('patients')
const file        = ref(null)
const dragging    = ref(false)
const uploading   = ref(false)
const importing   = ref(false)

const preview     = ref(null)  // { path, preview, total, columns }
const result      = ref(null)  // { type, imported, skipped, errors }

const typeList = computed(() => Object.entries(props.types).map(([key, val]) => ({ key, ...val })))

const typeInfo = {
  patients:     { color: 'teal',   desc: 'Nombre, teléfono, email, cédula, fecha de nacimiento, género, dirección.' },
  treatments:   { color: 'blue',   desc: 'Nombre del tratamiento, categoría, precio, duración.' },
  appointments: { color: 'purple', desc: 'Referencia al paciente (email o cédula), fecha, estado, dentista.' },
  invoices:     { color: 'amber',  desc: 'Referencia al paciente, fecha, ítems, precio, ITBIS, monto pagado.' },
}

const onFileChange = (e) => {
  const f = e.target.files?.[0] || e.dataTransfer?.files?.[0]
  if (f) { file.value = f; uploadPreview() }
}

const onDrop = (e) => {
  dragging.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) { file.value = f; uploadPreview() }
}

const uploadPreview = async () => {
  if (!file.value) return
  uploading.value = true
  preview.value = null

  const fd = new FormData()
  fd.append('file', file.value)
  fd.append('type', selectedType.value)
  fd.append('_token', page.props.csrf_token ?? document.querySelector('meta[name=csrf-token]')?.content)

  try {
    const res = await fetch(route('import.preview'), {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    if (!res.ok) throw new Error(await res.text())
    preview.value = await res.json()
    step.value = 'preview'
  } catch (e) {
    alert('Error al leer el archivo: ' + e.message)
  } finally {
    uploading.value = false
  }
}

const runImport = () => {
  if (!preview.value?.path || importing.value) return
  importing.value = true

  router.post(route('import.run'), {
    path: preview.value.path,
    type: selectedType.value,
  }, {
    onSuccess: () => {
      const r = page.props.flash?.import_result
      result.value = r
      step.value = 'done'
    },
    onFinish: () => importing.value = false,
  })
}

const reset = () => {
  step.value = 'select'
  file.value = null
  preview.value = null
  result.value = null
}
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Importar Datos</h1>
    </template>

    <div class="max-w-4xl mx-auto space-y-6">

      <!-- Downloads -->
      <div class="card p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <p class="font-semibold text-navy-900 dark:text-white text-sm">Archivos de ejemplo</p>
          <p class="text-xs text-navy-400 mt-0.5">Descarga la plantilla en blanco o un archivo con datos de ejemplo para entender el formato.</p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
          <a :href="route('import.template')" class="btn-outline text-sm flex items-center gap-1.5">
            <ArrowDownTrayIcon class="w-4 h-4" />
            Plantilla
          </a>
          <a :href="route('import.template') + '?sample=1'" class="btn-primary text-sm flex items-center gap-1.5">
            <ArrowDownTrayIcon class="w-4 h-4" />
            Con datos de ejemplo
          </a>
        </div>
      </div>

      <!-- Step 1 — Select type + upload -->
      <div v-if="step === 'select'" class="space-y-5">

        <!-- Type selector -->
        <div class="card p-5">
          <p class="text-sm font-semibold text-navy-700 dark:text-navy-300 mb-3">¿Qué deseas importar?</p>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button v-for="t in typeList" :key="t.key"
              @click="selectedType = t.key"
              :class="[
                'rounded-xl border-2 p-3 text-left transition-all',
                selectedType === t.key
                  ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20'
                  : 'border-navy-200 dark:border-navy-700 hover:border-teal-400',
              ]">
              <p class="font-semibold text-sm text-navy-900 dark:text-white">{{ t.label }}</p>
              <p class="text-xs text-navy-400 mt-1 leading-snug">{{ typeInfo[t.key]?.desc }}</p>
            </button>
          </div>
        </div>

        <!-- Drop zone -->
        <div class="card p-5">
          <p class="text-sm font-semibold text-navy-700 dark:text-navy-300 mb-3">Selecciona el archivo</p>
          <label
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="onDrop"
            :class="[
              'flex flex-col items-center justify-center gap-3 border-2 border-dashed rounded-xl p-10 cursor-pointer transition-colors',
              dragging ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20' : 'border-navy-200 dark:border-navy-700 hover:border-teal-400',
            ]">
            <input type="file" class="hidden" accept=".xlsx,.xls,.csv" @change="onFileChange" />
            <ArrowUpTrayIcon class="w-8 h-8 text-navy-300 dark:text-navy-600" />
            <div class="text-center">
              <p class="text-sm font-medium text-navy-700 dark:text-navy-300">
                {{ uploading ? 'Procesando…' : 'Arrastra aquí o haz clic para seleccionar' }}
              </p>
              <p class="text-xs text-navy-400 mt-1">Excel (.xlsx, .xls) o CSV — máx. 10 MB</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Step 2 — Preview -->
      <div v-if="step === 'preview' && preview" class="space-y-5">
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <p class="font-semibold text-navy-900 dark:text-white">
                Vista previa — {{ types[selectedType]?.label }}
              </p>
              <p class="text-xs text-navy-400 mt-0.5">
                Mostrando {{ preview.preview.length }} de {{ preview.total }} filas
              </p>
            </div>
            <button @click="reset" class="btn-ghost text-sm">← Cambiar archivo</button>
          </div>

          <!-- Table preview -->
          <div class="overflow-x-auto rounded-xl border border-navy-100 dark:border-navy-700">
            <table class="min-w-full text-xs">
              <thead>
                <tr class="bg-navy-50 dark:bg-navy-800">
                  <th v-for="col in preview.columns" :key="col"
                    class="px-3 py-2 text-left font-semibold text-navy-500 dark:text-navy-400 whitespace-nowrap uppercase tracking-wide">
                    {{ col }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-navy-50 dark:divide-navy-800">
                <tr v-for="(row, i) in preview.preview" :key="i" class="table-row">
                  <td v-for="col in preview.columns" :key="col"
                    class="px-3 py-2 text-navy-700 dark:text-navy-300 whitespace-nowrap max-w-[180px] truncate">
                    {{ row[col] ?? '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex items-center justify-between gap-4 pt-4 border-t border-navy-100 dark:border-navy-700">
            <p class="text-xs text-navy-400">
              Se importarán <strong class="text-navy-700 dark:text-navy-200">{{ preview.total }}</strong> filas.
              Los duplicados (mismo teléfono, email o cédula) se omitirán automáticamente.
            </p>
            <button @click="runImport" :disabled="importing" class="btn-primary">
              {{ importing ? 'Importando…' : `Importar ${preview.total} registros` }}
            </button>
          </div>
        </div>
      </div>

      <!-- Step 3 — Done -->
      <div v-if="step === 'done' && result" class="space-y-4">
        <div class="card p-6">
          <div class="flex items-start gap-4">
            <CheckCircleIcon class="w-8 h-8 text-teal-500 flex-shrink-0 mt-0.5" />
            <div class="flex-1">
              <p class="font-semibold text-navy-900 dark:text-white text-lg">Importación completada</p>
              <p class="text-sm text-navy-500 dark:text-navy-400 mt-1">{{ result.type }}</p>

              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                <div class="rounded-xl bg-teal-50 dark:bg-teal-900/20 p-4 text-center">
                  <p class="text-2xl font-bold text-teal-600">{{ result.imported }}</p>
                  <p class="text-xs text-navy-500 mt-1">Importados</p>
                </div>
                <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 p-4 text-center">
                  <p class="text-2xl font-bold text-amber-600">{{ result.skipped }}</p>
                  <p class="text-xs text-navy-500 mt-1">Omitidos</p>
                </div>
                <div class="rounded-xl p-4 text-center"
                  :class="result.errors.length ? 'bg-red-50 dark:bg-red-900/20' : 'bg-navy-50 dark:bg-navy-800'">
                  <p class="text-2xl font-bold" :class="result.errors.length ? 'text-red-600' : 'text-navy-400'">
                    {{ result.errors.length }}
                  </p>
                  <p class="text-xs text-navy-500 mt-1">Errores</p>
                </div>
              </div>

              <!-- Errors list -->
              <div v-if="result.errors.length" class="mt-4 space-y-1">
                <p class="text-xs font-semibold text-red-600 uppercase tracking-wide">Filas con error:</p>
                <ul class="space-y-1">
                  <li v-for="(err, i) in result.errors" :key="i"
                    class="flex items-start gap-2 text-xs text-red-700 dark:text-red-400">
                    <XCircleIcon class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" />
                    {{ err }}
                  </li>
                </ul>
                <p v-if="result.errors.length === 20" class="text-xs text-navy-400 mt-1">
                  Se muestran los primeros 20 errores.
                </p>
              </div>
            </div>
          </div>

          <div class="mt-6 flex gap-3">
            <button @click="reset" class="btn-primary">Importar otro archivo</button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
