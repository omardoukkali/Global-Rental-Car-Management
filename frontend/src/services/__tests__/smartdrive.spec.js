import { describe, expect, it, vi, beforeEach } from 'vitest'
import api from '@/services/api'
import smartdriveService from '../smartdrive'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
  },
}))

describe('smartdrive service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('sends the complete preference payload to the Laravel cars endpoint', async () => {
    api.get.mockResolvedValueOnce({ cars: [{ id: 'car-1' }] })

    const preferences = {
      budget: 450,
      startDate: '2026-09-21',
      endDate: '2026-09-25',
      city: 'Tanger',
      passengers: 3,
      vehicleType: 'SUV',
      transmission: 'Automatique',
      energy: 'Essence',
    }

    await expect(smartdriveService.getRecommendations(preferences)).resolves.toEqual([
      { id: 'car-1' },
    ])
    expect(api.get).toHaveBeenCalledWith('/cars', {
      params: {
        budget: 450,
        start_date: '2026-09-21',
        end_date: '2026-09-25',
        city: 'Tanger',
        passengers: 3,
        vehicle_type: 'SUV',
        transmission: 'Automatique',
        energy: 'Essence',
      },
    })
  })
})
