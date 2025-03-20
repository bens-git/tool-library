import { defineStore } from "pinia";
import axios from "../axios";
import { useResponseStore } from "./response";
import { useLoadingStore } from "./loading";
import useApi from "@/stores/api";

export const useLocationStore = defineStore("location", {
  state: () => ({
    locations: [],
  }),

  actions: {
    async fetchLocations() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("fetchLocations");

      try {
        const response = await axios.get("/locations");
        this.locations = response.data;
        responseStore.setResponse(true, "Locations fetched successfully.");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchLocations");
      }
    },

    getLocationById(id) {
      return this.locations.find((location) => location.id === id) || {};
    },

    async updateUserLocation(location) {
      const { sendRequest } = useApi();
      const data = await sendRequest(
        `/user/location/${location.id}`,
        "PUT",
        location
      );
      return data;
    },

    updateLocationInState(updatedLocation) {
      const index = this.locations.findIndex(
        (location) => location.id === updatedLocation.id
      );
      if (index !== -1) {
        this.locations.splice(index, 1, updatedLocation);
      }
    },

    async fetchUserLocation() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest("me/location");

      return data.data;
    },
  },
});
