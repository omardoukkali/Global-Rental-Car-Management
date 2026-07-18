import React, { useState, useEffect } from 'react';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';
import { useLanguage } from '../context/LanguageContext';
import { Shield, Users, Landmark, DollarSign, Calendar, Star, Check, X, AlertTriangle } from 'lucide-react';

const AdminDashboard = () => {
  const { showToast } = useAuth();
  const { t, language, isRtl } = useLanguage();
  const [activeTab, setActiveTab] = useState('overview'); // 'overview', 'agencies', 'users', 'escrow', 'reviews'

  // Platform Metrics
  const [metrics, setMetrics] = useState({
    total_clients: 0,
    total_agencies: 0,
    total_cars: 0,
    total_revenue: 0,
    pending_escrow: 0,
  });

  // Domain lists
  const [pendingAgencies, setPendingAgencies] = useState([]);
  const [users, setUsers] = useState([]);
  const [escrows, setEscrows] = useState([]);
  const [reviews, setReviews] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchMetrics();
    loadTabContent();
  }, [activeTab]);

  const fetchMetrics = async () => {
    try {
      const response = await api.get('/admin/metrics');
      setMetrics(response.data || response);
    } catch (err) {
      console.error(err);
    }
  };

  const loadTabContent = async () => {
    setLoading(true);
    try {
      if (activeTab === 'agencies') {
        const response = await api.get('/admin/agencies/pending');
        setPendingAgencies(response.data || response || []);
      } else if (activeTab === 'users') {
        const response = await api.get('/admin/users');
        setUsers(response.data || response || []);
      } else if (activeTab === 'escrow') {
        const response = await api.get('/admin/payments/escrow');
        setEscrows(response.data || response || []);
      } else if (activeTab === 'reviews') {
        const response = await api.get('/admin/reviews');
        setReviews(response.data || response || []);
      }
    } catch (err) {
      console.error(err);
      showToast('Failed to load portal information', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleApproveAgency = async (agencyId) => {
    try {
      await api.post(`/admin/agencies/${agencyId}/approve`);
      showToast('Agency approved successfully!', 'success');
      loadTabContent();
      fetchMetrics();
    } catch (err) {
      showToast(err.message || 'Action failed', 'error');
    }
  };

  const handleRejectAgency = async (agencyId) => {
    if (!window.confirm('Reject this application?')) return;
    try {
      await api.post(`/admin/agencies/${agencyId}/reject`);
      showToast('Agency application rejected.', 'success');
      loadTabContent();
      fetchMetrics();
    } catch (err) {
      showToast(err.message || 'Action failed', 'error');
    }
  };

  const handleToggleUserBlock = async (userId, isBlocked) => {
    const action = isBlocked ? 'unblock' : 'block';
    try {
      await api.post(`/admin/users/${userId}/${action}`);
      showToast(`User ${action}ed successfully!`, 'success');
      loadTabContent();
    } catch (err) {
      showToast(err.message || 'Action failed', 'error');
    }
  };

  const handleReleaseEscrow = async (paymentId) => {
    if (!window.confirm('Release payment from escrow to the agency?')) return;
    try {
      await api.post(`/admin/payments/${paymentId}/release`);
      showToast('Escrow funds released to agency successfully!', 'success');
      loadTabContent();
      fetchMetrics();
    } catch (err) {
      showToast(err.message || 'Release failed', 'error');
    }
  };

  const handleDeleteReview = async (reviewId) => {
    if (!window.confirm('Remove this review permanently from the system?')) return;
    try {
      await api.delete(`/admin/reviews/${reviewId}`);
      showToast('Review removed successfully!', 'success');
      loadTabContent();
    } catch (err) {
      showToast(err.message || 'Delete failed', 'error');
    }
  };

  return (
    <div className="container admin-dashboard-wrapper fade-in">
      <h2>{t('admin.title')}</h2>
      <p className="text-secondary">{t('admin.sub')}</p>

      {/* Stats Cards Section */}
      <section className="stats-grid mt-4" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '20px' }}>
        <div className="card stats-card flex-center no-hover" style={{ justifyContent: 'flex-start', gap: '16px' }}>
          <div className="icon-wrapper bg-primary-light"><Users className="text-primary" /></div>
          <div>
            <span className="stats-label">{t('admin.totalClients')}</span>
            <h4>{metrics.total_clients}</h4>
          </div>
        </div>

        <div className="card stats-card flex-center no-hover" style={{ justifyContent: 'flex-start', gap: '16px' }}>
          <div className="icon-wrapper bg-primary-light"><Landmark className="text-primary" /></div>
          <div>
            <span className="stats-label">{t('admin.totalAgencies')}</span>
            <h4>{metrics.total_agencies}</h4>
          </div>
        </div>

        <div className="card stats-card flex-center no-hover" style={{ justifyContent: 'flex-start', gap: '16px' }}>
          <div className="icon-wrapper bg-primary-light"><DollarSign className="text-success" /></div>
          <div>
            <span className="stats-label">{t('admin.totalRevenue')}</span>
            <h4 className="text-success">${parseFloat(metrics.total_revenue).toFixed(2)}</h4>
          </div>
        </div>

        <div className="card stats-card flex-center no-hover" style={{ justifyContent: 'flex-start', gap: '16px' }}>
          <div className="icon-wrapper bg-primary-light"><AlertTriangle className="text-warning" /></div>
          <div>
            <span className="stats-label">{t('admin.pendingEscrows')}</span>
            <h4 className="text-warning">${parseFloat(metrics.pending_escrow).toFixed(2)}</h4>
          </div>
        </div>
      </section>

      {/* Admin Tab Navigation */}
      <div className="tab-navigation mt-4" style={{ direction: isRtl ? 'rtl' : 'ltr' }}>
        <button onClick={() => setActiveTab('overview')} className={`tab-link ${activeTab === 'overview' ? 'active-tab' : ''}`}>{t('admin.overview')}</button>
        <button onClick={() => setActiveTab('agencies')} className={`tab-link ${activeTab === 'agencies' ? 'active-tab' : ''}`}>{t('admin.agencies')}</button>
        <button onClick={() => setActiveTab('users')} className={`tab-link ${activeTab === 'users' ? 'active-tab' : ''}`}>{t('admin.usersRegistry')}</button>
        <button onClick={() => setActiveTab('escrow')} className={`tab-link ${activeTab === 'escrow' ? 'active-tab' : ''}`}>{t('admin.escrow')}</button>
        <button onClick={() => setActiveTab('reviews')} className={`tab-link ${activeTab === 'reviews' ? 'active-tab' : ''}`}>{t('admin.reviewsMod')}</button>
      </div>

      <div className="tab-pane mt-4">
        {loading ? (
          <div className="flex-center" style={{ minHeight: '300px' }}>
            <div className="spinner"></div>
          </div>
        ) : (
          <>
            {/* OVERVIEW PANEL */}
            {activeTab === 'overview' && (
              <div className="card no-hover overview-welcome fade-in" style={{ padding: '40px' }}>
                <Shield size={48} className="text-primary" style={{ margin: '0 auto 16px' }} />
                <h3>{isRtl ? 'بوابة إدارة المنصة' : (language === 'fr' ? 'Portail de gestion' : 'System Administration Portal')}</h3>
                <p className="text-secondary" style={{ maxWidth: '600px', margin: '8px auto 0' }}>
                  Use the navigation tabs above to manage incoming agency onboardings, moderate reviews, block/activate clients, and release funds from escrow once bookings end.
                </p>
              </div>
            )}

            {/* AGENCIES PANEL */}
            {activeTab === 'agencies' && (
              <div className="fade-in">
                <h3>{isRtl ? 'الطلبات المعلقة للوكالات' : (language === 'fr' ? "Demandes d'agences" : 'Pending Agency Applications')}</h3>
                {pendingAgencies.length === 0 ? (
                  <p className="text-muted mt-4">No agencies are currently pending registration review.</p>
                ) : (
                  <div className="agencies-approval-list mt-4">
                    {pendingAgencies.map((agency) => (
                      <div key={agency.id} className="card pending-agency-card flex-between fade-in" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                        <div>
                          <h4>{agency.name}</h4>
                          <div className="agency-meta-tags mt-2" style={{ display: 'flex', gap: '16px', fontSize: '0.85rem', color: 'var(--text-secondary)' }}>
                            <span><strong>City:</strong> {agency.city?.name}</span>
                            <span><strong>Phone:</strong> {agency.phone}</span>
                            <span><strong>Address:</strong> {agency.address}</span>
                          </div>
                          {agency.description && <p className="mt-2" style={{ fontStyle: 'italic', fontSize: '0.9rem' }}>"{agency.description}"</p>}
                        </div>
                        <div className="action-buttons flex-center" style={{ gap: '8px' }}>
                          <button onClick={() => handleApproveAgency(agency.id)} className="btn btn-primary btn-sm flex-center" style={{ gap: '4px' }}>
                            <Check size={14} /> {t('admin.approve')}
                          </button>
                          <button onClick={() => handleRejectAgency(agency.id)} className="btn btn-secondary btn-sm flex-center text-danger" style={{ gap: '4px' }}>
                            <X size={14} /> {t('admin.reject')}
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* USERS REGISTRY PANEL */}
            {activeTab === 'users' && (
              <div className="fade-in">
                <h3>{t('admin.usersRegistry')}</h3>
                <div className="bookings-table-wrapper card mt-4 no-hover">
                  <table className="dashboard-table">
                    <thead>
                      <tr style={{ textAlign: isRtl ? 'right' : 'left' }}>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style={{ textAlign: isRtl ? 'left' : 'right' }}>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {users.map((usr) => (
                        <tr key={usr.id}>
                          <td><strong>{usr.first_name} {usr.last_name}</strong></td>
                          <td>{usr.email}</td>
                          <td><span className="badge badge-picked-up" style={{ textTransform: 'capitalize' }}>{usr.role}</span></td>
                          <td>
                            <span className={`badge ${usr.is_blocked ? 'badge-cancelled' : 'badge-active'}`}>
                              {usr.is_blocked ? 'Blocked' : 'Active'}
                            </span>
                          </td>
                          <td style={{ textAlign: isRtl ? 'left' : 'right' }}>
                            <div className="table-actions" style={{ justifyContent: isRtl ? 'flex-start' : 'flex-end', gap: '8px' }}>
                              <button
                                onClick={() => handleToggleUserBlock(usr.id, usr.is_blocked)}
                                className={`btn btn-sm ${usr.is_blocked ? 'btn-primary' : 'btn-secondary text-danger'}`}
                              >
                                {usr.is_blocked ? t('admin.activate') : t('admin.suspend')}
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* ESCROW PORTAL PANEL */}
            {activeTab === 'escrow' && (
              <div className="fade-in">
                <h3>{t('admin.escrow')}</h3>
                {escrows.length === 0 ? (
                  <p className="text-muted mt-4">No escrow payments are pending release.</p>
                ) : (
                  <div className="bookings-table-wrapper card mt-4 no-hover">
                    <table className="dashboard-table">
                      <thead>
                        <tr style={{ textAlign: isRtl ? 'right' : 'left' }}>
                          <th>Reservation</th>
                          <th>Agency</th>
                          <th>Escrow Amount</th>
                          <th>Status</th>
                          <th style={{ textAlign: isRtl ? 'left' : 'right' }}>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {escrows.map((esc) => (
                          <tr key={esc.id}>
                            <td className="table-ref">{esc.reservation?.reference_number}</td>
                            <td>{esc.reservation?.car?.agency?.name || 'Agency'}</td>
                            <td className="text-success"><strong>${parseFloat(esc.agency_amount).toFixed(2)}</strong></td>
                            <td><span className="badge badge-pending">{esc.status}</span></td>
                            <td style={{ textAlign: isRtl ? 'left' : 'right' }}>
                              <button
                                onClick={() => handleReleaseEscrow(esc.id)}
                                className="btn btn-primary btn-sm"
                              >
                                {t('admin.releaseFunds')}
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {/* REVIEWS PANEL */}
            {activeTab === 'reviews' && (
              <div className="fade-in">
                <h3>{t('admin.reviewsMod')}</h3>
                {reviews.length === 0 ? (
                  <p className="text-muted mt-4">No reviews registered on the system yet.</p>
                ) : (
                  <div className="reviews-list mt-4">
                    {reviews.map((rev) => (
                      <div key={rev.id} className="card review-card mt-3" style={{ padding: '20px', textAlign: isRtl ? 'right' : 'left' }}>
                        <div className="flex-between">
                          <div>
                            <strong>{rev.client?.first_name} {rev.client?.last_name}</strong>
                            <span className="text-muted" style={{ margin: '0 8px', fontSize: '0.85rem' }}>
                              on {rev.car?.brand} {rev.car?.model} ({rev.car?.agency?.name})
                            </span>
                          </div>
                          <div className="review-rating flex-center" style={{ gap: '6px' }}>
                            <Star size={14} className="star-icon" style={{ fill: 'var(--warning)', stroke: 'var(--warning)' }} />
                            <span>{rev.rating} / 5</span>
                          </div>
                        </div>
                        <p className="mt-2 text-secondary">"{rev.comment}"</p>
                        <div className="flex-end mt-4" style={{ display: 'flex', justifyContent: 'flex-end' }}>
                          <button
                            onClick={() => handleDeleteReview(rev.id)}
                            className="btn btn-secondary btn-sm text-danger flex-center"
                            style={{ gap: '4px' }}
                          >
                            <Shield size={14} /> {t('admin.delete')}
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </>
        )}
      </div>

      <style>{`
        .admin-dashboard-wrapper {
          padding-top: 30px;
          padding-bottom: 80px;
          text-align: left;
        }
        [dir="rtl"] .admin-dashboard-wrapper {
          text-align: right;
        }
        .stats-card {
          padding: 20px;
        }
        .icon-wrapper {
          width: 48px;
          height: 48px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .stats-label {
          font-family: var(--font-heading);
          font-size: 0.8rem;
          font-weight: 600;
          color: var(--text-secondary);
          text-transform: uppercase;
        }
        .tab-navigation {
          display: flex;
          border-bottom: 1px solid var(--border);
          gap: 16px;
        }
        .tab-link {
          background: none;
          border: none;
          color: var(--text-secondary);
          padding: 12px 4px;
          cursor: pointer;
          font-family: var(--font-heading);
          font-weight: 500;
          font-size: 0.95rem;
          position: relative;
        }
        .tab-link:hover {
          color: var(--text-primary);
        }
        .tab-link.active-tab {
          color: var(--primary);
          font-weight: 600;
        }
        .tab-link.active-tab::after {
          content: '';
          position: absolute;
          bottom: -1px;
          left: 0;
          right: 0;
          height: 2px;
          background: var(--primary);
        }
        .no-hover {
          transform: none !important;
          box-shadow: var(--glass-shadow), var(--shadow-sm) !important;
          border-color: var(--border) !important;
        }
        .pending-agency-card {
          padding: 24px;
          margin-top: 16px;
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

export default AdminDashboard;
