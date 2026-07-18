import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useLanguage } from '../context/LanguageContext';
import { KeyRound, Mail, ArrowRight } from 'lucide-react';

const LoginPage = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const { login } = useAuth();
  const { language, t, isRtl } = useLanguage();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email || !password) return;

    setSubmitting(true);
    try {
      const loggedUser = await login(email, password);
      if (loggedUser.role === 'admin') {
        navigate('/admin/dashboard');
      } else if (loggedUser.role === 'agency_owner') {
        navigate('/owner/dashboard');
      } else {
        navigate('/');
      }
    } catch (err) {
      console.error(err);
    } finally {
      setSubmitting(false);
    }
  };

  // Translations
  const title = isRtl ? 'مرحباً بك مجدداً' : (language === 'fr' ? 'Bon retour' : 'Welcome Back');
  const subtitle = isRtl 
    ? 'قم بتسجيل الدخول لإدارة تجربة تأجير السيارات الخاصة بك' 
    : (language === 'fr' ? 'Connectez-vous pour gérer votre expérience de location' : 'Log in to manage your car rental experience');
  const emailLabel = isRtl ? 'البريد الإلكتروني' : (language === 'fr' ? 'Adresse e-mail' : 'Email Address');
  const passLabel = isRtl ? 'كلمة المرور' : (language === 'fr' ? 'Mot de passe' : 'Password');
  const buttonText = submitting 
    ? (isRtl ? 'جاري الدخول...' : (language === 'fr' ? 'Connexion...' : 'Signing In...'))
    : (isRtl ? 'تسجيل الدخول' : (language === 'fr' ? 'Se connecter' : 'Sign In'));
  const footerText = isRtl ? 'ليس لديك حساب؟' : (language === 'fr' ? "Vous n'avez pas de compte ?" : "Don't have an account?");
  const footerLink = isRtl ? 'أنشئ حساباً هنا' : (language === 'fr' ? 'Créez-en un ici' : 'Create one here');

  return (
    <div className="auth-container flex-center hero-gradient">
      <div className="card auth-card fade-in">
        <h2 className="auth-title">{title}</h2>
        <p className="auth-subtitle">{subtitle}</p>

        <form onSubmit={handleSubmit} className="mt-4">
          <div className="form-group" style={{ textAlign: isRtl ? 'right' : 'left' }}>
            <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
              <Mail size={16} /> {emailLabel}
            </label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="form-input"
              placeholder="name@example.com"
              required
              disabled={submitting}
            />
          </div>

          <div className="form-group" style={{ textAlign: isRtl ? 'right' : 'left' }}>
            <label className="form-label flex-center" style={{ justifyContent: 'flex-start', gap: '8px' }}>
              <KeyRound size={16} /> {passLabel}
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="form-input"
              placeholder="••••••••"
              required
              disabled={submitting}
            />
          </div>

          <button type="submit" className="btn btn-primary w-full mt-4" disabled={submitting}>
            {buttonText}
            <ArrowRight size={18} style={{ transform: isRtl ? 'rotate(180deg)' : 'none' }} />
          </button>
        </form>

        <div className="auth-footer">
          <p>
            {footerText} <Link to="/register">{footerLink}</Link>
          </p>
        </div>
      </div>

      <style>{`
        .auth-container {
          min-height: calc(100vh - 73px);
          padding: 40px 24px;
        }
        .auth-card {
          width: 100%;
          max-width: 420px;
          text-align: center;
        }
        .auth-title {
          font-size: 2.2rem;
          margin-bottom: 8px;
        }
        .auth-subtitle {
          color: var(--text-secondary);
          font-size: 0.95rem;
          margin-bottom: 24px;
        }
        .auth-footer {
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

export default LoginPage;
