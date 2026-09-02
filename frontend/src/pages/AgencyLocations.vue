<template>
  <div class="agency-shell">
    <header class="agency-header">
      <div class="agency-header-inner">
        <RouterLink to="/" class="gr-logo">
          <span class="gr-logo-dot"></span>
          GlobalRental
        </RouterLink>

        <button type="button" class="user-pill" aria-label="Menu utilisateur">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
          <span class="user-avatar">AC</span>
        </button>
      </div>
    </header>

    <div class="agency-layout">
      <aside class="agency-sidebar">
        <div class="sb-profile">
          <div class="sb-avatar">AC</div>
          <div>
            <div class="sb-name">Atlas Cars</div>
            <div class="sb-verified">✓ Agence Certifiée</div>
          </div>
          <div class="sb-city">📍 Casablanca</div>
        </div>

        <nav class="sb-nav" aria-label="Navigation agence">
          <RouterLink to="/agency/locations" class="sb-link active">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Tableau de bord
          </RouterLink>
          <RouterLink to="/agency/profile" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><path d="M9 17H5m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/><path d="M13 17h5m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/></svg>
            Ma flotte
            <span class="sb-badge">8</span>
          </RouterLink>
          <RouterLink to="/agency/locations" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            Demandes
            <span class="sb-badge red">3</span>
          </RouterLink>
          <button type="button" class="sb-link" aria-label="Calendrier">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Calendrier
          </button>
          <button type="button" class="sb-link" aria-label="Revenus">
            <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Revenus
          </button>
          <button type="button" class="sb-link" aria-label="Avis reçus">
            <svg viewBox="0 0 24 24" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Avis reçus
            <span class="sb-badge">34</span>
          </button>

          <div class="sb-separator"></div>

          <RouterLink to="/agency/profile" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Mon agence
          </RouterLink>

          <RouterLink to="/agency/settings" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Paramètres
          </RouterLink>

          <div class="sb-separator"></div>

          <button type="button" class="sb-link sb-logout">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Se déconnecter
          </button>
        </nav>
      </aside>

      <main class="agency-main">
        <div class="anim d1">
          <h1 class="page-greeting">Bonjour, Atlas Cars</h1>
          <p class="page-sub">Voici les performances de votre flotte ce mois-ci.</p>
        </div>

        <div class="stats-grid anim d2">
          <div v-for="stat in stats" :key="stat.label" class="stat-card">
            <div class="stat-label">{{ stat.label }}</div>
            <div class="stat-value">{{ stat.value }}</div>
            <div v-if="stat.trend" class="stat-badge">{{ stat.trend }}</div>
            <div v-else class="stat-meta">{{ stat.meta }}</div>
          </div>
        </div>

        <div class="section-title anim d3">
          <h2>Demandes en attente</h2>
          <a href="#" class="section-link">Voir tout →</a>
        </div>

        <div class="req-grid anim d3">
          <div v-for="request in requests" :key="request.id" class="req-card">
            <div class="req-head">
              <div class="req-avatar" :style="request.avatarStyle">{{ request.initials }}</div>
              <div>
                <div class="req-name">
                  {{ request.name }}
                  <span class="req-verified">✓</span>
                </div>
                <div class="req-car">{{ request.car }}</div>
              </div>
            </div>

            <div class="req-box">
              <div v-for="detail in request.details" :key="detail.label" class="req-row">
                <span class="req-label">{{ detail.label }}</span>
                <span>{{ detail.value }}</span>
              </div>
            </div>

            <div class="req-actions">
              <button type="button" class="btn btn-primary">✓ Accepter</button>
              <button type="button" class="btn btn-outline">✗ Refuser</button>
            </div>
          </div>
        </div>

        <div class="section-title anim d4">
          <h2>Ma flotte</h2>
          <button type="button" class="btn btn-primary inline-btn">+ Ajouter</button>
        </div>

        <div class="table-wrap anim d4">
          <table>
            <thead>
              <tr>
                <th style="width: 50px;">Photo</th>
                <th>Véhicule</th>
                <th>Statut</th>
                <th>Voyages</th>
                <th>Note</th>
                <th>Prix/jour</th>
                <th style="text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="vehicle in fleet" :key="vehicle.name">
                <td><div class="table-thumb"></div></td>
                <td class="vehicle-name">{{ vehicle.name }}</td>
                <td><span :class="['pill', vehicle.statusClass]">{{ vehicle.status }}</span></td>
                <td class="muted-value">{{ vehicle.trips }}</td>
                <td class="vehicle-name">{{ vehicle.rating }} ★</td>
                <td class="vehicle-name">{{ vehicle.price }}</td>
                <td style="text-align: right;">
                  <a href="#" class="action-link">Modifier</a>
                  <a href="#" class="action-link muted">Désactiver</a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="section-title anim d5">
          <h2>Revenus</h2>
        </div>

        <div class="chart-card anim d5">
          <div class="chart-flex">
            <div v-for="month in revenue" :key="month.label" class="chart-col">
              <div :class="['chart-val', { active: month.active }]">{{ month.value }}</div>
              <div class="bar-bg">
                <div :class="['bar', { active: month.active } ]" :style="{ height: month.height }"></div>
              </div>
              <div class="chart-lbl">{{ month.label }}</div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'

const stats = [
  { label: 'Revenus du mois', value: '14 520', trend: '↑ +12%' },
  { label: 'Voyages complétés', value: '8', meta: 'Ce mois' },
  { label: 'Taux d\'occupation', value: '73%' },
  { label: 'Note moyenne', value: '5.0 ★' },
]

const requests = [
  {
    id: 1,
    initials: 'ME',
    avatarStyle: 'background:#E0E7FF; color:#3730A3;',
    name: 'Mohammed El Fassi',
    car: 'Dacia Logan II',
    details: [
      { label: 'Dates', value: '15-20 Août 2026' },
      { label: 'Durée', value: '5 jours' },
      { label: 'Total', value: '1 350 MAD' },
    ],
  },
  {
    id: 2,
    initials: 'SL',
    avatarStyle: 'background:#F3E8FF; color:#7E22CE;',
    name: 'Sophie Laurent',
    car: 'Range Rover Sport',
    details: [
      { label: 'Dates', value: '18-22 Août 2026' },
      { label: 'Durée', value: '4 jours' },
      { label: 'Total', value: '5 200 MAD' },
    ],
  },
  {
    id: 3,
    initials: 'AR',
    avatarStyle: 'background:#DCFCE7; color:#15803D;',
    name: 'Ahmed Rahimi',
    car: 'Tesla Model 3',
    details: [
      { label: 'Dates', value: '20-23 Août 2026' },
      { label: 'Durée', value: '3 jours' },
      { label: 'Total', value: '2 750 MAD' },
    ],
  },
]

const fleet = [
  { name: 'Mercedes C-Class', status: 'Disponible', statusClass: 'pill-green', trips: 42, rating: 4.9, price: '820 MAD/jour' },
  { name: 'BMW X5', status: 'Réservé', statusClass: 'pill-yellow', trips: 18, rating: 4.8, price: '1 250 MAD/jour' },
  { name: 'Volkswagen Tiguan', status: 'Disponible', statusClass: 'pill-green', trips: 27, rating: 4.7, price: '690 MAD/jour' },
  { name: 'Tesla Model 3', status: 'Maintenance', statusClass: 'pill-red', trips: 9, rating: 4.9, price: '980 MAD/jour' },
]

const revenue = [
  { label: 'Jan', value: '8k', height: '45%', active: false },
  { label: 'Fev', value: '11k', height: '60%', active: false },
  { label: 'Mar', value: '14k', height: '70%', active: true },
  { label: 'Avr', value: '10k', height: '52%', active: false },
  { label: 'Mai', value: '16k', height: '82%', active: true },
  { label: 'Jui', value: '15k', height: '76%', active: false },
]
</script>

<style scoped>
:root {
  --bg: #FAFAF9;
  --surface: #FFFFFF;
  --ink: #0A0A0B;
  --ink-secondary: #3D3D3F;
  --ink-muted: #7A7A7D;
  --border: #E8E8EA;
  --border-soft: #F2F2F3;
  --accent: #1A1A1C;
  --accent-subtle: #F2F2F3;
  --sidebar-w: 260px;
  --header-h: 72px;
  --radius: 18px;
  --shadow-sm: 0 1px 4px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
}

* { box-sizing: border-box; }

.agency-shell {
  min-height: 100vh;
  background: var(--bg);
  color: var(--ink);
  font-family: 'DM Sans', sans-serif;
}

.agency-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 20;
  height: var(--header-h);
  background: rgba(255,255,255,0.94);
  border-bottom: 1px solid var(--border);
  backdrop-filter: blur(20px);
}

.agency-header-inner {
  width: 100%;
  max-width: 1500px;
  margin: 0 auto;
  height: 100%;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.gr-logo {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--ink);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.gr-logo-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--ink);
  display: inline-block;
}

.user-pill {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 0 6px 0 16px;
  height: 44px;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: var(--surface);
  cursor: pointer;
  text-decoration: none;
}

.user-pill svg {
  width: 15px;
  height: 15px;
  stroke: #555;
  fill: none;
  stroke-width: 2.5;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--ink);
  color: #fff;
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 0.72rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

.agency-layout {
  display: flex;
  align-items: stretch;
  min-height: calc(100vh - var(--header-h));
  padding-top: var(--header-h);
}

.agency-sidebar {
  position: sticky;
  top: var(--header-h);
  width: var(--sidebar-w);
  min-width: var(--sidebar-w);
  height: calc(100vh - var(--header-h));
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  padding: 28px 16px;
  overflow-y: auto;
}

.sb-profile {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
  padding: 20px 16px;
  border-radius: var(--radius);
  background: var(--bg);
  border: 1px solid var(--border-soft);
  margin-bottom: 28px;
}

.sb-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--ink);
  color: #fff;
  font-family: 'Bricolage Grotesque', sans-serif;
  font-weight: 800;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sb-name {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--ink);
}

.sb-verified {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.65rem;
  font-weight: 700;
  color: #047857;
  background: #ECFDF5;
  padding: 3px 8px;
  border-radius: 999px;
}

.sb-city {
  font-size: 0.7rem;
  color: var(--ink-muted);
  font-weight: 600;
  margin-top: 2px;
}

.sb-nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}

.sb-link {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 11px 14px;
  border-radius: 12px;
  font-size: 0.83rem;
  font-weight: 600;
  color: var(--ink-secondary);
  text-decoration: none;
  cursor: pointer;
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
}

.sb-link:hover {
  background: var(--border-soft);
  color: var(--ink);
}

.sb-link.active {
  background: var(--ink);
  color: #fff;
}

.sb-link svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  fill: none;
  stroke-width: 2;
  flex-shrink: 0;
}

.sb-link.active svg {
  stroke: #fff;
}

.sb-badge {
  margin-left: auto;
  background: var(--border);
  color: var(--ink);
  font-size: 0.65rem;
  font-weight: 800;
  padding: 2px 7px;
  border-radius: 999px;
}

.sb-badge.red {
  background: #FEE2E2;
  color: #B91C1C;
}

.sb-separator {
  height: 1px;
  background: var(--border-soft);
  margin: 12px 0;
}

.sb-logout {
  color: var(--ink-muted) !important;
}

.sb-logout:hover {
  background: #FEF2F2 !important;
  color: #B91C1C !important;
}

.agency-main {
  flex: 1;
  min-width: 0;
  padding: 40px 44px 60px;
  box-sizing: border-box;
}

.page-greeting {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--ink);
  margin-bottom: 4px;
}

.page-sub {
  font-size: 0.88rem;
  color: var(--ink-muted);
  margin-bottom: 36px;
}

.section-title {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--ink);
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section-link {
  text-decoration: none;
  color: var(--ink);
  font-size: 0.75rem;
  font-weight: 700;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 36px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius);
  padding: 24px 24px 20px;
  box-shadow: var(--shadow-sm);
}

.stat-label {
  font-size: 0.73rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--ink-muted);
  margin-bottom: 6px;
}

.stat-value {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: var(--ink);
  line-height: 1;
  margin-bottom: 8px;
}

.stat-badge {
  display: inline-flex;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  background: #ECFDF5;
  color: #047857;
}

.stat-meta {
  font-size: 0.72rem;
  color: var(--ink-muted);
}

.req-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 36px;
}

.req-card {
  background: var(--surface);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius);
  padding: 20px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.req-head {
  display: flex;
  align-items: center;
  gap: 10px;
}

.req-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  font-weight: 800;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.req-name {
  font-weight: 700;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 4px;
}

.req-verified {
  color: #047857;
}

.req-car {
  font-size: 0.75rem;
  color: var(--ink-muted);
}

.req-box {
  background: var(--bg);
  border-radius: 12px;
  padding: 12px;
  font-size: 0.75rem;
  border: 1px solid var(--border-soft);
}

.req-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.req-row:last-child {
  margin-bottom: 0;
  padding-top: 8px;
  border-top: 1px solid var(--border-soft);
  font-weight: 700;
  font-size: 0.85rem;
}

.req-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.btn {
  flex: 1;
  padding: 9px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
}

.btn-primary {
  background: var(--ink);
  color: #fff;
}

.btn-outline {
  background: transparent;
  border: 1.5px solid var(--border);
  color: var(--ink);
}

.inline-btn {
  min-width: 110px;
  max-width: 110px;
}

.table-wrap {
  background: var(--surface);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  margin-bottom: 36px;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead th {
  text-align: left;
  padding: 14px 20px;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--ink-muted);
  background: var(--bg);
  border-bottom: 1px solid var(--border-soft);
}

tbody td {
  padding: 14px 20px;
  border-bottom: 1px solid var(--border-soft);
  font-size: 0.83rem;
  color: var(--ink);
  vertical-align: middle;
}

tbody tr:last-child td {
  border-bottom: none;
}

.table-thumb {
  width: 34px;
  height: 24px;
  border-radius: 8px;
  background: linear-gradient(135deg, #E5E7EB 0%, #D1D5DB 100%);
}

.pill {
  display: inline-flex;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.pill-green {
  background: #ECFDF5;
  color: #047857;
}

.pill-yellow {
  background: #FEFCE8;
  color: #CA8A04;
}

.pill-red {
  background: #FEF2F2;
  color: #B91C1C;
}

.action-link {
  font-size: 0.75rem;
  font-weight: 700;
  color: #2563EB;
  text-decoration: none;
  margin-right: 12px;
}

.action-link.muted {
  color: var(--ink-muted);
}

.chart-card {
  background: var(--surface);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius);
  padding: 24px;
}

.chart-flex {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 160px;
  padding-top: 20px;
}

.chart-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  height: 100%;
}

.chart-val {
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--ink-muted);
}

.chart-val.active {
  color: var(--ink);
}

.bar-bg {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.bar {
  width: 32px;
  background: var(--border);
  border-radius: 6px 6px 0 0;
}

.bar.active {
  background: var(--ink);
}

.chart-lbl {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--ink);
}

.anim {
  opacity: 0;
  animation: fadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.d1 { animation-delay: .06s; }
.d2 { animation-delay: .14s; }
.d3 { animation-delay: .22s; }
.d4 { animation-delay: .30s; }
.d5 { animation-delay: .38s; }

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 1180px) {
  .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .req-grid { grid-template-columns: 1fr; }
}

@media (max-width: 900px) {
  .agency-layout { display: block; }

  .agency-sidebar {
    position: static;
    width: 100%;
    min-width: 100%;
    height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border);
  }

  .agency-main {
    padding: 26px 16px 48px;
  }
}

@media (max-width: 640px) {
  .agency-header-inner { padding: 0 16px; }
  .stats-grid { grid-template-columns: 1fr; }
  .req-actions { flex-direction: column; }
  .section-title { align-items: flex-start; }
}
</style>
