// stores/categories.js
import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useLoadingStore } from "./loading";
import { useResponseStore } from "./response";
import useApi from "@/stores/api";

export const useBrandStore = defineStore("brand", {
  state: () => ({
    myBrandsListPage: 1,
    myBrandsListItemsPerPage: 10,
    myBrandsListSortBy: [{ key: "name", order: "asc" }],
    myBrandsListFilters: { brandId: null, search: null },
    myBrandsListBrands: [],
    myBrandsListTotalBrands: 0,
  }),
  actions: {
    async fetchMyBrands() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "me/brands", // API endpoint
        {
          page: this.myBrandsListPage,
          itemsPerPage: this.myBrandsListItemsPerPage,
          sortBy: this.myBrandsListSortBy,
          archetypeId: this.myBrandsListFilters.archetypeId,
          brandId: this.myBrandsListFilters.brandId,
          resource: this.myBrandsListFilters.resource,
          search: this.myBrandsListFilters.search,
        }
      );
      this.myBrandsListBrands = data.data;
      this.myBrandsListTotalBrands = data.total;
    },

    async fetchInitialItemFormBrands(search) {
      const { fetchRequest } = useApi();

      const brands = await fetchRequest(
        "brands", // API endpoint
        {
          itemsPerPage: 1000,
          sortBy: null,
          search: search,
          brandId: null,
        }
      );

      return brands.data;
    },

    async fetchAutocompleteSelectBrands(search) {
      const { fetchRequest } = useApi();

      const brands = await fetchRequest(
        "brands", // API endpoint
        {
          itemsPerPage: 1000,
          sortBy: null,
          search: search,
          brandId: null,
        }
      );

      return brands.data;
    },

    async createBrand(brandData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`brands`, "POST", brandData);
      if (data) {
        this.myBrandsListBrands.push(data.data);
        this.myBrandsListTotalBrands++;
      }
    },

    async updateMyBrand(brand) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `brands/${brand.id}`, // API endpoint
        "put", // HTTP method
        brand // Payload
      );

      if (data?.success) {
        // Find and update the brand in the store
        const updatedIndex = this.myBrandListItems.findIndex(
          (brand) => brand.id === data.data.id
        );
        if (updatedIndex !== -1) {
          this.myBrandsListItems[updatedIndex] = data.data;
        }
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
