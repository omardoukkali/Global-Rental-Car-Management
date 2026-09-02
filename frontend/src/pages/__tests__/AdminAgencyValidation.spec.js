import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AdminAgencyValidation from '../AdminAgencyValidation.vue'

describe('AdminAgencyValidation', () => {
    let wrapper

    beforeEach(() => {
        wrapper = mount(AdminAgencyValidation, {
            global: {
                stubs: ['RouterLink'], // évite d'avoir besoin de vue-router
            },
        })
    })

    it('affiche les 3 agences en attente au chargement', () => {
        const cards = wrapper.findAll('.req-card')
        expect(cards).toHaveLength(3)
    })

    it('affiche le nom et la ville de chaque agence', () => {
        const firstCard = wrapper.find('.req-card')
        expect(firstCard.text()).toContain('Atlas Cars Casablanca')
        expect(firstCard.text()).toContain('Casablanca')
    })

    it('affiche le badge avec le nombre d\'agences en attente', () => {
        const badge = wrapper.find('.pending-badge')
        expect(badge.text()).toBe('3')
    })

    it('retire une agence de la liste quand on clique sur Valider', async () => {
        const firstCard = wrapper.find('.req-card')
        const validateButton = firstCard.find('.btn-primary')

        await validateButton.trigger('click')

        const remainingCards = wrapper.findAll('.req-card')
        expect(remainingCards).toHaveLength(2)
        expect(wrapper.text()).not.toContain('Atlas Cars Casablanca')
    })

    it('retire une agence de la liste quand on clique sur Rejeter', async () => {
        const firstCard = wrapper.find('.req-card')
        const rejectButton = firstCard.find('.btn-outline')

        await rejectButton.trigger('click')

        const remainingCards = wrapper.findAll('.req-card')
        expect(remainingCards).toHaveLength(2)
    })

    it('met à jour le badge quand une agence est validée', async () => {
        expect(wrapper.find('.pending-badge').text()).toBe('3')

        await wrapper.find('.req-card .btn-primary').trigger('click')

        expect(wrapper.find('.pending-badge').text()).toBe('2')
    })

    it('affiche les vérifications (SIRET, Adresse, etc.) pour chaque agence', () => {
        const firstCard = wrapper.find('.req-card')
        expect(firstCard.text()).toContain('SIRET')
        expect(firstCard.text()).toContain('Pièces jointes')
        expect(firstCard.text()).toContain('Adresse')
        expect(firstCard.text()).toContain('Contrat')
    })

    it('affiche ✓ pour une vérification valide et — pour une invalide', () => {
        const firstCard = wrapper.find('.req-card')
        const rows = firstCard.findAll('.req-row')

        // D'après les données : SIRET=true, Pièces=true, Adresse=false, Contrat=true
        expect(rows[0].text()).toContain('✓')  // SIRET
        expect(rows[2].text()).toContain('—')  // Adresse
    })
})