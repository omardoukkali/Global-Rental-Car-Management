<template>
  <div class="admin-shell">
    <header class="header">
      <div class="header-inner">
        <div style="display:flex; align-items:center; gap: 16px;">
          <RouterLink to="/" class="gr-logo">
            <span class="gr-logo-dot"></span>
            GlobalRental
          </RouterLink>
          <span class="sb-badge admin-badge">Admin</span>
        </div>

        <button type="button" class="user-pill" aria-label="Menu utilisateur">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
          <span class="user-avatar">AD</span>
        </button>
      </div>
    </header>

    <div class="layout">
      <aside class="sidebar">
        <div class="sb-profile">
          <div class="sb-avatar">AD</div>
          <div>
            <div class="sb-name">Admin GlobalRental</div>
            <div class="sb-verified">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              </svg>
              Super Admin
            </div>
          </div>
        </div>

        <nav class="sb-nav" aria-label="Navigation admin">
          <a href="#" class="sb-link active">
            <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Tableau de bord
          </a>
          <RouterLink to="/admin/agencies/validation" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="3" width="20" height="18" rx="2"/><line x1="8" y1="21" x2="8" y2="3"/><line x1="16" y1="21" x2="16" y2="3"/><line x1="2" y1="9" x2="22" y2="9"/><line x1="2" y1="15" x2="22" y2="15"/></svg>
            Agences
            <span class="sb-badge orange">{{ pendingAgencies.length }}</span>
          </RouterLink>
          <a href="#" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Utilisateurs
          </a>
          <a href="#" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><path d="M9 17H5m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/><path d="M13 17h5m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/></svg>
            Véhicules
          </a>
          <a href="#" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            Réservations
          </a>
          <a href="#" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Revenus
          </a>
          <a href="#" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Avis & Signalements
          </a>

          <div class="sb-separator"></div>

          <a href="#" class="sb-link">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Paramètres
          </a>

          <div class="sb-separator"></div>

          <button type="button" class="sb-link sb-logout">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Se déconnecter
          </button>
        </nav>
      </aside>

      <main class="main">
        <div class="anim d1">
          <h1 class="page-greeting">Tableau de bord Admin</h1>
          <p class="page-sub">Vue globale des performances de GlobalRental Maroc.</p>
        </div>

        <div class="stats-grid anim d2">
          <div class="stat-card">
            <div class="stat-label">Utilisateurs</div>
            <div class="stat-value">3 842</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Agences actives</div>
            <div class="stat-value">47</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Voitures</div>
            <div class="stat-value">312</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Réserv. mois</div>
            <div class="stat-value">289</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Revenu (8%)</div>
            <div class="stat-value">184k</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Note plateforme</div>
            <div class="stat-value">4.9 ★</div>
          </div>
        </div>

        <div class="section-title anim d3">
          <h2>
            Agences en attente de vérification
            <span class="sb-badge orange pending-badge">{{ pendingAgencies.length }}</span>
          </h2>
        </div>

        <div class="req-grid anim d3">
          <div v-for="agency in pendingAgencies" :key="agency.id" class="req-card">
            <div class="req-head">
              <div>
                <div class="req-name">{{ agency.name }}</div>
                <div class="req-car">{{ agency.city }} • {{ agency.manager }}</div>
              </div>
              <div class="req-rc">{{ agency.reference }}</div>
            </div>

            <div class="req-box">
              <div v-for="check in agency.checks" :key="check.label" class="req-row">
                <span style="color:var(--ink-muted);">{{ check.label }}</span>
                <span :style="check.valid ? 'color:#047857; font-weight:700;' : 'color:#B91C1C; font-weight:700;'">{{ check.valid ? '✓' : '—' }}</span>
              </div>
            </div>

            <div class="req-actions">
              <button type="button" class="btn btn-primary" @click="approveAgency(agency.id)">✓ Valider</button>
              <button type="button" class="btn btn-outline" @click="rejectAgency(agency.id)">✗ Rejeter</button>
            </div>
          </div>
        </div>

        <div class="grid-content">
          <div class="anim d4">
            <div class="section-title">
              <h2>Dernières réservations</h2>
              <a href="#" class="table-link">Voir tout →</a>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Agence</th>
                    <th>Montant</th>
                    <th>Com. (8%)</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="mono">#R-9042</td>
                    <td class="bold">Youssef T.</td>
                    <td>Luxury Auto</td>
                    <td class="bold">6 000</td>
                    <td class="positive">+480</td>
                    <td><span class="pill pill-green">Confirmé</span></td>
                  </tr>
                  <tr>
                    <td class="mono">#R-9041</td>
                    <td class="bold">Sara L.</td>
                    <td>Marrakech Rent</td>
                    <td class="bold">800</td>
                    <td class="positive">+64</td>
                    <td><span class="pill pill-green">Confirmé</span></td>
                  </tr>
                  <tr>
                    <td class="mono">#R-9040</td>
                    <td class="bold">Amine K.</td>
                    <td>Agadir Voyages</td>
                    <td class="bold">2 100</td>
                    <td class="positive">+168</td>
                    <td><span class="pill pill-blue">En cours</span></td>
                  </tr>
                  <tr>
                    <td class="mono">#R-9039</td>
                    <td class="bold">Hassan M.</td>
                    <td>Rabat Auto</td>
                    <td class="bold">1 500</td>
                    <td class="positive">+120</td>
                    <td><span class="pill pill-yellow">Attente</span></td>
                  </tr>
                  <tr>
                    <td class="mono">#R-9038</td>
                    <td class="bold">Karima B.</td>
                    <td>Casa Cars</td>
                    <td class="bold">3 500</td>
                    <td class="positive">+280</td>
                    <td><span class="pill pill-gray">Terminé</span></td>
                  </tr>
                  <tr>
                    <td class="mono">#R-9036</td>
                    <td class="bold">Nabil R.</td>
                    <td>Premium Rent</td>
                    <td class="bold">1 400</td>
                    <td class="strike">0</td>
                    <td><span class="pill pill-red">Annulé</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="anim d5">
            <div class="section-title"><h2>Revenus plateforme</h2></div>
            <div class="chart-card">
              <div class="chart-flex">
                <div v-for="month in revenueChart" :key="month.label" class="chart-col">
                  <div class="bar-bg">
                    <div :class="['bar', { active: month.active } ]" :style="{ height: month.height }"></div>
                  </div>
                  <div :class="['chart-lbl', { active: month.active }]">{{ month.label }}</div>
                </div>
              </div>
            </div>

            <div class="section-title" style="margin-top:32px;"><h2>Signalements récents</h2></div>

            <div class="report-item">
              <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                <span class="pill pill-red">En cours</span>
                <span style="font-size:0.7rem; color:var(--ink-muted);">Il y a 2h</span>
              </div>
              <h4>Voiture non conforme aux photos</h4>
              <p>Tesla Model 3 • Signalé par Mohammed A.</p>
              <button class="btn btn-primary report-btn">Examiner</button>
            </div>

            <div class="report-item">
              <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                <span class="pill pill-green">Résolu</span>
                <span style="font-size:0.7rem; color:var(--ink-muted);">Hier</span>
              </div>
              <h4>Retard de restitution</h4>
              <p>Dacia Logan II • Signalé par Leila B.</p>
              <button class="btn btn-outline report-btn">Voir historique</button>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const pendingAgencies = ref([
  {
    id: 1,
    name: 'Atlas Cars Casablanca',
    city: 'Casablanca',
    manager: 'Yassine B.',
    reference: 'AG-2041',
    checks: [
      { label: 'SIRET', valid: true },
      { label: 'Pièces jointes', valid: true },
      { label: 'Adresse', valid: false },
      { label: 'Contrat', valid: true },
    ],
  },
  {
    id: 2,
    name: 'Riviera Rent',
    city: 'Marrakech',
    manager: 'Samira D.',
    reference: 'AG-2042',
    checks: [
      { label: 'SIRET', valid: true },
      { label: 'Pièces jointes', valid: false },
      { label: 'Adresse', valid: true },
      { label: 'Contrat', valid: true },
    ],
  },
  {
    id: 3,
    name: 'Desert Moto',
    city: 'Agadir',
    manager: 'Omar T.',
    reference: 'AG-2043',
    checks: [
      { label: 'SIRET', valid: false },
      { label: 'Pièces jointes', valid: true },
      { label: 'Adresse', valid: true },
      { label: 'Contrat', valid: false },
    ],
  },
])

const revenueChart = [
  { label: 'Jan', height: '35%', active: false },
  { label: 'Fev', height: '50%', active: false },
  { label: 'Mar', height: '68%', active: true },
  { label: 'Avr', height: '58%', active: false },
  { label: 'Mai', height: '80%', active: true },
  { label: 'Jui', height: '72%', active: false },
]

function approveAgency(id) {
  pendingAgencies.value = pendingAgencies.value.filter((agency) => agency.id !== id)
}

function rejectAgency(id) {
  pendingAgencies.value = pendingAgencies.value.filter((agency) => agency.id !== id)
}
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

.admin-shell {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--ink);
  min-height: 100vh;
  overflow-x: hidden;
}

.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  height: var(--header-h);
  background: rgba(255,255,255,0.94);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
}

.header-inner {
  width: 100%;
  max-width: 1500px;
  margin: 0 auto;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.gr-logo {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-weight: 800;
  font-size: 1.35rem;
  letter-spacing: -0.03em;
  color: var(--ink);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.gr-logo-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #888;
  display: inline-block;
}

.admin-badge {
  background: var(--ink);
  color: #fff;
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
  flex-shrink: 0;
}

.layout {
  display: flex;
  align-items: stretch;
  min-height: calc(100vh - var(--header-h));
  padding-top: var(--header-h);
}

.sidebar {
  position: sticky;
  top: var(--header-h);
  left: 0;
  width: var(--sidebar-w);
  min-width: var(--sidebar-w);
  height: calc(100vh - var(--header-h));
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  padding: 28px 16px;
  overflow-y: auto;
  z-index: 10;
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
  color: #D97706;
  background: #FEF3C7;
  padding: 3px 8px;
  border-radius: 999px;
}

.sb-verified svg {
  width: 12px;
  height: 12px;
  stroke: currentColor;
  fill: none;
  stroke-width: 2.5;
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

.sb-link.active svg {
  stroke: #fff;
}

.sb-link svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  fill: none;
  stroke-width: 2;
  flex-shrink: 0;
}

.sb-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 800;
  background: var(--border);
  color: var(--ink);
}

.sb-badge.orange {
  background: #FFEDD5;
  color: #C2410C;
}

.pending-badge {
  margin-left: 8px;
  vertical-align: middle;
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

.main {
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

.section-title h2 {
  display: flex;
  align-items: center;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 36px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius);
  padding: 20px 20px 16px;
  box-shadow: var(--shadow-sm);
}

.stat-label {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--ink-muted);
  margin-bottom: 6px;
}

.stat-value {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: var(--ink);
  line-height: 1;
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
  border-top: 4px solid #F59E0B;
}

.req-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.req-name {
  font-weight: 800;
  font-size: 1rem;
  margin-bottom: 2px;
}

.req-car {
  font-size: 0.75rem;
  color: var(--ink-muted);
  font-weight: 500;
}

.req-rc {
  font-family: monospace;
  font-size: 0.7rem;
  background: var(--bg);
  border: 1px solid var(--border);
  padding: 2px 6px;
  border-radius: 4px;
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
  transition: all .15s;
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

.grid-content {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 32px;
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

.table-link {
  font-size: 0.75rem;
  color: var(--ink);
  text-decoration: none;
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
  font-size: 0.8rem;
  color: var(--ink);
  vertical-align: middle;
}

.mono {
  font-family: monospace;
  color: var(--ink-muted);
}

.bold {
  font-weight: 700;
}

.positive {
  color: #047857;
  font-weight: 700;
}

.strike {
  color: var(--ink-muted);
  text-decoration: line-through;
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

.pill-green { background: #ECFDF5; color: #047857; }
.pill-blue { background: #E0E7FF; color: #3730A3; }
.pill-yellow { background: #FEFCE8; color: #CA8A04; }
.pill-gray { background: var(--border-soft); color: var(--ink-secondary); }
.pill-red { background: #FEF2F2; color: #B91C1C; }

.chart-card {
  background: var(--surface);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius);
  padding: 24px;
  margin-bottom: 24px;
}

.chart-flex {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 140px;
  padding-top: 10px;
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

.bar-bg {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.bar {
  width: 24px;
  background: var(--border);
  border-radius: 4px 4px 0 0;
  transition: background .2s;
}

.bar.active { background: var(--ink); }

.chart-lbl {
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--ink-muted);
}

.chart-lbl.active { color: var(--ink); }

.report-item {
  padding: 14px;
  border: 1px solid var(--border-soft);
  border-radius: 12px;
  margin-bottom: 12px;
  background: var(--surface);
}

.report-item h4 {
  font-size: 0.85rem;
  font-weight: 800;
  margin-bottom: 4px;
}

.report-item p {
  font-size: 0.75rem;
  color: var(--ink-muted);
  margin-bottom: 12px;
}

.report-btn {
  width: 100%;
  padding: 6px;
}

.anim {
  opacity: 0;
  animation: fadeUp 0.55s cubic-bezier(0.16,1,0.3,1) forwards;
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

@media (max-width: 1280px) {
  .stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .req-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .grid-content { grid-template-columns: 1fr; }
}

@media (max-width: 980px) {
  .layout { display: block; }

  .sidebar {
    position: static;
    width: 100%;
    min-width: 100%;
    height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border);
  }

  .main {
    width: 100%;
    padding: 32px 20px 50px;
  }

  .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .req-grid { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
  .header-inner { padding: 0 16px; }
  .sidebar { padding: 20px 12px; }
  .main { padding: 24px 16px 40px; }
  .stats-grid { grid-template-columns: 1fr; }
  .req-head { flex-direction: column; align-items: flex-start; }
  .req-actions { flex-direction: column; }
}
</style>
