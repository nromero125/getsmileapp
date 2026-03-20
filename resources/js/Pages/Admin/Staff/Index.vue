<script setup>
import { ref, computed } from 'vue'
import { useForm, router, Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { UserPlusIcon, PencilIcon, UserMinusIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  staff: Array,
  activeCount: Number,
  seatsIncluded: Number,
  extraSeats: Number,
  onTrial: Boolean,
  activeDentists: Array,
})

// ── Add ──────────────────────────────────────────
const showAddModal = ref(false)
const addForm = useForm({ name: '', email: '', password: '', role: 'dentist', phone: '', specialty: '' })
const submitAdd = () => {
  addForm.post(route('staff.store'), {
    onSuccess: () => { showAddModal.value = false; addForm.reset() }
  })
}

// ── Edit ─────────────────────────────────────────
const showEditModal = ref(false)
const editingUser = ref(null)
const editForm = useForm({ name: '', phone: '', specialty: '', role: 'dentist', is_active: true })
const openEdit = (member) => {
  editingUser.value = member
  editForm.name      = member.name
  editForm.phone     = member.phone ?? ''
  editForm.specialty = member.specialty ?? ''
  editForm.role      = member.role
  editForm.is_active = member.is_active
  showEditModal.value = true
}
const submitEdit = () => {
  editForm.put(route('staff.update', editingUser.value.id), {
    onSuccess: () => { showEditModal.value = false }
  })
}

// ── Deactivate with reassignment ─────────────────
const deactivateModal = ref({ show: false, user: null })
const reassignTo = ref('')

const openDeactivate = (user) => {
  reassignTo.value = ''
  deactivateModal.value = { show: true, user }
}

const hasFutureAppointments = computed(() =>
  (deactivateModal.value.user?.future_appointments_count ?? 0) > 0
)

const otherActiveDentists = computed(() =>
  props.activeDentists.filter(d => d.id !== deactivateModal.value.user?.id)
)

const doDeactivate = () => {
  router.delete(route('staff.destroy', deactivateModal.value.user.id), {
    data: reassignTo.value ? { reassign_to: reassignTo.value } : {},
    onFinish: () => { deactivateModal.value = { show: false, user: null } }
  })
}

// ── Reactivate ───────────────────────────────────
const doReactivate = (user) => {
  router.put(route('staff.update', user.id), { ...user, is_active: true })
}

const roleColors = { admin: 'badge-blue', dentist: 'badge-teal', receptionist: 'badge-amber' }
const roleLabels = { admin: 'Admin', dentist: 'Dentista', receptionist: 'Recepcionista' }
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Gestión de Personal</h1>
    </template>
    <Head title="Personal" />

    <div class="space-y-4 max-w-4xl">

      <!-- Seat usage -->
      <div class="card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-6">
            <div class="text-center">
              <p class="text-2xl font-bold text-navy-900 dark:text-white">{{ activeCount }}</p>
              <p class="text-xs text-navy-500">Activos</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold text-navy-900 dark:text-white">{{ seatsIncluded }}</p>
              <p class="text-xs text-navy-500">Incluidos</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold" :class="extraSeats > 0 ? 'text-teal-600' : 'text-navy-900 dark:text-white'">{{ extraSeats }}</p>
              <p class="text-xs text-navy-500">Extra</p>
            </div>
          </div>
          <div v-if="extraSeats > 0"
            class="flex items-center gap-2 px-3 py-2 rounded-xl bg-teal-50 dark:bg-teal-900/20 text-sm text-teal-700 dark:text-teal-400">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ extraSeats }} usuario{{ extraSeats > 1 ? 's' : '' }} extra · +${{ extraSeats * 3 }}/mes
          </div>
          <div v-else-if="onTrial && activeCount >= seatsIncluded"
            class="flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-sm text-amber-700 dark:text-amber-400">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            Límite del período de prueba alcanzado
          </div>
        </div>
      </div>

      <!-- Staff list -->
      <div class="card">
        <div class="p-4 border-b border-navy-100 dark:border-navy-800 flex items-center justify-between">
          <p class="text-sm text-navy-500">{{ staff.length }} miembros del equipo</p>
          <button @click="showAddModal = true" class="btn-primary">
            <UserPlusIcon class="w-4 h-4" /> Agregar Personal
          </button>
        </div>
        <div class="divide-y divide-navy-100 dark:divide-navy-800">
          <div v-for="member in staff" :key="member.id"
            class="flex items-center gap-4 px-4 py-4 transition-colors"
            :class="member.is_active ? 'hover:bg-navy-50/50 dark:hover:bg-navy-800/30' : 'opacity-60'">
            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&background=${member.is_active ? '0F1F3D' : '94a3b8'}&color=${member.is_active ? '00BFA6' : 'ffffff'}`"
              class="w-11 h-11 rounded-2xl flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="font-semibold text-navy-900 dark:text-white">{{ member.name }}</p>
                <span :class="['badge', roleColors[member.role] || 'badge-gray']">{{ roleLabels[member.role] ?? member.role }}</span>
                <span v-if="!member.is_active" class="badge bg-red-100 text-red-600">Inactivo</span>
              </div>
              <p class="text-sm text-navy-500">{{ member.email }}</p>
              <p v-if="member.specialty" class="text-xs text-teal-600 mt-0.5">{{ member.specialty }}</p>
              <p v-if="member.is_active && member.future_appointments_count > 0"
                class="text-xs text-amber-600 mt-0.5">
                {{ member.future_appointments_count }} cita{{ member.future_appointments_count > 1 ? 's' : '' }} futuras
              </p>
            </div>
            <div class="flex gap-1">
              <button @click="openEdit(member)"
                class="btn-ghost p-1.5 text-navy-400 hover:text-navy-700 dark:hover:text-white" title="Editar">
                <PencilIcon class="w-4 h-4" />
              </button>
              <!-- Reactivar -->
              <button v-if="!member.is_active" @click="doReactivate(member)"
                class="btn-ghost p-1.5 text-teal-500 hover:text-teal-700" title="Reactivar usuario">
                <ArrowPathIcon class="w-4 h-4" />
              </button>
              <!-- Desactivar -->
              <button v-if="member.is_active" @click="openDeactivate(member)"
                class="btn-ghost p-1.5 text-red-400 hover:text-red-600" title="Desactivar">
                <UserMinusIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Modal -->
    <Modal :show="showAddModal" title="Agregar Miembro del Personal" size="md" @close="showAddModal = false; addForm.reset()">
      <form @submit.prevent="submitAdd" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="label">Nombre completo *</label><input v-model="addForm.name" type="text" class="input" required /></div>
          <div><label class="label">Correo electrónico *</label><input v-model="addForm.email" type="email" class="input" required /></div>
          <div><label class="label">Contraseña *</label><input v-model="addForm.password" type="password" class="input" required /></div>
          <div><label class="label">Rol *</label>
            <select v-model="addForm.role" class="input">
              <option value="dentist">Dentista</option>
              <option value="receptionist">Recepcionista</option>
            </select>
          </div>
          <div><label class="label">Teléfono</label><input v-model="addForm.phone" type="tel" class="input" /></div>
          <div><label class="label">Especialidad</label><input v-model="addForm.specialty" type="text" class="input" placeholder="Ortodoncia, Endodoncia…" /></div>
        </div>
        <div v-if="Object.keys(addForm.errors).length" class="p-3 bg-red-50 rounded-xl text-sm text-red-600">
          <p v-for="(err, key) in addForm.errors" :key="key">{{ err }}</p>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showAddModal = false; addForm.reset()" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="addForm.processing">Agregar Miembro</button>
        </div>
      </form>
    </Modal>

    <!-- Edit Modal -->
    <Modal :show="showEditModal" :title="`Editar: ${editingUser?.name}`" size="md" @close="showEditModal = false">
      <form @submit.prevent="submitEdit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="label">Nombre completo *</label><input v-model="editForm.name" type="text" class="input" required /></div>
          <div><label class="label">Rol *</label>
            <select v-model="editForm.role" class="input">
              <option value="dentist">Dentista</option>
              <option value="receptionist">Recepcionista</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div><label class="label">Teléfono</label><input v-model="editForm.phone" type="tel" class="input" /></div>
          <div><label class="label">Especialidad</label><input v-model="editForm.specialty" type="text" class="input" /></div>
        </div>
        <div class="flex items-center gap-2 pt-1">
          <input id="is_active" v-model="editForm.is_active" type="checkbox" class="rounded text-teal-500 focus:ring-teal-500" />
          <label for="is_active" class="text-sm text-navy-700 dark:text-navy-300">Usuario activo</label>
        </div>
        <div v-if="Object.keys(editForm.errors).length" class="p-3 bg-red-50 rounded-xl text-sm text-red-600">
          <p v-for="(err, key) in editForm.errors" :key="key">{{ err }}</p>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showEditModal = false" class="btn-outline">Cancelar</button>
          <button type="submit" class="btn-primary" :disabled="editForm.processing">Guardar Cambios</button>
        </div>
      </form>
    </Modal>

    <!-- Deactivate Modal -->
    <Modal
      :show="deactivateModal.show"
      :title="`Desactivar: ${deactivateModal.user?.name}`"
      size="md"
      @close="deactivateModal.show = false">

      <div class="space-y-4">
        <!-- Warning about future appointments -->
        <div v-if="hasFutureAppointments" class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
          <p class="text-sm font-semibold text-amber-800 dark:text-amber-400 mb-1">
            Este dentista tiene {{ deactivateModal.user?.future_appointments_count }} cita{{ deactivateModal.user?.future_appointments_count > 1 ? 's' : '' }} futuras pendientes.
          </p>
          <p class="text-xs text-amber-700 dark:text-amber-500">
            Puedes reasignarlas a otro dentista antes de desactivar.
          </p>
        </div>

        <div v-else class="text-sm text-navy-600 dark:text-navy-400">
          ¿Desactivar a <strong>{{ deactivateModal.user?.name }}</strong>? Ya no podrá iniciar sesión.
        </div>

        <!-- Reassign dropdown -->
        <div v-if="hasFutureAppointments && otherActiveDentists.length > 0">
          <label class="label">Reasignar citas futuras a</label>
          <select v-model="reassignTo" class="input">
            <option value="">— No reasignar (dejar sin dentista asignado) —</option>
            <option v-for="d in otherActiveDentists" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>

        <div v-if="hasFutureAppointments && otherActiveDentists.length === 0" class="text-xs text-red-600">
          No hay otros dentistas activos para reasignar.
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="deactivateModal.show = false" class="btn-outline">Cancelar</button>
          <button @click="doDeactivate" class="btn-danger">
            Desactivar{{ hasFutureAppointments && reassignTo ? ' y reasignar' : '' }}
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>
