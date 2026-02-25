import axios from 'axios'
import { useLoadingStore } from '@/Stores/loading'

// Create custom instance
const api = axios.create({
    baseURL: '', // Use relative URLs - routes are defined in web.php without /api prefix
})

// Request interceptor
api.interceptors.request.use(
    (config) => {
        const loadingStore = useLoadingStore()
        loadingStore.start()

        const token = localStorage.getItem('authToken')

        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }

        // Fix: If the URL is absolute (full URL from route()), remove baseURL to avoid double URLs
        if (config.url && (config.url.startsWith('http://') || config.url.startsWith('https://'))) {
            // Extract the path from the full URL
            const url = new URL(config.url)
            config.url = url.pathname + url.search
        }

        return config
    },
    (error) => {
        const loadingStore = useLoadingStore()
        loadingStore.finish()
        return Promise.reject(error)
    }
)

// Response interceptor
api.interceptors.response.use(
    (response) => {
        const loadingStore = useLoadingStore()
        loadingStore.finish()
        return response
    },
    (error) => {
        const loadingStore = useLoadingStore()
        loadingStore.finish()
        return Promise.reject(error)
    }
)

export default api