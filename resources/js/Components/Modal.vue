<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  title: String,
  size: { type: String, default: 'md' },
  closeable: { type: Boolean, default: true },
})
const emit = defineEmits(['close'])

const sizes = {
  sm: 'max-w-sm',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0">
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-navy-950/60 backdrop-blur-sm" @click="closeable && emit('close')" />

          <!-- Panel -->
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">
            <div v-if="show" :class="['relative bg-white dark:bg-navy-900 rounded-2xl shadow-card-lg border border-navy-100 dark:border-navy-800 w-full', sizes[size]]">
              <div class="flex items-center justify-between p-6 border-b border-navy-100 dark:border-navy-800">
                <h3 class="text-lg font-semibold text-navy-900 dark:text-white">{{ title }}</h3>
                <button v-if="closeable" @click="emit('close')" class="p-1.5 rounded-lg text-navy-400 hover:text-navy-900 hover:bg-navy-100 dark:hover:bg-navy-800 transition-colors">
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>
              <div class="p-6">
                <slot />
              </div>
              <div v-if="$slots.footer" class="px-6 pb-6 flex justify-end gap-3">
                <slot name="footer" />
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
