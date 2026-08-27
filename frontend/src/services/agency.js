import api from '@/services/api'

export default {
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
    }
} 