<script setup>
import Modal from './Modal.vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

defineProps({
  show: Boolean,
  title: { type: String, default: 'Confirmar acción' },
  message: { type: String, default: '¿Está seguro de que desea continuar? Esta acción no se puede deshacer.' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  danger: { type: Boolean, default: true },
})
const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Modal :show="show" :title="title" size="sm" @close="emit('cancel')">
    <div class="flex items-start gap-4">
      <div :class="['flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center', danger ? 'bg-red-100' : 'bg-amber-100']">
        <ExclamationTriangleIcon :class="['w-5 h-5', danger ? 'text-red-600' : 'text-amber-600']" />
      </div>
      <p class="text-sm text-navy-600 dark:text-navy-300 pt-2">{{ message }}</p>
    </div>
    <template #footer>
      <button @click="emit('cancel')" class="btn-outline">{{ cancelText }}</button>
      <button @click="emit('confirm')" :class="danger ? 'btn-danger' : 'btn-primary'">{{ confirmText }}</button>
    </template>
  </Modal>
</template>
