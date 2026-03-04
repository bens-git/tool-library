import { defineStore } from "pinia";
export const useResponseStore = defineStore("response", {
  state: () => ({
    response: {
      success: false,
      message: "",
      errors: [],
    },
  }),

  actions: {
    setResponse(success, message, errors = []) {
      this.response = {
        success,
        message,
        errors,
      };
    },

    setSuccess(message) {
      this.response = {
        success: true,
        message,
        errors: [],
      };
    },

    setError(message, errors = []) {
      this.response = {
        success: false,
        message,
        errors,
      };
    },

    clearResponse() {
      this.response = {
        success: false,
        message: "",
        errors: [],
      };
    },
  },
});
