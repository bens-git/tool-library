import { defineStore } from "pinia";
import axios from "../axios";
import { useResponseStore } from "./response";
import { useLoadingStore } from "./loading";

export const useUserStore = defineStore("user", {
  state: () => ({
    user: null,
  }),

  actions: {
    async register(userData) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("register");

      try {
        const response = await axios.post("/register", userData);
        responseStore.setResponse(true, response.data.message);
      } catch (error) {
        responseStore.setResponse(
          false,
          error.response.data?.message
            ? error.response.data?.message
            : error.response.data?.[0]?.message
              ? error.response.data?.[0]?.message
              : "",
          [error.response.data.errors]
        );
      } finally {
        loadingStore.stopLoading("register");
      }
    },

    async login(email, password) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("login");

      try {
        const response = await axios.post("/login", { email, password });
        const token = response.data.token;

        localStorage.setItem("authToken", token);
        axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;

        await this.getUser();

        responseStore.setResponse(true, "Login successful.");
      } catch (error) {
        console.log(error);
        responseStore.setResponse(
          false,
          error.response.data?.message
            ? error.response.data?.message
            : error.response.data?.[0]?.message
              ? error.response.data?.[0]?.message
              : "",
          [error.response.data.errors]
        );
      } finally {
        loadingStore.stopLoading("login");
      }
    },

    loginToDiscord() {
      // Discord OAuth2 parameters
      const clientId = process.env.VUE_APP_DISCORD_CLIENT_ID;
      const redirectUri = encodeURIComponent(
        process.env.VUE_APP_DISCORD_REDIRECT_URI
      );

      const responseType = "code"; // OAuth response type
      const scope = "identify"; // Permissions requested

      // Construct the Discord OAuth2 authorization URL
      const discordAuthUrl = `https://discord.com/api/oauth2/authorize?client_id=${clientId}&redirect_uri=${redirectUri}&response_type=${responseType}&scope=${scope}`;

      // Redirect to Discord's OAuth2 page
      window.location.href = discordAuthUrl;
    },

    async linkWithDiscord(code) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("linkWithDiscord");

      try {
        const response = await axios.post("/link-with-discord", { code: code });
        await this.getUser();
        responseStore.setResponse(true, response.data.message);
      } catch (error) {
        responseStore.setResponse(
          false,
          error.response.data?.message
            ? error.response.data?.message
            : error.response.data?.[0]?.message
              ? error.response.data?.[0]?.message
              : "",
          [error.response.data.errors]
        );
      } finally {
        loadingStore.stopLoading("linkWithDiscord");
      }
    },

    async logout() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("logout");

      try {
        await axios.post("/logout");
        this.user = null;

        localStorage.removeItem("authToken");
        delete axios.defaults.headers.common["Authorization"];
        responseStore.setResponse(true, "Logout successful.");
      } catch (error) {
        this.user = null;
        localStorage.removeItem("authToken");
        localStorage.removeItem("user");
        delete axios.defaults.headers.common["Authorization"];
        responseStore.setResponse(true, "Logout successful.");
      } finally {
        loadingStore.stopLoading("logout");
      }
    },

    async getUser() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("getUser");

      try {
        const response = await axios.get("/user");
        this.user = response.data;
      } catch (error) {
        console.log(error);
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("getUser");
      }
    },

    async updateUser(user) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("updateUser");

      try {
        const response = await axios.put(`/user/${user.id}`, user);
        this.user = response.data;
        responseStore.setResponse(true, "User updated successfully.");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("updateUser");
      }
    },

    async deleteUser() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("deleteUser");

      try {
        await axios.delete(`/user`);
        this.user = null;
        responseStore.setResponse(true, "User deleted successfully.");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("deleteUser");
      }
    },

    async changePassword(currentPassword, newPassword, confirmPassword) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("changePassword");

      try {
        // Prepare the data to be sent in the request
        const data = {
          current_password: currentPassword,
          new_password: newPassword,
          new_password_confirmation: confirmPassword,
        };

        // Make the PUT request to update the password
        await axios.put(`/user/password`, data);

        // Update the user data if needed
        // this.user = response.data; // Uncomment if you have user data to update

        // Set success response
        responseStore.setResponse(true, "User password updated successfully.");
      } catch (error) {
        // Set error response
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("changePassword");
      }
    },

    async changePasswordWithToken(newPassword, confirmPassword, token) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("changePassword");

      try {
        // Prepare the data to be sent in the request
        const data = {
          new_password: newPassword,
          new_password_confirmation: confirmPassword,
          token: token,
        };

        // Make the PUT request to update the password
        await axios.put(`/user/password-with-token`, data);

        // Update the user data if needed
        // this.user = response.data; // Uncomment if you have user data to update

        // Set success response
        responseStore.setResponse(true, "User password updated successfully.");
      } catch (error) {
        // Set error response
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("changePassword");
      }
    },

    async requestPasswordReset(email) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("requestPasswordReset");

      try {
        await axios.post(`/request-password-reset`, email);
        responseStore.setResponse(true, "Password request sent.");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("requestPasswordReset");
      }
    },
  },

  persist: {
    enabled: true,
    strategies: [
      {
        key: "userStore",
        storage: localStorage,
      },
    ],
  },
});
