/**
 * Tests unitaires du store d'authentification (Pinia + Composition API).
 *
 * On teste chaque fonction du store en isolant complètement les dépendances externes :
 * - api (axios) → mocké
 * - localStorage → fourni par jsdom, reset avant chaque test
 * - Pinia → recréé avant chaque test pour éviter le state partagé
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

// === MOCK DU MODULE API ===
// On remplace @/services/api par un faux module avant tout import du store.
// Les fonctions post() et get() sont des mocks vitest qu'on pourra contrôler dans chaque test.
vi.mock('@/services/api', () => ({
    default: {
        post: vi.fn(),
        get: vi.fn(),
    }
}))

// On importe le store APRÈS le mock (sinon le vrai api serait utilisé)
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

describe('useAuthStore', () => {
    /**
     * beforeEach s'exécute AVANT CHAQUE test.
     * Équivalent d'un [SetUp] en NUnit ou d'un @BeforeEach en JUnit.
     * Garantit que chaque test démarre avec un environnement propre et isolé.
     */
    beforeEach(() => {
        // 1. Recréer une instance Pinia propre
        setActivePinia(createPinia())
        // 2. Vider le localStorage
        localStorage.clear()
        // 3. Reset les mocks (efface les appels précédents et les valeurs de retour)
        vi.clearAllMocks()
    })

    // =====================================================================
    // TESTS DE setSession
    // =====================================================================
    describe('setSession', () => {
        it('stocke le token et le user dans le state Pinia', () => {
            // Arrange
            const auth = useAuthStore()
            const token = 'test-token-123'
            const user = { id: '1', email: 'test@example.com', role: 'client' }

            // Act
            auth.setSession(token, user)

            // Assert
            expect(auth.token).toBe(token)
            expect(auth.user).toEqual(user)
        })

        it('persiste le token et le user dans localStorage', () => {
            const auth = useAuthStore()
            const token = 'persistent-token'
            const user = { id: '2', email: 'persist@example.com', role: 'agency' }

            auth.setSession(token, user)

            expect(localStorage.getItem('token')).toBe(token)
            expect(JSON.parse(localStorage.getItem('user'))).toEqual(user)
        })
    })

    // =====================================================================
    // TESTS DE clearSession
    // =====================================================================
    describe('clearSession', () => {
        it('vide le state Pinia', () => {
            const auth = useAuthStore()
            auth.setSession('some-token', { id: '1' }) // On met d'abord une session

            auth.clearSession()

            expect(auth.token).toBeNull()
            expect(auth.user).toBeNull()
        })

        it('supprime token et user du localStorage', () => {
            const auth = useAuthStore()
            auth.setSession('some-token', { id: '1' })

            auth.clearSession()

            expect(localStorage.getItem('token')).toBeNull()
            expect(localStorage.getItem('user')).toBeNull()
        })
    })

    // =====================================================================
    // TESTS DE login
    // =====================================================================
    describe('login', () => {
        it('appelle POST /login avec les credentials', async () => {
            // Arrange : on configure le mock pour renvoyer une fausse réponse
            api.post.mockResolvedValueOnce({
                token: 'fake-token',
                user: { id: '1', email: 'test@example.com', role: 'client' },
            })
            const auth = useAuthStore()
            const credentials = { email: 'test@example.com', password: 'Password123' }

            // Act
            await auth.login(credentials)

            // Assert
            expect(api.post).toHaveBeenCalledWith('/login', credentials)
            expect(api.post).toHaveBeenCalledTimes(1)
        })

        it('stocke la session en cas de succès', async () => {
            const fakeUser = { id: '42', email: 'user@example.com', role: 'client' }
            api.post.mockResolvedValueOnce({
                token: 'success-token',
                user: fakeUser,
            })
            const auth = useAuthStore()

            await auth.login({ email: 'user@example.com', password: 'Password123' })

            expect(auth.token).toBe('success-token')
            expect(auth.user).toEqual(fakeUser)
            expect(localStorage.getItem('token')).toBe('success-token')
        })

        it('retourne le user en cas de succès', async () => {
            const fakeUser = { id: '99', email: 'return@example.com', role: 'agency' }
            api.post.mockResolvedValueOnce({
                token: 'any-token',
                user: fakeUser,
            })
            const auth = useAuthStore()

            const returnedUser = await auth.login({ email: 'return@example.com', password: 'xxx' })

            expect(returnedUser).toEqual(fakeUser)
        })

        it("propage l'erreur et ne stocke rien en cas d'échec", async () => {
            // On simule un rejet du mock (comme si l'API renvoyait 401)
            const error = { message: 'Invalid credentials', status: 401 }
            api.post.mockRejectedValueOnce(error)
            const auth = useAuthStore()

            // Vérifie que login rejette bien avec l'erreur attendue
            await expect(
                auth.login({ email: 'wrong@example.com', password: 'wrong' })
            ).rejects.toEqual(error)

            // Aucune session ne doit être créée
            expect(auth.token).toBeNull()
            expect(auth.user).toBeNull()
            expect(localStorage.getItem('token')).toBeNull()
        })
    })

    // =====================================================================
    // TESTS DE registerClient
    // =====================================================================
    describe('registerClient', () => {
        it('appelle POST /register/client avec le payload', async () => {
            api.post.mockResolvedValueOnce({ user: { id: '1' } })
            const auth = useAuthStore()
            const payload = {
                first_name: 'Mehdi',
                last_name: 'El Fassi',
                email: 'mehdi@example.com',
                password: 'Password123',
                password_confirmation: 'Password123',
            }

            await auth.registerClient(payload)

            expect(api.post).toHaveBeenCalledWith('/register/client', payload)
        })

        it("ne crée pas de session (l'inscription client ne renvoie pas de token)", async () => {
            api.post.mockResolvedValueOnce({ user: { id: '1' } })
            const auth = useAuthStore()

            await auth.registerClient({ email: 'test@example.com', password: 'Password123' })

            expect(auth.token).toBeNull()
            expect(auth.user).toBeNull()
        })
    })

    // =====================================================================
    // TESTS DE registerAgency
    // =====================================================================
    describe('registerAgency', () => {
        it('appelle POST /register/agency avec le payload', async () => {
            api.post.mockResolvedValueOnce({ user: {}, agency: {} })
            const auth = useAuthStore()
            const payload = {
                first_name: 'Omar',
                agency_name: 'AutoMaroc',
                agency_city: 'uuid-here',
            }

            await auth.registerAgency(payload)

            expect(api.post).toHaveBeenCalledWith('/register/agency', payload)
        })
    })

    // =====================================================================
    // TESTS DE logout
    // =====================================================================
    describe('logout', () => {
        it('appelle POST /logout puis vide la session', async () => {
            api.post.mockResolvedValueOnce({ message: 'Logout successful.' })
            const auth = useAuthStore()
            auth.setSession('token-before-logout', { id: '1' })

            await auth.logout()

            expect(api.post).toHaveBeenCalledWith('/logout')
            expect(auth.token).toBeNull()
            expect(auth.user).toBeNull()
            expect(localStorage.getItem('token')).toBeNull()
        })

        it("vide quand même la session locale si l'API échoue", async () => {
            // Sécurité : même si le backend est down, l'utilisateur doit être déconnecté localement
            api.post.mockRejectedValueOnce(new Error('Network error'))
            const auth = useAuthStore()
            auth.setSession('token-before-logout', { id: '1' })

            await auth.logout()

            expect(auth.token).toBeNull()
            expect(auth.user).toBeNull()
        })
    })

    // =====================================================================
    // TESTS DE restoreSession
    // =====================================================================
    describe('restoreSession', () => {
        it("retourne false et clear s'il n'y a pas de token en localStorage", async () => {
            const auth = useAuthStore()

            const result = await auth.restoreSession()

            expect(result).toBe(false)
            expect(auth.token).toBeNull()
            expect(api.get).not.toHaveBeenCalled()
        })

        it('restaure la session avec le token existant en cas de succès', async () => {
            localStorage.setItem('token', 'existing-token')
            const fakeUser = { id: '1', email: 'existing@example.com' }
            api.get.mockResolvedValueOnce({ user: fakeUser })

            const auth = useAuthStore()
            const result = await auth.restoreSession()

            expect(result).toBe(true)
            expect(api.get).toHaveBeenCalledWith('/me')
            expect(auth.token).toBe('existing-token')
            expect(auth.user).toEqual(fakeUser)
        })

        it('vide la session si le token est invalide (401)', async () => {
            localStorage.setItem('token', 'expired-token')
            api.get.mockRejectedValueOnce({ status: 401, message: 'Unauthenticated' })

            const auth = useAuthStore()
            const result = await auth.restoreSession()

            expect(result).toBe(false)
            expect(auth.token).toBeNull()
            expect(localStorage.getItem('token')).toBeNull()
        })
    })

    // =====================================================================
    // TESTS DES GETTERS
    // =====================================================================
    describe('isAuthenticated', () => {
        it('renvoie false quand il n\'y a pas de token', () => {
            const auth = useAuthStore()

            expect(auth.isAuthenticated).toBe(false)
        })

        it('renvoie true quand un token est présent', () => {
            const auth = useAuthStore()
            auth.setSession('any-token', { id: '1' })

            expect(auth.isAuthenticated).toBe(true)
        })
    })
})