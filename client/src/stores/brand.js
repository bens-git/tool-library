// stores/categories.js
import { defineStore } from "pinia";
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
          brandId: this.myBrandsListFilters.brandId,
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
      await this.fetchMyBrands();

      return data;
    },

    async updateBrand(brand) {
      const { sendRequest } = useApi();

      const data = await sendRequest(`brands/${brand.id}`, "put", brand);
      await this.fetchMyBrands();

      return data;
    },

    async deleteBrand(brandId) {
      const { sendRequest } = useApi();
      await sendRequest(`brands/${brandId}`, "delete");
      await this.fetchMyBrands();
    },
  },
});
