<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({ status: String })

const form = useForm({ email: '' })
const submit = () => form.post(route('password.email'))
</script>

<template>
  <Head title="Forgot Password" />

  <div class="min-h-screen bg-navy-900 bg-tooth-pattern flex items-center justify-center p-6">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="flex items-center justify-center gap-2 mb-8">
        <div class="w-9 h-9 bg-teal-500 rounded-2xl flex items-center justify-center shadow-lg">
          <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z" />
          </svg>
        </div>
        <span class="font-display text-white font-semibold text-xl">Dentarix</span>
      </div>

      <div class="auth-card bg-white rounded-3xl p-8 shadow-card-lg">
        <div class="mb-6">
          <h2 class="font-display text-2xl font-bold text-navy-900">Forgot password?</h2>
          <p class="text-navy-500 text-sm mt-1">Enter your email and we'll send you a reset link.</p>
        </div>

        <div v-if="status" class="mb-4 p-3 bg-teal-50 border border-teal-200 text-teal-700 rounded-xl text-sm">
          {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="label">Email Address</label>
            <input v-model="form.email" type="email" class="input" :class="{'input-error': form.errors.email}"
              required autofocus autocomplete="username" />
            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
          </div>

          <button type="submit" class="btn-primary w-full justify-center py-3" :disabled="form.processing">
            {{ form.processing ? 'Sending…' : 'Send Reset Link' }}
          </button>
        </form>

        <p class="text-center text-sm text-navy-500 mt-6">
          Remember your password?
          <Link :href="route('login')" class="text-teal-600 hover:text-teal-700 font-medium">Sign in</Link>
        </p>
      </div>
    </div>
  </div>
</template>
