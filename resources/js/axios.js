import axios from "axios";
import { useLoadingStore } from "@/Stores/loading";

const apiHost = import.meta.env.APP_URL;
const environment = import.meta.env.VUE_APP_ENVIRONMENT;

const baseURL =
  environment == "development"
    ? `http://${apiHost}/api`
    : `https://${apiHost}/api`;

const apiClient = axios.create({
  baseURL: `${baseURL}`,
});

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    const loadingStore = useLoadingStore();
    loadingStore.start();
    console.log('test')

    const token = localStorage.getItem("authToken");

    if (token) {
      config.headers["Authorization"] = `Bearer ${token}`;
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
    return Promise.reject(error);
  }
);

export default apiClient;