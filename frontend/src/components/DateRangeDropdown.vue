<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  startDate: { type: String, default: '' },
  endDate: { type: String, default: '' },
  startTime: { type: String, default: '10:00' },
  endTime: { type: String, default: '10:00' },
})
const emit = defineEmits(['update:startDate', 'update:endDate', 'update:startTime', 'update:endTime'])

const MONTHS = [
  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
]
const WEEK = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const open = ref(false)
const picking = ref('start')
const cursor = ref(startOfMonth(new Date()))
const root = ref(null)
const panel = ref(null)
const panelStyle = ref({})

const today = ymd(new Date())

function pad(n) {
  return String(n).padStart(2, '0')
}
function ymd(date) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}
function startOfMonth(date) {
  return new Date(date.getFullYear(), date.getMonth(), 1)
}
function addMonths(date, n) {
  return new Date(date.getFullYear(), date.getMonth() + n, 1)
}
function parseYmd(value) {
  if (!value) return null
  const [y, m, d] = value.split('-').map(Number)
  return new Date(y, m - 1, d)
}
function hourOf(value) {
  return String(value || '10:00').slice(0, 2)
}
function minuteOf(value) {
  return String(value || '10:00').slice(3, 5)
}
function setTime(which, hour, minute) {
  const h = String(Math.min(23, Math.max(0, Number(hour) || 0))).padStart(2, '0')
  const m = String(Math.min(59, Math.max(0, Number(minute) || 0))).padStart(2, '0')
  emit(which === 'start' ? 'update:startTime' : 'update:endTime', `${h}:${m}`)
}

function formatLong(value) {
  const date = parseYmd(value)
  if (!date) return ''
  return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })
}

const nextMonth = computed(() => addMonths(cursor.value, 1))

function monthTitle(date) {
  return `${MONTHS[date.getMonth()]} ${date.getFullYear()}`
}

function cellsFor(date) {
  const year = date.getFullYear()
  const month = date.getMonth()
  const firstDow = (new Date(year, month, 1).getDay() + 6) % 7
  const count = new Date(year, month + 1, 0).getDate()
  const cells = []
  for (let i = 0; i < firstDow; i += 1) cells.push(null)
  for (let day = 1; day <= count; day += 1) {
    cells.push(ymd(new Date(year, month, day)))
  }
  return cells
}

function isDisabled(value) {
  return !value || value < today
}
function isStart(value) {
  return Boolean(value && value === props.startDate)
}
function isEnd(value) {
  return Boolean(value && value === props.endDate)
}
function inRange(value) {
  return Boolean(value && props.startDate && props.endDate && value > props.startDate && value < props.endDate)
}

function pickDay(value) {
  if (isDisabled(value)) return
  if (picking.value === 'start' || !props.startDate || value < props.startDate) {
    emit('update:startDate', value)
    if (props.endDate && value > props.endDate) emit('update:endDate', '')
    picking.value = 'end'
    return
  }
  emit('update:endDate', value)
  picking.value = 'start'
}

function placePanel() {
  if (!root.value) return
  const box = root.value.getBoundingClientRect()
  const width = Math.min(720, window.innerWidth - 24)
  let left = box.left
  if (left + width > window.innerWidth - 12) left = window.innerWidth - width - 12
  panelStyle.value = {
    position: 'fixed',
    top: `${Math.round(box.bottom + 10)}px`,
    left: `${Math.max(12, Math.round(left))}px`,
    width: `${width}px`,
    zIndex: 300,
  }
}

async function openPanel(which) {
  picking.value = which
  open.value = true
  const anchor = parseYmd(which === 'end' ? props.endDate || props.startDate : props.startDate)
  if (anchor) cursor.value = startOfMonth(anchor)
  await nextTick()
  placePanel()
}

function close() {
  open.value = false
}

function onDocClick(event) {
  if (!open.value) return
  if (root.value?.contains(event.target)) return
  if (panel.value?.contains(event.target)) return
  close()
}

function onKey(event) {
  if (event.key === 'Escape') close()
}

onMounted(() => {
  document.addEventListener('mousedown', onDocClick)
  document.addEventListener('keydown', onKey)
  window.addEventListener('resize', placePanel)
  window.addEventListener('scroll', placePanel, true)
})
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocClick)
  document.removeEventListener('keydown', onKey)
  window.removeEventListener('resize', placePanel)
  window.removeEventListener('scroll', placePanel, true)
})
</script>

<template>
  <div ref="root" class="date-range" data-testid="date-range">
    <div class="triggers">
      <button
        type="button"
        class="trigger"
        :class="{ open: open && picking === 'start' }"
        data-testid="search-start"
        @click="openPanel('start')"
      >
        <span class="search-label">Départ</span>
        <span :class="startDate ? 'value' : 'placeholder'">
          {{ startDate ? formatLong(startDate) : 'Ajouter une date' }}
        </span>
      </button>

      <button
        type="button"
        class="trigger"
        :class="{ open: open && picking === 'end' }"
        data-testid="search-end"
        @click="openPanel('end')"
      >
        <span class="search-label">Retour</span>
        <span :class="endDate ? 'value' : 'placeholder'">
          {{ endDate ? formatLong(endDate) : 'Ajouter une date' }}
        </span>
      </button>
    </div>

    <Teleport to="body">
      <div
        v-if="open"
        class="date-backdrop"
        data-testid="date-backdrop"
        @click="close"
      ></div>
      <div
        v-if="open"
        ref="panel"
        class="panel"
        :style="panelStyle"
        data-testid="date-panel"
      >
        <div class="times">
          <div class="time-field">
            <span>Heure de départ</span>
            <div class="time-inputs">
              <input
                type="number"
                min="0"
                max="23"
                inputmode="numeric"
                :value="hourOf(startTime)"
                aria-label="Heure de départ"
                @change="setTime('start', $event.target.value, minuteOf(startTime))"
              />
              <span>:</span>
              <input
                type="number"
                min="0"
                max="59"
                inputmode="numeric"
                :value="minuteOf(startTime)"
                aria-label="Minutes de départ"
                @change="setTime('start', hourOf(startTime), $event.target.value)"
              />
            </div>
          </div>
          <div class="time-field">
            <span>Heure de retour</span>
            <div class="time-inputs">
              <input
                type="number"
                min="0"
                max="23"
                inputmode="numeric"
                :value="hourOf(endTime)"
                aria-label="Heure de retour"
                @change="setTime('end', $event.target.value, minuteOf(endTime))"
              />
              <span>:</span>
              <input
                type="number"
                min="0"
                max="59"
                inputmode="numeric"
                :value="minuteOf(endTime)"
                aria-label="Minutes de retour"
                @change="setTime('end', hourOf(endTime), $event.target.value)"
              />
            </div>
          </div>
        </div>

        <div class="panel-head">
          <p class="hint">
            {{ picking === 'start' ? 'Choisissez la date de départ' : 'Choisissez la date de retour' }}
          </p>
          <div class="nav">
            <button type="button" class="nav-btn" aria-label="Mois précédent" @click="cursor = addMonths(cursor, -1)">‹</button>
            <button type="button" class="nav-btn" aria-label="Mois suivant" @click="cursor = addMonths(cursor, 1)">›</button>
          </div>
        </div>

        <div class="months">
          <div v-for="(monthDate, idx) in [cursor, nextMonth]" :key="idx" class="month">
            <p class="month-title">{{ monthTitle(monthDate) }}</p>
            <div class="week">
              <span v-for="(d, i) in WEEK" :key="i">{{ d }}</span>
            </div>
            <div class="days">
              <button
                v-for="(day, i) in cellsFor(monthDate)"
                :key="i"
                type="button"
                class="day"
                :class="{
                  empty: !day,
                  disabled: Boolean(day && isDisabled(day)),
                  start: isStart(day),
                  end: isEnd(day),
                  range: inRange(day),
                  today: day === today,
                }"
                :disabled="!day || isDisabled(day)"
                @click="day && pickDay(day)"
              >
                <span v-if="day">{{ Number(day.slice(8)) }}</span>
              </button>
            </div>
          </div>
        </div>

        <div class="panel-foot">
          <button type="button" class="link" @click="emit('update:startDate', ''); emit('update:endDate', '')">
            Effacer
          </button>
          <button type="button" class="done" @click="close">Valider</button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.date-range { position: relative; width: 100%; min-width: 0; }
.triggers {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px;
}
.trigger {
  display: block;
  width: 100%;
  text-align: left;
  padding: 10px 16px;
  border-radius: 14px;
  border: 0;
  background: transparent;
  cursor: pointer;
  min-width: 0;
}
.trigger:hover,
.trigger.open { background: #F8FAFC; }
.search-label {
  display: block;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #94A3B8;
  margin-bottom: 4px;
}
.value {
  display: block;
  font-size: 15px;
  font-weight: 600;
  color: #0F172A;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.placeholder {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: #94A3B8;
}
</style>

<style>
.date-backdrop {
  position: fixed;
  inset: 0;
  z-index: 290;
  background: rgba(15, 23, 42, 0.18);
}
.panel[data-testid="date-panel"] {
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 20px;
  box-shadow: 0 22px 60px rgba(15, 23, 42, 0.18);
  padding: 18px 20px 16px;
  color: #0F172A;
  z-index: 300;
}
.panel[data-testid="date-panel"] .times {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 14px;
}
.panel[data-testid="date-panel"] .time-field span {
  display: block;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #94A3B8;
  margin-bottom: 6px;
}
.panel[data-testid="date-panel"] .time-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
}
.panel[data-testid="date-panel"] .time-inputs span {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #0F172A;
  letter-spacing: 0;
  text-transform: none;
}
.panel[data-testid="date-panel"] .time-inputs input {
  width: 70px;
  height: 40px;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 0 8px;
  font-size: 16px;
  font-weight: 700;
  background: #fff;
  color: #0F172A;
  text-align: center;
  appearance: textfield;
}
.panel[data-testid="date-panel"] .time-inputs input::-webkit-outer-spin-button,
.panel[data-testid="date-panel"] .time-inputs input::-webkit-inner-spin-button {
  appearance: none;
  margin: 0;
}
.panel[data-testid="date-panel"] .panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.panel[data-testid="date-panel"] .hint {
  font-size: 14px;
  font-weight: 600;
  color: #475569;
}
.panel[data-testid="date-panel"] .nav { display: flex; gap: 8px; }
.panel[data-testid="date-panel"] .nav-btn {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 1px solid #E2E8F0;
  background: #fff;
  font-size: 20px;
  line-height: 1;
  color: #0F172A;
  cursor: pointer;
}
.panel[data-testid="date-panel"] .months {
  display: grid;
  grid-template-columns: 1fr;
  gap: 28px;
}
@media (min-width: 700px) {
  .panel[data-testid="date-panel"] .months { grid-template-columns: 1fr 1fr; }
}
.panel[data-testid="date-panel"] .month-title {
  font-size: 16px;
  font-weight: 800;
  text-align: center;
  margin: 0 0 12px;
}
.panel[data-testid="date-panel"] .week,
.panel[data-testid="date-panel"] .days {
  display: grid;
  grid-template-columns: repeat(7, minmax(36px, 1fr));
  gap: 2px;
}
.panel[data-testid="date-panel"] .week span {
  font-size: 11px;
  font-weight: 700;
  color: #94A3B8;
  text-align: center;
  padding: 4px 0 8px;
}
.panel[data-testid="date-panel"] .day {
  min-height: 40px;
  border: 0;
  background: transparent;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 600;
  color: #0F172A;
  cursor: pointer;
}
.panel[data-testid="date-panel"] .day.empty {
  visibility: hidden;
  pointer-events: none;
}
.panel[data-testid="date-panel"] .day.disabled {
  color: #CBD5E1;
  cursor: default;
}
.panel[data-testid="date-panel"] .day.today {
  box-shadow: inset 0 0 0 1px #94A3B8;
}
.panel[data-testid="date-panel"] .day.range {
  background: #F1F5F9;
  border-radius: 0;
}
.panel[data-testid="date-panel"] .day.start,
.panel[data-testid="date-panel"] .day.end {
  background: #0F172A;
  color: #fff;
}
.panel[data-testid="date-panel"] .day:not(.disabled):not(.empty):hover {
  background: #E2E8F0;
}
.panel[data-testid="date-panel"] .day.start:hover,
.panel[data-testid="date-panel"] .day.end:hover {
  background: #0F172A;
  color: #fff;
}
.panel[data-testid="date-panel"] .panel-foot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid #F1F5F9;
}
.panel[data-testid="date-panel"] .link {
  border: 0;
  background: none;
  font-size: 13px;
  font-weight: 600;
  color: #64748B;
  cursor: pointer;
  text-decoration: underline;
}
.panel[data-testid="date-panel"] .done {
  height: 38px;
  padding: 0 18px;
  border: 0;
  border-radius: 999px;
  background: #0F172A;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}
</style>
