import api from '@/services/api'

export default {
    createReservation(payload) {
        return api.post('/reservations', payload)
    },

    getReservations() {
        return api.get('/reservations')
    },

    getReservation(id) {
        return api.get(`/reservations/${id}`)
    },

    checkAvailability(carId, params) {
        return api.get(`/cars/${carId}/availability`, { params })
    },

    /** Client: change dates and/or pickup & return points (pending / confirmed, unpaid) */
    updateReservation(id, payload) {
        return api.put(`/reservations/${id}`, payload)
    },

    /** Client: open a dispute on a picked-up reservation */
    disputeReservation(id) {
        return api.patch(`/reservations/${id}/dispute`)
    },

    cancelReservation(id) {
        return api.patch(`/reservations/${id}/cancel`)
    },

    confirmPickupClient(id) {
        return api.patch(`/reservations/${id}/pickup/confirm-client`)
    },

    confirmPickupAgency(id) {
        return api.patch(`/reservations/${id}/pickup/confirm-agency`)
    },

    confirmReturnClient(id) {
        return api.patch(`/reservations/${id}/return/confirm-client`)
    },

    confirmReturnAgency(id) {
        return api.patch(`/reservations/${id}/return/confirm-agency`)
    },
}