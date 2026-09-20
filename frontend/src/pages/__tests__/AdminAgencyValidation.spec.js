import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/services/admin', () => ({
    default: {
        getAgencies: vi.fn(),
        approveAgency: vi.fn(),
        rejectAgency: vi.fn(),
    },
}))

import AdminAgencyValidation from '../AdminAgencyValidation.vue'
import adminService from '@/services/admin'

        const mockAgencies = [
    {
        id: 'ag-1',
        name: 'Atlas Cars Casablanca',
        city: 'Casablanca',
        manager: 'Yassine Berrada',
        owner_email: 'yassine@atlas.ma',
        reference: 'AG-2041',
        email: 'contact@atlas.ma',
        phone: '+212522000001',
        address: '',
        commission_rate: 15,
        cars_count: 4,
        points_count: 2,
    },
    {
        id: 'ag-2',
        name: 'Riviera Rent',
        city: 'Marrakech',
        manager: 'Samira D.',
        owner_email: 'samira@riviera.ma',
        reference: 'AG-2042',
        email: 'contact@riviera.ma',
        phone: '',
        address: '12 Avenue Mohammed V',
        commission_rate: 12.5,
        cars_count: 1,
        points_count: 1,
    },
    {
        id: 'ag-3',
        name: 'Desert Moto',
        city: 'Agadir',
        manager: 'Omar T.',
        owner_email: 'omar@desert.ma',
        reference: 'AG-2043',
        email: '',
        phone: '+212528000003',
        address: 'Rue Hassan II',
        commission_rate: 10,
        cars_count: 0,
        points_count: 0,
    },
]

function mountPage() {
    const pinia = createPinia()
    setActivePinia(pinia)
    return mount(AdminAgencyValidation, {
        global: {
            plugins: [pinia],
            stubs: {
                RouterLink: {
                    props: ['to'],
                    template: '<a :href="to"><slot /></a>',
                },
                AdminLayout: {
                    props: ['pendingCount'],
                    template: '<div><slot /></div>',
                },
            },
        },
    })
}

describe('AdminAgencyValidation', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        adminService.getAgencies.mockResolvedValue({ agencies: mockAgencies })
        adminService.approveAgency.mockResolvedValue({ message: 'ok' })
        adminService.rejectAgency.mockResolvedValue({ message: 'ok' })
    })

    it('charge les agences pending depuis l’API', async () => {
        mountPage()
        await flushPromises()
        expect(adminService.getAgencies).toHaveBeenCalledWith({ status: 'pending' })
    })

    it('affiche les 3 agences en attente au chargement', async () => {
        const wrapper = mountPage()
        await flushPromises()
        expect(wrapper.findAll('.req-card')).toHaveLength(3)
    })

    it('affiche le nom et la ville de chaque agence', async () => {
        const wrapper = mountPage()
        await flushPromises()
        const firstCard = wrapper.find('.req-card')
        expect(firstCard.text()).toContain('Atlas Cars Casablanca')
        expect(firstCard.text()).toContain('Casablanca')
    })

    it('affiche le badge avec le nombre d\'agences en attente', async () => {
        const wrapper = mountPage()
        await flushPromises()
        expect(wrapper.find('.pending-badge').text()).toBe('3')
    })

    it('retire une agence de la liste quand on clique sur Valider', async () => {
        const wrapper = mountPage()
        await flushPromises()

        await wrapper.find('.req-card .btn-primary').trigger('click')
        await flushPromises()

        expect(adminService.approveAgency).toHaveBeenCalledWith('ag-1')
        expect(wrapper.findAll('.req-card')).toHaveLength(2)
        expect(wrapper.text()).not.toContain('Atlas Cars Casablanca')
    })

    it('retire une agence de la liste quand on clique sur Rejeter', async () => {
        const wrapper = mountPage()
        await flushPromises()

        await wrapper.find('.req-card .btn-outline').trigger('click')
        await flushPromises()

        expect(adminService.rejectAgency).toHaveBeenCalledWith('ag-1')
        expect(wrapper.findAll('.req-card')).toHaveLength(2)
    })

    it('met à jour le badge quand une agence est validée', async () => {
        const wrapper = mountPage()
        await flushPromises()
        expect(wrapper.find('.pending-badge').text()).toBe('3')

        await wrapper.find('.req-card .btn-primary').trigger('click')
        await flushPromises()

        expect(wrapper.find('.pending-badge').text()).toBe('2')
    })

    it('affiche les informations de l’agence (email, téléphone, adresse)', async () => {
        const wrapper = mountPage()
        await flushPromises()
        const firstCard = wrapper.find('.req-card')
        expect(firstCard.text()).toContain('Email')
        expect(firstCard.text()).toContain('contact@atlas.ma')
        expect(firstCard.text()).toContain('+212522000001')
        expect(firstCard.text()).toContain('Yassine Berrada')
        expect(firstCard.text()).toContain('15 %')
        expect(firstCard.text()).not.toContain('Flotte')
        expect(firstCard.text()).not.toContain('véhicule')
    })

    it('affiche — quand une information est manquante', async () => {
        const wrapper = mountPage()
        await flushPromises()
        const rows = wrapper.find('.req-card').findAll('.req-row')
        expect(rows[2].text()).toContain('Adresse')
        expect(rows[2].text()).toContain('—')
    })

    it('affiche une erreur si le chargement échoue', async () => {
        adminService.getAgencies.mockRejectedValueOnce({ message: 'Le serveur est indisponible. Vérifiez votre connexion.' })
        const wrapper = mountPage()
        await flushPromises()
        expect(wrapper.text()).toContain('Le serveur est indisponible')
    })
})
