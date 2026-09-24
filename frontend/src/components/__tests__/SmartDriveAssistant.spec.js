import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'

const { getRecommendation } = vi.hoisted(() => ({ getRecommendation: vi.fn() }))
vi.mock('@/services/smartdrive', () => ({ default: { getRecommendation } }))

const { apiGet } = vi.hoisted(() => ({ apiGet: vi.fn() }))
vi.mock('@/services/api', () => ({ default: { get: apiGet } }))

import SmartDriveAssistant from '../SmartDriveAssistant.vue'

const CITY = '11111111-1111-1111-1111-111111111111'

function car(overrides = {}) {
    return {
        id: 'car-best',
        brand: 'Dacia',
        model: 'Duster',
        type: 'suv',
        transmission: 'automatic',
        seats: 5,
        energy_type: 'diesel',
        year: 2023,
        daily_price: 400,
        total_price: 1600,
        score: 88,
        confidence: 92,
        explanation: {
            strengths: ['respecte votre budget', 'correspond au type demandé'],
            tradeoffs: ['efficacité énergétique plus faible'],
            summary: 'respecte votre budget ; correspond au type demandé',
        },
        agency: { name: 'Atlas Cars', avg_rating: 4.6, total_reviews: 120 },
        ...overrides,
    }
}

function recommendation() {
    const best = car()
    const value = car({ id: 'car-value', brand: 'Kia', model: 'Picanto', type: 'hatchback', daily_price: 240, total_price: 960, score: 74 })
    const comfort = car({ id: 'car-comfort', brand: 'Ford', model: 'Transit', type: 'van', seats: 9, daily_price: 700, total_price: 2800, score: 69 })
    return {
        trip: { days: 4, passengers: 3, city_id: CITY },
        preferences: { budget_per_day: 450 },
        total: 3,
        recommended: best,
        results: [best, value, comfort],
        alternatives: { best_value: value, most_comfortable: comfort },
        source: 'laravel',
    }
}

function mountAssistant() {
    return mount(SmartDriveAssistant, {
        global: {
            stubs: {
                RouterLink: { props: ['to'], template: '<a class="rl"><slot /></a>' },
            },
        },
    })
}

async function openAndSubmit(wrapper) {
    await wrapper.find('.smartdrive-launcher').trigger('click')
    await flushPromises() // cities load
    await wrapper.find('.smartdrive-form').trigger('submit')
    await flushPromises() // service resolves, hit the 500ms pause
    await vi.advanceTimersByTimeAsync(600)
    await flushPromises() // view switches
}

describe('SmartDriveAssistant (SCRUM-181)', () => {
    beforeEach(() => {
        vi.useFakeTimers()
        getRecommendation.mockReset()
        apiGet.mockReset()
        apiGet.mockResolvedValue({ cities: [{ id: CITY, name: 'Tanger' }] })
    })
    afterEach(() => vi.useRealTimers())

    it('calls the Laravel service and renders the best vehicle with ML fields', async () => {
        getRecommendation.mockResolvedValueOnce(recommendation())
        const wrapper = mountAssistant()
        await openAndSubmit(wrapper)

        expect(getRecommendation).toHaveBeenCalledTimes(1)

        const best = wrapper.find('[data-testid="smartdrive-best"]')
        expect(best.exists()).toBe(true)
        expect(best.find('.smartdrive-best-badge').text()).toBe('Meilleur choix')

        const text = wrapper.text()
        expect(text).toContain('88%')            // compatibility
        expect(text).toContain('Confiance 92%')  // confidence
        expect(text).toContain('au total')       // estimated total rental cost
        expect(text).toContain('4.6/5')          // agency rating
        expect(text).toContain('respecte votre budget') // reasons/strengths
        expect(text).toContain('À savoir')       // trade-offs
    })

    it('shows the smart alternatives (Best Value / Most Comfortable)', async () => {
        getRecommendation.mockResolvedValueOnce(recommendation())
        const wrapper = mountAssistant()
        await openAndSubmit(wrapper)

        const alts = wrapper.find('[data-testid="smartdrive-alternatives"]')
        expect(alts.exists()).toBe(true)
        expect(alts.text()).toContain('Meilleur rapport qualité-prix')
        expect(alts.text()).toContain('Le plus confortable')
        // The #1 recommendation is never repeated as an alternative.
        expect(alts.text()).not.toContain('Duster')
    })

    it('renders the empty state when no vehicle is eligible', async () => {
        getRecommendation.mockResolvedValueOnce({ total: 0, results: [], message: 'Aucun véhicule disponible pour ce trajet.' })
        const wrapper = mountAssistant()
        await openAndSubmit(wrapper)

        expect(wrapper.find('.smartdrive-empty').exists()).toBe(true)
        expect(wrapper.text()).toContain('Aucun véhicule disponible')
    })

    it('renders the error state when the AI service is unavailable (503)', async () => {
        getRecommendation.mockRejectedValueOnce({ code: 'unavailable', message: 'Le service de recommandation est momentanément indisponible.' })
        const wrapper = mountAssistant()
        await openAndSubmit(wrapper)

        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
        expect(wrapper.text()).toContain('momentanément indisponible')
    })

    it('keeps the form open and shows the message on a validation error (422)', async () => {
        getRecommendation.mockRejectedValueOnce({ code: 'validation', message: 'La date de retour doit être postérieure au départ.' })
        const wrapper = mountAssistant()
        await openAndSubmit(wrapper)

        expect(wrapper.find('.smartdrive-form').exists()).toBe(true)
        expect(wrapper.find('.smartdrive-error').text()).toContain('La date de retour')
    })
})
