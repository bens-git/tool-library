// stores/categories.js
import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useBrandStore = defineStore("brand", {
  state: () => ({
    brandListPage: 1,
    brandListItemsPerPage: 10,
    brandListSortBy: [{ key: "name", order: "asc" }],
    brandListFilters: { brandId: null, search: null },
    brandListBrands: [],
    brandListTotalBrands: 0,
  }),
  actions: {
    async destroy(brandId) {
      const { sendRequest } = useApi();
      const data =await sendRequest(`brands/${brandId}`, "delete");
      await this.index();
      return data
    },

    async index() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "brands", 
        {
          page: this.brandListPage,
          itemsPerPage: this.brandListItemsPerPage,
          sortBy: this.brandListSortBy,
          brandId: this.brandListFilters.brandId,
          search: this.brandListFilters.search,
        }
      );
      this.brandListBrands = data.data;
      this.brandListTotalBrands = data.total;
    },

    async indexForAutocomplete(search) {
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

    async store(brandData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`brands`, "POST", brandData);
      await this.index();

      return data;
    },

    async update(brand) {
      const { sendRequest } = useApi();

      const data = await sendRequest(`brands/${brand.id}`, "put", brand);
      await this.index();

      return data;
    },

   
  },
});
