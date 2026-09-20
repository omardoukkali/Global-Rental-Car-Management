<script setup>
defineProps({
  agency: { type: Object, default: null },
  totalCars: { type: [Number, String], default: null },
})
</script>

<template>
  <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <slot />
  </div>
</template>
