import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useCategoryStore = defineStore("category", {
  state: () => ({
    categoryListPage: 1,
    categoryListItemsPerPage: 10,
    categoryListSortBy: [{ key: "name", order: "asc" }],
    categoryListFilters: {
      search: null,
    },
    categoryListCategories: [],
    categoryListTotalCategories: 0,
    categoryListSelectedCategories: null,
  }),
  actions: {
    async destroy(categoryId) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`categories/${categoryId}`, "delete");
      await this.index();
      return data;
    },

    async index() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "me/categories", // API endpoint
        {
          page: this.categoryListPage,
          itemsPerPage: this.categoryListItemsPerPage,
          sortBy: this.categoryListSortBy,
          search: this.categoryListFilters.search,
        }
      );
      this.categoryListCategories = data?.data;
      this.categoryListTotalCategories = data?.total;
      return data.data;
    },

    async indexForAutocomplete(search) {
      const { fetchRequest } = useApi();

      const categories = await fetchRequest(
        "categories", // API endpoint
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

      return categories.data;
    },

   
    async store(categoryData) {
      const { sendRequest } = useApi();

      const data = await sendRequest("categories", "post", categoryData);

      await this.fetchMyCategories();

      return data;
    },

    async update(category) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `categories/${category.id}`,
        "put",
        category
      );

      await this.fetchMyCategories();

      return data;
    },
  },
});
