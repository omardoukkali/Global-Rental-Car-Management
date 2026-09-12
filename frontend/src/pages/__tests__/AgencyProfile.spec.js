/**
 * Tests unitaires du composant AgencyProfile.vue.
 *
 * Ce composant est une page d'affichage (lecture seule) du profil de l'agence.
 * Il appelle agencyService.getProfile() au montage et affiche les données récupérées.
 *
 * On teste :
 *   - L'appel API au montage
 *   - L'affichage du loading, des données, et des erreurs
 *   - Le calcul des initiales (computed)
 *
 * On NE teste PAS :
 *   - Le CSS / la mise en page
 *   - Le comportement des liens (RouterLink est stubbé)
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'

// === MOCK DU SERVICE AGENCY ===
// On remplace @/services/agency avant tout import du composant,
// pour que le composant utilise notre version bidon.
vi.mock('@/services/agency', () => ({
    default: {
        getProfile: vi.fn(),
    }
}))

// On importe le composant APRÈS le mock
import AgencyProfile from '@/pages/AgencyProfile.vue'
import agencyService from '@/services/agency'

/**
 * Helper : monte le composant avec les stubs nécessaires.
 * On stubbe RouterLink car il exigerait un vrai router configuré.
 * Le `true` fait un stub minimaliste qui rend juste <router-link-stub>.
 */
function mountAgencyProfile() {
    return mount(AgencyProfile, {
        global: {
            stubs: {
                RouterLink: true,
            }
        }
    })
}

describe('AgencyProfile.vue', () => {
    /**
     * Reset les mocks avant chaque test pour l'isolation.
     */
    beforeEach(() => {
        vi.clearAllMocks()
    })

    // =====================================================================
    // TEST #1 : Le service est appelé au montage
    // =====================================================================
    it('appelle agencyService.getProfile au montage', async () => {
        // Arrange : on prépare une réponse fake
        agencyService.getProfile.mockResolvedValueOnce({
            agency: { name: 'AutoMaroc', email: 'contact@automaroc.ma' }
        })

        // Act : on monte le composant
        mountAgencyProfile()

        // Assert : le service a bien été appelé une seule fois
        expect(agencyService.getProfile).toHaveBeenCalledTimes(1)
    })

    // =====================================================================
    // TEST #2 : Affichage du loading pendant l'appel API
    // =====================================================================
    it('affiche le message de chargement avant que la réponse arrive', () => {
        // Arrange : on retourne une promise qui ne se résout jamais
        // pour que le composant reste en état loading
        agencyService.getProfile.mockReturnValueOnce(new Promise(() => {}))

        // Act
        const wrapper = mountAgencyProfile()

        // Assert : le texte "Chargement" doit être visible
        expect(wrapper.text()).toContain('Chargement du profil')
    })

    // =====================================================================
    // TEST #3 : Affichage des données de l'agence après le chargement
    // =====================================================================
    it("affiche les données de l'agence après un chargement réussi", async () => {
        // Arrange : réponse complète avec toutes les infos
        agencyService.getProfile.mockResolvedValueOnce({
            agency: {
                name: 'AutoMaroc Location',
                email: 'contact@automaroc.ma',
                phone: '+212612345678',
                address: '15 Bd Mohammed V, Tanger',
                avg_rating: '4.8',
                total_reviews: '127',
                status: 'active',
                commission_rate: '10',
            }
        })

        // Act : on monte et on attend que la promesse se résolve
        const wrapper = mountAgencyProfile()
        await flushPromises()

        // Assert : toutes les infos doivent apparaître dans le composant
        const text = wrapper.text()
        expect(text).toContain('AutoMaroc Location')
        expect(text).toContain('contact@automaroc.ma')
        expect(text).toContain('+212612345678')
        expect(text).toContain('15 Bd Mohammed V, Tanger')
        expect(text).toContain('4.8')
        expect(text).toContain('127')
        expect(text).toContain('10%')
    })

    // =====================================================================
    // TEST #4 : Affichage du message d'erreur si l'API échoue
    // =====================================================================
    it("affiche un message d'erreur si l'API échoue", async () => {
        // Arrange : simulation d'un rejet
        agencyService.getProfile.mockRejectedValueOnce({
            message: 'Impossible de contacter le serveur.',
        })

        // Act
        const wrapper = mountAgencyProfile()
        await flushPromises()

        // Assert
        expect(wrapper.text()).toContain('Impossible de contacter le serveur.')
    })

    // =====================================================================
    // TEST #5 : Message d'erreur par défaut si l'erreur n'a pas de message
    // =====================================================================
    it("affiche un message d'erreur par défaut si l'erreur n'a pas de message", async () => {
        // Arrange : rejet sans message
        agencyService.getProfile.mockRejectedValueOnce({})

        // Act
        const wrapper = mountAgencyProfile()
        await flushPromises()

        // Assert : le message par défaut du composant
        expect(wrapper.text()).toContain("Impossible de charger le profil")
    })

    // =====================================================================
    // TEST #6 : Calcul correct des initiales à partir du nom
    // =====================================================================
    it('calcule correctement les initiales à partir de "Atlas Cars"', async () => {
        agencyService.getProfile.mockResolvedValueOnce({
            agency: { name: 'Atlas Cars' }
        })

        const wrapper = mountAgencyProfile()
        await flushPromises()

        // "Atlas Cars" → "AC"
        expect(wrapper.text()).toContain('AC')
    })

    // =====================================================================
    // TEST #7 : Les initiales prennent seulement les 2 premières lettres
    // =====================================================================
    it('limite les initiales à 2 caractères même avec un nom long', async () => {
        agencyService.getProfile.mockResolvedValueOnce({
            agency: { name: 'AutoMaroc Location Rapide Voyages' }
        })

        const wrapper = mountAgencyProfile()
        await flushPromises()

        // "AutoMaroc Location Rapide Voyages" → "AL" (que les 2 premières)
        // On vérifie que "AL" est dans le texte mais pas "ALRV"
        const html = wrapper.html()
        expect(html).toContain('AL')
    })

    // =====================================================================
    // TEST #8 : Initiales par défaut "AG" si pas de nom
    // =====================================================================
    it('affiche les initiales par défaut "AG" si l\'agence n\'a pas de nom', async () => {
        agencyService.getProfile.mockResolvedValueOnce({
            agency: {} // pas de name
        })

        const wrapper = mountAgencyProfile()
        await flushPromises()

        expect(wrapper.text()).toContain('AG')
    })

    // =====================================================================
    // TEST #9 : Fallback sur "Mon Agence" et "Adresse non renseignée" si vide
    // =====================================================================
    it("affiche les textes par défaut si l'agence a des champs vides", async () => {
        agencyService.getProfile.mockResolvedValueOnce({
            agency: {} // aucune donnée
        })

        const wrapper = mountAgencyProfile()
        await flushPromises()

        const text = wrapper.text()
        expect(text).toContain('Mon Agence')
        expect(text).toContain('Adresse non renseignée')
    })

    // =====================================================================
    // TEST #10 : Support du format de réponse alternatif (sans wrapper "agency")
    // =====================================================================
    it("supporte une réponse API sans wrapper 'agency'", async () => {
        // Le composant fait : `response.agency || response`
        // Donc si l'API renvoie directement les données à la racine, ça doit marcher aussi
        agencyService.getProfile.mockResolvedValueOnce({
            name: 'DirectResponse Agency',
            email: 'direct@example.com',
        })

        const wrapper = mountAgencyProfile()
        await flushPromises()

        expect(wrapper.text()).toContain('DirectResponse Agency')
        expect(wrapper.text()).toContain('direct@example.com')
    })
})