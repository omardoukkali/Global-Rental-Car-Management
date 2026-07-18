<template>
  <AppLayout>
    <div class="owner-dashboard">
      <header class="dashboard-header">
        <div>
          <h1>Agency Fleet Roster</h1>
          <p class="text-muted">{{ agency?.name }} — {{ agency?.status }}</p>
        </div>
        <button @click="openAddModal" class="btn btn-accent">
          <PlusCircle :size="16" />
          Add Vehicle to Roster
        </button>
      </header>

      <div v-if="cars.data.length === 0" class="empty-state">
        <Car :size="48" class="empty-icon" />
        <p>Your fleet roster is currently empty.</p>
        <button @click="openAddModal" class="btn btn-accent mt-2">Add Your First Vehicle</button>
      </div>

      <div v-else class="fleet-ledger">
        <table class="ledger-table">
          <thead>
            <tr>
              <th>Vehicle</th>
              <th>Reference Plate</th>
              <th>Status</th>
              <th>Rate / Day</th>
              <th class="actions-cell">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="car in cars.data" :key="car.id">
              <td class="vehicle-cell">
                <div class="vehicle-thumbnail">
                  <img :src="primaryImage(car)" :alt="car.brand" />
                </div>
                <div class="vehicle-info">
                  <span class="brand-model">{{ car.brand }} {{ car.model }}</span>
                  <span class="year-type">{{ car.year }} • {{ car.type }}</span>
                </div>
              </td>
              <td class="plate-cell">{{ car.plate_number }}</td>
              <td class="status-cell">
                <span class="stamp-badge" :class="car.status">{{ car.status }}</span>
              </td>
              <td class="price-cell">{{ car.price_per_day }} MAD</td>
              <td class="actions-cell">
                <button @click="openEditModal(car)" class="btn-icon" title="Edit">
                  <Edit :size="16" />
                </button>
                <button @click="deleteCar(car)" class="btn-icon delete" :disabled="isProcessing(car.id)" title="Delete">
                  <Trash2 :size="16" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <Pagination :links="cars.links" class="mt-4" />

      <!-- Car Modal -->
      <Modal :show="showCarModal" @close="closeModal">
        <div class="car-modal">
          <h2>{{ isEditing ? 'Update Vehicle Log' : 'New Vehicle Entry' }}</h2>
          <form @submit.prevent="submitCar">
            <div class="form-grid">
              <!-- Basic Info -->
              <div class="form-group">
                <label>Brand</label>
                <input v-model="form.brand" type="text" required />
                <span class="error" v-if="form.errors.brand">{{ form.errors.brand }}</span>
              </div>
              <div class="form-group">
                <label>Model</label>
                <input v-model="form.model" type="text" required />
                <span class="error" v-if="form.errors.model">{{ form.errors.model }}</span>
              </div>
              <div class="form-group">
                <label>Year</label>
                <input v-model="form.year" type="number" required min="2000" />
                <span class="error" v-if="form.errors.year">{{ form.errors.year }}</span>
              </div>
              <div class="form-group">
                <label>Plate Number</label>
                <input v-model="form.plate_number" type="text" class="mono-input" required />
                <span class="error" v-if="form.errors.plate_number">{{ form.errors.plate_number }}</span>
              </div>

              <!-- Details -->
              <div class="form-group">
                <label>Class/Type</label>
                <input v-model="form.type" type="text" required placeholder="e.g. Sedan, SUV" />
                <span class="error" v-if="form.errors.type">{{ form.errors.type }}</span>
              </div>
              <div class="form-group">
                <label>Transmission</label>
                <select v-model="form.transmission" required>
                  <option value="automatic">Automatic</option>
                  <option value="manual">Manual</option>
                </select>
              </div>
              <div class="form-group">
                <label>Seats</label>
                <input v-model="form.seats" type="number" required min="2" max="9" />
              </div>
              <div class="form-group">
                <label>Color</label>
                <input v-model="form.color" type="text" required />
              </div>
              
              <!-- Location & Pricing -->
              <div class="form-group">
                <label>City</label>
                <select v-model="form.city_id" required>
                  <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Rate per day (MAD)</label>
                <input v-model="form.price_per_day" type="number" step="0.01" class="price-input" required />
                <span class="error" v-if="form.errors.price_per_day">{{ form.errors.price_per_day }}</span>
              </div>
            </div>

            <!-- Status (Edit only) -->
            <div class="form-group mt-3" v-if="isEditing">
              <label>Current Status</label>
              <select v-model="form.status" required>
                <option value="available">Available</option>
                <option value="rented">Rented</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <div class="form-group mt-3">
              <label>Log Notes / Description</label>
              <textarea v-model="form.description" rows="3"></textarea>
            </div>

            <!-- Image Management -->
            <div class="form-group mt-4 img-management-section">
              <label>Vehicle Photos</label>
              
              <div v-if="isEditing && editingCar.images.length" class="current-images-ledger mt-2">
                <div v-for="img in editingCar.images" :key="img.id" class="img-ledger-row">
                  <img :src="'/storage/' + img.url" class="thumb" />
                  <span class="mono-input text-xs">{{ img.is_primary ? 'PRIMARY' : 'SECONDARY' }}</span>
                  <button type="button" class="btn-remove-img" @click.prevent="deleteImage(img.id)" :disabled="isProcessingImg(img.id)">
                    <X :size="14" />
                  </button>
                </div>
              </div>

              <div class="upload-area mt-3">
                <label class="text-sm">Append new photos (JPG/PNG/WEBP, Max 2MB)</label>
                <input type="file" @change="handleFileUpload" multiple accept="image/jpeg,image/png,image/webp" />
                <span class="error" v-if="form.errors.images">{{ form.errors.images }}</span>
              </div>
            </div>

            <div class="modal-actions mt-4">
              <button type="button" @click="closeModal" class="btn btn-cancel">Cancel Entry</button>
              <button type="submit" class="btn btn-accent" :disabled="form.processing">
                {{ form.processing ? 'Committing...' : 'Commit to Ledger' }}
              </button>
            </div>
          </form>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { PlusCircle, Edit, Trash2, Car, X } from 'lucide-vue-next';

const props = defineProps({
  agency: Object,
  cars: Object,
  cities: Array,
});

const processingIds = ref(new Set());
const processingImgIds = ref(new Set());
const showCarModal = ref(false);
const isEditing = ref(false);
const editingCar = ref(null);

const form = useForm({
  brand: '',
  model: '',
  year: new Date().getFullYear(),
  color: '',
  plate_number: '',
  type: 'Sedan',
  transmission: 'automatic',
  seats: 5,
  price_per_day: 0,
  description: '',
  city_id: '',
  status: 'available',
  images: [], 
});

function isProcessing(id) {
  return processingIds.value.has(id);
}

function isProcessingImg(id) {
  return processingImgIds.value.has(id);
}

function primaryImage(car) {
  const primary = car.images?.find(img => img.is_primary);
  return primary ? `/storage/${primary.url}` : '/images/car-placeholder.jpg';
}

function openAddModal() {
  isEditing.value = false;
  editingCar.value = null;
  form.reset();
  form.clearErrors();
  if (props.cities.length > 0) form.city_id = props.cities[0].id;
  showCarModal.value = true;
}

function openEditModal(car) {
  isEditing.value = true;
  editingCar.value = car;
  form.reset();
  form.clearErrors();
  
  Object.keys(form.data()).forEach(key => {
    if (key !== 'images') {
      form[key] = car[key];
    }
  });
  form.images = [];
  
  showCarModal.value = true;
}

function closeModal() {
  showCarModal.value = false;
  form.reset();
}

function handleFileUpload(e) {
  form.images = Array.from(e.target.files);
}

function submitCar() {
  if (isEditing.value) {
    form.transform((data) => ({
      ...data,
      _method: 'PUT'
    })).post(`/owner/cars/${editingCar.value.id}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/owner/cars', {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
}

function deleteCar(car) {
  if (!confirm(`Permanently strike ${car.plate_number} from the ledger?`)) return;
  
  processingIds.value.add(car.id);
  router.delete(`/owner/cars/${car.id}`, {
    preserveScroll: true,
    onFinish: () => processingIds.value.delete(car.id),
  });
}

function deleteImage(imageId) {
  if (!confirm('Remove this photo from the vehicle log?')) return;
  processingImgIds.value.add(imageId);
  router.delete(`/owner/cars/${editingCar.value.id}/images/${imageId}`, {
    preserveScroll: true,
    onSuccess: () => {
      // Refresh local editingCar image array without a full modal remount
      editingCar.value.images = editingCar.value.images.filter(img => img.id !== imageId);
    },
    onFinish: () => processingImgIds.value.delete(imageId)
  });
}
</script>

<style scoped>

.owner-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 3rem 1.5rem;
  font-family: 'Inter', sans-serif;
  color: #1B2430;
  background-color: #FAF9F6;
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2.5rem;
  border-bottom: 1px solid #E3E1DB;
  padding-bottom: 1rem;
}

.dashboard-header h1 {
  font-family: 'Fraunces', serif;
  font-size: 2.25rem;
  font-weight: 400;
  margin: 0 0 0.5rem;
  color: #1B2430;
}

.text-muted { 
  color: #6B7280; 
  font-size: 0.95rem; 
  margin: 0; 
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 5rem 2rem;
  color: #6B7280;
  background: #FFFFFF;
  border-radius: 4px;
  border: 1px solid #E3E1DB;
}

.empty-icon { 
  opacity: 0.3; 
  margin-bottom: 1rem; 
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.6rem 1.25rem;
  border: 1px solid transparent;
  border-radius: 4px; /* less rounded for document feel */
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s, background-color 0.2s;
  letter-spacing: 0.02em;
}

.btn:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-accent { 
  background: #21606B; 
  color: #FFFFFF; 
}

.btn-accent:hover {
  background: #1a4d56;
}

.btn-cancel { 
  background: #FFFFFF; 
  color: #6B7280;
  border-color: #E3E1DB;
}

.btn-cancel:hover {
  background: #f4f4f5;
  color: #1B2430;
}

.btn-icon {
  background: none;
  border: none;
  color: #6B7280;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 4px;
}

.btn-icon:hover {
  background: #E3E1DB;
  color: #1B2430;
}

.btn-icon.delete:hover {
  color: #9B4A42;
  background: #fee2e2;
}

/* Ledger Layout */
.fleet-ledger {
  background: #FFFFFF;
  border: 1px solid #E3E1DB;
  border-radius: 4px;
  overflow-x: auto;
}

.ledger-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.ledger-table th {
  padding: 1rem 1.5rem;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6B7280;
  border-bottom: 1px solid #E3E1DB;
  font-weight: 600;
}

.ledger-table td {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #E3E1DB;
  vertical-align: middle;
}

.ledger-table tr:last-child td {
  border-bottom: none;
}

.vehicle-cell {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.vehicle-thumbnail {
  width: 60px;
  height: 40px;
  border-radius: 2px;
  overflow: hidden;
  border: 1px solid #E3E1DB;
  flex-shrink: 0;
}

.vehicle-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.vehicle-info {
  display: flex;
  flex-direction: column;
}

.brand-model {
  font-weight: 500;
  color: #1B2430;
  font-size: 0.95rem;
}

.year-type {
  font-size: 0.8rem;
  color: #6B7280;
  margin-top: 0.15rem;
}

.plate-cell {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 0.9rem;
  color: #1B2430;
}

.price-cell {
  font-family: 'Fraunces', serif;
  font-size: 1.1rem;
  color: #1B2430;
}

.actions-cell {
  text-align: right;
  white-space: nowrap;
}

/* Stamp Badges */
.stamp-badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  font-family: 'IBM Plex Mono', monospace;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border: 1.5px solid;
  border-radius: 2px;
  transform: rotate(-3deg);
  background: transparent;
}

.stamp-badge.available { color: #4C7A5D; border-color: #4C7A5D; }
.stamp-badge.maintenance { color: #9C6B1F; border-color: #9C6B1F; }
.stamp-badge.inactive { color: #9B4A42; border-color: #9B4A42; }
.stamp-badge.rented { color: #3F5770; border-color: #3F5770; }

/* Modal form */
.car-modal { 
  padding: 2rem; 
  max-width: 650px; 
  background: #FFFFFF;
}

.car-modal h2 { 
  font-family: 'Fraunces', serif;
  margin-top: 0; 
  margin-bottom: 2rem; 
  font-size: 1.5rem; 
  font-weight: 400;
  color: #1B2430;
  border-bottom: 1px solid #E3E1DB;
  padding-bottom: 0.75rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.form-group label {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  margin-bottom: 0.35rem;
  color: #6B7280;
}

.form-group input, 
.form-group select, 
.form-group textarea {
  width: 100%;
  padding: 0.6rem;
  border: 1px solid #E3E1DB;
  border-radius: 2px;
  font-family: 'Inter', sans-serif;
  font-size: 0.9rem;
  color: #1B2430;
  background: #FAF9F6;
}

.form-group input:focus, 
.form-group select:focus, 
.form-group textarea:focus {
  outline: none;
  border-color: #21606B;
}

.mono-input {
  font-family: 'IBM Plex Mono', monospace !important;
}

.price-input {
  font-family: 'Fraunces', serif !important;
}

.error { 
  color: #9B4A42; 
  font-size: 0.75rem; 
  display: block; 
  margin-top: 0.3rem; 
}

/* Image Management */
.img-management-section {
  border: 1px solid #E3E1DB;
  padding: 1rem;
  border-radius: 2px;
}

.current-images-ledger {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.img-ledger-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem;
  background: #FAF9F6;
  border: 1px solid #E3E1DB;
  border-radius: 2px;
}

.img-ledger-row .thumb {
  width: 48px;
  height: 32px;
  object-fit: cover;
  border-radius: 1px;
}

.btn-remove-img {
  background: none;
  border: none;
  color: #9B4A42;
  cursor: pointer;
  padding: 0.2rem;
  opacity: 0.7;
}

.btn-remove-img:hover {
  opacity: 1;
}

.btn-remove-img:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.upload-area {
  padding-top: 1rem;
  border-top: 1px dashed #E3E1DB;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2.5rem;
}

.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 1rem; }
.mt-4 { margin-top: 1.5rem; }
.text-xs { font-size: 0.7rem; color: #6B7280; }
.text-sm { font-size: 0.85rem; color: #6B7280; display: block; margin-bottom: 0.5rem; }
</style>
