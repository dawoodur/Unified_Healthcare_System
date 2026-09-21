import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ensureCsrfCookie } from './api/client';
import { loadTranslations, I18nProvider } from './i18n';
import App from './App';

function DoctorBootstrap() {
  const [translations, setTranslations] = useState(null);
  const [bootError, setBootError] = useState(null);

  useEffect(() => {
    let active = true;

    ensureCsrfCookie()
      .then(() => loadTranslations())
      .then((loadedTranslations) => {
        if (active) {
          setTranslations(loadedTranslations);
        }
      })
      .catch((error) => {
        console.error('Doctor app bootstrap failed:', error);

        if (active) {
          setBootError('The doctor workspace could not start. Please reload the page and try again.');
        }
      });

    return () => {
      active = false;
    };
  }, []);

  if (bootError) {
    return (
      <div className="doctor-page">
        <div className="doctor-card" role="alert">
          <h2>Doctor workspace unavailable</h2>
          <p className="muted">{bootError}</p>
          <button type="button" className="btn" onClick={() => window.location.reload()}>
            Reload page
          </button>
        </div>
      </div>
    );
  }

  if (!translations) {
    return (
      <div className="doctor-page">
        <div className="doctor-card">Loading doctor workspace…</div>
      </div>
    );
  }

  return (
    <I18nProvider translations={translations}>
      <App />
    </I18nProvider>
  );
}

const container = document.getElementById('doctor-app');

if (container) {
  createRoot(container).render(
    <React.StrictMode>
      <DoctorBootstrap />
    </React.StrictMode>
  );
} else {
  console.error('Doctor app root element #doctor-app was not found.');
}