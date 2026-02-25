import axios from "axios";
import { useLoadingStore } from "@/Stores/loading";
import { router } from '@inertiajs/vue3';

const apiHost = import.meta.env.APP_URL;
const environment = import.meta.env.VUE_APP_ENVIRONMENT;

const baseURL =
  environment == "development"
    ? `http://${apiHost}/api`
    : `https://${apiHost}/api`;

const apiClient = axios.create({
  baseURL: `${baseURL}`,
});

// Get CSRF token from page props (set by HandleInertiaRequests middleware)
function getCsrfToken() {
  // First try to get from window object (Inertia shares csrfToken)
  if (window.csrfToken) {
    return window.csrfToken;
  }
  // Fallback: get from meta tag
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  return metaTag ? metaTag.getAttribute('content') : null;
}

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    const loadingStore = useLoadingStore();
    loadingStore.start();

    const token = localStorage.getItem("authToken");
    const csrfToken = getCsrfToken();

    if (token) {
      config.headers["Authorization"] = `Bearer ${token}`;
    }

    // Add CSRF token for non-GET requests
    if (csrfToken && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(config.method?.toUpperCase())) {
      config.headers["X-CSRF-TOKEN"] = csrfToken;
    }

    return config;
  },
  (error) => {
    const loadingStore = useLoadingStore();
    loadingStore.finish();
    return Promise.reject(error);
  }
);

// Response interceptor
apiClient.interceptors.response.use(
  (response) => {
    const loadingStore = useLoadingStore();
    loadingStore.finish();
    return response;
  },
  (error) => {
    const loadingStore = useLoadingStore();
    loadingStore.finish();

    // Handle 419 Page Expired error - redirect to login
    if (error.response && error.response.status === 419) {
      router.get('/login');
    }

    return Promise.reject(error);
  }
);

export default apiClient;

