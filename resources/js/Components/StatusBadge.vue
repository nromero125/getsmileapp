<script setup>
const props = defineProps({ status: String, type: { type: String, default: 'appointment' } })

const appointmentColors = {
  scheduled:   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  confirmed:   'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
  in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  completed:   'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  cancelled:   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  no_show:     'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
}
const invoiceColors = {
  draft:    'bg-gray-100 text-gray-700',
  pending:  'bg-amber-100 text-amber-700',
  partial:  'bg-blue-100 text-blue-700',
  paid:     'bg-green-100 text-green-700',
  cancelled:'bg-red-100 text-red-700',
}
const colorMap = props.type === 'invoice' ? invoiceColors : appointmentColors
const dotColors = { scheduled:'bg-blue-400', confirmed:'bg-teal-400', in_progress:'bg-amber-400 animate-pulse', completed:'bg-green-400', cancelled:'bg-red-400', no_show:'bg-gray-400', paid:'bg-green-400', pending:'bg-amber-400', partial:'bg-blue-400', draft:'bg-gray-400' }

const labels = {
  scheduled:   'Programada',
  confirmed:   'Confirmada',
  in_progress: 'En curso',
  completed:   'Completada',
  cancelled:   'Cancelada',
  no_show:     'No se presentó',
  draft:       'Borrador',
  pending:     'Pendiente',
  partial:     'Pago parcial',
  paid:        'Pagada',
}
</script>

<template>
  <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', colorMap[status] || 'bg-gray-100 text-gray-700']">
    <span :class="['w-1.5 h-1.5 rounded-full', dotColors[status] || 'bg-gray-400']" />
    {{ labels[status] ?? status?.replace('_', ' ') }}
  </span>
</template>
