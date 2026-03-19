<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const form = useForm({
  first_name: '', last_name: '', email: '', phone: '', phone_alt: '',
  date_of_birth: '', gender: '', address: '', city: '', state: '', zip_code: '',
  blood_type: '', allergies: '', medical_notes: '',
  insurance_provider: '', insurance_policy_number: '',
  emergency_contact_name: '', emergency_contact_phone: '', emergency_contact_relation: '',
})

const submit = () => form.post(route('patients.store'))

const bloodTypes = ['A+','A-','B+','B-','AB+','AB-','O+','O-']
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('patients.index')" class="btn-ghost p-1.5"><ArrowLeftIcon class="w-4 h-4" /></Link>
        <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Nuevo paciente</h1>
      </div>
    </template>
    <Head title="Nuevo paciente" />

    <form @submit.prevent="submit" class="max-w-4xl space-y-6">
      <!-- Basic Info -->
      <div class="card p-6">
        <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Información personal</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Nombre *</label>
            <input v-model="form.first_name" type="text" class="input" :class="{'input-error': form.errors.first_name}" required />
            <p v-if="form.errors.first_name" class="text-xs text-red-500 mt-1">{{ form.errors.first_name }}</p>
          </div>
          <div>
            <label class="label">Apellido *</label>
            <input v-model="form.last_name" type="text" class="input" :class="{'input-error': form.errors.last_name}" required />
            <p v-if="form.errors.last_name" class="text-xs text-red-500 mt-1">{{ form.errors.last_name }}</p>
          </div>
          <div>
            <label class="label">Correo</label>
            <input v-model="form.email" type="email" class="input" />
          </div>
          <div>
            <label class="label">Teléfono *</label>
            <input v-model="form.phone" type="tel" class="input" :class="{'input-error': form.errors.phone}" required />
            <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1">{{ form.errors.phone }}</p>
          </div>
          <div>
            <label class="label">Fecha de nacimiento</label>
            <input v-model="form.date_of_birth" type="date" class="input" />
          </div>
          <div>
            <label class="label">Género</label>
            <select v-model="form.gender" class="input">
              <option value="">Seleccionar…</option>
              <option value="male">Masculino</option>
              <option value="female">Femenino</option>
              <option value="other">Otro</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Address -->
      <div class="card p-6">
        <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Dirección</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="label">Dirección</label>
            <input v-model="form.address" type="text" class="input" />
          </div>
          <div>
            <label class="label">Ciudad</label>
            <input v-model="form.city" type="text" class="input" />
          </div>
          <div>
            <label class="label">Estado/Provincia</label>
            <input v-model="form.state" type="text" class="input" />
          </div>
          <div>
            <label class="label">Código postal</label>
            <input v-model="form.zip_code" type="text" class="input" />
          </div>
        </div>
      </div>

      <!-- Medical -->
      <div class="card p-6">
        <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Información médica</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Tipo de sangre</label>
            <select v-model="form.blood_type" class="input">
              <option value="">Desconocido</option>
              <option v-for="bt in bloodTypes" :key="bt" :value="bt">{{ bt }}</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="label">Alergias</label>
            <textarea v-model="form.allergies" rows="2" class="input" placeholder="Lista de alergias conocidas…" />
          </div>
          <div class="sm:col-span-2">
            <label class="label">Notas médicas</label>
            <textarea v-model="form.medical_notes" rows="3" class="input" placeholder="Historial médico relevante, condiciones…" />
          </div>
        </div>
      </div>

      <!-- Insurance -->
      <div class="card p-6">
        <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Seguro médico</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Aseguradora</label>
            <input v-model="form.insurance_provider" type="text" class="input" placeholder="BlueCross, Aetna…" />
          </div>
          <div>
            <label class="label">Número de póliza</label>
            <input v-model="form.insurance_policy_number" type="text" class="input" />
          </div>
        </div>
      </div>

      <!-- Emergency Contact -->
      <div class="card p-6">
        <h2 class="font-display font-semibold text-navy-900 dark:text-white mb-4">Contacto de emergencia</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="label">Nombre</label>
            <input v-model="form.emergency_contact_name" type="text" class="input" />
          </div>
          <div>
            <label class="label">Teléfono</label>
            <input v-model="form.emergency_contact_phone" type="tel" class="input" />
          </div>
          <div>
            <label class="label">Parentesco</label>
            <input v-model="form.emergency_contact_relation" type="text" class="input" placeholder="Cónyuge/Padre o madre…" />
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <Link :href="route('patients.index')" class="btn-outline">Cancelar</Link>
        <button type="submit" class="btn-primary" :disabled="form.processing">
          <span v-if="form.processing">Guardando…</span>
          <span v-else>Crear paciente</span>
        </button>
      </div>
    </form>
  </AppLayout>
</template>
