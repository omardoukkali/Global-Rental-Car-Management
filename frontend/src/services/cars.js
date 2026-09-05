import api from '@/services/api'

export default {
  getCars() {
    return api.get('/agency/cars')
  },

  getCar(id) {
    return api.get(`/agency/cars/${id}`)
  },

  createCar(payload) {
    return api.post('/agency/cars', payload)
  },

  updateCar(id, payload) {
    return api.put(`/agency/cars/${id}`, payload)
  },

  disableCar(id) {
    return api.patch(`/agency/cars/${id}/disable`)
  }
}