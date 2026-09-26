import api from '@/services/api'

export default {
    createPayment(reservationId) {
        return api.post('/payments', {
            reservation_id: reservationId,
        })
    },

    /** Client: every payment with its reservation and refund (GET /payments) */
    getPayments() {
        return api.get('/payments')
    },
}
