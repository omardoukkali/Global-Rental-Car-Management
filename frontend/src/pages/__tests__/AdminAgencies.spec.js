import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

const push = vi.fn()

vi.mock('@/services/admin', () => ({
    default: {
        getAgencies: vi.fn(),
    },
}))

vi.mock('vue-router', async () => {
    const actual = await vi.importActual('vue-router')
    return {
        ...actual,
        useRouter: () => ({ push }),
    }
})

import AdminAgencies from '../AdminAgencies.vue'
import adminService from '@/services/admin'

function mountPage() {
    const pinia = createPinia()
    setActivePinia(pinia)
    return mount(AdminAgencies, {
        global: {
            plugins: [pinia],
            stubs: {
                RouterLink: {
                    props: ['to'],
                    template: '<a :href="to"><slot /></a>',
                },
                AdminLayout: { template: '<div><slot /></div>' },
            },
        },
    })
}

describe('AdminAgencies', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        adminService.getAgencies.mockResolvedValue({
            agencies: [
                {
                    id: 'ag-1',
                    name: 'Demo Rent Cars',
                    city: 'Tangier',
                    manager: 'Karim Demo',
                    email: 'agency@example.com',
                    phone: '+212600000000',
                    reference: 'AG-1111',
                    commission_rate: 12,
                    status: 'approved',
                },
                {
                    id: 'ag-2',
                    name: 'Riad Location',
                    city: 'Marrakech',
                    manager: 'Hamza Benali',
                    email: 'contact@riad-location.ma',
                    phone: '+212511352517',
                    reference: 'AG-A7FD',
                    commission_rate: 18,
                    status: 'pending',
                },
            ],
        })
    })

    it('charge toutes les agences depuis l’API', async () => {
        const wrapper = mountPage()
        await flushPromises()
        expect(adminService.getAgencies).toHaveBeenCalledWith({ status: undefined })
        expect(wrapper.text()).toContain('Demo Rent Cars')
        expect(wrapper.text()).toContain('Riad Location')
        expect(wrapper.text()).toContain('Approuvée')
        expect(wrapper.text()).toContain('En attente')
    })

    it('filtre par statut via l’API', async () => {
        const wrapper = mountPage()
        await flushPromises()
        adminService.getAgencies.mockResolvedValue({ agencies: [] })
        await wrapper.find('select').setValue('approved')
        await flushPromises()
        expect(adminService.getAgencies).toHaveBeenCalledWith({ status: 'approved' })
    })

    it('ouvre la fiche agence au clic sur le nom', async () => {
        const wrapper = mountPage()
        await flushPromises()
        const links = wrapper.findAll('.agency-link')
        expect(links[0].attributes('href')).toBe('/admin/agencies/ag-1')
        expect(links[1].attributes('href')).toBe('/admin/agencies/ag-2')
    })

    it('ouvre la fiche agence au clic sur la ligne', async () => {
        const wrapper = mountPage()
        await flushPromises()
        await wrapper.find('.row-link').trigger('click')
        expect(push).toHaveBeenCalledWith('/admin/agencies/ag-1')
    })
})
