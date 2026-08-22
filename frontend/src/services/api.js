import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
})

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

api.interceptors.response.use(
    (res) => res.data,
    (err) => {
        const status = err.response?.status
        const responseMessage = err.response?.data?.message
        let message = responseMessage || 'Une erreur est survenue.'

        if (!responseMessage) {
            if (!err.response) message = 'Le serveur est indisponible. Vérifiez votre connexion.'
            else if (status === 401) message = 'Identifiants invalides.'
            else if (status === 403) message = 'Vous n’êtes pas autorisé à effectuer cette action.'
            else if (status >= 500) message = 'Le serveur rencontre un problème. Réessayez plus tard.'
        }

        return Promise.reject({
            message,
            errors: err.response?.data?.errors || null,
            status,
        })
    }
)

export default api