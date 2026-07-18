<template>
  <div v-if="links && links.length > 3" class="flex flex-wrap items-center justify-center gap-1.5 mt-8">
    <template v-for="(link, key) in links" :key="key">
      <!-- Disabled Null Links -->
      <div
        v-if="link.url === null"
        class="px-3.5 py-2 text-xs font-semibold text-slate-500 border border-white/5 bg-slate-900/30 rounded select-none"
        v-html="link.label"
      ></div>

      <!-- Active Page Links -->
      <Link
        v-else
        :href="link.url"
        class="px-3.5 py-2 text-xs font-semibold border rounded transition-all"
        :class="{
          'bg-indigo-600 border-indigo-600 text-white font-bold': link.active,
          'bg-slate-900 border-white/10 text-slate-300 hover:bg-slate-800 hover:border-white/20': !link.active
        }"
        v-html="link.label"
        preserve-scroll
      ></Link>
    </template>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  links: {
    type: Array,
    required: true
  }
});
</script>
