import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/services/admin', () => ({
    default: {
        getDashboard: vi.fn(),
        approveAgency: vi.fn(),
        rejectAgency: vi.fn(),
    },
}))

import AdminDashboard from '../AdminDashboard.vue'
import adminService from '@/services/admin'

const dashboardPayload = {
    stats: {
        users: 43,
        active_agencies: 12,
        cars: 82,
        reservations_this_month: 28,
        platform_revenue: 184000,
        avg_rating: 4.9,
    },
    pending_agencies: [
        {
            id: 'ag-1',
            name: 'Atlas Cars Casablanca',
            city: 'Casablanca',
            manager: 'Yassine B.',
            reference: 'AG-2041',
            email: 'contact@atlas.ma',
            phone: '+212522000001',
            address: '12 Rue Atlas',
            owner_email: 'yassine@atlas.ma',
            commission_rate: 15,
            cars_count: 4,
            points_count: 1,
        },
        {
            id: 'ag-2',
            name: 'Riviera Rent',
            city: 'Marrakech',
            manager: 'Samira D.',
            reference: 'AG-2042',
            checks: [
                { label: 'Email', valid: true },
                { label: 'Téléphone', valid: false },
                { label: 'Adresse', valid: true },
                { label: 'Ville', valid: true },
            ],
        },
    ],
    recent_reservations: [
        {
            id: 'r1',
            reference: 'RES-9042',
            client: 'Youssef T.',
            agency: 'Luxury Auto',
            amount: 6000,
            commission: 480,
            status: 'confirmed',
        },
        {
            id: 'r2',
            reference: 'RES-9036',
            client: 'Nabil R.',
            agency: 'Premium Rent',
            amount: 1400,
            commission: 0,
            status: 'cancelled',
        },
    ],
    monthly_revenue: [
        { year: 2026, month: 4, label: 'Avr', revenue: 10 },
        { year: 2026, month: 5, label: 'Mai', revenue: 20 },
        { year: 2026, month: 6, label: 'Jui', revenue: 30 },
        { year: 2026, month: 7, label: 'Jul', revenue: 40 },
        { year: 2026, month: 8, label: 'Aou', revenue: 50 },
        { year: 2026, month: 9, label: 'Sep', revenue: 184000 },
    ],
    reports: [
        {
            id: 'd1',
            reference: 'RES-DISP',
            title: 'Litige sur la réservation',
            car: 'Tesla Model 3',
            client: 'Mohammed A.',
            status: 'disputed',
            created_at: new Date().toISOString(),
        },
    ],
}

function mountPage() {
    const pinia = createPinia()
    setActivePinia(pinia)
    return mount(AdminDashboard, {
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

describe('AdminDashboard', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        adminService.getDashboard.mockResolvedValue(dashboardPayload)
        adminService.approveAgency.mockResolvedValue({ message: 'ok' })
        adminService.rejectAgency.mockResolvedValue({ message: 'ok' })
    })

    it('charge le tableau de bord depuis l’API', async () => {
        mountPage()
        await flushPromises()
        expect(adminService.getDashboard).toHaveBeenCalledTimes(1)
    })

    it('affiche les KPI réels', async () => {
        const wrapper = mountPage()
        await flushPromises()
        expect(wrapper.text()).toContain('43')
        expect(wrapper.text()).toContain('12')
        expect(wrapper.text()).toContain('82')
        expect(wrapper.text()).toContain('28')
        expect(wrapper.text()).toContain('184k')
        expect(wrapper.text()).toContain('4.9 ★')
        expect(wrapper.text()).toContain('Revenu plateforme')
        expect(wrapper.text()).toContain('En attente')
        expect(wrapper.get('[data-testid="kpi-agencies"]').attributes('href')).toBe('/admin/agencies')
        expect(wrapper.get('[data-testid="kpi-pending-agencies"]').attributes('href')).toBe('/admin/agencies/validation')
        expect(wrapper.get('[data-testid="link-all-agencies"]').attributes('href')).toBe('/admin/agencies')
        expect(wrapper.get('[data-testid="link-pending-agencies"]').attributes('href')).toBe('/admin/agencies/validation')
    })

    it('affiche les agences en attente et les dernières réservations', async () => {
        const wrapper = mountPage()
        await flushPromises()
        expect(wrapper.findAll('.req-card')).toHaveLength(2)
        expect(wrapper.text()).toContain('Atlas Cars Casablanca')
        expect(wrapper.text()).toContain('RES-9042')
        expect(wrapper.text()).toContain('Youssef T.')
        expect(wrapper.text()).toContain('Luxury Auto')
        expect(wrapper.text()).toContain('Confirmé')
        expect(wrapper.text()).toContain('Annulé')
        expect(wrapper.text()).toContain('Tesla Model 3')
    })

    it('retire une agence après validation API', async () => {
        const wrapper = mountPage()
        await flushPromises()
        await wrapper.find('.req-card .btn-primary').trigger('click')
        await flushPromises()
        expect(adminService.approveAgency).toHaveBeenCalledWith('ag-1')
        expect(wrapper.findAll('.req-card')).toHaveLength(1)
        expect(wrapper.find('.pending-badge').text()).toBe('1')
    })

    it('affiche une erreur si le chargement échoue', async () => {
        adminService.getDashboard.mockRejectedValueOnce({
            message: 'Le serveur est indisponible. Vérifiez votre connexion.',
        })
        const wrapper = mountPage()
        await flushPromises()
        expect(wrapper.text()).toContain('Le serveur est indisponible')
    })
})
