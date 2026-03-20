<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({ email: String, token: String })

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => form.post(route('password.store'), {
  onFinish: () => form.reset('password', 'password_confirmation'),
})
</script>

<template>
  <Head title="Reset Password" />

  <div class="min-h-screen bg-navy-900 bg-tooth-pattern flex items-center justify-center p-6">
    <div class="w-full max-w-md">
      <div class="flex items-center justify-center gap-2 mb-8">
        <div class="w-9 h-9 bg-teal-500 rounded-2xl flex items-center justify-center shadow-lg">
          <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z" />
          </svg>
        </div>
        <span class="font-display text-white font-semibold text-xl">Dentaris</span>
      </div>

      <div class="auth-card bg-white rounded-3xl p-8 shadow-card-lg">
        <div class="mb-6">
          <h2 class="font-display text-2xl font-bold text-navy-900">Set new password</h2>
          <p class="text-navy-500 text-sm mt-1">Choose a strong password for your account</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="label">Email Address</label>
            <input v-model="form.email" type="email" class="input" :class="{'input-error': form.errors.email}"
              required autocomplete="username" />
            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
          </div>
          <div>
            <label class="label">New Password</label>
            <input v-model="form.password" type="password" class="input" :class="{'input-error': form.errors.password}"
              required autocomplete="new-password" autofocus />
            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
          </div>
          <div>
            <label class="label">Confirm Password</label>
            <input v-model="form.password_confirmation" type="password" class="input"
              required autocomplete="new-password" />
          </div>

          <button type="submit" class="btn-primary w-full justify-center py-3" :disabled="form.processing">
            {{ form.processing ? 'Resetting…' : 'Reset Password' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
