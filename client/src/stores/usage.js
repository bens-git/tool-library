import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useUsageStore = defineStore("usage", {
  state: () => ({
    usages: [],
    totalUsages: 0,
    page: 1,
    itemsPerPage: 10,
    sortBy: "item_name",
    order: "asc",
    search: "",

    myUsagesListPage: 1,
    myUsagesListItemsPerPage: 10,
    myUsagesListSortBy: [{ key: "name", order: "asc" }],
    myUsagesListFilters: {
      search: null,
    },
    myUsagesListUsages: [],
    myUsagesListTotalUsages: 0,
    myUsagesListSelectedUsages: null,
  }),
  actions: {
    async fetchUsages() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "usages", // API endpoint
        {
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
          search: this.search,
        }
      );
      this.usages = data?.data;
      this.totalUsages = data?.total;
    },

    async fetchMyUsages() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest("me/usages", {
        page: this.myUsagesListPage,
        itemsPerPage: this.myUsagesListItemsPerPage,
        sortBy: this.myUsagesListSortBy,
        search: this.myUsagesListFilters.search,
      });
      this.myUsagesListUsages = data?.data;
      this.myUsagesListTotalUsages = data?.total;
    },

    async createUsage(usageData) {
      const { sendRequest } = useApi();

      const data = await sendRequest("usages", "post", usageData);

      await this.fetchMyUsages();

      return data;
    },

    async saveUsage(usage) {
      const { sendRequest } = useApi();

      const data = await sendRequest(`usages/${usage.id}`, "put", usage);

      await this.fetchMyUsages();

      return data;
    },

    async deleteUsage(usageId) {
      const { sendRequest } = useApi();
      await sendRequest(
        `usages/${usageId}`, 
        "delete" 
      );
      await this.fetchMyUsages();
    },
  },
});
