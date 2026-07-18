<template>
  <div class="fade-in">
    <div class="flex-between" style="margin-bottom:24px">
      <h1>{{ t('owner.title') }}</h1>
      <button class="btn btn-primary btn-sm" @click="openAddCar">{{ t('owner.addCar') }}</button>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button :class="['tab', { active: tab === 'cars' }]" @click="tab = 'cars'">{{ t('owner.myCars') }}</button>
      <button :class="['tab', { active: tab === 'reservations' }]" @click="tab = 'reservations'">{{ t('owner.reservations') }}</button>
    </div>

    <div v-if="loading" class="flex-center" style="padding:64px 0"><div class="spinner spinner-lg"></div></div>

    <!-- Cars Tab -->
    <div v-else-if="tab === 'cars'">
      <div v-if="!cars.length" class="empty-state glass-card">
        <p>{{ t('owner.noCars') }}</p>
        <button class="btn btn-primary btn-sm" style="margin-top:12px" @click="openAddCar">{{ t('owner.addFirst') }}</button>
      </div>

      <div v-else class="grid-3">
        <div v-for="c in cars" :key="c.id" class="glass-card car-card">
          <div class="car-img-wrap">
            <img :src="resolveUrl(c.images?.[0]?.url)" class="car-img" />
            <span class="badge" :class="`badge-${c.status}`" style="position:absolute;top:10px;inset-inline-end:10px;">{{ t(`status.${c.status}`, c.status) }}</span>
          </div>
          <div class="car-body">
            <h3>{{ c.brand }} {{ c.model }}</h3>
            <p style="color:var(--muted);font-size:0.85rem">{{ c.year }} • {{ c.plate_number }}</p>
            <p style="color:var(--muted);font-size:0.85rem">📍 {{ t('cities.' + c.city?.name, c.city?.name) }}</p>
            <div class="flex-between" style="margin-top:16px">
              <span style="color:var(--accent);font-weight:700"><span class="price-value">{{ Number(c.price_per_day).toFixed(0) }} MAD</span>/{{ t('car.perDay').replace('/ ', '') }}</span>
              <div style="display:flex;gap:8px">
                <button class="btn btn-secondary btn-sm" @click="openUpload(c)">{{ t('owner.images') }}</button>
                <button class="btn btn-danger btn-sm" @click="deleteCar(c.id)">{{ t('owner.delete') }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reservations Tab -->
    <div v-else-if="tab === 'reservations'">
      <div v-if="!reservations.length" class="empty-state glass-card">
        <p>{{ t('owner.noReservations') }}</p>
      </div>

      <div v-else class="res-list">
        <div v-for="r in reservations" :key="r.id" class="glass-card res-card">
          <div class="flex-between">
            <div>
              <h3>{{ r.car?.brand }} {{ r.car?.model }}</h3>
              <p style="color:var(--muted);font-size:0.88rem;margin-top:4px">{{ t('owner.client') }}: {{ r.client?.first_name }} {{ r.client?.last_name }}</p>
              <p style="color:var(--muted);font-size:0.88rem">📅 {{ formatDate(r.start_date) }} → {{ formatDate(r.end_date) }}</p>
              <p style="margin-top:8px;font-weight:700;color:var(--accent)"><span class="price-value">{{ Number(r.agency_earning || r.total_amount).toFixed(0) }} MAD</span> {{ t('owner.earnings') }}</p>
            </div>
            <div style="text-align:right">
              <span class="badge" :class="`badge-${r.status}`">{{ t(`status.${r.status}`, r.status) }}</span>
              <p style="color:var(--muted);font-size:0.8rem;margin-top:8px">Ref: {{ r.reference_number }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Car Modal -->
    <div v-if="carModal" class="modal-overlay" @click.self="carModal = false">
      <div class="glass-card modal-box">
        <h2 style="margin-bottom:20px">{{ t('owner.addCar') }}</h2>
        <form @submit.prevent="submitCar">
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">{{ t('owner.brand') }}</label>
              <input v-model="carForm.brand" type="text" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">{{ t('owner.model') }}</label>
              <input v-model="carForm.model" type="text" class="form-input" required />
            </div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">{{ t('owner.year') }}</label>
              <input v-model="carForm.year" type="number" class="form-input" min="2000" max="2026" required />
            </div>
            <div class="form-group">
              <label class="form-label">{{ t('owner.color') }}</label>
              <input v-model="carForm.color" type="text" class="form-input" required />
            </div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">{{ t('owner.type') }}</label>
              <select v-model="carForm.type" class="form-input" required>
                <option value="sedan">{{ t('filter.sedan') }}</option>
                <option value="suv">{{ t('filter.suv') }}</option>
                <option value="hatchback">{{ t('filter.hatchback') }}</option>
                <option value="coupe">{{ t('filter.coupe') }}</option>
                <option value="van">{{ t('filter.van') }}</option>
                <option value="truck">{{ t('filter.truck') }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">{{ t('owner.transmission') }}</label>
              <select v-model="carForm.transmission" class="form-input" required>
                <option value="automatic">{{ t('filter.automatic') }}</option>
                <option value="manual">{{ t('filter.manual') }}</option>
              </select>
            </div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">{{ t('owner.seats') }}</label>
              <input v-model="carForm.seats" type="number" class="form-input" min="2" max="12" required />
            </div>
            <div class="form-group">
              <label class="form-label">{{ t('owner.pricePerDay') }}</label>
              <input v-model="carForm.price_per_day" type="number" class="form-input" min="0" required />
            </div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">{{ t('owner.plateNumber') }}</label>
              <input v-model="carForm.plate_number" type="text" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">{{ t('owner.city') }}</label>
              <select v-model="carForm.city_id" class="form-input" required>
                <option v-for="c in cities" :key="c.id" :value="c.id">{{ t('cities.' + c.name, c.name) }}</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">{{ t('owner.description') }}</label>
            <textarea v-model="carForm.description" class="form-input" rows="2"></textarea>
          </div>

          <div v-if="carError" class="error-banner">{{ carError }}</div>

          <div style="display:flex;gap:12px;margin-top:24px">
            <button type="button" class="btn btn-secondary flex-1" @click="carModal = false">{{ t('owner.cancel') }}</button>
            <button type="submit" class="btn btn-primary flex-1" :disabled="submitting">
              <span v-if="submitting" class="spinner" style="width:16px;height:16px;border-width:2px"></span>
              <span v-else>{{ t('owner.saveCar') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Image Upload Modal -->
    <div v-if="uploadModal" class="modal-overlay" @click.self="uploadModal = null">
      <div class="glass-card modal-box">
        <h2 style="margin-bottom:20px">{{ t('owner.manageImages') }} {{ uploadModal.brand }}</h2>

        <div class="grid-3" style="margin-bottom:20px">
          <div v-for="img in uploadModal.images" :key="img.id" style="position:relative">
            <img :src="resolveUrl(img.url)" style="width:100%;height:100px;object-fit:cover;border-radius:var(--radius-sm)" />
            <button class="btn btn-danger btn-sm" style="position:absolute;top:4px;inset-inline-end:4px;padding:2px 6px" @click="deleteImage(uploadModal.id, img.id)">×</button>
            <span v-if="img.is_primary" class="badge badge-completed" style="position:absolute;bottom:4px;inset-inline-start:4px;font-size:0.7rem">{{ t('owner.primary') }}</span>
          </div>
        </div>

        <form @submit.prevent="submitUpload">
          <div class="form-group">
            <label class="form-label">{{ t('owner.uploadNew') }}</label>
            <input type="file" @change="handleFile" class="form-input" accept="image/*" required />
          </div>
          <div v-if="carError" class="error-banner">{{ carError }}</div>

          <div style="display:flex;gap:12px;margin-top:24px">
            <button type="button" class="btn btn-secondary flex-1" @click="uploadModal = null">{{ t('owner.close') }}</button>
            <button type="submit" class="btn btn-primary flex-1" :disabled="submitting || !uploadFile">
              <span v-if="submitting" class="spinner" style="width:16px;height:16px;border-width:2px"></span>
              <span v-else>{{ t('owner.uploadImage') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, inject, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

const { t } = useI18n()
const toast = inject('toast')

const tab = ref('cars')
const loading = ref(true)
const cars = ref([])
const reservations = ref([])
const cities = ref([])

const carModal = ref(false)
const submitting = ref(false)
const carError = ref('')
const carForm = ref({
  brand: '', model: '', year: 2023, color: '', type: 'sedan',
  transmission: 'automatic', seats: 5, price_per_day: 300,
  plate_number: '', city_id: '', description: ''
})

const uploadModal = ref(null)
const uploadFile = ref(null)

function resolveUrl(url) {
  if (!url) return 'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&q=80&w=800'
  return url.startsWith('http') || url.startsWith('/storage/') ? url : `/storage/${url}`
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' })
}

async function loadData() {
  loading.value = true
  try {
    if (tab.value === 'cars') {
      const res = await api.get('/agency/cars')
      cars.value = res.data ?? res
    } else {
      const res = await api.get('/agency/reservations')
      reservations.value = res.data ?? res
    }
  } catch (e) {
    toast(e.message, 'error')
  } finally {
    loading.value = false
  }
}

watch(tab, loadData)

async function loadCities() {
  try {
    const res = await api.get('/cities')
    cities.value = res.data ?? res
    if (cities.value.length) carForm.value.city_id = cities.value[0].id
  } catch (e) {}
}

function openAddCar() {
  carError.value = ''
  carModal.value = true
}

async function submitCar() {
  submitting.value = true
  carError.value = ''
  try {
    await api.post('/agency/cars', carForm.value)
    toast(t('owner.carAdded'), 'success')
    carModal.value = false
    loadData()
  } catch (e) {
    carError.value = e.message || t('owner.carAdded')
  } finally {
    submitting.value = false
  }
}

async function deleteCar(id) {
  if (!confirm(t('owner.confirmDeleteCar'))) return
  try {
    await api.delete(`/agency/cars/${id}`)
    toast(t('owner.carDeleted'), 'success')
    loadData()
  } catch (e) {
    toast(e.message, 'error')
  }
}

function openUpload(car) {
  uploadModal.value = car
  uploadFile.value = null
  carError.value = ''
}

function handleFile(e) {
  uploadFile.value = e.target.files[0]
}

async function submitUpload() {
  if (!uploadFile.value) return
  submitting.value = true
  carError.value = ''
  try {
    const fd = new FormData()
    fd.append('image', uploadFile.value)
    if (!uploadModal.value.images?.length) fd.append('is_primary', 1)

    await api.post(`/agency/cars/${uploadModal.value.id}/images`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast(t('owner.imageUploaded'), 'success')
    uploadFile.value = null
    await loadData()
    uploadModal.value = cars.value.find(c => c.id === uploadModal.value.id) || null
  } catch (e) {
    carError.value = e.message || 'Upload failed'
  } finally {
    submitting.value = false
  }
}

async function deleteImage(carId, imageId) {
  const car = cars.value.find(c => c.id === carId)
  if (car && car.images && car.images.length <= 1) {
    toast(t('owner.cannotDeleteLast'), 'error')
    return
  }
  if (!confirm(t('owner.confirmDeleteImage'))) return
  try {
    await api.delete(`/agency/cars/${carId}/images/${imageId}`)
    toast(t('owner.imageDeleted'), 'success')
    await loadData()
    uploadModal.value = cars.value.find(c => c.id === carId) || null
  } catch (e) {
    toast(e.message, 'error')
  }
}

onMounted(() => {
  loadData()
  loadCities()
})
</script>

<style scoped>
.tabs { display: flex; border-bottom: 1px solid var(--line); margin-bottom: 24px; gap: 16px; }
.tab { padding: 10px 4px; font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; color: var(--muted); background: none; border: none; cursor: pointer; border-bottom: 2px solid transparent; transition: all var(--transition-fast); }
.tab.active { color: var(--accent); border-bottom-color: var(--accent); }

.grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
.car-card { display: flex; flex-direction: column; overflow: hidden; }
.car-img-wrap { position: relative; height: 160px; background: linear-gradient(135deg, var(--bg) 0%, var(--line) 100%); border-bottom: 1px solid var(--line); }
.car-img { width: 100%; height: 100%; object-fit: cover; }
.car-body { padding: 16px; display: flex; flex-direction: column; flex: 1; }

.res-list { display: flex; flex-direction: column; gap: 16px; }
.res-card { padding: 20px; }

.empty-state { text-align: center; padding: 64px 32px; color: var(--muted); }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
.modal-box { max-width: 600px; width: 100%; padding: 32px; max-height: 90vh; overflow-y: auto; }

.error-banner { background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.88rem; margin-top: 16px; }
</style>
