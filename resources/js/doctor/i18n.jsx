import React, { createContext, useContext } from 'react';
import client from './api/client';

const I18nContext = createContext(null);

export function loadTranslations() {
  return client.get('/translations').then((res) => res.data);
}

export function I18nProvider({ translations, children }) {
  return <I18nContext.Provider value={translations}>{children}</I18nContext.Provider>;
}

/** e.g. const t = useT('dashboard'); t('welcome', { name: doctor.full_name }) */
export function useT(namespace) {
  const translations = useContext(I18nContext);
  const strings = translations?.[namespace] || {};

  return (key, replace) => {
    let value = strings[key] ?? key;
    if (replace) {
      Object.entries(replace).forEach(([placeholder, val]) => {
        value = value.replace(`:${placeholder}`, val);
      });
    }
    return value;
  };
}
