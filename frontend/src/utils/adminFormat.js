export function formatCount(value) {
  return new Intl.NumberFormat('fr-FR').format(Number(value) || 0)
}

export function formatMoney(value) {
  return new Intl.NumberFormat('fr-FR').format(Math.round(Number(value) || 0))
}

export function formatKpiMoney(value) {
  const amount = Number(value) || 0
  if (amount >= 1000) {
    const thousands = amount / 1000
    const label = thousands >= 10
      ? Math.round(thousands).toString()
      : thousands.toFixed(1).replace(/\.0$/, '')
    return `${label}k`
  }
  return formatCount(Math.round(amount))
}

export function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('fr-FR')
}

function pill(label, className) {
  return { label, className }
}

export function reservationStatus(status) {
  const map = {
    pending: pill('Attente', 'pill-yellow'),
    confirmed: pill('Confirmé', 'pill-green'),
    picked_up: pill('En cours', 'pill-blue'),
    completed: pill('Terminé', 'pill-gray'),
    cancelled: pill('Annulé', 'pill-red'),
    disputed: pill('Signalé', 'pill-red'),
    rejected: pill('Rejeté', 'pill-red'),
  }
  return map[status] || pill(status || '—', 'pill-gray')
}

export function userRole(role) {
  const map = {
    admin: pill('Admin', 'pill-blue'),
    client: pill('Client', 'pill-gray'),
    agency: pill('Agence', 'pill-yellow'),
  }
  return map[role] || pill(role || '—', 'pill-gray')
}

export function userStatus(status) {
  const map = {
    active: pill('Actif', 'pill-green'),
    pending: pill('Attente', 'pill-yellow'),
    suspended: pill('Suspendu', 'pill-red'),
  }
  return map[status] || pill(status || '—', 'pill-gray')
}

export function agencyStatus(status) {
  const map = {
    approved: pill('Approuvée', 'pill-green'),
    pending: pill('En attente', 'pill-yellow'),
    rejected: pill('Rejetée', 'pill-red'),
  }
  return map[status] || pill(status || '—', 'pill-gray')
}

export function carStatus(status) {
  const map = {
    available: pill('Dispo', 'pill-green'),
    unavailable: pill('Indispo', 'pill-gray'),
    maintenance: pill('Maintenance', 'pill-yellow'),
  }
  return map[status] || pill(status || '—', 'pill-gray')
}

export function paymentStatus(status) {
  const map = {
    pending: pill('Attente', 'pill-yellow'),
    paid: pill('Payé', 'pill-green'),
    failed: pill('Échoué', 'pill-red'),
    refunded: pill('Remboursé', 'pill-blue'),
  }
  return map[status] || pill(status || '—', 'pill-gray')
}
