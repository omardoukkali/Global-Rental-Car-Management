import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ClientReservations from '../ClientReservations.vue'
import reservationsService from '@/services/reservations'

vi.mock('@/services/reservations', () => ({
  default: {
    getReservations: vi.fn(),
    cancelReservation: vi.fn(),
    confirmPickupClient: vi.fn(),
    confirmReturnClient: vi.fn(),
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

  it('shows pickup button when reservation is confirmed and client has not confirmed', async () => {
    reservationsService.getReservations.mockResolvedValueOnce({
      reservations: [
        {
          id: 'res-pickup',
          reference: 'RES-PICKUP',
          status: 'confirmed',
          client_pickup_confirmed_at: null,
          agency_pickup_confirmed_at: null,
          start_at: '2026-09-10T10:00:00.000000Z',
          end_at: '2026-09-12T10:00:00.000000Z',
          total_amount: '500.00',
          car: { brand: 'Toyota', model: 'Yaris', year: 2023, images: [] },
          agency: { name: 'Atlas Cars' },
        },
      ],
    })

    const wrapper = mount(ClientReservations, {
      global: { stubs: ['RouterLink'] },
    })
    await flushPromises()

    expect(wrapper.find('[data-testid="confirm-pickup-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="waiting-agency-pickup"]').exists()).toBe(false)
  })

  it('calls confirmPickupClient and shows waiting-agency state', async () => {
    reservationsService.getReservations.mockResolvedValueOnce({
      reservations: [
        {
          id: 'res-pickup',
          reference: 'RES-PICKUP',
          status: 'confirmed',
          client_pickup_confirmed_at: null,
          agency_pickup_confirmed_at: null,
          start_at: '2026-09-10T10:00:00.000000Z',
          end_at: '2026-09-12T10:00:00.000000Z',
          total_amount: '500.00',
          car: { brand: 'Toyota', model: 'Yaris', year: 2023, images: [] },
          agency: { name: 'Atlas Cars' },
        },
      ],
    })
    reservationsService.confirmPickupClient.mockResolvedValueOnce({
      reservation: {
        id: 'res-pickup',
        reference: 'RES-PICKUP',
        status: 'confirmed',
        client_pickup_confirmed_at: '2026-09-14T12:00:00.000000Z',
        agency_pickup_confirmed_at: null,
        start_at: '2026-09-10T10:00:00.000000Z',
        end_at: '2026-09-12T10:00:00.000000Z',
        total_amount: '500.00',
        car: { brand: 'Toyota', model: 'Yaris', year: 2023, images: [] },
        agency: { name: 'Atlas Cars' },
      },
    })

    const wrapper = mount(ClientReservations, {
      global: { stubs: ['RouterLink'] },
    })
    await flushPromises()

    await wrapper.find('[data-testid="confirm-pickup-button"]').trigger('click')
    await flushPromises()

    expect(reservationsService.confirmPickupClient).toHaveBeenCalledWith('res-pickup')
    expect(wrapper.find('[data-testid="confirm-pickup-button"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="waiting-agency-pickup"]').exists()).toBe(true)
  })

  it('shows return button when status is picked_up and client has not confirmed return', async () => {
    reservationsService.getReservations.mockResolvedValueOnce({
      reservations: [
        {
          id: 'res-return',
          reference: 'RES-RETURN',
          status: 'picked_up',
          client_return_confirmed_at: null,
          agency_return_confirmed_at: null,
          start_at: '2026-09-10T10:00:00.000000Z',
          end_at: '2026-09-12T10:00:00.000000Z',
          total_amount: '500.00',
          car: { brand: 'Toyota', model: 'Yaris', year: 2023, images: [] },
          agency: { name: 'Atlas Cars' },
        },
      ],
    })

    const wrapper = mount(ClientReservations, {
      global: { stubs: ['RouterLink'] },
    })
    await flushPromises()

    expect(wrapper.find('[data-testid="confirm-return-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="waiting-agency-return"]').exists()).toBe(false)
  })

  it('calls confirmReturnClient and shows waiting-agency-return state', async () => {
    reservationsService.getReservations.mockResolvedValueOnce({
      reservations: [
        {
          id: 'res-return',
          reference: 'RES-RETURN',
          status: 'picked_up',
          client_return_confirmed_at: null,
          agency_return_confirmed_at: null,
          start_at: '2026-09-10T10:00:00.000000Z',
          end_at: '2026-09-12T10:00:00.000000Z',
          total_amount: '500.00',
          car: { brand: 'Toyota', model: 'Yaris', year: 2023, images: [] },
          agency: { name: 'Atlas Cars' },
        },
      ],
    })
    reservationsService.confirmReturnClient.mockResolvedValueOnce({
      reservation: {
        id: 'res-return',
        reference: 'RES-RETURN',
        status: 'picked_up',
        client_return_confirmed_at: '2026-09-14T15:00:00.000000Z',
        agency_return_confirmed_at: null,
        start_at: '2026-09-10T10:00:00.000000Z',
        end_at: '2026-09-12T10:00:00.000000Z',
        total_amount: '500.00',
        car: { brand: 'Toyota', model: 'Yaris', year: 2023, images: [] },
        agency: { name: 'Atlas Cars' },
      },
    })

    const wrapper = mount(ClientReservations, {
      global: { stubs: ['RouterLink'] },
    })
    await flushPromises()

    await wrapper.find('[data-testid="confirm-return-button"]').trigger('click')
    await flushPromises()

    expect(reservationsService.confirmReturnClient).toHaveBeenCalledWith('res-return')
    expect(wrapper.find('[data-testid="confirm-return-button"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="waiting-agency-return"]').exists()).toBe(true)
  })
})
