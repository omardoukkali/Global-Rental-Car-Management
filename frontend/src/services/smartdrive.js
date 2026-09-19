import axios from 'axios'

const aiApi = axios.create({
  baseURL: import.meta.env.VITE_SMARTDRIVE_AI_URL || 'http://localhost:5000',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

function extractCars(response) {
  if (Array.isArray(response?.results)) return response.results
  if (Array.isArray(response?.vehicles)) return response.vehicles
  if (Array.isArray(response?.cars)) return response.cars
  if (Array.isArray(response?.data?.vehicles)) return response.data.vehicles
  if (Array.isArray(response?.data?.cars)) return response.data.cars
  if (Array.isArray(response?.data)) return response.data
  return Array.isArray(response) ? response : []
}

export default {
  async getEligibleVehicles(preferences) {
    try {
      const response = await aiApi.post('/api/recommend', {
        budget_per_day: preferences.budget,
        start_at: preferences.startDate,
        end_at: preferences.endDate,
        city_id: preferences.cityId,
        passengers: preferences.passengers,
        vehicle_type: preferences.vehicleType || null,
        transmission: preferences.transmission || null,
        energy_type: preferences.energy || null,
      })

      return extractCars(response.data)
    } catch (error) {
      if (!error.response) {
        throw new Error('Le service SmartDrive AI est indisponible. Démarrez le service Python sur le port 5000.')
      }
      throw error
    }
  },
}
