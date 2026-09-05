import api from '@/services/api'

export default {
    // Récupérer toutes les voitures de l'agence
    getCars() {
        return api.get('/agency/cars')
    },

    // Récupérer une seule voiture par son ID
    getCar(id) {
        return api.get(`/agency/cars/${id}`)
    },

    // Désactiver une voiture
    disableCar(id) {
        return api.patch(`/agency/cars/${id}/disable`)
    }
}