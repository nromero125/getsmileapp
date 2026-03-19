<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ArrowLeftIcon, UserCircleIcon, LockClosedIcon, TrashIcon } from '@heroicons/vue/24/outline'

defineProps({ mustVerifyEmail: Boolean, status: String })

const user = usePage().props.auth.user

const profileForm = useForm({
  name: user.name,
  email: user.email,
  phone: user.phone || '',
  specialty: user.specialty || '',
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const deleteForm = useForm({ password: '' })

const updateProfile = () => profileForm.patch(route('profile.update'))
const updatePassword = () => passwordForm.put(route('password.update'), {
  onSuccess: () => passwordForm.reset(),
})

const showDeleteConfirm = () => {
  if (confirm('Are you sure you want to delete your account? This action is irreversible.')) {
    deleteForm.delete(route('profile.destroy'))
  }
}
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">My Profile</h1>
    </template>
    <Head title="Profile" />

    <div class="max-w-2xl space-y-6">

      <!-- Profile Info Card -->
      <div class="card p-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-9 h-9 bg-teal-50 dark:bg-teal-900/20 rounded-xl flex items-center justify-center">
            <UserCircleIcon class="w-5 h-5 text-teal-600" />
          </div>
          <div>
            <h2 class="font-display font-semibold text-navy-900 dark:text-white">Profile Information</h2>
            <p class="text-xs text-navy-400">Update your name, email and contact details</p>
          </div>
        </div>

        <!-- Avatar preview -->
        <div class="flex items-center gap-4 mb-6 p-4 bg-navy-50 dark:bg-navy-800 rounded-2xl">
          <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=0F1F3D&color=00BFA6&size=80`"
            class="w-16 h-16 rounded-2xl" />
          <div>
            <p class="font-semibold text-navy-900 dark:text-white">{{ user.name }}</p>
            <p class="text-sm text-navy-500 capitalize">{{ user.role }}</p>
            <p v-if="user.specialty" class="text-xs text-teal-600 mt-0.5">{{ user.specialty }}</p>
          </div>
        </div>

        <form @submit.prevent="updateProfile" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="label">Full Name</label>
              <input v-model="profileForm.name" type="text" class="input" :class="{'input-error': profileForm.errors.name}" required />
              <p v-if="profileForm.errors.name" class="text-xs text-red-500 mt-1">{{ profileForm.errors.name }}</p>
            </div>
            <div>
              <label class="label">Email Address</label>
              <input v-model="profileForm.email" type="email" class="input" :class="{'input-error': profileForm.errors.email}" required />
              <p v-if="profileForm.errors.email" class="text-xs text-red-500 mt-1">{{ profileForm.errors.email }}</p>
            </div>
            <div>
              <label class="label">Phone</label>
              <input v-model="profileForm.phone" type="tel" class="input" />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Specialty <span class="text-navy-400 font-normal normal-case">(dentists only)</span></label>
              <input v-model="profileForm.specialty" type="text" class="input" placeholder="Orthodontics, Endodontics…" />
            </div>
          </div>

          <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-primary" :disabled="profileForm.processing">
              {{ profileForm.processing ? 'Saving…' : 'Save Changes' }}
            </button>
            <Transition enter-active-class="transition-opacity duration-300" leave-active-class="transition-opacity duration-300"
              enter-from-class="opacity-0" leave-to-class="opacity-0">
              <p v-if="profileForm.recentlySuccessful" class="text-sm text-teal-600 font-medium">✓ Saved</p>
            </Transition>
          </div>
        </form>
      </div>

      <!-- Change Password Card -->
      <div class="card p-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-9 h-9 bg-navy-50 dark:bg-navy-800 rounded-xl flex items-center justify-center">
            <LockClosedIcon class="w-5 h-5 text-navy-600" />
          </div>
          <div>
            <h2 class="font-display font-semibold text-navy-900 dark:text-white">Change Password</h2>
            <p class="text-xs text-navy-400">Use a strong password of at least 8 characters</p>
          </div>
        </div>

        <form @submit.prevent="updatePassword" class="space-y-4">
          <div>
            <label class="label">Current Password</label>
            <input v-model="passwordForm.current_password" type="password" class="input" :class="{'input-error': passwordForm.errors.current_password}" autocomplete="current-password" />
            <p v-if="passwordForm.errors.current_password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.current_password }}</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="label">New Password</label>
              <input v-model="passwordForm.password" type="password" class="input" :class="{'input-error': passwordForm.errors.password}" autocomplete="new-password" />
              <p v-if="passwordForm.errors.password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.password }}</p>
            </div>
            <div>
              <label class="label">Confirm New Password</label>
              <input v-model="passwordForm.password_confirmation" type="password" class="input" autocomplete="new-password" />
            </div>
          </div>

          <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-secondary" :disabled="passwordForm.processing">
              {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
            </button>
            <Transition enter-active-class="transition-opacity duration-300" leave-active-class="transition-opacity duration-300"
              enter-from-class="opacity-0" leave-to-class="opacity-0">
              <p v-if="passwordForm.recentlySuccessful" class="text-sm text-teal-600 font-medium">✓ Password updated</p>
            </Transition>
          </div>
        </form>
      </div>

      <!-- Delete Account Card -->
      <div class="card p-6 border-red-200 dark:border-red-900/50">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-9 h-9 bg-red-50 dark:bg-red-900/20 rounded-xl flex items-center justify-center">
            <TrashIcon class="w-5 h-5 text-red-500" />
          </div>
          <div>
            <h2 class="font-display font-semibold text-red-600">Delete Account</h2>
            <p class="text-xs text-navy-400">This action is permanent and cannot be undone</p>
          </div>
        </div>
        <button @click="showDeleteConfirm" class="btn-danger">Delete My Account</button>
      </div>

    </div>
  </AppLayout>
</template>
