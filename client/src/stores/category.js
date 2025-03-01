import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useCategoryStore = defineStore("category", {
  state: () => ({
    categories: [],
    totalCategories: 0,
    page: 1,
    itemsPerPage: 10,
    sortBy: "item_name",
    order: "asc",
    search: "",

    myCategoriesListPage: 1,
    myCategoriesListItemsPerPage: 10,
    myCategoriesListSortBy: [{ key: "name", order: "asc" }],
    myCategoriesListFilters: {
      search: null,
    },
    myCategoriesListCategories: [],
    myCategoriesListTotalCategories: 0,
    myCategoriesListSelectedCategories: null,
  }),
  actions: {
    async fetchCategories() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "categories", // API endpoint
        {
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
          search: this.search,
        }
      );
      this.categories = data?.data;
      this.totalCategories = data?.total;
    },

    async fetchMyCategories() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "me/categories", // API endpoint
        {
          page: this.myCategoriesListPage,
          itemsPerPage: this.myCategoriesListItemsPerPage,
          sortBy: this.myCategoriesListSortBy,
          search: this.myCategoriesListFilters.search,
        }
      );
      this.myCategoriesListCategories = data?.data;
      this.myCategoriesListTotalCategories = data?.total;
    },

    async createCategory(categoryData) {
      const { sendRequest } = useApi();

      const data = await sendRequest("categories", "post", categoryData);

      await this.fetchMyCategories();

      return data;
    },

    async updateCategory(category) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `categories/${category.id}`, 
        "put", 
        category 
      );

      await this.fetchMyCategories();

      return data;
    },

    async deleteCategory(categoryId) {
      const { sendRequest } = useApi();
      await sendRequest(
        `categories/${categoryId}`,
        "delete" 
      );
      await this.fetchMyCategories();
    },
  },
});
