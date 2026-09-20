<script setup>
import { computed, ref } from 'vue'

/**
 * Photo gallery editor for a car.
 *
 * - `images`: [{ id?, url, is_primary, display_order }]
 * - Emits `add` (url), `remove` (image), `primary` (image), `move` ({ image, direction }).
 * The parent decides whether to call the API (edit) or keep the list locally (create).
 */
const props = defineProps({
  images: { type: Array, default: () => [] },
  busy: { type: Boolean, default: false },
  max: { type: Number, default: 10 },
})

const emit = defineEmits(['add', 'remove', 'primary', 'move'])

const url = ref('')
const urlError = ref('')

const sorted = computed(() =>
  [...props.images].sort((a, b) => {
    if (!!a.is_primary !== !!b.is_primary) return a.is_primary ? -1 : 1
    return (a.display_order ?? 0) - (b.display_order ?? 0)
  })
)

const canAdd = computed(() => props.images.length < props.max)

function isValidUrl(value) {
  try {
    const parsed = new URL(value)
    return parsed.protocol === 'http:' || parsed.protocol === 'https:'
  } catch {
    return false
  }
}

const previewOk = computed(() => isValidUrl(url.value.trim()))

function submitUrl() {
  urlError.value = ''
  const value = url.value.trim()
  if (!value) return
  if (!isValidUrl(value)) {
    urlError.value = 'Entrez une adresse d’image valide (https://…).'
    return
  }
  if (props.images.some((img) => img.url === value)) {
    urlError.value = 'Cette photo est déjà dans la galerie.'
    return
  }
  if (!canAdd.value) {
    urlError.value = `Maximum ${props.max} photos.`
    return
  }
  emit('add', value)
  url.value = ''
}

function imageKey(image, index) {
  return image.id || `${image.url}-${index}`
}
</script>

<template>
  <div class="space-y-4" data-testid="car-image-gallery">
    <div class="flex flex-col sm:flex-row gap-2">
      <div class="flex-1">
        <label class="sr-only" for="gallery-url">Adresse de la photo</label>
        <input
          id="gallery-url"
          v-model="url"
          type="url"
          class="form-input"
          :class="{ 'is-invalid': urlError }"
          placeholder="https://… (lien direct vers une photo du véhicule)"
          :disabled="busy || !canAdd"
          @keydown.enter.prevent="submitUrl"
        />
        <p v-if="urlError" class="text-xs text-red-500 mt-1">{{ urlError }}</p>
        <p v-else class="text-xs text-slate-400 mt-1">
          {{ images.length }} / {{ max }} photos · la première photo est celle affichée dans le catalogue.
        </p>
      </div>
      <button
        type="button"
        class="px-4 py-2.5 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50 shrink-0 h-[42px]"
        :disabled="busy || !canAdd || !previewOk"
        data-testid="gallery-add"
        @click="submitUrl"
      >
        Ajouter la photo
      </button>
    </div>

    <div
      v-if="previewOk"
      class="flex items-center gap-3 p-3 rounded-xl border border-dashed border-slate-300 bg-slate-50"
    >
      <img :src="url.trim()" alt="Aperçu" class="w-20 h-14 object-cover rounded-lg bg-slate-200" />
      <p class="text-xs text-slate-500">Aperçu — cliquez sur « Ajouter la photo » pour l’enregistrer.</p>
    </div>

    <div
      v-if="sorted.length === 0"
      class="border-2 border-dashed border-slate-300 bg-slate-50 rounded-2xl p-8 text-center"
    >
      <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-3 text-slate-500">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <p class="text-sm font-bold text-[#0F172A]">Aucune photo pour le moment</p>
      <p class="text-xs text-slate-500 mt-1">Ajoutez plusieurs vues : extérieur, intérieur, coffre…</p>
    </div>

    <ul v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3" role="list">
      <li
        v-for="(image, index) in sorted"
        :key="imageKey(image, index)"
        class="relative group rounded-2xl overflow-hidden border bg-slate-100"
        :class="image.is_primary ? 'border-[#0F172A] ring-2 ring-[#0F172A]/20' : 'border-slate-200'"
        data-testid="gallery-item"
      >
        <img
          :src="image.url"
          :alt="`Photo ${index + 1}`"
          class="w-full aspect-[4/3] object-cover"
          loading="lazy"
        />

        <span
          v-if="image.is_primary"
          class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-[#0F172A] text-white text-[10px] font-bold uppercase tracking-wider"
        >
          Principale
        </span>
        <span
          v-else
          class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-white/90 text-slate-700 text-[10px] font-bold"
        >
          {{ index + 1 }}
        </span>

        <div class="absolute inset-x-0 bottom-0 p-2 flex items-center justify-between gap-1 bg-gradient-to-t from-black/70 to-transparent">
          <button
            v-if="!image.is_primary"
            type="button"
            class="text-[11px] font-bold text-white hover:underline disabled:opacity-50"
            :disabled="busy"
            data-testid="gallery-primary"
            @click="emit('primary', image)"
          >
            Définir principale
          </button>
          <span v-else class="text-[11px] text-white/80">Affichée en premier</span>

          <div class="flex items-center gap-1">
            <button
              v-if="!image.is_primary && index > 1"
              type="button"
              class="w-6 h-6 rounded-md bg-white/20 text-white text-xs hover:bg-white/40 disabled:opacity-50"
              :disabled="busy"
              aria-label="Monter"
              @click="emit('move', { image, direction: -1 })"
            >
              ↑
            </button>
            <button
              v-if="!image.is_primary && index < sorted.length - 1"
              type="button"
              class="w-6 h-6 rounded-md bg-white/20 text-white text-xs hover:bg-white/40 disabled:opacity-50"
              :disabled="busy"
              aria-label="Descendre"
              @click="emit('move', { image, direction: 1 })"
            >
              ↓
            </button>
            <button
              type="button"
              class="w-6 h-6 rounded-md bg-rose-500/80 text-white text-xs hover:bg-rose-500 disabled:opacity-50"
              :disabled="busy"
              aria-label="Supprimer la photo"
              data-testid="gallery-remove"
              @click="emit('remove', image)"
            >
              ✕
            </button>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
