import React from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useLanguage } from '../context/LanguageContext';
import { Car, LogOut, User, LayoutDashboard, Shield, Landmark } from 'lucide-react';

const Navbar = () => {
  const { user, logout } = useAuth();
  const { language, setLanguage, t } = useLanguage();
  const navigate = useNavigate();
  const location = useLocation();

  const handleLogout = async () => {
    await logout();
    navigate('/');
  };

  const isActive = (path) => location.pathname === path;

  return (
    <nav className="navbar">
      <div className="container flex-between">
        <Link to="/" className="navbar-brand">
          <Car size={28} className="text-primary" style={{ strokeWidth: 2.5 }} />
          <span>GlobalRental</span>
        </Link>

        <ul className="navbar-links">
          <li>
            <Link to="/" className={isActive('/') ? 'active-link' : ''}>
              {t('nav.browse')}
            </Link>
          </li>

          {/* Guest Nav */}
          {!user && (
            <>
              <li>
                <Link to="/login" className={`btn btn-secondary btn-sm ${isActive('/login') ? 'btn-active' : ''}`}>
                  {t('nav.login')}
                </Link>
              </li>
              <li>
                <Link to="/register" className={`btn btn-primary btn-sm ${isActive('/register') ? 'btn-active' : ''}`}>
                  {t('nav.register')}
                </Link>
              </li>
            </>
          )}

          {/* Client Nav */}
          {user && user.role === 'client' && (
            <>
              <li>
                <Link to="/client/reservations" className={isActive('/client/reservations') ? 'active-link' : ''}>
                  {t('nav.bookings')}
                </Link>
              </li>
              <li className="user-profile-badge">
                <span className="badge badge-confirmed">
                  <User size={14} className="mr-2" />
                  {user.first_name}
                </span>
              </li>
              <li>
                <button onClick={handleLogout} className="btn btn-secondary btn-sm" title={t('nav.logout')}>
                  <LogOut size={16} />
                </button>
              </li>
            </>
          )}

          {/* Agency Owner Nav */}
          {user && user.role === 'agency_owner' && (
            <>
              <li>
                <Link to="/owner/dashboard" className={isActive('/owner/dashboard') ? 'active-link' : ''}>
                  <span className="flex-center" style={{ gap: '4px' }}>
                    <LayoutDashboard size={16} /> {t('nav.dashboard')}
                  </span>
                </Link>
              </li>
              <li className="user-profile-badge">
                <span className="badge badge-picked-up">
                  <Landmark size={14} className="mr-2" />
                  {user.agency?.name || 'My Agency'}
                </span>
              </li>
              <li>
                <button onClick={handleLogout} className="btn btn-secondary btn-sm" title={t('nav.logout')}>
                  <LogOut size={16} />
                </button>
              </li>
            </>
          )}

          {/* Admin Nav */}
          {user && user.role === 'admin' && (
            <>
              <li>
                <Link to="/admin/dashboard" className={isActive('/admin/dashboard') ? 'active-link' : ''}>
                  <span className="flex-center" style={{ gap: '4px' }}>
                    <Shield size={16} /> {t('nav.admin')}
                  </span>
                </Link>
              </li>
              <li className="user-profile-badge">
                <span className="badge badge-cancelled">
                  <Shield size={14} className="mr-2" />
                  Admin
                </span>
              </li>
              <li>
                <button onClick={handleLogout} className="btn btn-secondary btn-sm" title={t('nav.logout')}>
                  <LogOut size={16} />
                </button>
              </li>
            </>
          )}

          {/* Language Selector */}
          <li>
            <select
              value={language}
              onChange={(e) => setLanguage(e.target.value)}
              className="lang-select"
            >
              <option value="en">EN</option>
              <option value="fr">FR</option>
              <option value="ar">AR</option>
            </select>
          </li>
        </ul>
      </div>
      <style>{`
        .active-link {
          color: var(--primary) !important;
          font-weight: 600;
        }
        .navbar-links {
          display: flex;
          align-items: center;
          gap: 20px;
          list-style: none;
        }
        .navbar-links li a {
          color: var(--text-secondary);
          font-weight: 500;
          font-size: 0.95rem;
          transition: color var(--transition-fast);
        }
        .navbar-links li a:hover {
          color: var(--primary);
        }
        .user-profile-badge {
          display: flex;
          align-items: center;
        }
        .lang-select {
          background: transparent;
          border: 1px solid var(--border);
          color: var(--text-primary);
          padding: 4px 8px;
          border-radius: var(--radius-sm);
          font-family: var(--font-heading);
          font-weight: 600;
          font-size: 0.85rem;
          outline: none;
          cursor: pointer;
        }
        .lang-select:focus {
          border-color: var(--primary);
        }
        [dir="rtl"] .user-profile-badge span svg {
          margin-left: 8px;
          margin-right: 0;
        }
      `}</style>
    </nav>
  );
};

export default Navbar;
