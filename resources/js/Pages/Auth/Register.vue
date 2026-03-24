<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  clinic_name: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => form.post(route('register'), {
  onFinish: () => form.reset('password', 'password_confirmation'),
})
</script>

<template>
  <Head title="Crear cuenta" />

  <div class="min-h-screen bg-navy-900 bg-tooth-pattern flex">
    <!-- Left Panel — Branding -->
    <div class="hidden lg:flex flex-col justify-between w-1/2 p-12 relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-navy-900 via-navy-800 to-teal-900/30" />
      <div class="relative z-10">
        <div class="flex items-center gap-3 mb-16">
          <div class="w-10 h-10 bg-teal-500 rounded-2xl flex items-center justify-center shadow-lg">
            <svg viewBox="0 0 24 24" fill="none" class="w-6 h-6 text-white" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z" />
            </svg>
          </div>
          <span class="font-display text-white font-semibold text-xl">Dentarix</span>
        </div>
        <h1 class="font-display text-5xl font-bold text-white leading-tight mb-4">
          Tu clínica,<br/>en minutos
        </h1>
        <p class="text-navy-300 text-lg max-w-xs leading-relaxed">
          Crea tu cuenta y empieza a gestionar pacientes, citas y facturación hoy mismo.
        </p>
      </div>
      <div class="relative z-10 space-y-4">
        <div v-for="feature in ['Sin tarjeta de crédito requerida', 'Configuración en menos de 5 minutos', 'Soporte incluido desde el primer día']" :key="feature"
          class="flex items-center gap-3 text-navy-300 text-sm">
          <div class="w-5 h-5 bg-teal-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-3 h-3 text-teal-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
          </div>
          {{ feature }}
        </div>

        <div class="pt-6 border-t border-white/10 mt-6">
          <p class="text-navy-400 text-sm mb-3">¿Ya tienes una cuenta?</p>
          <Link :href="route('login')"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-xl transition-colors">
            Iniciar sesión
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </Link>
        </div>
      </div>
    </div>

    <!-- Right Panel — Form -->
    <div class="flex-1 flex items-center justify-center p-6">
      <div class="w-full max-w-md">
        <!-- Mobile logo -->
        <div class="flex items-center gap-2 mb-8 lg:hidden">
          <div class="w-8 h-8 bg-teal-500 rounded-xl flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z" />
            </svg>
          </div>
          <span class="font-display text-white font-semibold text-lg">Dentarix</span>
        </div>

        <div class="auth-card bg-white rounded-3xl p-8 shadow-card-lg">
          <div class="mb-6">
            <h2 class="font-display text-2xl font-bold text-navy-900">Crear cuenta</h2>
            <p class="text-navy-500 text-sm mt-1">Registra tu clínica en Dentarix</p>
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="label">Nombre de la Clínica</label>
              <input v-model="form.clinic_name" type="text" class="input" :class="{'input-error': form.errors.clinic_name}"
                required autofocus placeholder="Ej. Clínica Dental Sonrisa" />
              <p v-if="form.errors.clinic_name" class="text-xs text-red-500 mt-1">{{ form.errors.clinic_name }}</p>
            </div>
            <div>
              <label class="label">Tu Nombre Completo</label>
              <input v-model="form.name" type="text" class="input" :class="{'input-error': form.errors.name}"
                required autocomplete="name" />
              <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="label">Correo Electrónico</label>
              <input v-model="form.email" type="email" class="input" :class="{'input-error': form.errors.email}"
                required autocomplete="username" />
              <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label">Contraseña</label>
                <input v-model="form.password" type="password" class="input" :class="{'input-error': form.errors.password}"
                  required autocomplete="new-password" />
                <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
              </div>
              <div>
                <label class="label">Confirmar Contraseña</label>
                <input v-model="form.password_confirmation" type="password" class="input"
                  required autocomplete="new-password" />
              </div>
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-3 text-base" :disabled="form.processing">
              {{ form.processing ? 'Creando cuenta…' : 'Crear Cuenta' }}
            </button>
          </form>

          <!-- Divider -->
          <div class="flex items-center gap-3 my-5">
            <div class="flex-1 h-px bg-navy-100" />
            <span class="text-xs text-navy-400">¿ya tienes cuenta?</span>
            <div class="flex-1 h-px bg-navy-100" />
          </div>

          <Link :href="route('login')"
            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border-2 border-navy-200 text-sm font-semibold text-navy-700 hover:border-teal-400 hover:text-teal-600 transition-colors">
            Iniciar sesión
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
