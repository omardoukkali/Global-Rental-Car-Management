import api from '@/services/api'

export default {
  createReservation(payload) {
    return api.post('/reservations', payload)
  },

  getReservations() {
    return api.get('/reservations')
  },

  getReservation(id) {
    return api.get(`/reservations/${id}`)
  },

  checkAvailability(carId, params) {
    return api.get(`/cars/${carId}/availability`, { params })
  },

  cancelReservation(id) {
    return api.patch(`/reservations/${id}/cancel`)
  }
}
