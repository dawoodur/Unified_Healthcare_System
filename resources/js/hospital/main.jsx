import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ensureCsrfCookie } from './api/client';
import { loadTranslations, I18nProvider } from './i18n';
import App from './App';

function HospitalBootstrap() {
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
        console.error('Hospital app bootstrap failed:', error);

        if (active) {
          setBootError('The hospital workspace could not start. Please reload the page and try again.');
        }
      });

    return () => {
      active = false;
    };
  }, []);

  if (bootError) {
    return (
      <div className="hospital-page">
        <div className="hospital-card" role="alert">
          <h2>Hospital workspace unavailable</h2>
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
      <div className="hospital-page">
        <div className="hospital-card">Loading hospital workspace…</div>
      </div>
    );
  }

  return (
    <I18nProvider translations={translations}>
      <App />
    </I18nProvider>
  );
}

const container = document.getElementById('hospital-app');

if (container) {
  createRoot(container).render(
    <React.StrictMode>
      <HospitalBootstrap />
    </React.StrictMode>
  );
} else {
  console.error('Hospital app root element #hospital-app was not found.');
}
