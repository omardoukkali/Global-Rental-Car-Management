const API_ROOT = window.SMARTDRIVE_API_ROOT || ''
const root = document.querySelector('#smartdrive-root')

const state = {
  open: false,
  step: 'form',
  cities: [],
  loadingCities: false,
  loading: false,
  progress: 0,
  results: null,
  preferences: {
    cityId: '', startDate: '', endDate: '', budget: 450, passengers: 2,
    type: '', transmission: '', energy: ''
  }
}

const fallbackCars = [
  { id: 'sandero', brand: 'Dacia', model: 'Sandero', type: 'Citadine', seats: 5, transmission: 'Manuelle', energy_type: 'Essence', daily_price: 260, rating: 4.7, economy: '5.4 L/100 km', image: 'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&w=900&q=85' },
  { id: 'clio', brand: 'Renault', model: 'Clio V', type: 'Citadine', seats: 5, transmission: 'Automatique', energy_type: 'Essence', daily_price: 340, rating: 4.9, economy: '5.8 L/100 km', image: 'https://images.unsplash.com/photo-1502877338535-766e1452684a?auto=format&fit=crop&w=900&q=85' },
  { id: 'duster', brand: 'Dacia', model: 'Duster', type: 'SUV', seats: 5, transmission: 'Manuelle', energy_type: 'Diesel', daily_price: 480, rating: 4.8, economy: '6.2 L/100 km', image: 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=900&q=85' },
  { id: '208', brand: 'Peugeot', model: '208', type: 'Citadine', seats: 5, transmission: 'Automatique', energy_type: 'Electrique', daily_price: 390, rating: 4.8, economy: '16 kWh/100 km', image: 'https://images.unsplash.com/photo-1504215680853-026ed2a45def?auto=format&fit=crop&w=900&q=85' }
]

function esc(value) { return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[char]) }
function money(value) { return Number(value || 0).toLocaleString('fr-MA') }
function title(car) { return `${car.brand || ''} ${car.model || ''}`.trim() }
function normalized(value) { return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '') }

function score(car) {
  const p = state.preferences
  let total = 54
  if (p.type && normalized(car.type) === normalized(p.type)) total += 16
  if (p.transmission && normalized(car.transmission) === normalized(p.transmission)) total += 13
  if (p.energy && normalized(car.energy_type) === normalized(p.energy)) total += 10
  if (Number(car.seats || 0) >= Number(p.passengers)) total += 10
  if (Number(car.daily_price || 0) <= Number(p.budget)) total += 10
  else total -= Math.min(18, Math.ceil((Number(car.daily_price) - Number(p.budget)) / 50))
  return Math.max(1, Math.min(99, total))
}

function render() {
  root.innerHTML = `<button class="fab ${state.open ? 'fab-hidden' : ''}" data-action="open" aria-label="Ouvrir SmartDrive AI">
    <span class="fab-orbit" aria-hidden="true"></span><span class="fab-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 16h14l-1.1-5.2a2 2 0 0 0-2-1.6H8.1a2 2 0 0 0-2 1.6L5 16Z"/><path d="M4 16v2.5M20 16v2.5M7 16h.01M17 16h.01M7 9.2 8.2 7h7.6L17 9.2"/></svg><b>✦</b></span><span class="fab-label">M'aider à choisir</span><span class="online-dot"></span>
  </button>`
  if (state.open) {
    root.insertAdjacentHTML('beforeend', `<button class="backdrop" data-action="close" aria-label="Fermer SmartDrive"></button>${panel()}`)
  }
  bind()
}

function panel() {
  return `<section class="panel" role="dialog" aria-modal="true" aria-labelledby="smartdrive-title">
    <div class="panel-line"></div><header class="panel-head"><span class="ai-badge">✦</span><div><small>GLOBALRENTAL AI</small><h2 id="smartdrive-title">SmartDrive</h2><em><i></i> Assistant disponible</em></div><button class="close" data-action="close" aria-label="Fermer">×</button></header>
    <div class="panel-body">${state.step === 'form' ? form() : state.step === 'loading' ? loading() : results()}</div>
  </section>`
}

function form() {
  const p = state.preferences
  const chips = (key, items) => items.map(([value, label]) => `<button type="button" class="chip ${p[key] === value ? 'active' : ''}" data-chip="${key}" data-value="${value}">${label}</button>`).join('')
  return `<div class="intro"><span class="soft-tag">✦ Recommandations personnalisées</span><h3>Trouvez la voiture qui vous ressemble.</h3><p>Répondez à quelques questions et nous trouverons votre meilleur match.</p></div>
  <form id="preferences-form"><div class="field wide"><label for="city">Ville de départ</label><select id="city" name="city" required ${state.loadingCities || !state.cities.length ? 'disabled' : ''}><option value="">${state.loadingCities ? 'Chargement des villes…' : 'Choisir une ville'}</option>${state.cities.map(city => `<option value="${esc(city.id)}" ${p.cityId === city.id ? 'selected' : ''}>${esc(city.name)}</option>`).join('')}</select>${!state.loadingCities && !state.cities.length ? '<small class="field-error">Les villes sont indisponibles pour le moment.</small>' : ''}</div>
  <div class="field"><label for="startDate">Départ</label><input id="startDate" type="date" value="${p.startDate}" required></div><div class="field"><label for="endDate">Retour</label><input id="endDate" type="date" value="${p.endDate}" required></div>
  <div class="control-block wide"><div class="control-label"><label for="budget">Budget quotidien</label><strong><output id="budget-output">${money(p.budget)} MAD</output><span>/ jour</span></strong></div><input id="budget" type="range" min="150" max="1500" step="25" value="${p.budget}"><div class="range-labels"><span>150 MAD</span><span>1 500 MAD</span></div></div>
  <div class="control-block wide"><div class="control-label"><label>Voyageurs</label><span class="stepper"><button type="button" data-passenger="-1" aria-label="Retirer un voyageur">−</button><b>${p.passengers}</b><button type="button" data-passenger="1" aria-label="Ajouter un voyageur">+</button></span></div></div>
  <div class="control-block wide"><label>Type de véhicule</label><div class="chips">${chips('type', [['hatchback', 'Citadine'], ['sedan', 'Berline'], ['suv', 'SUV'], ['van', 'Utilitaire']])}</div></div><div class="control-block wide"><label>Boîte</label><div class="chips">${chips('transmission', [['automatic', 'Automatique'], ['manual', 'Manuelle']])}</div></div><div class="control-block wide"><label>Énergie</label><div class="chips">${chips('energy', [['gasoline', 'Essence'], ['diesel', 'Diesel'], ['hybrid', 'Hybride'], ['electric', 'Électrique']])}</div></div>
  <button class="primary wide" type="submit">Voir mes recommandations <span>→</span></button></form>`
}

function loading() {
  const items = ['Comparaison des besoins', 'Calcul des scores de compatibilité', 'Classement des véhicules éligibles']
  return `<div class="loading"><div class="scanner"><div class="scanner-ring"></div><span>🚙</span></div><h3>SmartDrive analyse votre trajet</h3><p>Nous comparons les véhicules selon vos priorités.</p><ol>${items.map((item, index) => `<li class="${state.progress > index ? 'done' : state.progress === index ? 'current' : ''}"><i>${state.progress > index ? '✓' : index + 1}</i>${item}<b>${state.progress > index ? 'Terminé' : state.progress === index ? 'En cours' : ''}</b></li>`).join('')}</ol></div>`
}

function results() {
  const [best, ...alternatives] = state.results || []
  if (!best) return `<div class="empty"><h3>Aucun véhicule disponible pour ces critères.</h3><p>Ajustez vos critères pour élargir la recherche.</p><button class="secondary" data-action="form">Ajuster mes critères</button></div>`
  const feature = (icon, value, label) => `<div class="spec"><span>${icon}</span><strong>${esc(value)}</strong><small>${label}</small></div>`
  return `<button class="back-link" data-action="form">← Ajuster mes critères</button><div class="result-heading"><div><span class="soft-tag">Votre recommandation</span><h3>Voici votre meilleur match.</h3><p>Un choix calculé à partir de vos préférences.</p></div><div class="score-ring" style="--score:${best.score * 3.6}deg"><svg viewBox="0 0 42 42"><circle cx="21" cy="21" r="16"/><circle class="ring-value" cx="21" cy="21" r="16"/></svg><strong>${best.score}%</strong><small>match</small></div></div>
  <article class="hero-car"><div class="hero-image"><img src="${esc(best.image || '')}" alt="${esc(title(best))}" onerror="this.style.display='none'"><span class="image-fallback">🚙</span><b>Meilleur choix</b></div><div class="hero-details"><h4>${esc(title(best))}</h4><p>${esc(best.type || 'Véhicule')} · ${esc(best.daily_price)} MAD / jour</p><div class="spec-grid">${feature('⌂', `${best.seats || '—'}`, 'places')}${feature('⇄', best.transmission || '—', 'boîte')}${feature('◌', best.economy || '—', 'efficacité')}${feature('★', Number(best.rating || 0).toFixed(1), 'avis')}</div><h5>Pourquoi ce véhicule ?</h5><ul class="reasons"><li>Respecte au mieux votre budget quotidien</li><li>Adapté à votre nombre de voyageurs</li><li>Correspond à vos préférences de conduite</li></ul><button class="primary" data-action="choose" data-id="${esc(best.id)}">Choisir ce véhicule <span>→</span></button></div></article>
  ${alternatives.length ? `<div class="alternatives"><div class="section-title"><h4>Autres options pour vous</h4><span>${alternatives.length} alternatives</span></div>${alternatives.map((car, index) => `<article class="alternative"><div class="alt-thumb"><img src="${esc(car.image || '')}" alt="" onerror="this.style.display='none'"><span>🚙</span></div><div><h5>${esc(title(car))}</h5><p>${esc(car.type || 'Véhicule')} · ${money(car.daily_price)} MAD / jour</p><div class="score-bar"><i style="width:${car.score}%"></i></div></div><strong>${car.score}%<small>${index === 0 ? 'Bon rapport qualité-prix' : 'Alternative fiable'}</small></strong></article>`).join('')}</div>` : ''}`
}

function bind() {
  root.querySelectorAll('[data-action="open"]').forEach(button => button.addEventListener('click', async () => { state.open = true; state.step = 'form'; render(); await loadCities() }))
  root.querySelectorAll('[data-action="close"]').forEach(button => button.addEventListener('click', () => { state.open = false; render() }))
  root.querySelectorAll('[data-action="form"]').forEach(button => button.addEventListener('click', () => { state.step = 'form'; render() }))
  const formElement = root.querySelector('#preferences-form')
  if (formElement) {
    formElement.addEventListener('submit', submitPreferences)
    root.querySelector('#city').addEventListener('change', event => { state.preferences.cityId = event.target.value })
    root.querySelector('#startDate').addEventListener('change', event => { state.preferences.startDate = event.target.value })
    root.querySelector('#endDate').addEventListener('change', event => { state.preferences.endDate = event.target.value })
    const range = root.querySelector('#budget'); range.addEventListener('input', event => { state.preferences.budget = Number(event.target.value); root.querySelector('#budget-output').textContent = `${money(state.preferences.budget)} MAD` })
    root.querySelectorAll('[data-passenger]').forEach(button => button.addEventListener('click', () => { state.preferences.passengers = Math.max(1, Math.min(9, state.preferences.passengers + Number(button.dataset.passenger))); render() }))
    root.querySelectorAll('[data-chip]').forEach(button => button.addEventListener('click', () => { const key = button.dataset.chip; state.preferences[key] = state.preferences[key] === button.dataset.value ? '' : button.dataset.value; render() }))
  }
  root.querySelector('[data-action="choose"]')?.addEventListener('click', event => { window.location.href = `/reservations/new?car_id=${encodeURIComponent(event.currentTarget.dataset.id)}` })
}

async function loadCities() {
  if (state.cities.length || state.loadingCities) return
  state.loadingCities = true; render()
  try { const response = await fetch(`${API_ROOT}/cities`); const data = await response.json(); state.cities = (data.cities || data.data || data).filter(city => city?.id && city?.name) }
  catch { state.cities = [{ id: 'casablanca', name: 'Casablanca' }, { id: 'marrakech', name: 'Marrakech' }, { id: 'Rabat', name: 'Rabat' }, { id: 'tanger', name: 'Tanger' }, { id: 'agadir', name: 'Agadir' }] }
  finally { state.loadingCities = false; render() }
}

async function submitPreferences(event) {
  event.preventDefault(); state.step = 'loading'; state.loading = true; state.progress = 0; render()
  const progressTimer = window.setInterval(() => { state.progress = Math.min(3, state.progress + 1); render(); if (state.progress === 3) window.clearInterval(progressTimer) }, 500)
  try {
    const response = await fetch(`${API_ROOT}/api/recommend`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ budget_per_day: state.preferences.budget, start_at: state.preferences.startDate, end_at: state.preferences.endDate, city_id: state.preferences.cityId, passengers: state.preferences.passengers, vehicle_type: state.preferences.type || null, transmission: state.preferences.transmission || null, energy_type: state.preferences.energy || null }) })
    if (!response.ok) throw new Error('fallback')
    const data = await response.json(); state.results = (data.results || [data.recommended]).map(car => ({ ...car, score: car.score || 80 }))
  } catch { state.results = [] }
  window.setTimeout(() => { state.loading = false; state.step = 'results'; render() }, 1650)
}

render()
