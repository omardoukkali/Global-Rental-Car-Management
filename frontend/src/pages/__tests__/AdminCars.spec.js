import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

const push = vi.fn()

vi.mock('@/services/admin', () => ({
    default: {
        getCars: vi.fn(),
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

import AdminCars from '../AdminCars.vue'
import adminService from '@/services/admin'

function mountPage() {
    const pinia = createPinia()
    setActivePinia(pinia)
    return mount(AdminCars, {
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

describe('AdminCars', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        adminService.getCars.mockResolvedValue({
            cars: [
                {
                    id: 'car-42',
                    brand: 'Dacia',
                    model: 'Logan',
                    year: 2020,
                    plate_number: '12345-A-12',
                    agency: 'Demo Rent Cars',
                    city: 'Tangier',
                    daily_price: 300,
                    status: 'available',
                },
            ],
            meta: { current_page: 1, last_page: 1, total: 1 },
        })
    })

    it('affiche un lien vers la page du véhicule', async () => {
        const wrapper = mountPage()
        await flushPromises()
        const link = wrapper.find('.car-link')
        expect(link.attributes('href')).toBe('/cars/car-42/reserve')
        expect(wrapper.text()).toContain('Dacia Logan')
    })

    it('ouvre la page du véhicule au clic sur la ligne', async () => {
        const wrapper = mountPage()
        await flushPromises()
        await wrapper.find('.row-link').trigger('click')
        expect(push).toHaveBeenCalledWith('/cars/car-42/reserve')
    })
})
