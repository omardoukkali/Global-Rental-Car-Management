<script setup>
defineProps({
  name: { type: String, required: true },
  compact: { type: Boolean, default: false },
})
</script>

<template>
  <span class="car-spec-icon" :class="{ 'is-compact': compact }" aria-hidden="true">
    <!-- Fuel pump (diesel / gasoline) -->
    <svg
      v-if="name === 'fuel'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M4 20V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v15" />
      <path d="M3 20h13" />
      <path d="M8 8h5" />
      <path d="M15 12h1.4a1.6 1.6 0 0 1 1.6 1.6V17a1.5 1.5 0 0 0 3 0V9.2L18.4 6.6" />
    </svg>

    <!-- Electric -->
    <svg
      v-else-if="name === 'bolt'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M13 2 4 14h7l-1 8 9-12h-7l1-8z" />
    </svg>

    <!-- Hybrid -->
    <svg
      v-else-if="name === 'hybrid'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
      <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
    </svg>

    <!-- Seats -->
    <svg
      v-else-if="name === 'seats'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M19 9V6a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v3" />
      <path d="M3 16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v2H7v-2a2 2 0 0 0-4 0z" />
      <path d="M5 18v2" />
      <path d="M19 18v2" />
    </svg>

    <!-- Transmission -->
    <svg
      v-else-if="name === 'gearbox'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <circle cx="7" cy="7" r="3" />
      <circle cx="17" cy="17" r="3" />
      <path d="M10 7h10" />
      <path d="M4 17h10" />
    </svg>

    <!-- Body type -->
    <svg
      v-else-if="name === 'car'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2" />
      <circle cx="7" cy="17" r="2" />
      <path d="M9 17h6" />
      <circle cx="17" cy="17" r="2" />
    </svg>

    <!-- Color -->
    <svg
      v-else-if="name === 'color'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z" />
    </svg>

    <!-- Fuel consumption -->
    <svg
      v-else-if="name === 'gauge'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="m12 14 4-4" />
      <path d="M3.34 19a10 10 0 1 1 17.32 0" />
    </svg>

    <!-- Electric range -->
    <svg
      v-else-if="name === 'battery'"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <rect x="2" y="7" width="16" height="10" rx="2" />
      <path d="M22 11v2" />
      <path d="m7 12 2 2 4-4" />
    </svg>
  </span>
</template>

<style scoped>
.car-spec-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--turo-surface, var(--bg));
  border: 1px solid var(--turo-divider, var(--border));
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--turo-ink, var(--ink));
}

.car-spec-icon svg {
  width: 18px;
  height: 18px;
  display: block;
}

.car-spec-icon.is-compact {
  width: auto;
  height: auto;
  border: 0;
  background: none;
  border-radius: 0;
  color: currentColor;
}

.car-spec-icon.is-compact svg {
  width: 16px;
  height: 16px;
}
</style>
