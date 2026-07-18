import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useLanguage } from '../context/LanguageContext';
import api from '../services/api';
import { MapPin, Calendar, Star, Landmark, Info, CheckCircle2, ArrowLeft, User } from 'lucide-react';

const CarDetailPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user, showToast } = useAuth();
  const { t, isRtl } = useLanguage();

  const [car, setCar] = useState(null);
  const [reviews, setReviews] = useState([]);
  const [loading, setLoading] = useState(true);

  // Booking Form State
  const [startDate, setStartDate] = useState('');
  const [startTime, setStartTime] = useState('10:00');
  const [endDate, setEndDate] = useState('');
  const [endTime, setEndTime] = useState('10:00');
  const [bookingLoading, setBookingLoading] = useState(false);

  useEffect(() => {
    fetchCarDetails();
  }, [id]);

  const fetchCarDetails = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/cars/${id}`);
      setCar(response.data || response);

      // Fetch reviews
      const reviewsResponse = await api.get(`/cars/${id}/reviews`);
      setReviews(reviewsResponse.data || reviewsResponse || []);
    } catch (err) {
      console.error(err);
      showToast('Error loading car details', 'error');
      navigate('/');
    } finally {
      setLoading(false);
    }
  };

  // Helper: Calculate days and price
  const calculateBilling = () => {
    if (!startDate || !endDate) return { days: 0, total: 0 };
    
    const startStr = `${startDate}T${startTime}`;
    const endStr = `${endDate}T${endTime}`;
    const start = new Date(startStr);
    const end = new Date(endStr);

    if (isNaN(start.getTime()) || isNaN(end.getTime()) || start >= end) {
      return { days: 0, total: 0 };
    }

    const diffMs = end - start;
    const diffHours = diffMs / (1000 * 60 * 60);
    const days = Math.ceil(diffHours / 24);
    const total = days * (car?.price_per_day || 0);

    return { days, total };
  };

  const handleBooking = async (e) => {
    e.preventDefault();
    if (!user) {
      showToast('Please log in to make a reservation', 'error');
      navigate('/login');
      return;
    }

    if (user.role !== 'client') {
      showToast('Only clients can reserve cars', 'error');
      return;
    }

    const { days, total } = calculateBilling();
    if (days <= 0) {
      showToast('Please select valid pickup and return dates', 'error');
      return;
    }

    setBookingLoading(true);
    try {
      const start_date = `${startDate} ${startTime}`;
      const end_date = `${endDate} ${endTime}`;

      const bookingPayload = {
        car_id: car.id,
        start_date,
        end_date,
      };

      await api.post('/client/reservations', bookingPayload);
      showToast('Reservation created! Please confirm or pay on your dashboard.', 'success');
      navigate('/client/reservations');
    } catch (err) {
      console.error(err);
      showToast(err.message || 'Booking failed', 'error');
    } finally {
      setBookingLoading(false);
    }
  };

  const getCarImageUrl = (car) => {
    if (car?.images && car.images.length > 0) {
      const primaryImage = car.images.find(img => img.is_primary) || car.images[0];
      if (primaryImage.image_url) {
        return primaryImage.image_url.startsWith('http') 
          ? primaryImage.image_url 
          : `http://localhost:8000/storage/${primaryImage.image_url}`;
      }
    }
    const carType = car?.type?.toLowerCase() || 'sedan';
    if (carType === 'suv') return 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80';
    if (carType === 'sports') return 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80';
    return 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80';
  };

  const { days, total } = calculateBilling();

  if (loading) {
    return (
      <div className="flex-center" style={{ minHeight: '80vh' }}>
        <div className="spinner"></div>
      </div>
    );
  }

  if (!car) return null;

  return (
    <div className="container car-detail-wrapper fade-in">
      <Link to="/" className="back-link flex-center" style={{ justifyContent: 'flex-start', gap: '6px' }}>
        <ArrowLeft size={16} style={{ transform: isRtl ? 'rotate(180deg)' : 'none' }} /> {t('carDetail.back')}
      </Link>

      <div className="detail-layout">
        {/* Left Column: Media & Specs */}
        <div className="media-specs-column">
          <div className="car-gallery card no-hover">
            <img src={getCarImageUrl(car)} alt={`${car.brand} ${car.model}`} className="main-display-image" />
          </div>

          <div className="specs-details card mt-4 no-hover">
            <h2 className="detail-heading">{t('carDetail.specs')}</h2>
            <div className="specs-grid">
              <div className="spec-item">
                <span className="spec-label">{t('carDetail.brand')}</span>
                <span className="spec-value">{car.brand}</span>
              </div>
              <div className="spec-item">
                <span className="spec-label">{t('carDetail.model')}</span>
                <span className="spec-value">{car.model}</span>
              </div>
              <div className="spec-item">
                <span className="spec-label">{t('carDetail.year')}</span>
                <span className="spec-value">{car.year}</span>
              </div>
              <div className="spec-item">
                <span className="spec-label">{t('filter.gearbox')}</span>
                <span className="spec-value">
                  {car.transmission === 'automatic' ? t('filter.automatic') : t('filter.manual')}
                </span>
              </div>
              <div className="spec-item">
                <span className="spec-label">{t('carDetail.fuel')}</span>
                <span className="spec-value" style={{ textTransform: 'capitalize' }}>{car.fuel_type || 'Petrol'}</span>
              </div>
              <div className="spec-item">
                <span className="spec-label">{t('carDetail.class')}</span>
                <span className="spec-value" style={{ textTransform: 'capitalize' }}>{car.type}</span>
              </div>
            </div>
            {car.description && (
              <div className="car-desc mt-4">
                <h4 className="desc-title">{t('carDetail.description')}</h4>
                <p>{car.description}</p>
              </div>
            )}
          </div>

          {/* Reviews List */}
          <div className="reviews-section card mt-4 no-hover">
            <h2 className="detail-heading">{t('carDetail.reviews')}</h2>
            {reviews.length === 0 ? (
              <p className="text-muted">{t('carDetail.noReviews')}</p>
            ) : (
              <div className="reviews-list">
                {reviews.map((rev) => (
                  <div key={rev.id} className="review-card">
                    <div className="flex-between">
                      <div className="reviewer-info flex-center" style={{ gap: '8px' }}>
                        <div className="avatar-placeholder"><User size={14} /></div>
                        <strong>{rev.client?.first_name} {rev.client?.last_name}</strong>
                      </div>
                      <div className="review-rating flex-center" style={{ gap: '4px' }}>
                        <Star size={14} className="star-icon" />
                        <span>{rev.rating} / 5</span>
                      </div>
                    </div>
                    <p className="review-comment mt-2">{rev.comment}</p>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Price & Booking Form */}
        <div className="booking-column">
          <div className="card booking-card no-hover">
            <div className="price-header">
              <span className="price-label">{t('carDetail.pricePerDay')}</span>
              <h3>${car.price_per_day} <span className="price-term">/ {t('landing.day')}</span></h3>
            </div>

            <hr className="divider" />

            <form onSubmit={handleBooking} className="booking-form">
              <div className="form-group">
                <label className="form-label">{t('filter.pickup')}</label>
                <input
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="form-input"
                  required
                  min={new Date().toISOString().split('T')[0]}
                  disabled={bookingLoading}
                />
              </div>

              <div className="form-group">
                <label className="form-label">{t('filter.pickupTime')}</label>
                <input
                  type="time"
                  value={startTime}
                  onChange={(e) => setStartTime(e.target.value)}
                  className="form-input"
                  required
                  disabled={bookingLoading}
                />
              </div>

              <div className="form-group">
                <label className="form-label">{t('filter.return')}</label>
                <input
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="form-input"
                  required
                  min={startDate || new Date().toISOString().split('T')[0]}
                  disabled={bookingLoading}
                />
              </div>

              <div className="form-group">
                <label className="form-label">{t('filter.returnTime')}</label>
                <input
                  type="time"
                  value={endTime}
                  onChange={(e) => setEndTime(e.target.value)}
                  className="form-input"
                  required
                  disabled={bookingLoading}
                />
              </div>

              {days > 0 && (
                <div className="billing-summary fade-in">
                  <div className="flex-between summary-row">
                    <span>{isRtl ? 'المدة' : 'Duration'}</span>
                    <span>{days} {days === 1 ? t('landing.day') : (isRtl ? 'أيام' : 'days')}</span>
                  </div>
                  <div className="flex-between summary-row">
                    <span>{t('carDetail.pricePerDay')}</span>
                    <span>${car.price_per_day}</span>
                  </div>
                  <hr className="divider-sm" />
                  <div className="flex-between total-row">
                    <span>{isRtl ? 'المجموع المقدر' : 'Estimated Total'}</span>
                    <span>${total}</span>
                  </div>
                </div>
              )}

              {user?.role === 'agency_owner' ? (
                <div className="alert alert-error" style={{ fontSize: '0.85rem' }}>
                  <Info size={16} /> {t('carDetail.ownerCannotBook')}
                </div>
              ) : (
                <button
                  type="submit"
                  className="btn btn-primary w-full mt-4"
                  disabled={bookingLoading || (user && user.role !== 'client')}
                >
                  {bookingLoading ? '...' : user ? t('carDetail.bookNow') : t('carDetail.logInToBook')}
                </button>
              )}
            </form>

            <div className="safety-info mt-4">
              <div className="safety-item flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
                <CheckCircle2 size={16} className="text-primary" />
                <span>{t('carDetail.freeCancel')}</span>
              </div>
              <div className="safety-item flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
                <Landmark size={16} className="text-primary" />
                <span>{t('carDetail.rentedVia')} {car.agency?.name}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <style>{`
        .car-detail-wrapper {
          padding: 30px 24px 80px;
          text-align: left;
        }
        [dir="rtl"] .car-detail-wrapper {
          text-align: right;
        }
        .back-link {
          color: var(--text-secondary);
          margin-bottom: 24px;
          font-weight: 500;
          display: inline-flex;
        }
        .back-link:hover {
          color: var(--primary);
        }
        .detail-layout {
          display: grid;
          grid-template-columns: 2fr 1fr;
          gap: 32px;
        }
        @media (max-width: 900px) {
          .detail-layout {
            grid-template-columns: 1fr;
          }
        }
        .no-hover {
          transform: none !important;
          box-shadow: var(--glass-shadow), var(--shadow-sm) !important;
          border-color: var(--border) !important;
        }
        .main-display-image {
          width: 100%;
          max-height: 400px;
          object-fit: cover;
          border-radius: var(--radius-md);
        }
        .detail-heading {
          font-size: 1.4rem;
          margin-bottom: 20px;
          border-bottom: 2px solid var(--primary-light);
          padding-bottom: 8px;
        }
        .specs-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
          gap: 20px;
        }
        .spec-item {
          display: flex;
          flex-direction: column;
          border-bottom: 1px solid var(--border);
          padding-bottom: 8px;
        }
        .spec-label {
          font-size: 0.85rem;
          color: var(--text-secondary);
          font-family: var(--font-heading);
          font-weight: 600;
        }
        .spec-value {
          font-weight: 600;
          color: var(--text-primary);
          margin-top: 2px;
        }
        .car-desc p {
          color: var(--text-secondary);
        }
        .desc-title {
          font-size: 1rem;
          margin-bottom: 8px;
        }
        .reviews-list {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }
        .review-card {
          padding: 16px;
          border: 1px solid var(--border);
          border-radius: var(--radius-sm);
          background: rgba(255, 255, 255, 0.02);
        }
        .avatar-placeholder {
          width: 24px;
          height: 24px;
          background: var(--border);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--text-secondary);
        }
        .review-comment {
          color: var(--text-secondary);
          font-size: 0.95rem;
        }
        .price-header {
          display: flex;
          flex-direction: column;
          align-items: flex-start;
        }
        [dir="rtl"] .price-header {
          align-items: flex-end;
        }
        .price-label {
          font-size: 0.85rem;
          color: var(--text-secondary);
          font-weight: 600;
          font-family: var(--font-heading);
        }
        .price-term {
          font-size: 1rem;
          color: var(--text-secondary);
          font-weight: 400;
        }
        .divider {
          margin: 16px 0;
          border: 0;
          border-top: 1px solid var(--border);
        }
        .divider-sm {
          margin: 8px 0;
          border: 0;
          border-top: 1px solid var(--border);
        }
        .billing-summary {
          background: var(--primary-light);
          padding: 12px 16px;
          border-radius: var(--radius-sm);
          margin-top: 16px;
        }
        .summary-row {
          font-size: 0.85rem;
          color: var(--text-secondary);
          margin-bottom: 4px;
        }
        .total-row {
          font-weight: 700;
          color: var(--text-primary);
        }
        .safety-info {
          display: flex;
          flex-direction: column;
          gap: 8px;
          font-size: 0.85rem;
          color: var(--text-secondary);
        }
        .spinner {
          width: 48px;
          height: 48px;
          border: 4px solid var(--border);
          border-top: 4px solid var(--primary);
          border-radius: 50%;
          animation: spin 1s linear infinite;
        }
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
};

export default CarDetailPage;
