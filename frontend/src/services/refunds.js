import api from '@/services/api'

/**
 * Refunds.
 *
 * Client side, a refund is created by the API when a paid reservation is cancelled
 * (PATCH /reservations/:id/cancel): 100 % processed automatically when the pickup is
 * at least 24 h away, otherwise a 50 % refund is left *pending* for the agency to decide.
 * Agencies see their refunds here and settle the pending ones (50 % → 100 %).
 */
export default {
    /** Agency: refunds of the agency, optional `status` filter (pending, processing, processed, failed) */
    getAgencyRefunds(params = {}) {
        return api.get('/agency/refunds', { params })
    },

    /** Agency: settle a pending refund with the final percentage (50–100) */
    decideRefund(id, payload) {
        return api.patch(`/refunds/${id}/decision`, payload)
    },

    /** Agency: refund a paid reservation on its own initiative (not picked up yet) */
    createRefund(payload) {
        return api.post('/refunds', payload)
    },
}
