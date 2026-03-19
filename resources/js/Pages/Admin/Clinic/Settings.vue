<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { BuildingOfficeIcon, ArrowUpTrayIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ clinic: Object })

const logoInput = ref(null)
const logoPreview = ref(props.clinic.logo_url || null)

const form = useForm({
  _method: 'put',
  clinic_name: props.clinic.name || '',
  email: props.clinic.email || '',
  phone: props.clinic.phone || '',
  address: props.clinic.address || '',
  website: props.clinic.website || '',
  tax_id: props.clinic.tax_id || '',
  logo: null,
})

const onLogoChange = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  form.logo = file
  logoPreview.value = URL.createObjectURL(file)
}

const removeLogo = () => {
  form.logo = null
  logoPreview.value = null
  if (logoInput.value) logoInput.value.value = ''
}

const submit = () => form.post(route('clinic.settings.update'))
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Configuración de Clínica</h1>
    </template>
    <Head title="Configuración de Clínica" />

    <div class="max-w-2xl">
      <form @submit.prevent="submit" class="space-y-4">

        <!-- Logo -->
        <div class="card p-6">
          <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Logo de la clínica</h2>
          <div class="flex items-center gap-5">
            <!-- Preview -->
            <div class="w-20 h-20 rounded-2xl border-2 border-dashed border-navy-200 dark:border-navy-700 flex items-center justify-center overflow-hidden flex-shrink-0 bg-navy-50 dark:bg-navy-800">
              <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-1" alt="Logo" />
              <BuildingOfficeIcon v-else class="w-8 h-8 text-navy-300" />
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-navy-900 dark:text-white mb-1">{{ clinic.name }}</p>
              <p class="text-xs text-navy-400 mb-3">PNG, JPG o SVG · Máx. 2 MB · Se mostrará en facturas PDF</p>
              <div class="flex gap-2">
                <button type="button" @click="logoInput.click()"
                  class="btn-outline text-xs gap-1.5">
                  <ArrowUpTrayIcon class="w-3.5 h-3.5" />
                  {{ logoPreview ? 'Cambiar logo' : 'Subir logo' }}
                </button>
                <button v-if="logoPreview" type="button" @click="removeLogo"
                  class="btn-ghost text-xs text-red-400 hover:text-red-600 gap-1.5">
                  <TrashIcon class="w-3.5 h-3.5" />
                  Quitar
                </button>
              </div>
              <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoChange" />
              <p v-if="form.errors.logo" class="text-xs text-red-500 mt-1">{{ form.errors.logo }}</p>
            </div>
          </div>
        </div>

        <!-- Details -->
        <div class="card p-6">
          <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Información de la Clínica</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2"><label class="label">Nombre de la Clínica *</label><input v-model="form.clinic_name" type="text" class="input" required /></div>
            <div><label class="label">Correo electrónico</label><input v-model="form.email" type="email" class="input" /></div>
            <div><label class="label">Teléfono</label><input v-model="form.phone" type="tel" class="input" /></div>
            <div class="sm:col-span-2"><label class="label">Dirección</label><textarea v-model="form.address" rows="2" class="input" /></div>
            <div><label class="label">Sitio web</label><input v-model="form.website" type="url" class="input" placeholder="https://…" /></div>
            <div><label class="label">RFC / ID Fiscal</label><input v-model="form.tax_id" type="text" class="input" /></div>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="btn-primary" :disabled="form.processing">
            {{ form.processing ? 'Guardando…' : 'Guardar Configuración' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
