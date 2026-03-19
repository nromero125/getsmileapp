<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

defineProps({ links: Array })
</script>

<template>
  <div v-if="links && links.length > 3" class="flex items-center justify-between mt-6 py-3 border-t border-navy-100 dark:border-navy-800">
    <p class="text-sm text-navy-500">
      Mostrando resultados
    </p>
    <div class="flex items-center gap-1">
      <template v-for="link in links" :key="link.label">
        <Link v-if="link.url"
          :href="link.url"
          :class="[
            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
            link.active
              ? 'bg-navy-900 text-white'
              : 'text-navy-600 hover:bg-navy-100 dark:hover:bg-navy-800'
          ]"
          v-html="link.label" preserve-scroll />
        <span v-else :class="['px-3 py-1.5 rounded-lg text-sm text-navy-400', link.active ? 'font-bold' : '']" v-html="link.label" />
      </template>
    </div>
  </div>
</template>
