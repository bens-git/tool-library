import axios from 'axios'
import { useLoadingStore } from '@/Stores/loading'

// Create custom instance
const api = axios.create({
    baseURL: '/api', // adjust if needed
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