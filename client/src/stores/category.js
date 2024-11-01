// stores/categories.js
import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useLoadingStore } from "./loading";
import { useResponseStore } from "./response";

export const useCategoryStore = defineStore("category", {
  state: () => ({
    userCategories: [],
    totalUserCategories: 0,
    categories: [],
    totalCategories: 0,
    page: 1,
    itemsPerPage: 10,
    sortBy: "item_name",
    order: "asc",
    search: "",
    paginateCategories: false,
  }),
  actions: {
    updateUserCategoriesOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchUserCategories();
    },
    async fetchCategories() {
      try {
        const response = await apiClient.get("/categories", {
          params: {
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            order: this.order,
            search: this.search,
          },
        });
        this.categories = response.data.data;
        this.totalCategories = response.data.count;
      } catch (error) {
        console.error("Error fetching categories:", error);
      }
    },

    async fetchUserCategories() {
      try {
        const response = await apiClient.get("/user/categories", {
          params: {
            paginate: true,
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            search: this.search,
          },
        });
        this.userCategories = response.data.categories;
        this.totalUserCategories = response.data.count;
      } catch (error) {
        console.error("Error fetching categories:", error);
      }
    },

    async createCategory(categoryData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("createCategory");

      try {
        const response = await apiClient.post("/categories", categoryData, {
          headers: { "Content-Category": "multipart/form-data" },
        });
        this.userCategories.push(response.data);
        this.totalUserCategories++;
        responseStore.setResponse(true, "Category created successfully");
      } catch (error) {
        console.log(error.response.data);
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("createCategory");
      }
    },

    async updateCategory(categoryData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("updateCategory");

      try {
        // Create a new FormData object
        const formData = new FormData();

        // Append all item data fields to formData
        for (const [key, value] of categoryData.entries()) {
          formData.append(key, value);
        }

        // Send POST request with FormData
        const response = await apiClient.post(
          `/update-category/${categoryData.get("id")}`,
          formData,
          {
            headers: { "Content-Category": "multipart/form-data" },
          }
        );

        // Find and update the item in the store
        const updatedCategoryIndex = this.userCategories.findIndex(
          (category) => category.id === response.data.id
        );
        if (updatedCategoryIndex !== -1) {
          this.userCategories[updatedCategoryIndex] = response.data;
        }

        responseStore.setResponse(true, "Category updated successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("updateCategory");
      }
    },

    async deleteCategory(categoryId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("deleteCategory");

      try {
        await apiClient.delete(`/categories/${categoryId}`);
        this.userCategories = this.userCategories.filter(
          (category) => category.id !== categoryId
        );
        this.totalUserCategories--;
        responseStore.setResponse(true, "Category deleted successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("deleteCategory");
      }
    },
  },
});
