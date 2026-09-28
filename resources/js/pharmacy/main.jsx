import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ensureCsrfCookie } from './api/client';
import { loadTranslations, I18nProvider } from './i18n';
import App from './App';

function PharmacyBootstrap() {
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
        console.error('Pharmacy app bootstrap failed:', error);

        if (active) {
          setBootError('The pharmacy workspace could not start. Please reload the page and try again.');
        }
      });

    return () => {
      active = false;
    };
  }, []);

  if (bootError) {
    return (
      <div className="pharmacy-page">
        <div className="pharmacy-card" role="alert">
          <h2>Pharmacy workspace unavailable</h2>
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
      <div className="pharmacy-page">
        <div className="pharmacy-card">Loading pharmacy workspace…</div>
      </div>
    );
  }

  return (
    <I18nProvider translations={translations}>
      <App />
    </I18nProvider>
  );
}

const container = document.getElementById('pharmacy-app');

if (container) {
  createRoot(container).render(
    <React.StrictMode>
      <PharmacyBootstrap />
    </React.StrictMode>
  );
} else {
  console.error('Pharmacy app root element #pharmacy-app was not found.');
}
