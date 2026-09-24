import { describe, expect, it, vi, beforeEach } from 'vitest'

// The service now goes through the shared Laravel `api` instance, whose
// interceptor already returns the response body and rejects as { message,
// errors, status }. We mock that instance directly.
const { post } = vi.hoisted(() => ({ post: vi.fn() }))
vi.mock('@/services/api', () => ({ default: { post } }))

import smartdriveService, { buildPayload, SmartDriveError } from '../smartdrive'

const preferences = {
  budget: 450,
  startDate: '2026-09-21',
  endDate: '2026-09-25',
  cityId: '11111111-1111-1111-1111-111111111111',
  passengers: 3,
  vehicleType: 'suv',
  transmission: 'automatic',
  energy: 'gasoline',
}

describe('smartdrive service (SCRUM-181)', () => {
  beforeEach(() => post.mockReset())

  it('maps the form to the Laravel snake_case contract', () => {
    expect(buildPayload(preferences)).toEqual({
      budget_per_day: 450,
      start_at: '2026-09-21',
      end_at: '2026-09-25',
      city_id: '11111111-1111-1111-1111-111111111111',
      passengers: 3,
      vehicle_type: 'suv',
      transmission: 'automatic',
      energy_type: 'gasoline',
    })
  })

  it('turns empty optional preferences into null (so nullable|in passes)', () => {
    const payload = buildPayload({ ...preferences, vehicleType: '', transmission: '', energy: '' })
    expect(payload.vehicle_type).toBeNull()
    expect(payload.transmission).toBeNull()
    expect(payload.energy_type).toBeNull()
  })

  it('calls POST /smartdrive/recommend and returns the recommendation body', async () => {
    const body = {
      trip: { days: 4 },
      total: 2,
      recommended: { id: 'car-1', score: 87 },
      results: [{ id: 'car-1', score: 87 }, { id: 'car-2', score: 71 }],
      alternatives: { best_value: { id: 'car-2' } },
      source: 'laravel',
    }
    post.mockResolvedValueOnce(body)

    await expect(smartdriveService.getRecommendation(preferences)).resolves.toBe(body)
    expect(post).toHaveBeenCalledWith('/smartdrive/recommend', buildPayload(preferences))
  })

  it('maps a 422 to a validation SmartDriveError carrying the field message', async () => {
    post.mockRejectedValueOnce({
      status: 422,
      message: 'The given data was invalid.',
      errors: { end_at: ['La date de retour doit être postérieure au départ.'] },
    })

    const error = await smartdriveService.getRecommendation(preferences).catch((e) => e)
    expect(error).toBeInstanceOf(SmartDriveError)
    expect(error.code).toBe('validation')
    expect(error.message).toBe('La date de retour doit être postérieure au départ.')
    expect(error.errors).toHaveProperty('end_at')
  })

  it('maps a 503 to an "unavailable" SmartDriveError', async () => {
    post.mockRejectedValueOnce({ status: 503, message: 'Service indisponible.' })
    const error = await smartdriveService.getRecommendation(preferences).catch((e) => e)
    expect(error).toBeInstanceOf(SmartDriveError)
    expect(error.code).toBe('unavailable')
  })

  it('treats a network failure (no status) as "unavailable"', async () => {
    post.mockRejectedValueOnce({ status: null, message: 'Le serveur est indisponible.' })
    const error = await smartdriveService.getRecommendation(preferences).catch((e) => e)
    expect(error.code).toBe('unavailable')
    expect(error.message).toContain('indisponible')
  })
})
