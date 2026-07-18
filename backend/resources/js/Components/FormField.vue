<template>
  <div class="form-group">
    <label v-if="label" class="form-label mb-1 text-xs font-semibold text-slate-400 select-none">
      {{ label }}
      <span v-if="required" class="text-rose-500">*</span>
    </label>
    
    <!-- Textarea Input -->
    <textarea
      v-if="type === 'textarea'"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-input min-h-[100px] py-2 px-3 border rounded text-sm bg-slate-900 border-white/10 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/20 text-slate-200 outline-none resize-none transition-all disabled:opacity-50"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
    ></textarea>

    <!-- Select Dropdown Input -->
    <select
      v-else-if="type === 'select'"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      class="form-input py-2 px-3 border rounded text-sm bg-slate-900 border-white/10 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/20 text-slate-200 outline-none transition-all disabled:opacity-50"
      :required="required"
      :disabled="disabled"
    >
      <slot />
    </select>

    <!-- Standard Text/Password/Date/Number Input -->
    <input
      v-else
      :type="type"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-input py-2 px-3 border rounded text-sm bg-slate-900 border-white/10 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/20 text-slate-200 outline-none transition-all disabled:opacity-50"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :min="min"
      :max="max"
      :step="step"
    />

    <!-- Validation Error Alert -->
    <span v-if="error" class="text-xs text-rose-500 mt-1 font-medium select-none animate-slide">
      {{ error }}
    </span>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  modelValue: [String, Number],
  error: String,
  type: {
    type: String,
    default: 'text'
  },
  placeholder: String,
  required: Boolean,
  disabled: Boolean,
  min: [String, Number],
  max: [String, Number],
  step: [String, Number]
});

defineEmits(['update:modelValue']);
</script>
