import api from '@/services/api'

function extractCars(response) {
  if (Array.isArray(response?.cars)) return response.cars
  if (Array.isArray(response?.data?.cars)) return response.data.cars
  if (Array.isArray(response?.data)) return response.data
  return Array.isArray(response) ? response : []
}

export default {
  async getRecommendations(preferences) {
    const response = await api.get('/cars', {
      params: {
        budget: preferences.budget,
        start_date: preferences.startDate,
        end_date: preferences.endDate,
        city: preferences.city,
        passengers: preferences.passengers,
        vehicle_type: preferences.vehicleType,
        transmission: preferences.transmission,
        energy: preferences.energy,
      },
    })

    return extractCars(response)
  },
}
