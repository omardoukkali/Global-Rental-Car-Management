import api from '@/services/api'

function extractCars(response) {
  if (Array.isArray(response?.vehicles)) return response.vehicles
  if (Array.isArray(response?.cars)) return response.cars
  if (Array.isArray(response?.data?.vehicles)) return response.data.vehicles
  if (Array.isArray(response?.data?.cars)) return response.data.cars
  if (Array.isArray(response?.data)) return response.data
  return Array.isArray(response) ? response : []
}

export default {
  async getEligibleVehicles(preferences) {
    const response = await api.post('/smartdrive/eligible-vehicles', {
      budget_per_day: preferences.budget,
      start_at: preferences.startDate,
      end_at: preferences.endDate,
      city_id: preferences.cityId,
      passengers: preferences.passengers,
      vehicle_type: preferences.vehicleType || null,
      transmission: preferences.transmission || null,
      energy_type: preferences.energy || null,
    })

    return extractCars(response)
  },
}
