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
        return Promise.reject({
            message: err.response?.data?.message || 'Une erreur est survenue.',
            errors: err.response?.data?.errors || null,
            status: err.response?.status,
        })
    }
)

export default api