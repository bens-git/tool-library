import { useLoadingStore } from "@/Stores/loading";
import { useUserStore } from "@/Stores/user";
import { useResponseStore } from "@/Stores/response";
import axios from "axios";

export default function useApi() {
  const loadingStore = useLoadingStore();
  const responseStore = useResponseStore();
  const userStore = useUserStore();

  const apiHost = import.meta.env.VUE_APP_API_HOST;
  const environment = import.meta.env.VUE_APP_ENVIRONMENT;

  const baseApiURL =
    environment === "development"
      ? `http://${apiHost}/api`
      : `https://${apiHost}/api`;

  const baseWebURL =
    environment === "development"
      ? `http://${apiHost}`
      : `https://${apiHost}`;

  // Axios instances
  const apiClient = axios.create({
    baseURL: baseApiURL,
    withCredentials: true,
  });

  const webClient = axios.create({
    baseURL: baseWebURL,
    withCredentials: true,
  });

  // Add auth token if present
  const addAuthInterceptor = (client) => {
    client.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem("authToken");
        if (token) config.headers["Authorization"] = `Bearer ${token}`;
        return config;
      },
      (error) => Promise.reject(error)
    );
  };

  addAuthInterceptor(apiClient);
  addAuthInterceptor(webClient);

  // Generate unique request key
  const generateRequestKey = (endpoint) => `${endpoint}-${Date.now()}`;

  // Track if CSRF cookie was already called
  let csrfCalled = false;

  const handleApiError = async (error) => {
    if (
      error.response?.status === 401 ||
      error.response?.data?.message === "Unauthenticated."
    ) {
      localStorage.removeItem("authToken");
      localStorage.removeItem("user");
      await userStore.logout();
      responseStore.setResponse(
        false,
        "Session expired. Please log in again.",
        error?.response?.data?.errors
      );
      window.location.href = "/login";
    } else {
      responseStore.setResponse(
        false,
        error?.response?.data?.message || "An error occurred",
        error?.response?.data?.errors
      );
      return error?.response;
    }
  };

  /**
   * Central request handler
   * @param {string} endpoint
   * @param {string} method
   * @param {object} params
   * @param {boolean} useWeb
   */
  const handleRequest = async (endpoint, method = "GET", params = null, useWeb = false) => {
    const requestKey = generateRequestKey(endpoint);
    const client = useWeb ? webClient : apiClient;

    try {
      loadingStore.startLoading(requestKey);

      // Only call CSRF cookie once per session
      if (useWeb && !csrfCalled) {
        await client.get("/sanctum/csrf-cookie");
        csrfCalled = true;
      }

      const config = { url: endpoint, method };

      if (method.toUpperCase() === "GET") {
        config.params = params;
      } else {
        config.data = params;
      }

      const response = await client(config);

      // Set success message for non-GET
      if (response && method.toUpperCase() !== "GET") {
        responseStore.setResponse(true, response?.data?.message || "Success");
      }

      return response.data;
    } catch (error) {
      return handleApiError(error);
    } finally {
      loadingStore.stopLoading(requestKey);
    }
  };

  const fetchRequest = (endpoint, params = null, useWeb = false) =>
    handleRequest(endpoint, "GET", params, useWeb);

  const sendRequest = (endpoint, method = "POST", data = null, useWeb = false) =>
    handleRequest(endpoint, method, data, useWeb);

  const fullImageUrl = (imagePath) =>
    `${environment === "development" ? `http://${apiHost}` : `https://${apiHost}`}/${imagePath}`;

  return { handleRequest, fetchRequest, sendRequest, fullImageUrl };
}
