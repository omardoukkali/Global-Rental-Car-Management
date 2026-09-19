import api from '@/services/api'

export default {
    createPayment(reservationId) {
        return api.post('/payments', {
            reservation_id: reservationId,
        })
    },
}
