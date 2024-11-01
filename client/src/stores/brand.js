// stores/categories.js
import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useLoadingStore } from "./loading";
import { useResponseStore } from "./response";

export const useBrandStore = defineStore("brand", {
  state: () => ({
    brands: [],
    totalBrands: 0,
    userBrands: [],
    totalUserBrands: 0,
  }),
  actions: {
    async fetchBrands() {
      try {
        const response = await apiClient.get("/brands");
        this.brands = response.data.data;
        this.totalCategories = response.data.count;
      } catch (error) {
        console.error("Error fetching brands:", error);
      }
    },

    async fetchUserBrands() {
      try {
        const response = await apiClient.get("/user/brands", {
          params: {
            paginate: true,
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            search: this.search,
          },
        });
        this.userBrands = response.data.brands;
        this.totalUserBrands = response.data.count;
      } catch (error) {
        console.error("Error fetching brands:", error);
      }
    },

    async createBrand(brandData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("createBrand");

      try {
        const response = await apiClient.post("/brands", brandData, {
          headers: { "Content-Category": "multipart/form-data" },
        });
        this.brands.push(response.data);
        this.totalBrands++;
        responseStore.setResponse(true, "Brand created successfully");
      } catch (error) {
        console.log(error.response.data);
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("createBrand");
      }
    },

    async updateBrand(brandData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("updateBrand");

      try {
        // Create a new FormData object
        const formData = new FormData();

        // Append all item data fields to formData
        for (const [key, value] of brandData.entries()) {
          formData.append(key, value);
        }

        // Send POST request with FormData
        const response = await apiClient.post(
          `/update-brand/${brandData.get("id")}`,
          formData,
          {
            headers: { "Content-Brand": "multipart/form-data" },
          }
        );

        // Find and update the item in the store
        const updatedBrandIndex = this.userBrands.findIndex(
          (brand) => brand.id === response.data.id
        );
        if (updatedBrandIndex !== -1) {
          this.userBrands[updatedBrandIndex] = response.data;
        }

        responseStore.setResponse(true, "Brand updated successfully");
      } catch (error) {
        console.log(error);
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("updateBrand");
      }
    },

    async deleteBrand(brandId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("deleteBrand");

      try {
        await apiClient.delete(`/brands/${brandId}`);
        this.userBrands = this.userBrands.filter(
          (brand) => brand.id !== brandId
        );
        this.totalUserBrands--;
        responseStore.setResponse(true, "Brand deleted successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("deleteBrand");
      }
    },
  },
});
