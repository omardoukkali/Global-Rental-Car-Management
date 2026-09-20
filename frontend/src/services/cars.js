import api from '@/services/api'

export default {
  /** Public catalog (guests and clients). `params` = search filters of GET /cars */
  getPublicCars(params = {}) {
    return api.get('/cars', { params })
  },

  getPublicCar(id) {
    return api.get(`/cars/${id}`)
  },

  getCarReviews(id) {
    return api.get(`/cars/${id}/reviews`)
  },

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
  },

  /** Photo gallery of an agency car */
  getCarImages(carId) {
    return api.get(`/agency/cars/${carId}/images`)
  },

  addCarImage(carId, payload) {
    return api.post(`/agency/cars/${carId}/images`, payload)
  },

  setPrimaryCarImage(carId, imageId) {
    return api.patch(`/agency/cars/${carId}/images/${imageId}/primary`)
  },

  deleteCarImage(carId, imageId) {
    return api.delete(`/agency/cars/${carId}/images/${imageId}`)
  },
}
