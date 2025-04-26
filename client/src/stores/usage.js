import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useUsageStore = defineStore("usage", {
  state: () => ({
    usageListPage: 1,
    usageListItemsPerPage: 10,
    usageListSortBy: [{ key: "name", order: "asc" }],
    usageListFilters: {
      search: null,
    },
    usageListUsages: [],
    usageListTotalUsages: 0,
    usageListSelectedUsages: null,
  }),
  actions: {
    async destroy(usageId) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`usages/${usageId}`, "delete");
      await this.index();
      return data;
    },

    async index() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest("usages", {
        page: this.usageListPage,
        itemsPerPage: this.usageListItemsPerPage,
        sortBy: this.usageListSortBy,
        search: this.usageListFilters.search,
      });
      this.usageListUsages = data?.data;
      this.usageListTotalUsages = data?.total;
    },

    async indexForAutocomplete(search) {
      const { fetchRequest } = useApi();

      const usages = await fetchRequest(
        "usages", // API endpoint
        {
          itemsPerPage: 1000,
          sortBy: null,
          search: search,
          categoryId: null,
          usageId: null,
          brandId: null,
          archetypeId: null,
          startDate: null,
          endDate: null,
          location: null,
          radius: null,
          resource: null,
        }
      );

      return usages.data;
    },

    async store(usageData) {
      const { sendRequest } = useApi();

      const data = await sendRequest("usages", "post", usageData);

      await this.index();

      return data;
    },

    async update(usage) {
      const { sendRequest } = useApi();

      const data = await sendRequest(`usages/${usage.id}`, "put", usage);

      await this.index();

      return data;
    },
  },
});
