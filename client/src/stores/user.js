import { defineStore } from "pinia";
import useApi from "@/stores/api";
import axios from "../axios";

export const useUserStore = defineStore("user", {
  state: () => ({
    user: null,
  }),

  actions: {
    async register(userData) {
      const { sendRequest } = useApi();
      const data = await sendRequest("register", "POST", userData);

      return data;
    },

    async login(email, password) {
      const { sendRequest } = useApi();

      const data = await sendRequest("login", "POST", { email, password });
      if (data?.success && data?.token) {
        const token = data.token;

        localStorage.setItem("authToken", token);
        axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;

        await this.getUser();
      }
      return data;
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
      const { sendRequest } = useApi();

      const data = await sendRequest("link-with-discord", "POST", {
        code: code,
      });
      await this.getUser();
    },

    async logout() {
      const { sendRequest } = useApi();

      const data = await sendRequest("logout", "POST");
      this.user = null;

      localStorage.removeItem("authToken");
      localStorage.removeItem("user");
      delete axios.defaults.headers.common["Authorization"];
    },

    async getUser() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest("user");
      this.user = data.data;
    },

    async updateUser(user) {
      const { sendRequest } = useApi();

      const data = await sendRequest(`me`, "PUT", user);

      if (data?.success) {
        this.user = data.data;
      }
    },

    async deleteUser() {
      const { sendRequest } = useApi();

      const data = await sendRequest("user", "DELETE");

      if (data?.success) {
        this.user = null;
        localStorage.removeItem("authToken");
        localStorage.removeItem("user");
        delete axios.defaults.headers.common["Authorization"];
      }

      return data;
    },

    async changePassword(currentPassword, newPassword, confirmPassword) {
      const { sendRequest } = useApi();

      // Prepare the data to be sent in the request
      const user = {
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      };

      const data = await sendRequest("/user/password", "PUT", user);
    },

    async changePasswordWithToken(newPassword, confirmPassword, token) {
      const user = {
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
        token: token,
      };
      const { sendRequest } = useApi();

      const data = await sendRequest("/user/password-with-token", "PUT", user);
    },

    async requestPasswordReset(email) {
      const { sendRequest } = useApi();

      const data = await sendRequest("request-password-reset", "POST", email);
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
