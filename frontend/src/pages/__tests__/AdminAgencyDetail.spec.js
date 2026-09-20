import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

const push = vi.fn()

vi.mock('@/services/admin', () => ({
    default: {
        getAgency: vi.fn(),
        updateAgency: vi.fn(),
        deleteAgency: vi.fn(),
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

import AdminAgencyDetail from '../AdminAgencyDetail.vue'
import adminService from '@/services/admin'

const agency = {
    id: 'ag-1',
    name: 'Demo Rent Cars',
    city: 'Tangier',
    manager: 'Karim Demo',
    reference: 'AG-1111',
    email: 'agency@example.com',
    phone: '+212600000000',
    address: '12 Rue Demo',
    owner_email: 'agency@example.com',
    commission_rate: 12,
    cars_count: 8,
    points_count: 2,
    avg_rating: 4.6,
    total_reviews: 20,
    status: 'approved',
}

function mountPage() {
    const pinia = createPinia()
    setActivePinia(pinia)
    return mount(AdminAgencyDetail, {
        props: { agencyId: 'ag-1' },
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

describe('AdminAgencyDetail', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        adminService.getAgency.mockResolvedValue({ agency })
        adminService.updateAgency.mockResolvedValue({
            agency: { ...agency, commission_rate: 20 },
        })
        adminService.deleteAgency.mockResolvedValue({ message: 'ok' })
    })

    it('affiche les informations de l’agence', async () => {
        const wrapper = mountPage()
        await flushPromises()
        expect(adminService.getAgency).toHaveBeenCalledWith('ag-1')
        expect(wrapper.text()).toContain('Demo Rent Cars')
        expect(wrapper.text()).toContain('agency@example.com')
        expect(wrapper.text()).toContain('Tangier')
        expect(wrapper.text()).toContain('Approuvée')
        expect(wrapper.find('#agency-commission').element.value).toBe('12')
    })

    it('enregistre la commission', async () => {
        const wrapper = mountPage()
        await flushPromises()
        await wrapper.find('#agency-commission').setValue(20)
        await wrapper.find('form').trigger('submit')
        await flushPromises()
        expect(adminService.updateAgency).toHaveBeenCalledWith('ag-1', { commission_rate: 20 })
        expect(wrapper.text()).toContain('Commission mise à jour')
    })

    it('supprime l’agence après confirmation', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true)
        const wrapper = mountPage()
        await flushPromises()
        await wrapper.find('.btn-danger').trigger('click')
        await flushPromises()
        expect(adminService.deleteAgency).toHaveBeenCalledWith('ag-1')
        expect(push).toHaveBeenCalledWith('/admin/agencies')
    })
})
