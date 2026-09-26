import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import agencyService from '@/services/agency'

/**
 * Agency profile of the logged-in agency owner.
 * Used by the router to keep pending / rejected agencies on the waiting screen.
 */
export const useAgencyStore = defineStore('agency', () => {
    const profile = ref(null)
    const loaded = ref(false)
    const loading = ref(false)
    const error = ref('')

    const status = computed(() => profile.value?.status || null)
    const isApproved = computed(() => status.value === 'approved')
    const isPending = computed(() => status.value === 'pending')
    const isRejected = computed(() => status.value === 'rejected')

    function extractAgency(data) {
        return data?.agency || data?.data?.agency || (data && data.status ? data : null)
    }

    async function fetchProfile({ force = false } = {}) {
        if (loaded.value && !force) return profile.value
        if (loading.value) return profile.value
        loading.value = true
        error.value = ''
        try {
            const data = await agencyService.getProfile()
            profile.value = extractAgency(data)
            loaded.value = true
        } catch (err) {
            error.value = err?.message || 'Impossible de charger le profil de l’agence.'
            // 404 = owner without an agency profile: nothing to wait for
            if (err?.status === 404) {
                profile.value = null
                loaded.value = true
            }
        } finally {
            loading.value = false
        }
        return profile.value
    }

    function setProfile(agency) {
        profile.value = agency || null
        loaded.value = true
    }

    function reset() {
        profile.value = null
        loaded.value = false
        loading.value = false
        error.value = ''
    }

    return {
        profile,
        loaded,
        loading,
        error,
        status,
        isApproved,
        isPending,
        isRejected,
        fetchProfile,
        setProfile,
        reset,
    }
})
