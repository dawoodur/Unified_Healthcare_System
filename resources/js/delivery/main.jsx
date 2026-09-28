import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ensureCsrfCookie } from './api/client';
import { loadTranslations, I18nProvider } from './i18n';
import App from './App';

function DeliveryBootstrap() {
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
        console.error('Delivery app bootstrap failed:', error);

        if (active) {
          setBootError('The delivery workspace could not start. Please reload the page and try again.');
        }
      });

    return () => {
      active = false;
    };
  }, []);

  if (bootError) {
    return (
      <div className="delivery-page">
        <div className="delivery-card" role="alert">
          <h2>Delivery workspace unavailable</h2>
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
      <div className="delivery-page">
        <div className="delivery-card">Loading delivery workspace…</div>
      </div>
    );
  }

  return (
    <I18nProvider translations={translations}>
      <App />
    </I18nProvider>
  );
}

const container = document.getElementById('delivery-app');

if (container) {
  createRoot(container).render(
    <React.StrictMode>
      <DeliveryBootstrap />
    </React.StrictMode>
  );
} else {
  console.error('Delivery app root element #delivery-app was not found.');
}
