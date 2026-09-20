import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import AgencyReturnConfirm from '../AgencyReturnConfirm.vue'
import reservationsService from '@/services/reservations'

vi.mock('@/services/reservations', () => ({
  default: {
    confirmReturnAgency: vi.fn(),
  },
}))

vi.mock('vue-router', () => ({
  RouterLink: {
    template: '<a><slot /></a>',
  },
  useRoute: () => ({ path: '/agency/return' }),
}))

describe('AgencyReturnConfirm.vue (SCRUM-155)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('calls confirmReturnAgency with the reservation id', async () => {
    reservationsService.confirmReturnAgency.mockResolvedValueOnce({
      message: 'Return confirmed successfully.',
      reservation: {
        id: 'res-agency',
        status: 'completed',
        reference: 'RES-AG',
      },
    })

    const wrapper = mount(AgencyReturnConfirm, {
      global: { stubs: ['RouterLink'] },
    })

    await wrapper.find('[data-testid="reservation-id-input"]').setValue('res-agency')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(reservationsService.confirmReturnAgency).toHaveBeenCalledWith('res-agency')
    expect(wrapper.find('[data-testid="agency-return-success"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('completed')
  })

  it('shows an error when agency return confirm fails', async () => {
    reservationsService.confirmReturnAgency.mockRejectedValueOnce({
      message: 'Only picked up reservations can confirm return.',
    })

    const wrapper = mount(AgencyReturnConfirm, {
      global: { stubs: ['RouterLink'] },
    })

    await wrapper.find('[data-testid="reservation-id-input"]').setValue('bad-id')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.find('[data-testid="agency-return-error"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Only picked up reservations')
  })
})
