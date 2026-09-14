import api from '@/services/api'

export default {
    getProfile() {
        return api.get('/agency/profile')
    },

    updateProfile(payload) {
        return api.put('/agency/profile', payload)
    },

    getAgencies(params = {}) {
        return api.get('/agencies', { params })
    },

    getAgency(id) {
        return api.get(`/agencies/${id}`)
    },

    createAgency(payload) {
        return api.post('/agencies', payload)
    },

    updateAgency(id, payload) {
        return api.put(`/agencies/${id}`, payload)
    },

    deleteAgency(id) {
        return api.delete(`/agencies/${id}`)
    },

    getPoints() {
        return api.get('/agency/points')
    },

    createPoint(payload) {
        return api.post('/agency/points', payload)
    },

    updatePoint(id, payload) {
        return api.put(`/agency/points/${id}`, payload)
    },

    togglePointStatus(id) {
        return api.patch(`/agency/points/${id}/toggle-status`)
    },
}
 