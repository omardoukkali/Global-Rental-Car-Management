import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ReservationForm from '../ReservationForm.vue'
import reservationsService from '@/services/reservations'
import carsService from '@/services/cars'

vi.mock('@/services/reservations', () => ({
  default: {
    createReservation: vi.fn(),
    checkAvailability: vi.fn(),
  }
}))

vi.mock('@/services/cars', () => ({
  default: {
    getCars: vi.fn(),
    getCar: vi.fn(),
  }
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
  }
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
    images: [{ url: 'http://example.com/clio.jpg', is_primary: true }]
  }

  beforeEach(() => {
    vi.clearAllMocks()
    carsService.getCars.mockResolvedValue({
      data: { cars: [mockCar] }
    })
    carsService.getCar.mockResolvedValue({
      data: { car: mockCar }
    })
    reservationsService.checkAvailability.mockResolvedValue({
      data: { available: true }
    })
  })

  it('affiche le formulaire et charge les informations de la voiture', async () => {
    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: {
        stubs: ['RouterLink']
      }
    })

    await flushPromises()

    expect(wrapper.text()).toContain('Formulaire de Réservation')
    expect(wrapper.text()).toContain('Renault Clio 5')
    expect(wrapper.text()).toContain('350')
  })

  it('calcule dynamiquement la durée et le prix total', async () => {
    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: {
        stubs: ['RouterLink']
      }
    })

    await flushPromises()

    const startInput = wrapper.find('#start-at')
    const endInput = wrapper.find('#end-at')

    await startInput.setValue('2026-09-10T10:00')
    await endInput.setValue('2026-09-13T10:00')

    await flushPromises()

    const durationDays = wrapper.find('[data-testid="duration-days"]')
    const totalPrice = wrapper.find('[data-testid="total-price"]')

    expect(durationDays.text()).toContain('3 jours')
    // 3 jours * 350 MAD = 1050 MAD
    expect(totalPrice.text()).toContain('1050')
  })

  it('affiche une erreur si la date de retour est antérieure à la date de départ', async () => {
    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: {
        stubs: ['RouterLink']
      }
    })

    await flushPromises()

    const startInput = wrapper.find('#start-at')
    const endInput = wrapper.find('#end-at')

    await startInput.setValue('2026-09-15T10:00')
    await endInput.setValue('2026-09-12T10:00')

    await flushPromises()

    const dateError = wrapper.find('[data-testid="date-error"]')
    expect(dateError.exists()).toBe(true)
    expect(dateError.text()).toContain('doit être ultérieure')
  })

  it('soumet la réservation avec succès et affiche la référence de confirmation', async () => {
    reservationsService.createReservation.mockResolvedValueOnce({
      data: {
        message: 'Reservation created successfully.',
        reservation: {
          id: 'res-abc-999',
          reference: 'RES-2026-001',
          total_amount: 1050,
          status: 'pending'
        }
      }
    })

    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: {
        stubs: ['RouterLink']
      }
    })

    await flushPromises()

    await wrapper.find('#start-at').setValue('2026-09-10T10:00')
    await wrapper.find('#end-at').setValue('2026-09-13T10:00')

    await flushPromises()

    const submitBtn = wrapper.find('[data-testid="submit-button"]')
    expect(submitBtn.attributes('disabled')).toBeUndefined()

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(reservationsService.createReservation).toHaveBeenCalledWith(
      expect.objectContaining({
        car_id: 'car-123',
        start_at: '2026-09-10T10:00',
        end_at: '2026-09-13T10:00'
      })
    )

    const successBanner = wrapper.find('[data-testid="success-banner"]')
    expect(successBanner.exists()).toBe(true)
    expect(wrapper.find('[data-testid="reservation-ref"]').text()).toBe('RES-2026-001')
  })

  it('affiche un message d\'erreur si la soumission API échoue', async () => {
    reservationsService.createReservation.mockRejectedValueOnce({
      response: {
        status: 422,
        data: { message: 'Car is already reserved for the selected period.' }
      }
    })

    const wrapper = mount(ReservationForm, {
      props: { carId: 'car-123' },
      global: {
        stubs: ['RouterLink']
      }
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
