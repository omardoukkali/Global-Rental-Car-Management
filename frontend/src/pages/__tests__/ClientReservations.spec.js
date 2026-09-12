import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ClientReservations from '../ClientReservations.vue'
import reservationsService from '@/services/reservations'

vi.mock('@/services/reservations', () => ({
  default: {
    getReservations: vi.fn(),
    cancelReservation: vi.fn(),
  },
}))

vi.mock('vue-router', () => ({
  RouterLink: {
    template: '<a><slot /></a>',
  },
}))

describe('ClientReservations.vue (SCRUM-110)', () => {
  const mockReservations = [
    {
      id: 'res-1',
      reference: 'RES-AAA',
      status: 'pending',
      start_at: '2026-09-10T10:00:00.000000Z',
      end_at: '2026-09-12T10:00:00.000000Z',
      total_amount: '700.00',
      car: { brand: 'Renault', model: 'Clio', year: 2023, images: [] },
      agency: { name: 'Atlas Cars' },
    },
    {
      id: 'res-2',
      reference: 'RES-BBB',
      status: 'completed',
      start_at: '2026-08-01T10:00:00.000000Z',
      end_at: '2026-08-03T10:00:00.000000Z',
      total_amount: '900.00',
      car: { brand: 'Peugeot', model: '208', year: 2022, images: [] },
      agency: { name: 'Casa Rent' },
    },
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    reservationsService.getReservations.mockResolvedValue({
      reservations: mockReservations,
    })
  })

  it('charge et affiche la liste des réservations client', async () => {
    const wrapper = mount(ClientReservations, {
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    expect(reservationsService.getReservations).toHaveBeenCalled()
    expect(wrapper.find('[data-testid="reservations-list"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Renault Clio')
    expect(wrapper.text()).toContain('Atlas Cars')
    expect(wrapper.text()).toContain('En attente')
    expect(wrapper.text()).toContain('Terminée')
  })

  it('affiche un état vide si aucune réservation', async () => {
    reservationsService.getReservations.mockResolvedValueOnce({ reservations: [] })

    const wrapper = mount(ClientReservations, {
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('pas encore de réservation')
  })

  it('affiche une erreur si le chargement échoue', async () => {
    reservationsService.getReservations.mockRejectedValueOnce({
      message: 'Le serveur est indisponible. Vérifiez votre connexion.',
    })

    const wrapper = mount(ClientReservations, {
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    expect(wrapper.find('[data-testid="list-error"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('indisponible')
  })

  it('annule une réservation pending via l’API', async () => {
    reservationsService.cancelReservation.mockResolvedValueOnce({
      reservation: { ...mockReservations[0], status: 'cancelled' },
    })

    const wrapper = mount(ClientReservations, {
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    const cancelButtons = wrapper.findAll('[data-testid="cancel-button"]')
    expect(cancelButtons).toHaveLength(1)

    await cancelButtons[0].trigger('click')
    await flushPromises()

    expect(reservationsService.cancelReservation).toHaveBeenCalledWith('res-1')
    expect(wrapper.text()).toContain('Annulée')
  })
})
