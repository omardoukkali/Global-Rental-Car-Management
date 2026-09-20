<script setup>
defineProps({
  meta: {
    type: Object,
    default: () => ({ current_page: 1, last_page: 1, total: 0 }),
  },
})

const emit = defineEmits(['prev', 'next'])
</script>

<template>
  <div v-if="meta.last_page > 1" class="pager">
    <button type="button" :disabled="meta.current_page <= 1" @click="emit('prev')">Précédent</button>
    <span>Page {{ meta.current_page }} / {{ meta.last_page }} · {{ meta.total }}</span>
    <button type="button" :disabled="meta.current_page >= meta.last_page" @click="emit('next')">Suivant</button>
  </div>
</template>
