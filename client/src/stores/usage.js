import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useLoadingStore } from "./loading";
import { useResponseStore } from "./response";

export const useUsageStore = defineStore("usage", {
  state: () => ({
    usages: [],
    totalUsages: 0,
    userUsages: [],
    totalUserUsages: 0,
    search: "",
    page: 1,
    itemsPerPage: 10,
    sortBy: "item_name",
    order: "asc",
    paginateUsages: false,
  }),
  actions: {
    updateUserUsagesOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchUserUsages();
    },
    async fetchUsages() {
      try {
        const response = await apiClient.get("usages", {
          params: {
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            order: this.order,
            search: this.search,
          },
        });
        this.usages = response.data.data;
        this.totalUsages = response.data.count;
      } catch (error) {
        console.error("There was an error fetching the usages:", error);
      }
    },

    async fetchUserUsages() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      loadingStore.startLoading("fetchUserUsages");
      try {
        const response = await apiClient.get("user/usages", {
          params: {
            paginate: true,
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            search: this.search,
          },
        });
        this.userUsages = response.data.usages;
        this.totalUserUsages = response.data.count;
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchUserUsages");
      }
    },

    async createUsage(usageData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("createUsage");

      try {
        const response = await apiClient.post("/usages", usageData, {
          headers: { "Content-Usage": "multipart/form-data" },
        });
        this.userUsages.push(response.data);
        this.totalUserUsages++;
        responseStore.setResponse(true, "Usage created successfully");
      } catch (error) {
        console.log(error);
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("createUsage");
      }
    },

    async updateUsage(usageData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("updateUsage");

      try {
        // Create a new FormData object
        const formData = new FormData();

        // Append all item data fields to formData
        for (const [key, value] of usageData.entries()) {
          formData.append(key, value);
        }

        // Send POST request with FormData
        const response = await apiClient.post(
          `/update-usage/${usageData.get("id")}`,
          formData,
          {
            headers: { "Content-Usage": "multipart/form-data" },
          }
        );

        // Find and update the item in the store
        const updatedUsageIndex = this.userUsages.findIndex(
          (usage) => usage.id === response.data.id
        );
        if (updatedUsageIndex !== -1) {
          this.userUsages[updatedUsageIndex] = response.data;
        }

        responseStore.setResponse(true, "Usage updated successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("updateUsage");
      }
    },

    async deleteUsage(usageId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("deleteUsage");

      try {
        await apiClient.delete(`/usages/${usageId}`);
        this.userUsages = this.userUsages.filter(
          (usage) => usage.id !== usageId
        );
        this.totalUserUsages--;
        responseStore.setResponse(true, "Usage deleted successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("deleteUsage");
      }
    },
  },
});
