import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/services/admin', () => ({
    default: {
        getUsers: vi.fn(),
        getAgencies: vi.fn(),
    },
}))

import AdminUsers from '../AdminUsers.vue'
import adminService from '@/services/admin'

function mountPage() {
    const pinia = createPinia()
    setActivePinia(pinia)
    return mount(AdminUsers, {
        global: {
            plugins: [pinia],
            stubs: {
                RouterLink: true,
                AdminLayout: { template: '<div><slot /></div>' },
            },
        },
    })
}

describe('AdminUsers', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        adminService.getUsers.mockResolvedValue({
            users: [
                {
                    id: 'u1',
                    name: 'Demo Client',
                    email: 'client@example.com',
                    phone: '+212600000001',
                    role: 'client',
                    status: 'active',
                },
            ],
            meta: { current_page: 1, last_page: 1, total: 1 },
        })
    })

    it('charge et affiche les utilisateurs', async () => {
        const wrapper = mountPage()
        await flushPromises()
        expect(adminService.getUsers).toHaveBeenCalled()
        expect(wrapper.text()).toContain('Demo Client')
        expect(wrapper.text()).toContain('client@example.com')
        expect(wrapper.text()).toContain('Client')
        expect(wrapper.text()).toContain('Actif')
    })
})
