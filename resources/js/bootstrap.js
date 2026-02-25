import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set up CSRF token for all axios requests
function getCsrfToken() {
    // Try to get from window.csrfToken (set by app.js from Inertia props)
    if (window.csrfToken) {
        return window.csrfToken;
    }
    // Fallback: get from meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : null;
}

// Add CSRF token to requests
axios.interceptors.request.use((config) => {
    const csrfToken = getCsrfToken();
    if (csrfToken && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(config.method?.toUpperCase())) {
        config.headers['X-CSRF-TOKEN'] = csrfToken;
    }
    return config;
});

// Handle 419 Page Expired error - redirect to login
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 419) {
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);
