<template>
  <Transition name="fade">
    <div v-if="show" class="fixed inset-0 z-50 flex-center p-6 bg-slate-950/70 backdrop-blur-sm">
      <!-- Modal Container -->
      <div 
        class="glass-card w-full bg-slate-900 border border-white/10 rounded-xl overflow-hidden shadow-2xl flex flex-col transition-all max-h-[90vh]"
        :style="{ maxWidth: maxWidth }"
      >
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-white/5 flex-between">
          <h3 class="text-lg font-bold text-slate-100">{{ title }}</h3>
          <button @click="$emit('close')" class="text-slate-400 hover:text-slate-200 transition-colors text-xl font-bold">
            &times;
          </button>
        </div>

        <!-- Modal Body Content -->
        <div class="px-6 py-6 overflow-y-auto flex-grow">
          <slot />
        </div>

        <!-- Modal Footer Actions -->
        <div v-if="$slots.footer" class="px-6 py-4 border-t border-white/5 bg-slate-950/20 flex justify-end gap-3">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  show: Boolean,
  title: String,
  maxWidth: {
    type: String,
    default: '500px'
  }
});

defineEmits(['close']);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-from .glass-card {
  transform: scale(0.95) translateY(10px);
}

.fade-leave-to .glass-card {
  transform: scale(0.95) translateY(10px);
}
</style>
