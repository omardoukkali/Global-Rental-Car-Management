import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { useLanguage } from '../context/LanguageContext';
import { Search, MapPin, Calendar, DollarSign, Settings, Navigation, Star } from 'lucide-react';

const LandingPage = () => {
  const { t } = useLanguage();
  const [cars, setCars] = useState([]);
  const [cities, setCities] = useState([]);
  const [loading, setLoading] = useState(true);

  // Search Filters State
  const [filters, setFilters] = useState({
    city_id: '',
    type: '',
    transmission: '',
    min_price: '',
    max_price: '',
    start_date: '',
    end_date: '',
  });

  useEffect(() => {
    fetchCities();
    fetchCars();
  }, []);

  const fetchCities = async () => {
    try {
      const response = await api.get('/cities');
      setCities(response.data || (Array.isArray(response) ? response : []));
    } catch (err) {
      console.error('Error fetching cities:', err);
    }
  };

  const fetchCars = async (activeFilters = filters) => {
    setLoading(true);
    try {
      // Build query string based on defined filters
      const queryParams = new URLSearchParams();
      Object.entries(activeFilters).forEach(([key, val]) => {
        if (val) queryParams.append(key, val);
      });

      const response = await api.get(`/cars?${queryParams.toString()}`);
      setCars(response.data || response || []);
    } catch (err) {
      console.error('Error fetching cars:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters((prev) => ({ ...prev, [name]: value }));
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    fetchCars();
  };

  const handleResetFilters = () => {
    const reset = {
      city_id: '',
      type: '',
      transmission: '',
      min_price: '',
      max_price: '',
      start_date: '',
      end_date: '',
    };
    setFilters(reset);
    fetchCars(reset);
  };

  // Safe fallback placeholder for car images
  const getCarImageUrl = (car) => {
    if (car.images && car.images.length > 0) {
      const primaryImage = car.images.find(img => img.is_primary) || car.images[0];
      if (primaryImage.image_url) {
        return primaryImage.image_url.startsWith('http') 
          ? primaryImage.image_url 
          : `http://localhost:8000/storage/${primaryImage.image_url}`;
      }
    }
    const carType = car.type?.toLowerCase() || 'sedan';
    if (carType === 'suv') return 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=600&q=80';
    if (carType === 'sports') return 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=600&q=80';
    return 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=600&q=80';
  };

  return (
    <div className="landing-wrapper">
      {/* Premium Hero Banner */}
      <header className="hero-section hero-gradient">
        <div className="container flex-center hero-content fade-in">
          <div className="hero-text-block">
            <span className="hero-tagline">{t('hero.tagline')}</span>
            <h1>{t('hero.title')}</h1>
            <p>{t('hero.desc')}</p>
          </div>
        </div>
      </header>

      {/* Floating Filter Panel */}
      <section className="filter-section container">
        <form onSubmit={handleSearchSubmit} className="card filter-panel fade-in">
          <div className="filter-grid">
            <div className="filter-col">
              <label className="filter-label">
                <MapPin size={16} /> {t('filter.city')}
              </label>
              <select
                name="city_id"
                value={filters.city_id}
                onChange={handleFilterChange}
                className="filter-input"
              >
                <option value="">{t('filter.anywhere')}</option>
                {cities.map((city) => (
                  <option key={city.id} value={city.id}>
                    {city.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="filter-col">
              <label className="filter-label">
                <Calendar size={16} /> {t('filter.pickup')}
              </label>
              <input
                type="date"
                name="start_date"
                value={filters.start_date}
                onChange={handleFilterChange}
                className="filter-input"
              />
            </div>

            <div className="filter-col">
              <label className="filter-label">
                <Calendar size={16} /> {t('filter.return')}
              </label>
              <input
                type="date"
                name="end_date"
                value={filters.end_date}
                onChange={handleFilterChange}
                className="filter-input"
              />
            </div>

            <div className="filter-col">
              <label className="filter-label">
                <Settings size={16} /> {t('filter.class')}
              </label>
              <select
                name="type"
                value={filters.type}
                onChange={handleFilterChange}
                className="filter-input"
              >
                <option value="">{t('filter.allClasses')}</option>
                <option value="sedan">Sedan</option>
                <option value="suv">SUV</option>
                <option value="coupe">Coupe</option>
                <option value="hatchback">Hatchback</option>
                <option value="convertible">Convertible</option>
              </select>
            </div>

            <div className="filter-col">
              <label className="filter-label">
                <Navigation size={16} /> {t('filter.gearbox')}
              </label>
              <select
                name="transmission"
                value={filters.transmission}
                onChange={handleFilterChange}
                className="filter-input"
              >
                <option value="">{t('filter.anyGearbox')}</option>
                <option value="automatic">{t('filter.automatic')}</option>
                <option value="manual">{t('filter.manual')}</option>
              </select>
            </div>
            
            <div className="filter-col">
              <label className="filter-label">
                <DollarSign size={16} /> {t('filter.budget')}
              </label>
              <div className="price-inputs">
                <input
                  type="number"
                  name="min_price"
                  value={filters.min_price}
                  onChange={handleFilterChange}
                  className="filter-input price-sub-input"
                  placeholder="Min"
                />
                <input
                  type="number"
                  name="max_price"
                  value={filters.max_price}
                  onChange={handleFilterChange}
                  className="filter-input price-sub-input"
                  placeholder="Max"
                />
              </div>
            </div>
          </div>

          <div className="filter-actions mt-4 flex-between">
            <button
              type="button"
              onClick={handleResetFilters}
              className="btn btn-secondary btn-sm"
            >
              {t('filter.reset')}
            </button>
            <button type="submit" className="btn btn-primary">
              <Search size={18} /> {t('filter.search')}
            </button>
          </div>
        </form>
      </section>

      {/* Main Grid Section */}
      <main className="container car-results-section">
        <h2 className="section-heading">{t('landing.fleet')}</h2>
        <p className="section-subheading">{t('landing.sub')}</p>

        {loading ? (
          <div className="loading-grid flex-center" style={{ minHeight: '300px' }}>
            <div className="spinner"></div>
          </div>
        ) : cars.length === 0 ? (
          <div className="empty-results card text-center">
            <h3>{t('landing.noCars')}</h3>
            <p>{t('landing.noCarsSub')}</p>
            <button onClick={handleResetFilters} className="btn btn-primary mt-4">
              {t('filter.reset')}
            </button>
          </div>
        ) : (
          <div className="grid grid-cols-3">
            {cars.map((car) => (
              <div key={car.id} className="card car-card fade-in">
                <div className="car-image-container">
                  <img
                    src={getCarImageUrl(car)}
                    alt={`${car.brand} ${car.model}`}
                    className="car-image"
                    loading="lazy"
                  />
                  <span className="car-price-badge">
                    <strong>${car.price_per_day}</strong> / {t('landing.day')}
                  </span>
                </div>

                <div className="car-details">
                  <div className="flex-between">
                    <span className="car-type-tag" style={{ textTransform: 'capitalize' }}>{car.type}</span>
                    <span className="car-location flex-center" style={{ gap: '4px' }}>
                      <MapPin size={14} className="text-muted" />
                      {car.agency?.city?.name || 'Local'}
                    </span>
                  </div>

                  <h3 className="car-name">
                    {car.brand} {car.model}
                  </h3>
                  
                  <div className="car-specs">
                    <span>{car.year} Model</span>
                    <span>•</span>
                    <span className="transmission-spec">
                      {car.transmission === 'automatic' ? t('filter.automatic') : t('filter.manual')}
                    </span>
                  </div>

                  <hr className="car-divider" />

                  <div className="flex-between car-footer">
                    <div className="agency-info">
                      <span className="agency-name">{car.agency?.name || 'Premium Agency'}</span>
                      {car.agency?.avg_rating && (
                        <div className="rating flex-center" style={{ gap: '4px' }}>
                          <Star size={14} className="star-icon" />
                          <span>{parseFloat(car.agency.avg_rating).toFixed(1)}</span>
                        </div>
                      )}
                    </div>
                    <Link to={`/cars/${car.id}`} className="btn btn-primary btn-sm">
                      {t('landing.rent')}
                    </Link>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>

      <style>{`
        .landing-wrapper {
          padding-bottom: 80px;
        }
        .hero-section {
          padding: 80px 0 140px;
          text-align: center;
        }
        .hero-content {
          flex-direction: column;
        }
        .hero-tagline {
          font-family: var(--font-heading);
          color: var(--primary);
          font-weight: 700;
          font-size: 0.95rem;
          letter-spacing: 0.1em;
          text-transform: uppercase;
        }
        .hero-text-block h1 {
          font-size: 3.2rem;
          margin: 12px 0 20px;
          letter-spacing: -0.03em;
        }
        .hero-text-block p {
          max-width: 600px;
          margin: 0 auto;
          color: var(--text-secondary);
          font-size: 1.15rem;
          line-height: 1.6;
        }
        .filter-section {
          margin-top: -80px;
          position: relative;
          z-index: 10;
        }
        .filter-panel {
          padding: 32px;
          border-radius: var(--radius-lg);
          background: var(--bg-card);
        }
        .filter-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
          gap: 20px;
        }
        .filter-col {
          display: flex;
          flex-direction: column;
          gap: 8px;
          text-align: left;
        }
        [dir="rtl"] .filter-col {
          text-align: right;
        }
        .filter-label {
          font-family: var(--font-heading);
          font-size: 0.85rem;
          font-weight: 600;
          color: var(--text-secondary);
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .filter-input {
          padding: 10px 12px;
          border: 1px solid var(--border);
          background: hsla(240, 10%, 96%, 0.4);
          color: var(--text-primary);
          border-radius: var(--radius-sm);
          font-size: 0.9rem;
          outline: none;
          transition: all var(--transition-fast);
          width: 100%;
        }
        .filter-input:focus {
          border-color: var(--primary);
          background: var(--bg-main);
          box-shadow: 0 0 0 3px hsla(var(--primary-hue), 85%, 58%, 0.1);
        }
        .price-inputs {
          display: flex;
          gap: 8px;
        }
        .price-sub-input {
          width: 50% !important;
          padding: 10px 8px;
        }
        .car-results-section {
          margin-top: 60px;
        }
        .section-heading {
          font-size: 2.2rem;
          margin-bottom: 6px;
          text-align: center;
        }
        .section-subheading {
          color: var(--text-secondary);
          text-align: center;
          margin-bottom: 40px;
          font-size: 1.05rem;
        }
        .empty-results {
          padding: 60px;
          max-width: 500px;
          margin: 0 auto;
        }
        .empty-results h3 {
          font-size: 1.5rem;
          margin-bottom: 8px;
        }
        .empty-results p {
          color: var(--text-secondary);
        }
        .car-card {
          padding: 0;
          overflow: hidden;
          display: flex;
          flex-direction: column;
          text-align: left;
        }
        [dir="rtl"] .car-card {
          text-align: right;
        }
        .car-image-container {
          position: relative;
          height: 200px;
          width: 100%;
          overflow: hidden;
          background-color: var(--border);
        }
        .car-image {
          width: 100%;
          height: 100%;
          object-fit: cover;
          transition: transform var(--transition-slow);
        }
        .car-card:hover .car-image {
          transform: scale(1.05);
        }
        .car-price-badge {
          position: absolute;
          bottom: 12px;
          right: 12px;
          background: rgba(15, 17, 26, 0.85);
          backdrop-filter: blur(8px);
          color: #fff;
          padding: 6px 12px;
          border-radius: var(--radius-sm);
          font-size: 0.85rem;
          border: 1px solid rgba(255, 255, 255, 0.15);
        }
        [dir="rtl"] .car-price-badge {
          right: auto;
          left: 12px;
        }
        .car-price-badge strong {
          font-size: 1.1rem;
          color: var(--primary);
        }
        .car-details {
          padding: 20px;
          flex-grow: 1;
          display: flex;
          flex-direction: column;
        }
        .car-type-tag {
          font-family: var(--font-heading);
          font-size: 0.75rem;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.05em;
          color: var(--primary);
          background: var(--primary-light);
          padding: 2px 8px;
          border-radius: var(--radius-sm);
        }
        .car-location {
          font-size: 0.85rem;
          color: var(--text-secondary);
        }
        .car-name {
          font-size: 1.25rem;
          margin: 10px 0 4px;
        }
        .car-specs {
          font-size: 0.85rem;
          color: var(--text-secondary);
          display: flex;
          gap: 8px;
          margin-bottom: 16px;
        }
        .transmission-spec {
          text-transform: capitalize;
        }
        .car-divider {
          border: 0;
          border-top: 1px solid var(--border);
          margin-top: auto;
          margin-bottom: 16px;
        }
        .car-footer {
          display: flex;
          align-items: center;
        }
        .agency-info {
          display: flex;
          flex-direction: column;
          align-items: flex-start;
        }
        [dir="rtl"] .agency-info {
          align-items: flex-end;
        }
        .agency-name {
          font-size: 0.85rem;
          font-weight: 600;
          color: var(--text-primary);
        }
        .rating {
          font-size: 0.8rem;
          font-weight: 700;
          color: var(--warning);
        }
        .star-icon {
          fill: var(--warning);
          stroke: var(--warning);
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

export default LandingPage;
