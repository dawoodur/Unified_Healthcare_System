import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ensureCsrfCookie } from './api/client';
import { loadTranslations, I18nProvider } from './i18n';
import App from './App';

function AdminBootstrap() {
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
        console.error('Admin app bootstrap failed:', error);

        if (active) {
          setBootError('The admin console could not start. Please reload the page and try again.');
        }
      });

    return () => {
      active = false;
    };
  }, []);

  if (bootError) {
    return (
      <div className="admin-page">
        <div className="admin-card" role="alert">
          <h2>Admin console unavailable</h2>
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
      <div className="admin-page">
        <div className="admin-card">Loading admin console…</div>
      </div>
    );
  }

  return (
    <I18nProvider translations={translations}>
      <App />
    </I18nProvider>
  );
}

const container = document.getElementById('admin-app');

if (container) {
  createRoot(container).render(
    <React.StrictMode>
      <AdminBootstrap />
    </React.StrictMode>
  );
} else {
  console.error('Admin app root element #admin-app was not found.');
}
