import { defineStore } from "pinia";
import axios from "../axios";
import { useResponseStore } from "./response";
import { useLoadingStore } from "./loading";

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
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      responseStore.clearResponse();
      loadingStore.startLoading("updateUserLocation");

      try {
        if (location.id) {
          const response = await axios.put(
            `/user/location/${location.id}`,
            location
          );
          const updatedLocation = response.data;
          this.updateLocationInState(updatedLocation);
          responseStore.setResponse(true, "Location updated successfully.");
          return updatedLocation.id;
        }
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
        return null;
      } finally {
        loadingStore.stopLoading("updateUserLocation");
      }
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
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      loadingStore.startLoading("fetchUserLocation");

      try {
        const response = await axios.get(`/user/location`);
        return response.data.location;
      } catch (error) {
        responseStore.clearResponse();
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchUserLocation");
      }
    },
  },
});
