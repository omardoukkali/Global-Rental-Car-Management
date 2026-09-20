import api from '@/services/api'

export default {
    /** Reviews written by the logged-in client */
    getMyReviews() {
        return api.get('/reviews')
    },

    /** payload: { reservation_id, car_rating, agency_rating, comment? } */
    createReview(payload) {
        return api.post('/reviews', payload)
    },

    updateReview(id, payload) {
        return api.put(`/reviews/${id}`, payload)
    },

    deleteReview(id) {
        return api.delete(`/reviews/${id}`)
    },
}
