import { describe, it, expect, beforeEach, vi } from 'vitest'

vi.mock('@/services/api', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
    },
}))

import adminService from '@/services/admin'
import api from '@/services/api'

describe('adminService', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('getDashboard appelle GET /admin/dashboard', async () => {
        api.get.mockResolvedValueOnce({ stats: {} })
        await adminService.getDashboard()
        expect(api.get).toHaveBeenCalledWith('/admin/dashboard')
    })

    it('getAgencies transmet le filtre status', async () => {
        api.get.mockResolvedValueOnce({ agencies: [] })
        await adminService.getAgencies({ status: 'pending' })
        expect(api.get).toHaveBeenCalledWith('/admin/agencies', { params: { status: 'pending' } })
    })

    it('getAgency appelle GET /admin/agencies/:id', async () => {
        api.get.mockResolvedValueOnce({ agency: {} })
        await adminService.getAgency('ag-1')
        expect(api.get).toHaveBeenCalledWith('/admin/agencies/ag-1')
    })

    it('updateAgency appelle PATCH /admin/agencies/:id', async () => {
        api.patch.mockResolvedValueOnce({ agency: {} })
        await adminService.updateAgency('ag-1', { commission_rate: 18 })
        expect(api.patch).toHaveBeenCalledWith('/admin/agencies/ag-1', { commission_rate: 18 })
    })

    it('deleteAgency appelle DELETE /admin/agencies/:id', async () => {
        api.delete.mockResolvedValueOnce({ message: 'ok' })
        await adminService.deleteAgency('ag-1')
        expect(api.delete).toHaveBeenCalledWith('/admin/agencies/ag-1')
    })

    it('approveAgency appelle PATCH /admin/agencies/:id/approve', async () => {
        api.patch.mockResolvedValueOnce({ message: 'ok' })
        await adminService.approveAgency('ag-1')
        expect(api.patch).toHaveBeenCalledWith('/admin/agencies/ag-1/approve')
    })

    it('rejectAgency appelle PATCH /admin/agencies/:id/reject', async () => {
        api.patch.mockResolvedValueOnce({ message: 'ok' })
        await adminService.rejectAgency('ag-1')
        expect(api.patch).toHaveBeenCalledWith('/admin/agencies/ag-1/reject')
    })

    it('getUsers appelle GET /admin/users', async () => {
        api.get.mockResolvedValueOnce({ users: [] })
        await adminService.getUsers({ role: 'client' })
        expect(api.get).toHaveBeenCalledWith('/admin/users', { params: { role: 'client' } })
    })

    it('getCars appelle GET /admin/cars', async () => {
        api.get.mockResolvedValueOnce({ cars: [] })
        await adminService.getCars()
        expect(api.get).toHaveBeenCalledWith('/admin/cars', { params: {} })
    })

    it('getReservations appelle GET /admin/reservations', async () => {
        api.get.mockResolvedValueOnce({ reservations: [] })
        await adminService.getReservations({ status: 'confirmed' })
        expect(api.get).toHaveBeenCalledWith('/admin/reservations', { params: { status: 'confirmed' } })
    })

    it('getRevenue appelle GET /admin/revenue', async () => {
        api.get.mockResolvedValueOnce({ payments: [] })
        await adminService.getRevenue({ page: 2 })
        expect(api.get).toHaveBeenCalledWith('/admin/revenue', { params: { page: 2 } })
    })

    it('getReviews appelle GET /admin/reviews', async () => {
        api.get.mockResolvedValueOnce({ reviews: [] })
        await adminService.getReviews()
        expect(api.get).toHaveBeenCalledWith('/admin/reviews', { params: {} })
    })

    it('updateProfile appelle PATCH /admin/profile', async () => {
        api.patch.mockResolvedValueOnce({ user: {} })
        await adminService.updateProfile({ first_name: 'Admin' })
        expect(api.patch).toHaveBeenCalledWith('/admin/profile', { first_name: 'Admin' })
    })
})
