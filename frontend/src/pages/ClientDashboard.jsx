import React, { useState, useEffect } from 'react';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';
import { useLanguage } from '../context/LanguageContext';
import { Calendar, DollarSign, FileText, CheckCircle2, AlertTriangle, Star, Trash2 } from 'lucide-react';

const ClientDashboard = () => {
  const { user, showToast } = useAuth();
  const { t, language, isRtl } = useLanguage();
  const [reservations, setReservations] = useState([]);
  const [loading, setLoading] = useState(true);

  // Review Modal State
  const [reviewModalOpen, setReviewModalOpen] = useState(false);
  const [selectedRes, setSelectedRes] = useState(null);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [submittingReview, setSubmittingReview] = useState(false);

  // Virtual Payment State
  const [paymentLoading, setPaymentLoading] = useState(false);

  useEffect(() => {
    fetchReservations();
  }, []);

  const fetchReservations = async () => {
    setLoading(true);
    try {
      const response = await api.get('/client/reservations');
      setReservations(response.data || response || []);
    } catch (err) {
      console.error(err);
      showToast('Error loading reservations', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handlePayment = async (reservationId) => {
    if (!window.confirm(isRtl ? 'تأكيد دفع مبلغ الحجز افتراضياً؟' : (language === 'fr' ? 'Confirmer le paiement virtuel ?' : 'Confirm virtual card checkout?'))) return;
    setPaymentLoading(true);
    try {
      await api.post(`/client/reservations/${reservationId}/pay`);
      showToast(isRtl ? 'تم الدفع بنجاح! تم تحويل المبالغ للضمان.' : (language === 'fr' ? 'Paiement réussi ! Mises sous séquestre.' : 'Payment successful! Funds held in escrow.'), 'success');
      fetchReservations();
    } catch (err) {
      showToast(err.message || 'Payment failed', 'error');
    } finally {
      setPaymentLoading(false);
    }
  };

  const handleCancel = async (reservationId) => {
    if (!window.confirm(isRtl ? 'هل أنت متأكد من إلغاء هذا الحجز؟' : (language === 'fr' ? 'Annuler cette réservation ?' : 'Are you sure you want to cancel this booking?'))) return;
    try {
      await api.delete(`/client/reservations/${reservationId}`);
      showToast(isRtl ? 'تم إلغاء الحجز ومعالجة المستردات بنجاح!' : (language === 'fr' ? 'Réservation annulée avec succès !' : 'Reservation cancelled successfully!'), 'success');
      fetchReservations();
    } catch (err) {
      showToast(err.message || 'Cancellation failed', 'error');
    }
  };

  const openReviewModal = (res) => {
    setSelectedRes(res);
    setRating(5);
    setComment('');
    setReviewModalOpen(true);
  };

  const handleReviewSubmit = async (e) => {
    e.preventDefault();
    if (!comment.trim()) return;

    setSubmittingReview(true);
    try {
      await api.post('/client/reviews', {
        car_id: selectedRes.car_id,
        reservation_id: selectedRes.id,
        rating,
        comment,
      });
      showToast(isRtl ? 'شكرًا لك! تم تسجيل تقييمك بنجاح.' : (language === 'fr' ? 'Merci ! Votre avis a été enregistré.' : 'Thank you! Review submitted successfully.'), 'success');
      setReviewModalOpen(false);
      fetchReservations();
    } catch (err) {
      showToast(err.message || 'Review failed', 'error');
    } finally {
      setSubmittingReview(false);
    }
  };

  // Safe fallback layout text
  const refHeader = isRtl ? 'المرجع' : (language === 'fr' ? 'Référence' : 'Reference');
  const carHeader = isRtl ? 'السيارة' : (language === 'fr' ? 'Voiture' : 'Car');
  const pickupHeader = isRtl ? 'الاستلام' : (language === 'fr' ? 'Départ' : 'Pickup');
  const returnHeader = isRtl ? 'الإرجاع' : (language === 'fr' ? 'Retour' : 'Return');
  const costHeader = isRtl ? 'التكلفة الإجمالية' : (language === 'fr' ? 'Coût total' : 'Total Cost');
  const statusHeader = isRtl ? 'الحالة' : (language === 'fr' ? 'Statut' : 'Status');
  const actionsHeader = isRtl ? 'إجراءات' : (language === 'fr' ? 'Actions' : 'Actions');

  return (
    <div className="container client-dashboard-wrapper fade-in">
      <h2>{t('client.title')}</h2>
      <p className="text-secondary">{t('client.sub')}</p>

      {loading ? (
        <div className="flex-center" style={{ minHeight: '300px' }}>
          <div className="spinner"></div>
        </div>
      ) : reservations.length === 0 ? (
        <div className="card text-center" style={{ padding: '60px', marginTop: '30px' }}>
          <FileText size={40} className="text-muted" style={{ margin: '0 auto 16px' }} />
          <h4>{t('client.noBookings')}</h4>
          <p className="text-secondary">{t('client.noBookingsSub')}</p>
        </div>
      ) : (
        <div className="bookings-table-wrapper card mt-4 no-hover">
          <table className="dashboard-table">
            <thead>
              <tr style={{ textAlign: isRtl ? 'right' : 'left' }}>
                <th>{refHeader}</th>
                <th>{carHeader}</th>
                <th>{pickupHeader}</th>
                <th>{returnHeader}</th>
                <th>{costHeader}</th>
                <th>{statusHeader}</th>
                <th style={{ textAlign: isRtl ? 'left' : 'right' }}>{actionsHeader}</th>
              </tr>
            </thead>
            <tbody>
              {reservations.map((res) => (
                <tr key={res.id}>
                  <td className="table-ref">{res.reference_number}</td>
                  <td>
                    <strong>
                      {res.car ? `${res.car.brand} ${res.car.model}` : 'Deleted Car'}
                    </strong>
                  </td>
                  <td>{new Date(res.start_date).toLocaleDateString()}</td>
                  <td>{new Date(res.end_date).toLocaleDateString()}</td>
                  <td>
                    <strong>${parseFloat(res.total_price).toFixed(2)}</strong>
                  </td>
                  <td>
                    <span className={`badge badge-${res.status}`}>{res.status}</span>
                  </td>
                  <td style={{ textAlign: isRtl ? 'left' : 'right' }}>
                    <div className="table-actions" style={{ justifyContent: isRtl ? 'flex-start' : 'flex-end', gap: '8px' }}>
                      {res.status === 'pending' && (
                        <>
                          <button
                            onClick={() => handlePayment(res.id)}
                            className="btn btn-primary btn-sm flex-center"
                            disabled={paymentLoading}
                          >
                            <DollarSign size={14} /> {t('client.payNow')}
                          </button>
                          <button
                            onClick={() => handleCancel(res.id)}
                            className="btn btn-secondary btn-sm text-danger"
                          >
                            {t('client.cancel')}
                          </button>
                        </>
                      )}

                      {res.status === 'paid' && (
                        <button
                          onClick={() => handleCancel(res.id)}
                          className="btn btn-secondary btn-sm text-danger"
                        >
                          {t('client.cancelRefund')}
                        </button>
                      )}

                      {res.status === 'completed' && !res.review && (
                        <button
                          onClick={() => openReviewModal(res)}
                          className="btn btn-secondary btn-sm flex-center"
                        >
                          <Star size={14} className="star-icon" /> {t('client.writeReview')}
                        </button>
                      )}

                      {res.status === 'completed' && res.review && (
                        <span className="badge badge-completed flex-center" style={{ gap: '4px' }}>
                          <CheckCircle2 size={12} /> {t('client.reviewed')}
                        </span>
                      )}

                      {res.status === 'cancelled' && res.refund && (
                        <div className="refund-summary-box text-left" style={{ fontSize: '0.8rem', opacity: 0.8 }}>
                          <div className="text-success" style={{ fontWeight: 600 }}>{t('client.refundedAlert')}</div>
                          <div>{t('client.refundedAmount')}: ${parseFloat(res.refund.refund_amount).toFixed(2)}</div>
                          {parseFloat(res.refund.cancellation_fee) > 0 && (
                            <div className="text-danger">{t('client.cancelFee')}: ${parseFloat(res.refund.cancellation_fee).toFixed(2)}</div>
                          )}
                        </div>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* LEAVE REVIEW MODAL */}
      {reviewModalOpen && selectedRes && (
        <div className="modal-overlay">
          <div className="modal-content" style={{ maxWidth: '500px' }}>
            <h3>{t('client.leaveReviewTitle')}</h3>
            <p className="text-secondary" style={{ fontSize: '0.85rem' }}>{t('client.leaveReviewSub')}</p>
            <hr className="divider" />

            <form onSubmit={handleReviewSubmit} style={{ textAlign: isRtl ? 'right' : 'left' }}>
              <div className="form-group">
                <label className="form-label">{t('client.ratingScore')}</label>
                <div className="stars-selector flex-center" style={{ gap: '8px', margin: '12px 0', justifyContent: isRtl ? 'flex-end' : 'flex-start' }}>
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button
                      key={star}
                      type="button"
                      onClick={() => setRating(star)}
                      style={{ background: 'none', border: 'none', cursor: 'pointer' }}
                    >
                      <Star
                        size={28}
                        className="star-icon"
                        style={{
                          fill: star <= rating ? 'var(--warning)' : 'none',
                          stroke: star <= rating ? 'var(--warning)' : 'var(--border)',
                          transition: 'all 0.15s ease',
                        }}
                      />
                    </button>
                  ))}
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">{t('client.comment')}</label>
                <textarea
                  value={comment}
                  onChange={(e) => setComment(e.target.value)}
                  className="form-input"
                  rows="4"
                  placeholder={isRtl ? 'اكتب رأيك هنا...' : (language === 'fr' ? 'Votre commentaire...' : 'Write your comment here...')}
                  required
                  style={{ resize: 'none' }}
                  disabled={submittingReview}
                />
              </div>

              <div className="flex-end" style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '24px' }}>
                <button
                  type="button"
                  onClick={() => setReviewModalOpen(false)}
                  className="btn btn-secondary btn-sm"
                  disabled={submittingReview}
                >
                  {isRtl ? 'إلغاء' : 'Cancel'}
                </button>
                <button
                  type="submit"
                  className="btn btn-primary btn-sm"
                  disabled={submittingReview}
                >
                  {submittingReview ? '...' : (isRtl ? 'تقديم التقييم' : 'Submit Review')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <style>{`
        .client-dashboard-wrapper {
          padding-top: 30px;
          padding-bottom: 80px;
          text-align: left;
        }
        [dir="rtl"] .client-dashboard-wrapper {
          text-align: right;
        }
        .no-hover {
          transform: none !important;
          box-shadow: var(--glass-shadow), var(--shadow-sm) !important;
          border-color: var(--border) !important;
        }
        .refund-summary-box {
          border-radius: var(--radius-sm);
          padding: 6px;
          background: rgba(0, 0, 0, 0.05);
          border: 1px solid var(--border);
        }
        .divider {
          margin: 16px 0;
          border: 0;
          border-top: 1px solid var(--border);
        }
      `}</style>
    </div>
  );
};

export default ClientDashboard;
