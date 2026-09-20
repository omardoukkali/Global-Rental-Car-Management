import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import AgencyPickupConfirm from '../AgencyPickupConfirm.vue'
import reservationsService from '@/services/reservations'

vi.mock('@/services/reservations', () => ({
  default: {
    confirmPickupAgency: vi.fn(),
  },
}))

vi.mock('vue-router', () => ({
  RouterLink: {
    template: '<a><slot /></a>',
  },
  useRoute: () => ({ path: '/agency/pickup' }),
}))

describe('AgencyPickupConfirm.vue (SCRUM-154)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('calls confirmPickupAgency with the reservation id', async () => {
    reservationsService.confirmPickupAgency.mockResolvedValueOnce({
      message: 'Pickup confirmed successfully.',
      reservation: {
        id: 'res-agency',
        status: 'picked_up',
        reference: 'RES-AG',
      },
    })

    const wrapper = mount(AgencyPickupConfirm, {
      global: { stubs: ['RouterLink'] },
    })

    await wrapper.find('[data-testid="reservation-id-input"]').setValue('res-agency')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(reservationsService.confirmPickupAgency).toHaveBeenCalledWith('res-agency')
    expect(wrapper.find('[data-testid="agency-pickup-success"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('picked_up')
  })

  it('shows an error when agency confirm fails', async () => {
    reservationsService.confirmPickupAgency.mockRejectedValueOnce({
      message: 'Only confirmed reservations can confirm pickup.',
    })

    const wrapper = mount(AgencyPickupConfirm, {
      global: { stubs: ['RouterLink'] },
    })

    await wrapper.find('[data-testid="reservation-id-input"]').setValue('bad-id')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.find('[data-testid="agency-pickup-error"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Only confirmed reservations')
  })
})
