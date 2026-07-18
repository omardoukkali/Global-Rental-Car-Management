import React, { useState, useEffect } from 'react';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';
import { useLanguage } from '../context/LanguageContext';
import { Landmark, Plus, Edit2, Trash2, Image, FileText, LayoutGrid, Calendar, DollarSign, RefreshCw, Upload, Star, Check } from 'lucide-react';

const OwnerDashboard = () => {
  const { user, refreshUser, showToast } = useAuth();
  const { t, language, isRtl } = useLanguage();
  const [cities, setCities] = useState([]);
  
  // Dashboard Tabs
  const [activeTab, setActiveTab] = useState('cars'); // 'cars', 'bookings', 'payments', 'settings'
  
  // Core lists
  const [cars, setCars] = useState([]);
  const [bookings, setBookings] = useState([]);
  const [payments, setPayments] = useState([]);
  const [loadingLists, setLoadingLists] = useState(false);

  // Agency Onboarding Form State
  const [agencyForm, setAgencyForm] = useState({
    name: '',
    agency_city: '',
    address: '',
    phone: '',
    description: '',
  });
  const [onboardingLoading, setOnboardingLoading] = useState(false);

  // Agency Edit Profile Form State
  const [profileForm, setProfileForm] = useState({
    name: '',
    address: '',
    phone: '',
    description: '',
  });

  // Car Modal Form State
  const [carModalOpen, setCarModalOpen] = useState(false);
  const [editingCar, setEditingCar] = useState(null); // null for create
  const [carForm, setCarForm] = useState({
    brand: '',
    model: '',
    year: '',
    price_per_day: '',
    transmission: 'automatic',
    type: 'sedan',
    description: '',
  });
  const [savingCar, setSavingCar] = useState(false);

  // Image Management Modal State
  const [imageModalOpen, setImageModalOpen] = useState(false);
  const [selectedCar, setSelectedCar] = useState(null);
  const [carImages, setCarImages] = useState([]);
  const [imageLoading, setImageLoading] = useState(false);

  useEffect(() => {
    fetchCities();
    if (user && user.agency) {
      setProfileForm({
        name: user.agency.name || '',
        address: user.agency.address || '',
        phone: user.agency.phone || '',
        description: user.agency.description || '',
      });
      loadTabContent();
    }
  }, [user]);

  useEffect(() => {
    if (user && user.agency && user.agency.status === 'approved') {
      loadTabContent();
    }
  }, [activeTab]);

  const fetchCities = async () => {
    try {
      const response = await api.get('/cities');
      setCities(response.data || (Array.isArray(response) ? response : []));
    } catch (err) {
      console.error(err);
    }
  };

  const loadTabContent = async () => {
    if (!user || !user.agency || user.agency.status !== 'approved') return;
    setLoadingLists(true);
    try {
      if (activeTab === 'cars') {
        const response = await api.get('/agency/cars');
        setCars(response.data || response || []);
      } else if (activeTab === 'bookings') {
        const response = await api.get('/agency/reservations');
        setBookings(response.data || response || []);
      } else if (activeTab === 'payments') {
        const response = await api.get('/agency/payments');
        setPayments(response.data || response || []);
      }
    } catch (err) {
      console.error(err);
      showToast('Error loading dashboard contents', 'error');
    } finally {
      setLoadingLists(false);
    }
  };

  const handleRegisterAgency = async (e) => {
    e.preventDefault();
    setOnboardingLoading(true);
    try {
      await api.post('/agencies', {
        name: agencyForm.name,
        city_id: agencyForm.agency_city,
        address: agencyForm.address,
        phone: agencyForm.phone,
        description: agencyForm.description,
      });
      showToast('Agency registered successfully! Pending admin approval.', 'success');
      await refreshUser();
    } catch (err) {
      showToast(err.message || 'Failed to register agency', 'error');
    } finally {
      setOnboardingLoading(false);
    }
  };

  const handleUpdateProfile = async (e) => {
    e.preventDefault();
    try {
      await api.put('/agency/profile', profileForm);
      showToast('Profile changes submitted for admin review.', 'success');
      await refreshUser();
    } catch (err) {
      showToast(err.message || 'Profile update failed', 'error');
    }
  };

  const openCarModal = (car = null) => {
    if (car) {
      setEditingCar(car);
      setCarForm({
        brand: car.brand || '',
        model: car.model || '',
        year: car.year || '',
        price_per_day: car.price_per_day || '',
        transmission: car.transmission || 'automatic',
        type: car.type || 'sedan',
        description: car.description || '',
      });
    } else {
      setEditingCar(null);
      setCarForm({
        brand: '',
        model: '',
        year: new Date().getFullYear(),
        price_per_day: '',
        transmission: 'automatic',
        type: 'sedan',
        description: '',
      });
    }
    setCarModalOpen(true);
  };

  const handleCarSubmit = async (e) => {
    e.preventDefault();
    setSavingCar(true);
    try {
      if (editingCar) {
        await api.put(`/agency/cars/${editingCar.id}`, carForm);
        showToast('Car updated successfully!', 'success');
      } else {
        await api.post('/agency/cars', carForm);
        showToast('Car added successfully!', 'success');
      }
      setCarModalOpen(false);
      loadTabContent();
    } catch (err) {
      showToast(err.message || 'Failed to save car', 'error');
    } finally {
      setSavingCar(false);
    }
  };

  const handleDeleteCar = async (carId) => {
    if (!window.confirm(isRtl ? 'هل أنت متأكد من حذف هذه السيارة من الأسطول؟' : (language === 'fr' ? 'Supprimer cette voiture ?' : 'Are you sure you want to remove this car?'))) return;
    try {
      await api.delete(`/agency/cars/${carId}`);
      showToast('Car removed successfully!', 'success');
      loadTabContent();
    } catch (err) {
      showToast(err.message || 'Failed to remove car', 'error');
    }
  };

  const openImageModal = async (car) => {
    setSelectedCar(car);
    setImageModalOpen(true);
    fetchCarImages(car.id);
  };

  const fetchCarImages = async (carId) => {
    setImageLoading(true);
    try {
      const response = await api.get(`/agency/cars/${carId}/images`);
      setCarImages(response.data || response || []);
    } catch (err) {
      console.error(err);
      showToast('Failed to load images', 'error');
    } finally {
      setImageLoading(false);
    }
  };

  const handleImageUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);

    setImageLoading(true);
    try {
      await api.post(`/agency/cars/${selectedCar.id}/images`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      showToast('Image uploaded successfully!', 'success');
      fetchCarImages(selectedCar.id);
      loadTabContent();
    } catch (err) {
      showToast(err.message || 'Image upload failed', 'error');
    } finally {
      setImageLoading(false);
    }
  };

  const handleSetPrimaryImage = async (imageId) => {
    setImageLoading(true);
    try {
      await api.put(`/agency/cars/${selectedCar.id}/images/${imageId}/primary`);
      showToast('Primary image updated!', 'success');
      fetchCarImages(selectedCar.id);
      loadTabContent();
    } catch (err) {
      showToast(err.message || 'Action failed', 'error');
    } finally {
      setImageLoading(false);
    }
  };

  const handleDeleteImage = async (imageId) => {
    if (!window.confirm('Delete this image?')) return;
    setImageLoading(true);
    try {
      await api.delete(`/agency/cars/${selectedCar.id}/images/${imageId}`);
      showToast('Image deleted successfully!', 'success');
      fetchCarImages(selectedCar.id);
      loadTabContent();
    } catch (err) {
      showToast(err.message || 'Delete failed', 'error');
    } finally {
      setImageLoading(false);
    }
  };

  const getCarImageUrl = (car) => {
    if (car.images && car.images.length > 0) {
      const primaryImage = car.images.find(img => img.is_primary) || car.images[0];
      if (primaryImage.image_url) {
        return primaryImage.image_url.startsWith('http') 
          ? primaryImage.image_url 
          : `http://localhost:8000/storage/${primaryImage.image_url}`;
      }
    }
    return 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=300&q=80';
  };

  // Render Helpers
  const renderOnboarding = () => (
    <div className="onboarding-panel card max-width no-hover fade-in" style={{ maxWidth: '600px', margin: '40px auto' }}>
      <Landmark size={48} className="text-primary" style={{ margin: '0 auto 16px' }} />
      <h2>{t('owner.agencyReg')}</h2>
      <p className="text-secondary" style={{ marginBottom: '24px' }}>
        {t('owner.agencyRegSub')}
      </p>

      <form onSubmit={handleRegisterAgency}>
        <div className="form-group">
          <label className="form-label">{t('owner.brandName')}</label>
          <input
            type="text"
            value={agencyForm.name}
            onChange={(e) => setAgencyForm((prev) => ({ ...prev, name: e.target.value }))}
            className="form-input"
            placeholder="E.g., Apex Luxury Rentals"
            required
            disabled={onboardingLoading}
          />
        </div>

        <div className="form-group">
          <label className="form-label">{t('owner.cityBase')}</label>
          <select
            value={agencyForm.agency_city}
            onChange={(e) => setAgencyForm((prev) => ({ ...prev, agency_city: e.target.value }))}
            className="form-input"
            required
            disabled={onboardingLoading}
          >
            <option value="">Select city</option>
            {cities.map((city) => (
              <option key={city.id} value={city.id}>
                {city.name}
              </option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label className="form-label">{t('owner.officeAddress')}</label>
          <input
            type="text"
            value={agencyForm.address}
            onChange={(e) => setAgencyForm((prev) => ({ ...prev, address: e.target.value }))}
            className="form-input"
            placeholder="123 Corporate Road"
            required
            disabled={onboardingLoading}
          />
        </div>

        <div className="form-group">
          <label className="form-label">{t('owner.businessPhone')}</label>
          <input
            type="text"
            value={agencyForm.phone}
            onChange={(e) => setAgencyForm((prev) => ({ ...prev, phone: e.target.value }))}
            className="form-input"
            placeholder="+1 (234) 567-890"
            required
            disabled={onboardingLoading}
          />
        </div>

        <div className="form-group">
          <label className="form-label">{t('carDetail.description')}</label>
          <textarea
            value={agencyForm.description}
            onChange={(e) => setAgencyForm((prev) => ({ ...prev, description: e.target.value }))}
            className="form-input"
            placeholder={t('owner.descPlaceholder')}
            rows="3"
            disabled={onboardingLoading}
            style={{ resize: 'none' }}
          />
        </div>

        <button type="submit" className="btn btn-primary w-full mt-4" disabled={onboardingLoading}>
          {onboardingLoading ? '...' : t('owner.submitApproval')}
        </button>
      </form>
    </div>
  );

  const renderPending = () => (
    <div className="card text-center fade-in" style={{ maxWidth: '600px', margin: '80px auto', padding: '60px' }}>
      <Landmark size={56} className="text-warning" style={{ margin: '0 auto 20px', animation: 'pulse 2s infinite' }} />
      <h2>{t('owner.pendingApprovalTitle')}</h2>
      <p className="text-secondary" style={{ marginTop: '12px', fontSize: '1.05rem' }}>
        {t('owner.pendingApprovalDesc')} (<strong>{user.agency.name}</strong>)
      </p>
      <p className="text-muted" style={{ marginTop: '24px', fontSize: '0.85rem' }}>
        {t('owner.checkBack')}
      </p>
      <button onClick={refreshUser} className="btn btn-secondary mt-4 flex-center" style={{ margin: '24px auto 0', gap: '6px' }}>
        <RefreshCw size={14} /> {isRtl ? 'تحديث الحالة' : (language === 'fr' ? 'Rafraîchir' : 'Refresh Status')}
      </button>
      <style>{`
        @keyframes pulse {
          0% { transform: scale(1); opacity: 1; }
          50% { transform: scale(1.05); opacity: 0.8; }
          100% { transform: scale(1); opacity: 1; }
        }
      `}</style>
    </div>
  );

  if (!user) return null;
  if (!user.agency) return renderOnboarding();
  if (user.agency.status === 'pending' || user.agency.status === 'rejected') return renderPending();

  // Translations
  const refLabel = isRtl ? 'المرجع' : (language === 'fr' ? 'Référence' : 'Reference');
  const carLabel = isRtl ? 'السيارة' : (language === 'fr' ? 'Voiture' : 'Car');
  const clientLabel = isRtl ? 'العميل' : (language === 'fr' ? 'Client' : 'Client');
  const pickupLabel = isRtl ? 'الاستلام' : (language === 'fr' ? 'Départ' : 'Pickup');
  const returnLabel = isRtl ? 'الإرجاع' : (language === 'fr' ? 'Retour' : 'Return');
  const costLabel = isRtl ? 'التكلفة الإجمالية' : (language === 'fr' ? 'Coût' : 'Earning');
  const statusLabel = isRtl ? 'الحالة' : (language === 'fr' ? 'Statut' : 'Status');

  return (
    <div className="container owner-dashboard-wrapper fade-in">
      <h2>{t('owner.title')}</h2>
      <p className="text-secondary">{t('owner.sub')}</p>

      {/* Dashboard Sub-navigation */}
      <div className="tab-navigation mt-4" style={{ justifyContent: isRtl ? 'flex-start' : 'flex-start', direction: isRtl ? 'rtl' : 'ltr' }}>
        <button
          onClick={() => setActiveTab('cars')}
          className={`tab-link ${activeTab === 'cars' ? 'active-tab' : ''}`}
        >
          <LayoutGrid size={16} /> {t('owner.myFleet')}
        </button>
        <button
          onClick={() => setActiveTab('bookings')}
          className={`tab-link ${activeTab === 'bookings' ? 'active-tab' : ''}`}
        >
          <Calendar size={16} /> {t('owner.bookingsLog')}
        </button>
        <button
          onClick={() => setActiveTab('payments')}
          className={`tab-link ${activeTab === 'payments' ? 'active-tab' : ''}`}
        >
          <DollarSign size={16} /> {t('owner.earnings')}
        </button>
        <button
          onClick={() => setActiveTab('settings')}
          className={`tab-link ${activeTab === 'settings' ? 'active-tab' : ''}`}
        >
          <Landmark size={16} /> {t('owner.settings')}
        </button>
      </div>

      <div className="tab-pane mt-4">
        {loadingLists ? (
          <div className="flex-center" style={{ minHeight: '300px' }}>
            <div className="spinner"></div>
          </div>
        ) : (
          <>
            {/* CARS TAB */}
            {activeTab === 'cars' && (
              <div className="fade-in">
                <div className="flex-between" style={{ marginBottom: '20px' }}>
                  <h3>{t('owner.myFleet')} ({cars.length})</h3>
                  <button onClick={() => openCarModal()} className="btn btn-primary btn-sm flex-center" style={{ gap: '6px' }}>
                    <Plus size={16} /> {t('owner.addCar')}
                  </button>
                </div>

                {cars.length === 0 ? (
                  <div className="card text-center" style={{ padding: '60px' }}>
                    <LayoutGrid size={40} className="text-muted" style={{ margin: '0 auto 16px' }} />
                    <h4>{t('owner.emptyFleet')}</h4>
                    <p className="text-secondary">{t('owner.emptyFleetSub')}</p>
                    <button onClick={() => openCarModal()} className="btn btn-primary btn-sm mt-4">
                      {t('owner.addCar')}
                    </button>
                  </div>
                ) : (
                  <div className="owner-cars-list">
                    {cars.map((car) => (
                      <div key={car.id} className="card owner-car-card flex-between fade-in" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                        <div className="car-info-block flex-center" style={{ justifyContent: 'flex-start', gap: '20px' }}>
                          <img src={getCarImageUrl(car)} alt={car.model} className="car-thumb" />
                          <div className="car-meta">
                            <span className={`badge ${car.status === 'available' ? 'badge-active' : 'badge-blocked'}`}>
                              {car.status}
                            </span>
                            <h4>{car.brand} {car.model}</h4>
                            <span className="car-details-sub">
                              {car.year} • {car.transmission === 'automatic' ? t('filter.automatic') : t('filter.manual')} • {car.type} • <strong>${car.price_per_day}/{t('landing.day')}</strong>
                            </span>
                          </div>
                        </div>

                        <div className="car-actions flex-center" style={{ gap: '8px' }}>
                          <button onClick={() => openImageModal(car)} className="btn btn-secondary btn-sm flex-center" title={t('owner.images')}>
                            <Image size={14} /> {t('owner.images')}
                          </button>
                          <button onClick={() => openCarModal(car)} className="btn btn-secondary btn-sm flex-center">
                            <Edit2 size={14} />
                          </button>
                          <button onClick={() => handleDeleteCar(car.id)} className="btn btn-secondary btn-sm flex-center text-danger">
                            <Trash2 size={14} />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* BOOKINGS TAB */}
            {activeTab === 'bookings' && (
              <div className="fade-in">
                <h3>{t('owner.bookingsLog')}</h3>
                {bookings.length === 0 ? (
                  <p className="text-muted mt-4">No reservations have been placed for your vehicles yet.</p>
                ) : (
                  <div className="bookings-table-wrapper card mt-4 no-hover">
                    <table className="dashboard-table">
                      <thead>
                        <tr style={{ textAlign: isRtl ? 'right' : 'left' }}>
                          <th>{refLabel}</th>
                          <th>{carLabel}</th>
                          <th>{clientLabel}</th>
                          <th>{pickupLabel}</th>
                          <th>{returnLabel}</th>
                          <th>{costLabel}</th>
                          <th>{statusLabel}</th>
                        </tr>
                      </thead>
                      <tbody>
                        {bookings.map((booking) => (
                          <tr key={booking.id}>
                            <td className="table-ref">{booking.reference_number}</td>
                            <td><strong>{booking.car?.brand} {booking.car?.model}</strong></td>
                            <td>{booking.client?.first_name} {booking.client?.last_name}</td>
                            <td>{new Date(booking.start_date).toLocaleDateString()}</td>
                            <td>{new Date(booking.end_date).toLocaleDateString()}</td>
                            <td className="text-primary"><strong>${parseFloat(booking.agency_earning).toFixed(2)}</strong></td>
                            <td>
                              <span className={`badge badge-${booking.status}`}>{booking.status}</span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {/* PAYMENTS TAB */}
            {activeTab === 'payments' && (
              <div className="fade-in">
                <h3>{t('owner.earnings')}</h3>
                {payments.length === 0 ? (
                  <p className="text-muted mt-4">No payments recorded yet.</p>
                ) : (
                  <div className="bookings-table-wrapper card mt-4 no-hover">
                    <table className="dashboard-table">
                      <thead>
                        <tr style={{ textAlign: isRtl ? 'right' : 'left' }}>
                          <th>Transaction ID</th>
                          <th>Reservation</th>
                          <th>Total Paid</th>
                          <th>My Earning (85%)</th>
                          <th>Commission (15%)</th>
                          <th>Status</th>
                          <th>Paid Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        {payments.map((p) => (
                          <tr key={p.id}>
                            <td className="table-ref" style={{ fontSize: '0.8rem' }}>{p.id.substring(0, 8)}...</td>
                            <td>{p.reservation?.reference_number}</td>
                            <td>${parseFloat(p.amount).toFixed(2)}</td>
                            <td className="text-success"><strong>${parseFloat(p.agency_amount).toFixed(2)}</strong></td>
                            <td className="text-muted">${parseFloat(p.commission_amount).toFixed(2)}</td>
                            <td>
                              <span className={`badge badge-${p.status === 'released' ? 'completed' : 'confirmed'}`}>
                                {p.status}
                              </span>
                            </td>
                            <td>{new Date(p.created_at).toLocaleDateString()}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {/* SETTINGS TAB */}
            {activeTab === 'settings' && (
              <div className="card no-hover max-width fade-in" style={{ maxWidth: '600px', margin: '0 auto' }}>
                <div className="flex-between" style={{ borderBottom: '1px solid var(--border)', paddingBottom: '12px', marginBottom: '24px' }}>
                  <h3>{t('owner.editProfile')}</h3>
                  {user.agency.status === 'pending_changes' && (
                    <span className="badge badge-pending">Pending Review</span>
                  )}
                </div>

                {user.agency.status === 'pending_changes' && (
                  <div className="alert alert-success" style={{ fontSize: '0.85rem' }}>
                    <Landmark size={16} /> Changes submitted. An administrator must approve these before they go live.
                  </div>
                )}

                <form onSubmit={handleUpdateProfile} style={{ textAlign: isRtl ? 'right' : 'left' }}>
                  <div className="form-group">
                    <label className="form-label">{t('owner.brandName')}</label>
                    <input
                      type="text"
                      value={profileForm.name}
                      onChange={(e) => setProfileForm((prev) => ({ ...prev, name: e.target.value }))}
                      className="form-input"
                      required
                    />
                  </div>

                  <div className="form-group">
                    <label className="form-label">{t('owner.officeAddress')}</label>
                    <input
                      type="text"
                      value={profileForm.address}
                      onChange={(e) => setProfileForm((prev) => ({ ...prev, address: e.target.value }))}
                      className="form-input"
                      required
                    />
                  </div>

                  <div className="form-group">
                    <label className="form-label">{t('owner.businessPhone')}</label>
                    <input
                      type="text"
                      value={profileForm.phone}
                      onChange={(e) => setProfileForm((prev) => ({ ...prev, phone: e.target.value }))}
                      className="form-input"
                      required
                    />
                  </div>

                  <div className="form-group">
                    <label className="form-label">{t('carDetail.description')}</label>
                    <textarea
                      value={profileForm.description}
                      onChange={(e) => setProfileForm((prev) => ({ ...prev, description: e.target.value }))}
                      className="form-input"
                      rows="4"
                      style={{ resize: 'none' }}
                    />
                  </div>

                  <button type="submit" className="btn btn-primary w-full mt-4">
                    {t('owner.submitUpdates')}
                  </button>
                </form>
              </div>
            )}
          </>
        )}
      </div>

      {/* CAR ADD/EDIT MODAL */}
      {carModalOpen && (
        <div className="modal-overlay">
          <div className="modal-content" style={{ maxWidth: '600px' }}>
            <h3>{editingCar ? t('owner.editProfile') : t('owner.addCar')}</h3>
            <hr className="divider" />
            
            <form onSubmit={handleCarSubmit} style={{ textAlign: isRtl ? 'right' : 'left' }}>
              <div className="form-row">
                <div className="form-group">
                  <label className="form-label">{t('carDetail.brand')}</label>
                  <input
                    type="text"
                    value={carForm.brand}
                    onChange={(e) => setCarForm((prev) => ({ ...prev, brand: e.target.value }))}
                    className="form-input"
                    placeholder="Tesla"
                    required
                    disabled={savingCar}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">{t('carDetail.model')}</label>
                  <input
                    type="text"
                    value={carForm.model}
                    onChange={(e) => setCarForm((prev) => ({ ...prev, model: e.target.value }))}
                    className="form-input"
                    placeholder="Model Y"
                    required
                    disabled={savingCar}
                  />
                </div>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label className="form-label">{isRtl ? 'سنة الموديل' : 'Model Year'}</label>
                  <input
                    type="number"
                    value={carForm.year}
                    onChange={(e) => setCarForm((prev) => ({ ...prev, year: e.target.value }))}
                    className="form-input"
                    placeholder="2025"
                    required
                    disabled={savingCar}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">{isRtl ? 'السعر اليومي ($)' : 'Daily Price ($)'}</label>
                  <input
                    type="number"
                    value={carForm.price_per_day}
                    onChange={(e) => setCarForm((prev) => ({ ...prev, price_per_day: e.target.value }))}
                    className="form-input"
                    placeholder="120"
                    required
                    disabled={savingCar}
                  />
                </div>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label className="form-label">{t('filter.gearbox')}</label>
                  <select
                    value={carForm.transmission}
                    onChange={(e) => setCarForm((prev) => ({ ...prev, transmission: e.target.value }))}
                    className="form-input"
                    required
                    disabled={savingCar}
                  >
                    <option value="automatic">{t('filter.automatic')}</option>
                    <option value="manual">{t('filter.manual')}</option>
                  </select>
                </div>
                <div className="form-group">
                  <label className="form-label">{t('carDetail.class')}</label>
                  <select
                    value={carForm.type}
                    onChange={(e) => setCarForm((prev) => ({ ...prev, type: e.target.value }))}
                    className="form-input"
                    required
                    disabled={savingCar}
                  >
                    <option value="sedan">Sedan</option>
                    <option value="suv">SUV</option>
                    <option value="coupe">Coupe</option>
                    <option value="hatchback">Hatchback</option>
                    <option value="convertible">Convertible</option>
                  </select>
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">{t('carDetail.description')}</label>
                <textarea
                  value={carForm.description}
                  onChange={(e) => setCarForm((prev) => ({ ...prev, description: e.target.value }))}
                  className="form-input"
                  placeholder="autopilot, leather seats, fast charging..."
                  rows="3"
                  style={{ resize: 'none' }}
                  disabled={savingCar}
                />
              </div>

              <div className="flex-end" style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '24px' }}>
                <button
                  type="button"
                  onClick={() => setCarModalOpen(false)}
                  className="btn btn-secondary btn-sm"
                  disabled={savingCar}
                >
                  {isRtl ? 'إلغاء' : 'Cancel'}
                </button>
                <button
                  type="submit"
                  className="btn btn-primary btn-sm"
                  disabled={savingCar}
                >
                  {savingCar ? '...' : (isRtl ? 'حفظ البيانات' : 'Save Car')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* IMAGES MANAGEMENT MODAL */}
      {imageModalOpen && selectedCar && (
        <div className="modal-overlay">
          <div className="modal-content" style={{ maxWidth: '600px' }}>
            <h3>{t('owner.images')} - {selectedCar.brand} {selectedCar.model}</h3>
            <p className="text-secondary" style={{ fontSize: '0.85rem' }}>Upload multiple photos and toggle which one is primary.</p>
            <hr className="divider" />

            <div className="image-upload-wrapper flex-center" style={{ justifyContent: 'flex-start', gap: '16px', marginBottom: '24px' }}>
              <label className="btn btn-secondary btn-sm flex-center" style={{ cursor: 'pointer', gap: '6px' }}>
                <Upload size={14} /> Upload Image
                <input
                  type="file"
                  onChange={handleImageUpload}
                  accept="image/*"
                  style={{ display: 'none' }}
                  disabled={imageLoading}
                />
              </label>
              {imageLoading && <div className="spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }}></div>}
            </div>

            <div className="photos-grid-list mt-4" style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '16px' }}>
              {carImages.map((img) => (
                <div key={img.id} className="image-item-card" style={{ position: 'relative', border: '1px solid var(--border)', borderRadius: 'var(--radius-sm)', overflow: 'hidden' }}>
                  <img
                    src={img.image_url.startsWith('http') ? img.image_url : `http://localhost:8000/storage/${img.image_url}`}
                    alt="Car image"
                    style={{ width: '100%', height: '100px', objectFit: 'cover' }}
                  />
                  <div className="image-item-actions flex-between" style={{ padding: '6px', background: 'rgba(0,0,0,0.6)' }}>
                    <button
                      type="button"
                      onClick={() => handleSetPrimaryImage(img.id)}
                      className={`btn btn-sm ${img.is_primary ? 'btn-primary' : 'btn-secondary'}`}
                      style={{ padding: '4px' }}
                      title="Set Primary"
                    >
                      <Check size={12} />
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDeleteImage(img.id)}
                      className="btn btn-sm btn-secondary text-danger"
                      style={{ padding: '4px' }}
                      title="Delete Image"
                    >
                      <Trash2 size={12} />
                    </button>
                  </div>
                </div>
              ))}
            </div>

            <div className="flex-end" style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '24px' }}>
              <button onClick={() => setImageModalOpen(false)} className="btn btn-primary btn-sm">
                Close
              </button>
            </div>
          </div>
        </div>
      )}

      <style>{`
        .owner-dashboard-wrapper {
          padding-top: 30px;
          padding-bottom: 80px;
          text-align: left;
        }
        [dir="rtl"] .owner-dashboard-wrapper {
          text-align: right;
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
          display: flex;
          align-items: center;
          gap: 6px;
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
        .owner-cars-list {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }
        .owner-car-card {
          padding: 16px;
        }
        .car-thumb {
          width: 80px;
          height: 60px;
          object-fit: cover;
          border-radius: var(--radius-sm);
          background: var(--border);
        }
        .car-details-sub {
          font-size: 0.85rem;
          color: var(--text-secondary);
          display: block;
          margin-top: 4px;
        }
        .no-hover {
          transform: none !important;
          box-shadow: var(--glass-shadow), var(--shadow-sm) !important;
          border-color: var(--border) !important;
        }
        .form-row {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 16px;
        }
        .divider {
          margin: 16px 0;
          border: 0;
          border-top: 1px solid var(--border);
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

export default OwnerDashboard;
