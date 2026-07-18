import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useLanguage } from '../context/LanguageContext';
import api from '../services/api';
import { User, Landmark, Mail, Lock, Phone, MapPin, Building, ChevronRight } from 'lucide-react';

const RegisterPage = () => {
  const [role, setRole] = useState('client'); // 'client' or 'agency'
  const [cities, setCities] = useState([]);
  const [submitting, setSubmitting] = useState(false);
  const { registerClient, registerAgency, showToast } = useAuth();
  const { language, t, isRtl } = useLanguage();
  const navigate = useNavigate();

  // Form State
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    agency_name: '',
    agency_city: '',
    address: '',
    agency_phone: '',
  });

  useEffect(() => {
    const fetchCities = async () => {
      try {
        const response = await api.get('/cities');
        setCities(response.data || (Array.isArray(response) ? response : []));
      } catch (err) {
        console.error('Failed to fetch cities:', err);
      }
    };
    fetchCities();
  }, []);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (role === 'agency' && formData.password !== formData.password_confirmation) {
      showToast('Passwords do not match!', 'error');
      return;
    }

    setSubmitting(true);
    try {
      if (role === 'client') {
        const clientData = {
          first_name: formData.first_name,
          last_name: formData.last_name,
          email: formData.email,
          phone: formData.phone,
          password: formData.password,
        };
        await registerClient(clientData);
        navigate('/');
      } else {
        const agencyData = {
          first_name: formData.first_name,
          last_name: formData.last_name,
          email: formData.email,
          phone: formData.phone,
          password: formData.password,
          password_confirmation: formData.password_confirmation,
          agency_name: formData.agency_name,
          agency_city: formData.agency_city,
          address: formData.address,
          agency_phone: formData.agency_phone,
        };
        await registerAgency(agencyData);
        navigate('/login');
      }
    } catch (err) {
      console.error(err);
    } finally {
      setSubmitting(false);
    }
  };

  // Inline Translations
  const titleText = isRtl ? 'ابدأ الآن' : (language === 'fr' ? 'Commencer' : 'Get Started');
  const subtitleText = isRtl 
    ? 'أنشئ حساباً للانضمام للمنصة' 
    : (language === 'fr' ? 'Créez un compte pour rejoindre la plateforme' : 'Create an account to join the platform');
  const clientTab = isRtl ? 'تسجيل كزبون' : (language === 'fr' ? 'Client' : 'Register as Client');
  const ownerTab = isRtl ? 'تسجيل وكالة' : (language === 'fr' ? 'Enregistrer une Agence' : 'Register Agency');

  const fNameLabel = isRtl ? 'الاسم الأول' : (language === 'fr' ? 'Prénom' : 'First Name');
  const lNameLabel = isRtl ? 'الاسم الأخير' : (language === 'fr' ? 'Nom de famille' : 'Last Name');
  const emailLabel = isRtl ? 'البريد الإلكتروني' : (language === 'fr' ? 'Adresse e-mail' : 'Email Address');
  const phoneLabel = isRtl ? 'رقم الهاتف الشخصي' : (language === 'fr' ? 'Téléphone personnel' : 'Personal Phone Number');
  const passLabel = isRtl ? 'كلمة المرور' : (language === 'fr' ? 'Mot de passe' : 'Password');
  const confirmPassLabel = isRtl ? 'تأكيد كلمة المرور' : (language === 'fr' ? 'Confirmer le mot de passe' : 'Confirm Password');

  const agencyHeader = isRtl ? 'تفاصيل الوكالة' : (language === 'fr' ? "Détails de l'agence" : 'Agency Details');
  const agencyNameLabel = isRtl ? 'اسم الوكالة التجاري' : (language === 'fr' ? "Nom commercial de l'agence" : 'Agency Business Name');
  const cityLabel = isRtl ? 'المقر (المدينة)' : (language === 'fr' ? 'Ville' : 'City Location');
  const selectCityOpt = isRtl ? 'اختر المدينة' : (language === 'fr' ? 'Sélectionner une ville' : 'Select a City');
  const addressLabel = isRtl ? 'عنوان المكتب' : (language === 'fr' ? "Adresse de l'agence" : 'Agency Address');
  const agencyPhoneLabel = isRtl ? 'هاتف عمل الوكالة' : (language === 'fr' ? "Téléphone de l'agence" : 'Agency Business Phone');

  const btnText = submitting
    ? (isRtl ? 'جاري إرسال الطلب...' : (language === 'fr' ? 'Création...' : 'Creating Account...'))
    : (role === 'client'
        ? (isRtl ? 'سجل الآن' : (language === 'fr' ? "S'inscrire" : 'Register Now'))
        : (isRtl ? 'تقديم للموافقة' : (language === 'fr' ? 'Soumettre' : 'Submit for Approval'))
      );

  const footerPrompt = isRtl ? 'لديك حساب بالفعل؟' : (language === 'fr' ? 'Vous avez déjà un compte ?' : 'Already have an account?');
  const footerLinkText = isRtl ? 'سجل دخولك هنا' : (language === 'fr' ? 'Se connecter ici' : 'Sign in here');

  return (
    <div className="register-container flex-center hero-gradient">
      <div className="card register-card fade-in">
        <h2 className="register-title">{titleText}</h2>
        <p className="register-subtitle">{subtitleText}</p>

        {/* Role Toggle Tabs */}
        <div className="role-tabs">
          <button
            type="button"
            className={`tab-btn ${role === 'client' ? 'active-tab' : ''}`}
            onClick={() => setRole('client')}
          >
            <User size={16} /> {clientTab}
          </button>
          <button
            type="button"
            className={`tab-btn ${role === 'agency' ? 'active-tab' : ''}`}
            onClick={() => setRole('agency')}
          >
            <Landmark size={16} /> {ownerTab}
          </button>
        </div>

        <form onSubmit={handleSubmit} className="mt-4" style={{ textAlign: isRtl ? 'right' : 'left' }}>
          <div className="form-row">
            <div className="form-group">
              <label className="form-label">{fNameLabel}</label>
              <input
                type="text"
                name="first_name"
                value={formData.first_name}
                onChange={handleInputChange}
                className="form-input"
                placeholder="John"
                required
                disabled={submitting}
              />
            </div>
            <div className="form-group">
              <label className="form-label">{lNameLabel}</label>
              <input
                type="text"
                name="last_name"
                value={formData.last_name}
                onChange={handleInputChange}
                className="form-input"
                placeholder="Doe"
                required
                disabled={submitting}
              />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
              <Mail size={15} /> {emailLabel}
            </label>
            <input
              type="email"
              name="email"
              value={formData.email}
              onChange={handleInputChange}
              className="form-input"
              placeholder="john.doe@example.com"
              required
              disabled={submitting}
            />
          </div>

          <div className="form-group">
            <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
              <Phone size={15} /> {phoneLabel}
            </label>
            <input
              type="text"
              name="phone"
              value={formData.phone}
              onChange={handleInputChange}
              className="form-input"
              placeholder="+123456789"
              required
              disabled={submitting}
            />
          </div>

          <div className="form-group">
            <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
              <Lock size={15} /> {passLabel}
            </label>
            <input
              type="password"
              name="password"
              value={formData.password}
              onChange={handleInputChange}
              className="form-input"
              placeholder="Min. 8 characters"
              required
              minLength="8"
              disabled={submitting}
            />
          </div>

          {/* Agency Registration Fields */}
          {role === 'agency' && (
            <div className="agency-fields-section fade-in">
              <div className="form-group">
                <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
                  <Lock size={15} /> {confirmPassLabel}
                </label>
                <input
                  type="password"
                  name="password_confirmation"
                  value={formData.password_confirmation}
                  onChange={handleInputChange}
                  className="form-input"
                  placeholder="Repeat password"
                  required
                  disabled={submitting}
                />
              </div>

              <hr className="divider" />
              <h3 className="section-title" style={{ textAlign: isRtl ? 'right' : 'left' }}>{agencyHeader}</h3>

              <div className="form-group">
                <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
                  <Building size={15} /> {agencyNameLabel}
                </label>
                <input
                  type="text"
                  name="agency_name"
                  value={formData.agency_name}
                  onChange={handleInputChange}
                  className="form-input"
                  placeholder="Apex Car Rental"
                  required
                  disabled={submitting}
                />
              </div>

              <div className="form-group">
                <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
                  <MapPin size={15} /> {cityLabel}
                </label>
                <select
                  name="agency_city"
                  value={formData.agency_city}
                  onChange={handleInputChange}
                  className="form-input"
                  required
                  disabled={submitting}
                >
                  <option value="">{selectCityOpt}</option>
                  {cities.map((city) => (
                    <option key={city.id} value={city.id}>
                      {city.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
                  <MapPin size={15} /> {addressLabel}
                </label>
                <input
                  type="text"
                  name="address"
                  value={formData.address}
                  onChange={handleInputChange}
                  className="form-input"
                  placeholder="123 Luxury Dr, Suite A"
                  required
                  disabled={submitting}
                />
              </div>

              <div className="form-group">
                <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
                  <Phone size={15} /> {agencyPhoneLabel}
                </label>
                <input
                  type="text"
                  name="agency_phone"
                  value={formData.agency_phone}
                  onChange={handleInputChange}
                  className="form-input"
                  placeholder="+198765432"
                  required
                  disabled={submitting}
                />
              </div>
            </div>
          )}

          <button type="submit" className="btn btn-primary w-full mt-4" disabled={submitting}>
            {btnText}
            <ChevronRight size={18} style={{ transform: isRtl ? 'rotate(180deg)' : 'none' }} />
          </button>
        </form>

        <div className="register-footer">
          <p>
            {footerPrompt} <Link to="/login">{footerLinkText}</Link>
          </p>
        </div>
      </div>

      <style>{`
        .register-container {
          min-height: calc(100vh - 73px);
          padding: 40px 24px;
        }
        .register-card {
          width: 100%;
          max-width: 500px;
          text-align: center;
        }
        .register-title {
          font-size: 2.2rem;
          margin-bottom: 8px;
        }
        .register-subtitle {
          color: var(--text-secondary);
          font-size: 0.95rem;
          margin-bottom: 24px;
        }
        .role-tabs {
          display: flex;
          background: var(--border);
          padding: 4px;
          border-radius: var(--radius-sm);
          margin-bottom: 20px;
        }
        .tab-btn {
          flex: 1;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 6px;
          border: none;
          background: transparent;
          color: var(--text-secondary);
          padding: 8px 12px;
          font-family: var(--font-heading);
          font-weight: 600;
          font-size: 0.85rem;
          border-radius: calc(var(--radius-sm) - 2px);
          cursor: pointer;
          transition: all var(--transition-fast);
        }
        .active-tab {
          background: var(--bg-main);
          color: var(--primary);
          box-shadow: var(--shadow-sm);
        }
        .form-row {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 16px;
        }
        .divider {
          margin: 24px 0;
          border: 0;
          border-top: 1px solid var(--border);
        }
        .section-title {
          font-size: 1.15rem;
          text-align: left;
          margin-bottom: 16px;
          color: var(--text-primary);
        }
        .register-footer {
          margin-top: 24px;
          padding-top: 16px;
          border-top: 1px solid var(--border);
          font-size: 0.9rem;
          color: var(--text-secondary);
        }
      `}</style>
    </div>
  );
};

export default RegisterPage;
