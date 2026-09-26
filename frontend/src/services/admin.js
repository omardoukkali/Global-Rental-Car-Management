import api from '@/services/api'

export default {
    getDashboard() {
        return api.get('/admin/dashboard')
    },

    getAgencies(params = {}) {
        return api.get('/admin/agencies', { params })
    },

    getAgency(id) {
        return api.get(`/admin/agencies/${id}`)
    },

    updateAgency(id, payload) {
        return api.patch(`/admin/agencies/${id}`, payload)
    },

    deleteAgency(id) {
        return api.delete(`/admin/agencies/${id}`)
    },

    approveAgency(id) {
        return api.patch(`/admin/agencies/${id}/approve`)
    },

    rejectAgency(id) {
        return api.patch(`/admin/agencies/${id}/reject`)
    },

    getUsers(params = {}) {
        return api.get('/admin/users', { params })
    },

    getCars(params = {}) {
        return api.get('/admin/cars', { params })
    },

    getReservations(params = {}) {
        return api.get('/admin/reservations', { params })
    },

    getRevenue(params = {}) {
        return api.get('/admin/revenue', { params })
    },

    getReviews(params = {}) {
        return api.get('/admin/reviews', { params })
    },

    getProfile() {
        return api.get('/admin/profile')
    },

    updateProfile(payload) {
        return api.patch('/admin/profile', payload)
    },
}
