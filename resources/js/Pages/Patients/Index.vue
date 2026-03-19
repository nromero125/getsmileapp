<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { MagnifyingGlassIcon, FunnelIcon, UserPlusIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  patients: Object,
  filters: Object,
})

const can = usePage().props.can
const search = ref(props.filters?.search || '')
const gender = ref(props.filters?.gender || '')
const deleteModal = ref({ show: false, patient: null })

const doSearch = () => {
  router.get(route('patients.index'), { search: search.value, gender: gender.value }, {
    preserveState: true, replace: true
  })
}

const confirmDelete = (patient) => deleteModal.value = { show: true, patient }
const doDelete = () => {
  router.delete(route('patients.destroy', deleteModal.value.patient.id), {
    onFinish: () => deleteModal.value = { show: false, patient: null }
  })
}
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="text-xl font-display font-semibold text-navy-900 dark:text-white">Pacientes</h1>
    </template>
    <Head title="Pacientes" />

    <div class="card">
      <!-- Toolbar -->
      <div class="p-4 border-b border-navy-100 dark:border-navy-800 flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-navy-400" />
          <input v-model="search" @keyup.enter="doSearch" type="text"
            placeholder="Buscar por nombre, teléfono o correo…"
            class="input pl-9" />
        </div>
        <select v-model="gender" @change="doSearch" class="input w-full sm:w-40">
          <option value="">Todos los géneros</option>
          <option value="male">Masculino</option>
          <option value="female">Femenino</option>
          <option value="other">Otro</option>
        </select>
        <Link :href="route('patients.create')" class="btn-primary flex-shrink-0">
          <UserPlusIcon class="w-4 h-4" />
          Agregar paciente
        </Link>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-navy-100 dark:border-navy-800">
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase tracking-wide">Paciente</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase tracking-wide hidden sm:table-cell">Contacto</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase tracking-wide hidden md:table-cell">Edad</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase tracking-wide hidden lg:table-cell">Ciudad</th>
              <th class="text-left px-4 py-3 font-semibold text-navy-500 text-xs uppercase tracking-wide hidden lg:table-cell">Seguro médico</th>
              <th class="w-24"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="patients?.data?.length === 0">
              <td colspan="6" class="px-4 py-16 text-center text-navy-400">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-12 h-12 bg-navy-100 rounded-2xl flex items-center justify-center">
                    <UsersIcon class="w-6 h-6 text-navy-400" />
                  </div>
                  <p class="font-medium">No se encontraron pacientes</p>
                  <p class="text-sm">Intenta ajustar tu búsqueda o agrega un nuevo paciente</p>
                </div>
              </td>
            </tr>
            <tr v-for="patient in patients?.data" :key="patient.id" class="table-row">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <img :src="patient.avatar_url" class="w-9 h-9 rounded-full flex-shrink-0" />
                  <div>
                    <Link :href="route('patients.show', patient.id)" class="font-semibold text-navy-900 dark:text-white hover:text-teal-600 transition-colors">
                      {{ patient.full_name }}
                    </Link>
                    <p class="text-xs text-navy-400 capitalize">{{ patient.gender }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 hidden sm:table-cell">
                <p class="text-navy-700 dark:text-navy-300">{{ patient.phone }}</p>
                <p class="text-xs text-navy-400">{{ patient.email }}</p>
              </td>
              <td class="px-4 py-3 hidden md:table-cell text-navy-700 dark:text-navy-300">{{ patient.age ? patient.age + ' años' : '—' }}</td>
              <td class="px-4 py-3 hidden lg:table-cell text-navy-700 dark:text-navy-300">{{ patient.city || '—' }}</td>
              <td class="px-4 py-3 hidden lg:table-cell">
                <span v-if="patient.insurance_provider" class="badge-teal">{{ patient.insurance_provider }}</span>
                <span v-else class="text-navy-300">—</span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <Link :href="route('patients.show', patient.id)" class="btn-ghost p-1.5" title="Ver">
                    <EyeIcon class="w-4 h-4" />
                  </Link>
                  <Link :href="route('patients.edit', patient.id)" class="btn-ghost p-1.5" title="Editar">
                    <PencilIcon class="w-4 h-4" />
                  </Link>
                  <button v-if="can.billing" @click="confirmDelete(patient)" class="btn-ghost p-1.5 text-red-400 hover:text-red-600" title="Eliminar">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-4">
        <Pagination :links="patients?.links" />
      </div>
    </div>

    <ConfirmModal
      :show="deleteModal.show"
      title="Eliminar paciente"
      :message="`¿Eliminar a ${deleteModal.patient?.full_name}? Se eliminarán todos sus registros.`"
      @confirm="doDelete"
      @cancel="deleteModal.show = false"
    />
  </AppLayout>
</template>
