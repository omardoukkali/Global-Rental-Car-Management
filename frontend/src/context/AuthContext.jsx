import React, { createContext, useState, useEffect, useContext } from 'react';
import api from '../services/api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('token') || null);
  const [loading, setLoading] = useState(true);
  const [toasts, setToasts] = useState([]);

  // Toast notifier helper
  const showToast = (message, type = 'success') => {
    const id = Date.now();
    setToasts((prev) => [...prev, { id, message, type }]);
    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 4000);
  };

  const removeToast = (id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  useEffect(() => {
    const initAuth = async () => {
      if (token) {
        try {
          // Sync api header and fetch user info
          const response = await api.get('/auth/me');
          setUser(response.user);
        } catch (error) {
          console.error('Failed to authenticate token:', error);
          logout();
        }
      }
      setLoading(false);
    };

    initAuth();
  }, [token]);

  const login = async (email, password) => {
    try {
      const response = await api.post('/auth/login', { email, password });
      localStorage.setItem('token', response.token);
      setToken(response.token);
      setUser(response.user);
      showToast('Logged in successfully!', 'success');
      return response.user;
    } catch (error) {
      showToast(error.message || 'Login failed', 'error');
      throw error;
    }
  };

  const registerClient = async (data) => {
    try {
      const response = await api.post('/auth/register/client', data);
      localStorage.setItem('token', response.token);
      setToken(response.token);
      setUser(response.user);
      showToast('Registered successfully!', 'success');
      return response.user;
    } catch (error) {
      showToast(error.message || 'Registration failed', 'error');
      throw error;
    }
  };

  const registerAgency = async (data) => {
    try {
      const response = await api.post('/auth/register/agency', data);
      showToast('Agency registration submitted! Waiting for admin approval.', 'success');
      return response;
    } catch (error) {
      showToast(error.message || 'Agency registration failed', 'error');
      throw error;
    }
  };

  const logout = async () => {
    try {
      if (token) {
        await api.post('/auth/logout');
      }
    } catch (e) {
      console.warn('Backend logout failed or token already invalid:', e);
    } finally {
      localStorage.removeItem('token');
      setToken(null);
      setUser(null);
      showToast('Logged out successfully', 'success');
    }
  };

  const refreshUser = async () => {
    if (!token) return;
    try {
      const response = await api.get('/auth/me');
      setUser(response.user);
    } catch (error) {
      console.error('Refresh user failed:', error);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        login,
        registerClient,
        registerAgency,
        logout,
        refreshUser,
        showToast,
        toasts,
        removeToast,
      }}
    >
      {children}
      {/* Toast Alert Portal */}
      <div className="toast-container">
        {toasts.map((t) => (
          <div
            key={t.id}
            className={`toast toast-${t.type}`}
            onClick={() => removeToast(t.id)}
            style={{ cursor: 'pointer' }}
          >
            <span>{t.message}</span>
          </div>
        ))}
      </div>
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
