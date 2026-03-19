<script setup>
defineProps({
  title: String,
  value: [String, Number],
  subtitle: String,
  icon: Object,
  trend: Number,
  color: { type: String, default: 'teal' },
  loading: Boolean,
})
const colors = {
  teal:  { bg: 'bg-teal-50 dark:bg-teal-900/20',  icon: 'text-teal-600',  border: 'border-teal-200/50' },
  navy:  { bg: 'bg-navy-50 dark:bg-navy-800/50',   icon: 'text-navy-600',  border: 'border-navy-200/50' },
  green: { bg: 'bg-green-50 dark:bg-green-900/20', icon: 'text-green-600', border: 'border-green-200/50' },
  amber: { bg: 'bg-amber-50 dark:bg-amber-900/20', icon: 'text-amber-600', border: 'border-amber-200/50' },
  red:   { bg: 'bg-red-50 dark:bg-red-900/20',     icon: 'text-red-600',   border: 'border-red-200/50' },
}
</script>

<template>
  <div class="card p-6 animate-slide-up">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="text-xs font-semibold uppercase tracking-widest text-navy-400 mb-2">{{ title }}</p>
        <div v-if="loading" class="skeleton h-8 w-32 mb-2" />
        <p v-else class="text-3xl font-display font-bold text-navy-900 dark:text-white">{{ value }}</p>
        <p v-if="subtitle" class="text-sm text-navy-500 mt-1">{{ subtitle }}</p>
      </div>
      <div v-if="icon" :class="['w-12 h-12 rounded-2xl flex items-center justify-center border', colors[color]?.bg, colors[color]?.border]">
        <component :is="icon" :class="['w-6 h-6', colors[color]?.icon]" />
      </div>
    </div>
    <div v-if="trend !== undefined" class="flex items-center gap-1 mt-3">
      <span :class="['text-xs font-semibold', trend >= 0 ? 'text-green-600' : 'text-red-500']">
        {{ trend >= 0 ? '↑' : '↓' }} {{ Math.abs(trend) }}%
      </span>
      <span class="text-xs text-navy-400">vs mes anterior</span>
    </div>
  </div>
</template>
