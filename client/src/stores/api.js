import { useLoadingStore } from "@/stores/loading";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
import apiClient from "@/axios";

export default function useApi() {
  const loadingStore = useLoadingStore();
  const responseStore = useResponseStore();
  const userStore = useUserStore();

  /**
   * Generates a unique request key based on the endpoint and timestamp.
   * @param {string} endpoint - The API endpoint being called.
   * @returns {string} - A unique request key.
   */
  function generateRequestKey(endpoint) {
    return `${endpoint}-${Date.now()}`;
  }

  /**
   * Handles API requests with automatic loading and error management.
   * @param {string} endpoint - The API endpoint.
   * @param {string} method - The HTTP method (GET, POST, PUT, DELETE, etc.).
   * @param {object} [params] - The payload for the request (optional for GET).
   * @returns {Promise<any>} - The API response data.
   */
  const handleRequest = async (endpoint, method = "GET", params = null) => {
    // Generate a unique request key
    const requestKey = generateRequestKey(endpoint);

    try {
      loadingStore.startLoading(requestKey);

      const config = {
        url: endpoint,
        method,
      };

      if (method.toUpperCase() === "GET") {
        config.params = params; // Query parameters for GET requests
      } else {
        config.data = params; // Payload for non-GET requests
      }

      const response = await apiClient(config);

      //respond with message if send
      if (response && method.toUpperCase() != "GET") {
        responseStore.setResponse(true, response?.data?.message || "Success");
      }
      return response.data; // Return the response data
    } catch (error) {
      handleApiError(error); // Handle errors
    } finally {
      loadingStore.stopLoading(requestKey);
    }
  };

  const fetchRequest = async (endpoint, data = null) => {
    return handleRequest(endpoint, "GET", data);
  };

  const sendRequest = async (endpoint, method = "POST", data = null) => {
    return handleRequest(endpoint, method, data);
  };

  const handleApiError = async (error) => {
    if (
      error.response?.status === 401 ||
      error.response?.data?.message === "Unauthenticated."
    ) {
      await userStore.logout(); // Assuming you have this method in your user store
      responseStore.setResponse(
        false,
        "Session expired. Please log in again.",
        error?.response?.data?.errors
      );

      window.location.href = "login-form";
    } else {
      // Handle other errors
      responseStore.setResponse(
        false,
        error?.response?.data?.message || "An error occurred",
        error?.response?.data?.errors
      );
    }
  };

  const apiHost = process.env.VUE_APP_API_HOST;
  const environment = process.env.VUE_APP_ENVIRONMENT;

  const baseURL =
    environment == "development" ? `http://${apiHost}` : `https://${apiHost}`;

  const fullImageUrl = (imagePath) => {
    return `${baseURL}/${imagePath}`;
  };

  return { handleRequest, fetchRequest, sendRequest, fullImageUrl };
}
