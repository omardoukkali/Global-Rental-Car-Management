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
    initials: 'YA',
    avatarStyle: 'background:#FFEDD5; color:#C2410C;',
    name: 'Youssef Amrani',
    car: 'Renault Clio 5',
    details: [
      { label: 'Dates', value: '25-28 Août 2026' },
      { label: 'Durée', value: '3 jours' },
      { label: 'Total', value: '900 MAD' },
    ],
  },
]

const fleet = [
  { name: 'Dacia Logan II', status: 'Disponible', statusClass: 'pill-green', trips: '42', rating: '5.0', price: '270 MAD' },
  { name: 'Renault Clio 5', status: 'Disponible', statusClass: 'pill-green', trips: '28', rating: '4.9', price: '300 MAD' },
  { name: 'Peugeot 208', status: 'En location', statusClass: 'pill-red', trips: '19', rating: '4.8', price: '280 MAD' },
  { name: 'Range Rover Evoque', status: 'Disponible', statusClass: 'pill-green', trips: '56', rating: '5.0', price: '1200 MAD' },
  { name: 'BMW Série 3', status: 'Maintenance', statusClass: 'pill-yellow', trips: '15', rating: '4.7', price: '750 MAD' },
]

const revenue = [
  { label: 'Mar', value: '8.4k', height: '58%' },
  { label: 'Avr', value: '10.2k', height: '70%' },
  { label: 'Mai', value: '11.8k', height: '81%' },
  { label: 'Juin', value: '13.2k', height: '91%' },
  { label: 'Juil', value: '12.9k', height: '89%' },
  { label: 'Août', value: '14.5k', height: '100%', active: true },
]
</script>
