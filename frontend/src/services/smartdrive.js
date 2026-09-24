import api from '@/services/api'

const AI_SERVICE_URL = (import.meta.env.VITE_SMARTDRIVE_AI_URL || 'http://localhost:5000').replace(/\/$/, '')

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
    const payload = {
      budget_per_day: preferences.budget,
      start_at: preferences.startDate,
      end_at: preferences.endDate,
      city_id: preferences.cityId,
      ...(preferences.cityName ? { city_name: preferences.cityName } : {}),
      passengers: preferences.passengers,
      vehicle_type: preferences.vehicleType || null,
      transmission: preferences.transmission || null,
      energy_type: preferences.energy || null,
    }

    try {
      const response = await api.post('/smartdrive/recommend', payload)
      const cars = extractCars(response)

      if (cars.length) return cars
      return await getAiFallback(payload)
    } catch (error) {
      try {
        return await getAiFallback(payload)
      } catch {
        const serviceError = new Error(error?.message || 'SmartDrive est momentanément indisponible. Réessayez dans quelques instants.')
        serviceError.status = error?.status
        throw serviceError
      }
    }
  },
}

async function getAiFallback(payload) {
  const response = await fetch(`${AI_SERVICE_URL}/api/recommend`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload),
  })

  if (!response.ok) {
    const error = new Error('Le service SmartDrive est indisponible.')
    error.status = response.status
    throw error
    }

  return extractCars(await response.json())
}
