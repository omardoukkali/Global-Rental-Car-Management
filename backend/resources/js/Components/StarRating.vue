<template>
  <div class="flex items-center gap-1">
    <button
      v-for="star in maxStars"
      :key="star"
      type="button"
      :disabled="!interactive"
      @click="setRating(star)"
      class="p-0.5 focus:outline-none transition-transform"
      :class="{ 'cursor-pointer hover:scale-110': interactive, 'cursor-default': !interactive }"
    >
      <StarIcon
        class="w-5 h-5 transition-all"
        :class="{
          'text-amber-400 fill-amber-400': star <= currentRating,
          'text-slate-600 fill-none': star > currentRating
        }"
      />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Star as StarIcon } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: Number,
    default: 0
  },
  rating: {
    type: Number,
    default: 0
  },
  maxStars: {
    type: Number,
    default: 5
  },
  interactive: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

const currentRating = computed(() => {
  return props.interactive ? props.modelValue : props.rating;
});

const setRating = (val) => {
  if (props.interactive) {
    emit('update:modelValue', val);
  }
};
</script>
