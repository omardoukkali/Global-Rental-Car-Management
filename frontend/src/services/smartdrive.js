import api from '@/services/api'

/**
 * SmartDrive AI client (SCRUM-181).
 *
 * The form now talks to the Laravel endpoint POST /api/smartdrive/recommend
 * (SCRUM-180) instead of calling the Python/FastAPI service directly. Laravel
 * validates the request, applies the rental business rules, asks the ML service
 * to score the eligible vehicles and returns the final recommendation.
 *
 * The shared `api` instance already unwraps the Axios response (its interceptor
 * returns `response.data`) and rejects errors as `{ message, errors, status }`.
 */

const RECOMMEND_URL = '/smartdrive/recommend'

/** Turns an empty selection ('') into null so Laravel's `nullable|in:` passes. */
function optional(value) {
  const trimmed = String(value ?? '').trim()
  return trimmed === '' ? null : trimmed
}

/** Maps the form's camelCase preferences to the flat snake_case API contract. */
export function buildPayload(preferences) {
  return {
    budget_per_day: Number(preferences.budget),
    start_at: preferences.startDate,
    end_at: preferences.endDate,
    city_id: preferences.cityId,
    passengers: Number(preferences.passengers),
    vehicle_type: optional(preferences.vehicleType),
    transmission: optional(preferences.transmission),
    energy_type: optional(preferences.energy),
  }
}

/**
 * A typed error so the UI can branch on the failure without parsing messages:
 *  - 'validation'  -> 422, preferences rejected (field errors in `.errors`)
 *  - 'unavailable' -> 503 or network failure, the AI/API is momentarily down
 *  - 'error'       -> anything else
 */
export class SmartDriveError extends Error {
  constructor(code, message, errors = null, status = null) {
    super(message)
    this.name = 'SmartDriveError'
    this.code = code
    this.errors = errors
    this.status = status
  }
}

function firstFieldMessage(errors) {
  if (!errors || typeof errors !== 'object') return ''
  const first = Object.values(errors)[0]
  return Array.isArray(first) ? first[0] : String(first || '')
}

export default {
  /**
   * Sends the preferences to Laravel and returns the full recommendation:
   *   { trip, preferences, total, recommended, results, alternatives, source }
   * Throws a SmartDriveError on validation / availability problems.
   */
  async getRecommendation(preferences) {
    try {
      // `api` already returns the JSON body (not the Axios response).
      return await api.post(RECOMMEND_URL, buildPayload(preferences))
    } catch (error) {
      const status = error?.status ?? null

      if (status === 422) {
        throw new SmartDriveError(
            'validation',
            firstFieldMessage(error.errors) || error.message || 'Vérifiez les informations saisies.',
            error.errors || null,
            status,
        )
      }

      if (status === 503 || status === null) {
        throw new SmartDriveError(
            'unavailable',
            error?.message || 'Le service de recommandation est momentanément indisponible. Réessayez dans quelques instants.',
            null,
            status,
        )
      }

      throw new SmartDriveError('error', error?.message || 'Une erreur est survenue.', error?.errors || null, status)
    }
  },
}
