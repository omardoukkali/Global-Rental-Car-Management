import { describe, expect, it, vi, beforeEach } from 'vitest'
import smartdriveService from '../smartdrive'

const { post } = vi.hoisted(() => ({ post: vi.fn() }))

vi.mock('@/services/api', () => ({
  default: { post },
}))

describe('smartdrive service', () => {
  beforeEach(() => {
    post.mockReset()
    vi.stubGlobal('fetch', vi.fn())
  })

  it('sends the complete preference payload to the Laravel SmartDrive endpoint', async () => {
    post.mockResolvedValueOnce({ results: [{ id: 'car-1' }] })

    const preferences = {
      budget: 450,
      startDate: '2026-09-21',
      endDate: '2026-09-25',
      cityId: 'city-1',
      cityName: 'Casablanca',
      passengers: 3,
      vehicleType: 'suv',
      transmission: 'automatic',
      energy: 'gasoline',
    }

    await expect(smartdriveService.getEligibleVehicles(preferences)).resolves.toEqual([
      { id: 'car-1' },
    ])
    expect(post).toHaveBeenCalledWith('/smartdrive/recommend', {
      budget_per_day: 450,
      start_at: '2026-09-21',
      end_at: '2026-09-25',
      city_id: 'city-1',
      passengers: 3,
      vehicle_type: 'suv',
      transmission: 'automatic',
      energy_type: 'gasoline',
      city_name: 'Casablanca',
    })
  })

  it('uses the AI fleet when live availability is empty', async () => {
    post.mockResolvedValueOnce({ total: 0, results: [] })
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ results: [{ id: 'experimental-car' }] }),
    })

    await expect(smartdriveService.getEligibleVehicles({
      budget: 450,
      startDate: '2026-09-21',
      endDate: '2026-09-25',
      cityId: 'city-1',
      cityName: 'Casablanca',
      passengers: 2,
      vehicleType: '',
      transmission: '',
      energy: '',
    })).resolves.toEqual([{ id: 'experimental-car' }])

    expect(fetch).toHaveBeenCalledWith(
      'http://localhost:5000/api/recommend',
      expect.objectContaining({ method: 'POST' }),
    )
  })
})
