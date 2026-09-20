import { describe, expect, it, vi, beforeEach } from 'vitest'
import api from '@/services/api'
import smartdriveService from '../smartdrive'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('smartdrive service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('sends the complete preference payload to the Laravel SmartDrive endpoint', async () => {
    api.post.mockResolvedValueOnce({ vehicles: [{ id: 'car-1' }] })

    const preferences = {
      budget: 450,
      startDate: '2026-09-21',
      endDate: '2026-09-25',
      cityId: 'city-1',
      passengers: 3,
      vehicleType: 'suv',
      transmission: 'automatic',
      energy: 'gasoline',
    }

    await expect(smartdriveService.getEligibleVehicles(preferences)).resolves.toEqual([
      { id: 'car-1' },
    ])
    expect(api.post).toHaveBeenCalledWith('/smartdrive/eligible-vehicles', {
      budget_per_day: 450,
      start_at: '2026-09-21',
      end_at: '2026-09-25',
      city_id: 'city-1',
      passengers: 3,
      vehicle_type: 'suv',
      transmission: 'automatic',
      energy_type: 'gasoline',
    })
  })
})
