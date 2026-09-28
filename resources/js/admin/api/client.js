import axios from 'axios';

// Sanctum SPA (cookie) auth — same pattern as the other five sections.
const client = axios.create({
  baseURL: window.ADMIN_API_BASE,
  withCredentials: true,
  withXSRFToken: true,
});

let csrfReady = null;

export function ensureCsrfCookie() {
  if (!csrfReady) {
    csrfReady = axios.get(window.SANCTUM_CSRF_URL, { withCredentials: true });
  }
  return csrfReady;
}

client.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    if (status === 401 || status === 403) {
      window.location.href = window.ADMIN_APP_BASE.replace(/\/admin$/, '/login');
    }
    return Promise.reject(error);
  }
);

export default client;
