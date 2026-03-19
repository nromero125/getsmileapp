<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { UserPlusIcon, PencilIcon, UserMinusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ staff: Array })

const showAddModal = ref(false)
const confirmDeactivate = ref({ show: false, user: null })

const form = useForm({
  name: '', email: '', password: '', role: 'dentist', phone: '', specialty: ''
})

const submit = () => {
  form.post(route('staff.store'), {
    onSuccess: () => { showAddModal.value = false; form.reset() }
  })
}

const deactivate = (user) => confirmDeactivate.value = { show: true, user }
const doDeactivate = () => {
  router.delete(route('staff.destroy', confirmDeactivate.value.user.id), {
    onFinish: () => confirmDeactivate.value = { show: false, user: null }
  })
}

const roleColors = { admin: 'badge-blue', dentist: 'badge-teal', receptionist: 'badge-amber' }
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Gestión de Personal</h1>
    </template>
    <Head title="Personal" />

    <div class="card">
      <div class="p-4 border-b border-navy-100 dark:border-navy-800 flex items-center justify-between">
        <p class="text-sm text-navy-500">{{ staff.length }} miembros del equipo</p>
        <button @click="showAddModal = true" class="btn-primary">
          <UserPlusIcon class="w-4 h-4" /> Agregar Personal
        </button>
      </div>

      <div class="divide-y divide-navy-100 dark:divide-navy-800">
        <div v-for="member in staff" :key="member.id"
          class="flex items-center gap-4 px-4 py-4 hover:bg-navy-50/50 dark:hover:bg-navy-800/30 transition-colors">
          <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&background=0F1F3D&color=00BFA6`"
            class="w-11 h-11 rounded-2xl flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="font-semibold text-navy-900 dark:text-white">{{ member.name }}</p>
              <span :class="roleColors[member.role] || 'badge-gray'" class="capitalize">{{ member.role }}</span>
              <span v-if="!member.is_active" class="badge bg-red-100 text-red-600">Inactivo</span>
            </div>
            <p class="text-sm text-navy-500">{{ member.email }}</p>
            <p v-if="member.specialty" class="text-xs text-teal-600 mt-0.5">{{ member.specialty }}</p>
          </div>
          <div class="flex gap-1">
            <button v-if="member.is_active" @click="deactivate(member)"
              class="btn-ghost p-1.5 text-red-400 hover:text-red-600" title="Desactivar">
              <UserMinusIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Staff Modal -->
    <Modal :show="showAddModal" title="Agregar Miembro del Personal" size="md" @close="showAddModal = false; form.reset()">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="label">Nombre completo *</label><input v-model="form.name" type="text" class="input" required /></div>
          <div><label class="label">Correo electrónico *</label><input v-model="form.email" type="email" class="input" required /></div>
          <div><label class="label">Contraseña *</label><input v-model="form.password" type="password" class="input" required /></div>
          <div><label class="label">Rol *</label>
            <select v-model="form.role" class="input">
              <option value="dentist">Dentista</option>
              <option value="receptionist">Recepcionista</option>
            </select>
          </div>
          <div><label class="label">Teléfono</label><input v-model="form.phone" type="tel" class="input" /></div>
          <div><label class="label">Especialidad</label><input v-model="form.specialty" type="text" class="input" placeholder="Ortodoncia, Endodoncia…" /></div>
        </div>
        <div v-if="Object.keys(form.errors).length" class="p-3 bg-red-50 rounded-xl text-sm text-red-600">
          <p v-for="(err, key) in form.errors" :key="key">{{ err }}</p>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showAddModal = false; form.reset()" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="form.processing">Agregar Miembro</button>
        </div>
      </form>
    </Modal>

    <ConfirmModal
      :show="confirmDeactivate.show"
      title="Desactivar Miembro del Personal"
      :message="`¿Desactivar a ${confirmDeactivate.user?.name}? Ya no podrá iniciar sesión.`"
      confirm-text="Desactivar"
      @confirm="doDeactivate"
      @cancel="confirmDeactivate.show = false"
    />
  </AppLayout>
</template>
