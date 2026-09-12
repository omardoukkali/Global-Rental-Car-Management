/**
 * Tests unitaires du service Agency.
 *
 * Le service `services/agency.js` encapsule les 7 appels API liés à la gestion
 * des agences (profil, CRUD). On teste ici que chaque méthode :
 *   1. Appelle la bonne route HTTP avec la bonne méthode (GET/POST/PUT/DELETE)
 *   2. Transmet correctement les paramètres et payloads
 *   3. Propage correctement les erreurs de l'API
 *
 * On isole complètement l'appel réseau en mockant le module `@/services/api`.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'

// === MOCK DU MODULE API ===
// On remplace @/services/api par un faux module avec les 4 méthodes HTTP.
// Chaque méthode est un vi.fn() qu'on pourra configurer test par test.
vi.mock('@/services/api', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
}))

// On importe le service APRÈS le mock (sinon le vrai api serait utilisé)
import agencyService from '@/services/agency'
import api from '@/services/api'

describe('agencyService', () => {
    /**
     * Reset les mocks avant chaque test pour éviter que les appels
     * d'un test polluent les assertions du suivant.
     */
    beforeEach(() => {
        vi.clearAllMocks()
    })

    // =====================================================================
    // TESTS DE getProfile
    // =====================================================================
    describe('getProfile', () => {
        it('appelle GET /agency/profile', async () => {
            api.get.mockResolvedValueOnce({ agency: { id: '1', name: 'AutoMaroc' } })

            await agencyService.getProfile()

            expect(api.get).toHaveBeenCalledWith('/agency/profile')
            expect(api.get).toHaveBeenCalledTimes(1)
        })

        it("propage l'erreur si l'API échoue", async () => {
            const error = { message: 'Unauthenticated', status: 401 }
            api.get.mockRejectedValueOnce(error)

            await expect(agencyService.getProfile()).rejects.toEqual(error)
        })
    })

    // =====================================================================
    // TESTS DE updateProfile
    // =====================================================================
    describe('updateProfile', () => {
        it('appelle PUT /agency/profile avec le payload', async () => {
            api.put.mockResolvedValueOnce({ agency: { id: '1' } })
            const payload = {
                name: 'AutoMaroc Location',
                email: 'contact@automaroc.ma',
                phone: '+212612345678',
                address: '15 Bd Mohammed V, Tanger',
            }

            await agencyService.updateProfile(payload)

            expect(api.put).toHaveBeenCalledWith('/agency/profile', payload)
        })

        it("propage l'erreur de validation (422)", async () => {
            const error = {
                message: 'The email field is required.',
                status: 422,
                errors: { email: ['The email field is required.'] },
            }
            api.put.mockRejectedValueOnce(error)

            await expect(
                agencyService.updateProfile({ name: 'Test' })
            ).rejects.toEqual(error)
        })
    })

    // =====================================================================
    // TESTS DE getAgencies
    // =====================================================================
    describe('getAgencies', () => {
        it('appelle GET /agencies sans paramètres par défaut', async () => {
            api.get.mockResolvedValueOnce({ data: [] })

            await agencyService.getAgencies()

            expect(api.get).toHaveBeenCalledWith('/agencies', { params: {} })
        })

        it('appelle GET /agencies avec les paramètres fournis', async () => {
            api.get.mockResolvedValueOnce({ data: [] })
            const params = { page: 2, per_page: 20, status: 'approved' }

            await agencyService.getAgencies(params)

            expect(api.get).toHaveBeenCalledWith('/agencies', { params })
        })
    })

    // =====================================================================
    // TESTS DE getAgency
    // =====================================================================
    describe('getAgency', () => {
        it("appelle GET /agencies/{id} avec l'UUID fourni", async () => {
            api.get.mockResolvedValueOnce({ agency: { id: 'abc-123' } })
            const agencyId = 'abc-123-uuid'

            await agencyService.getAgency(agencyId)

            expect(api.get).toHaveBeenCalledWith(`/agencies/${agencyId}`)
        })

        it("propage l'erreur si l'agence n'existe pas (404)", async () => {
            const error = { message: 'Agency not found', status: 404 }
            api.get.mockRejectedValueOnce(error)

            await expect(
                agencyService.getAgency('inexistant-uuid')
            ).rejects.toEqual(error)
        })
    })

    // =====================================================================
    // TESTS DE createAgency
    // =====================================================================
    describe('createAgency', () => {
        it('appelle POST /agencies avec le payload', async () => {
            api.post.mockResolvedValueOnce({ agency: { id: 'new-uuid' } })
            const payload = {
                name: 'Nouvelle Agence',
                city_id: 'city-uuid',
                address: '123 Rue Test',
                phone: '+212612345678',
            }

            await agencyService.createAgency(payload)

            expect(api.post).toHaveBeenCalledWith('/agencies', payload)
        })

        it("propage l'erreur si le payload est invalide", async () => {
            const error = {
                message: 'Validation failed',
                status: 422,
                errors: { name: ['The name field is required.'] },
            }
            api.post.mockRejectedValueOnce(error)

            await expect(agencyService.createAgency({})).rejects.toEqual(error)
        })
    })

    // =====================================================================
    // TESTS DE updateAgency
    // =====================================================================
    describe('updateAgency', () => {
        it("appelle PUT /agencies/{id} avec l'ID et le payload", async () => {
            api.put.mockResolvedValueOnce({ agency: { id: 'abc-123' } })
            const agencyId = 'abc-123-uuid'
            const payload = { name: 'Agence Renommée', status: 'approved' }

            await agencyService.updateAgency(agencyId, payload)

            expect(api.put).toHaveBeenCalledWith(`/agencies/${agencyId}`, payload)
        })
    })

    // =====================================================================
    // TESTS DE deleteAgency
    // =====================================================================
    describe('deleteAgency', () => {
        it("appelle DELETE /agencies/{id} avec l'ID fourni", async () => {
            api.delete.mockResolvedValueOnce({ message: 'Agency deleted.' })
            const agencyId = 'abc-123-uuid'

            await agencyService.deleteAgency(agencyId)

            expect(api.delete).toHaveBeenCalledWith(`/agencies/${agencyId}`)
        })

        it("propage l'erreur si l'agence n'existe pas ou n'est pas supprimable", async () => {
            const error = { message: 'Cannot delete agency with active reservations', status: 409 }
            api.delete.mockRejectedValueOnce(error)

            await expect(
                agencyService.deleteAgency('abc-123')
            ).rejects.toEqual(error)
        })
    })
})