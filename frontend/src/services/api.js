import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
})

/** Requests whose 401/403 must NOT be treated as an expired session. */
const SESSION_EXEMPT = ['/login', '/logout', '/register/client', '/register/agency', '/forgot-password', '/reset-password']

/** Backend message sent when a suspended account (tokens revoked) is still used. */
const INACTIVE_ACCOUNT_MESSAGE = 'your account is not active'

function isSessionExempt(url = '') {
    return SESSION_EXEMPT.some((path) => url.includes(path))
}

/**
 * F-04: the token is expired/revoked (401) or the account was suspended (403).
 * Clear the session locally (never call POST /logout: it would 401 again) and
 * send the user to the login page with a reason.
 */
async function endSession(reason, fromPath) {
    localStorage.removeItem('token')
    localStorage.removeItem('user')

    try {
        const { useAuthStore } = await import('@/stores/auth')
        useAuthStore().clearSession()
    } catch {
        /* Pinia not ready (e.g. unit tests): storage is already cleared */
    }

    try {
        const { default: router } = await import('@/router')
        if (router.currentRoute.value.path === '/login') return
        const query = { [reason]: '1' }
        if (fromPath && fromPath !== '/' && !fromPath.startsWith('/login')) query.redirect = fromPath
        router.push({ path: '/login', query })
    } catch {
        window.location.assign(`/login?${reason}=1`)
    }
}

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
        const url = err.config?.url || ''
        let message = responseMessage || 'Une erreur est survenue.'

        if (!responseMessage) {
            if (!err.response) message = 'Le serveur est indisponible. Vérifiez votre connexion.'
            else if (status === 401) message = 'Identifiants invalides.'
            else if (status === 403) message = 'Vous n’êtes pas autorisé à effectuer cette action.'
            else if (status >= 500) message = 'Le serveur rencontre un problème. Réessayez plus tard.'
        }

        // Rate limiting (login 5/min, register 3/min, password reset): one wording everywhere.
        if (status === 429) message = 'Trop de tentatives. Réessayez dans une minute.'

        const hadToken = !!localStorage.getItem('token')
        const currentPath = typeof window !== 'undefined'
            ? window.location.pathname + window.location.search
            : ''

        // Token expired (24 h) or revoked: log out and go to /login?expired=1.
        if (status === 401 && hadToken && !isSessionExempt(url)) {
            message = 'Votre session a expiré. Veuillez vous reconnecter.'
            endSession('expired', currentPath)
        }

        // Account suspended by an admin: its tokens were deleted, treat as logout.
        if (
            status === 403 &&
            hadToken &&
            !isSessionExempt(url) &&
            String(responseMessage || '').toLowerCase().includes(INACTIVE_ACCOUNT_MESSAGE)
        ) {
            message = 'Votre compte a été désactivé. Contactez le support.'
            endSession('suspended')
        }

        return Promise.reject({
            message,
            errors: err.response?.data?.errors || null,
            status,
        })
    }
)

export default api
