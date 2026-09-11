import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ReservationForm from '../ReservationForm.vue'
import reservationsService from '@/services/reservations'
import carsService from '@/services/cars'

vi.mock('@/services/reservations', () => ({
  default: {
    createReservation: vi.fn(),
    checkAvailability: vi.fn(),
  },
}))

vi.mock('@/services/cars', () => ({
  default: {
    getPublicCars: vi.fn(),
    getPublicCar: vi.fn(),
    getCars: vi.fn(),
    getCar: vi.fn(),
  },
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
    back: vi.fn(),
  }),
  useRoute: () => ({
    params: {},
    query: {},
  }),
  RouterLink: {
    template: '<a><slot /></a>',
  },
}))

describe('ReservationForm.vue (SCRUM-109)', () => {
  const mockCar = {
    id: 'car-123',
    brand: 'Renault',
    model: 'Clio 5',
    year: 2023,
    daily_price: 350,
    transmission: 'automatique',
    energy_type: 'essence',
    images: [{ url: 'http://example.com/clio.jpg', is_primary: true }],
    agency: {
      name: 'Atlas Cars',
      agency_points: [
        {
          id: 'point-1',
          name: 'Aéroport CMN',
          address: 'Terminal 1',
          allows_pickup: true,
          allows_return: true,
          is_active: true,
        },
      ],
    },
  }

  beforeEach(() => {
    vi.clearAllMocks()
    carsService.getPublicCars.mockResolvedValue({ cars: [mockCar] })
    carsService.getPublicCar.mockResolvedValue({ car: mockCar })
    reservationsService.checkAvailability.mockResolvedValue({ available: true })
  })

  it('charge le catalogue public et affiche la voiture', async () => {
    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    expect(carsService.getPublicCars).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Renault Clio 5')
    expect(wrapper.text()).toContain('350')
    expect(wrapper.text()).toMatch(/Continuer|Choisir un véhicule/)
  })

  it('calcule dynamiquement la durée et le prix total', async () => {
    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    await wrapper.find('#start-at').setValue('2026-09-10T10:00')
    await wrapper.find('#end-at').setValue('2026-09-13T10:00')
    await flushPromises()

    expect(wrapper.find('[data-testid="duration-days"]').text()).toContain('3 jours')
    expect(wrapper.find('[data-testid="total-price"]').text().replace(/[^\d]/g, '')).toMatch(/1050/)
  })

  it('affiche une erreur si la date de retour est antérieure à la date de départ', async () => {
    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    await wrapper.find('#start-at').setValue('2026-09-15T10:00')
    await wrapper.find('#end-at').setValue('2026-09-12T10:00')
    await flushPromises()

    const dateError = wrapper.find('[data-testid="date-error"]')
    expect(dateError.exists()).toBe(true)
    expect(dateError.text()).toContain('doit être ultérieure')
  })

  it('soumet la réservation avec des points réels de l’agence', async () => {
    reservationsService.createReservation.mockResolvedValueOnce({
      message: 'Reservation created successfully.',
      reservation: {
        id: 'res-abc-999',
        reference: 'RES-2026-001',
        total_amount: 1050,
        status: 'pending',
      },
    })

    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    await wrapper.find('#start-at').setValue('2026-09-10T10:00')
    await wrapper.find('#end-at').setValue('2026-09-13T10:00')
    await flushPromises()

    expect(wrapper.find('[data-testid="submit-button"]').attributes('disabled')).toBeUndefined()

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(reservationsService.createReservation).toHaveBeenCalledWith(
      expect.objectContaining({
        car_id: 'car-123',
        pickup_point_id: 'point-1',
        return_point_id: 'point-1',
        start_at: '2026-09-10T10:00',
        end_at: '2026-09-13T10:00',
      })
    )

    expect(wrapper.find('[data-testid="success-banner"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="reservation-ref"]').text()).toBe('RES-2026-001')
  })

  it("affiche un message d'erreur si la soumission API échoue", async () => {
    reservationsService.createReservation.mockRejectedValueOnce({
      message: 'Car is already reserved for the selected period.',
      status: 422,
    })

    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: { stubs: ['RouterLink'] },
    })

    await flushPromises()

    await wrapper.find('#start-at').setValue('2026-09-10T10:00')
    await wrapper.find('#end-at').setValue('2026-09-13T10:00')
    await flushPromises()

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const errorAlert = wrapper.find('[data-testid="global-error"]')
    expect(errorAlert.exists()).toBe(true)
    expect(errorAlert.text()).toContain('Car is already reserved')
  })
})
