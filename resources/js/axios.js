import axios from "axios";

const apiHost = import.meta.env.APP_URL;
const environment = import.meta.env.VUE_APP_ENVIRONMENT;
const baseURL =
  environment == "development"
    ? `http://${apiHost}/api`
    : `https://${apiHost}/api`;

// Create an axios instance
const apiClient = axios.create({
  baseURL: `${baseURL}`,
});


// Request interceptor to add the Authorization header
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("authToken"); // Assuming the token is stored in localStorage

    if (token) {
      config.headers["Authorization"] = `Bearer ${token}`;
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

export default apiClient;
