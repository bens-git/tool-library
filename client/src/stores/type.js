import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useLoadingStore } from "./loading";
import { useResponseStore } from "./response";

export const useTypeStore = defineStore("type", {
  state: () => ({
    paginatedTypes: [],
    allTypes: [],
    userTypes: [],
    totalUserTypes: 0,
    totalTypes: 0,
    search: "",
    selectedCategoryId: null,
    selectedUsageId: null,
    selectedBrandId: null,
    selectedTypeId: null,
    dateRange: [
      new Date(new Date().setHours(9, 0, 0, 0)),
      new Date(new Date().setHours(17, 0, 0, 0)),
    ],

    page: 1,
    itemsPerPage: 10,
    sortBy: "type_name",
    order: "asc",
    location: null,
    address: null,
    radius: 10,
  }),
  actions: {
    setLocation(location) {
      this.location = location;
    },
    setAddress(address) {
      this.address = address;
    },
    updateOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchPaginatedTypes();
    },

    updateUserTypeOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchUserTypes();
    },

    async fetchPaginatedTypes() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      loadingStore.startLoading("fetchPaginatedTypes");

      try {
        const { data } = await apiClient.get("/paginated-types", {
          params: {
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            order: this.order,
            search: this.search,
            categoryId: this.selectedCategoryId,
            usageId: this.selectedUsageId,
            brandId: this.selectedBrandId,
            typeId: this.selectedTypeId,
            startDate: this.dateRange[0].toISOString(),
            endDate: this.dateRange[this.dateRange.length - 1].toISOString(),
            location: this.location,
            radius: this.radius,
          },
        });
        this.paginatedTypes = data.types;

        this.totalTypes = data.count;
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchPaginatedTypes");
      }
    },

    async fetchAllTypes() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      loadingStore.startLoading("fetchAllTypes");

      try {
        const { data } = await apiClient.get("/all-types", {
          params: {
            sortBy: this.sortBy,
            order: this.order,
          },
        });
        this.allTypes = data.types;

        this.totalTypes = data.count;
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchAllTypes");
      }
    },

    async fetchUserTypes() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      loadingStore.startLoading("fetchUserTypes");

      try {
        const { data } = await apiClient.get("/user/types", {
          params: {
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            search: this.search,
            categoryId: this.selectedCategoryId,
            usageId: this.selectedUsageId,
            startDate: this.dateRange[0].toISOString(),
            endDate: this.dateRange[this.dateRange.length - 1].toISOString(),
          },
        });

        this.userTypes = data?.types?.map((userTypes) => {
          return {
            ...userTypes,
            category_ids: userTypes.category_ids
              ? userTypes.category_ids.split(",").map((id) => Number(id.trim()))
              : [],
            usage_ids: userTypes.usage_ids
              ? userTypes.usage_ids.split(",").map((id) => Number(id.trim()))
              : [],
          };
        });

        this.totalUserTypes = data.count;
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchUserTypes");
      }
    },

    resetFilters() {
      this.search = "";
      this.selectedCategoryId = null;
      this.selectedUsageId = null;
      // Default start date with 9 AM
      // Default end date with 5 PM
      this.dateRange = [
        new Date(new Date().setHours(9, 0, 0, 0)),
        new Date(new Date().setHours(17, 0, 0, 0)),
      ];

      this.fetchPaginatedTypes();
    },

    async createType(typeData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("createType");

      try {
        const response = await apiClient.post("/types", typeData, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        this.userTypes.push(response.data);
        this.totalUserTypes++;
        responseStore.setResponse(true, "Type created successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("createType");
      }
    },

    async updateType(typeData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("updateType");

      try {
        // Create a new FormData object
        const formData = new FormData();

        // Append all item data fields to formData
        for (const [key, value] of typeData.entries()) {
          if (key === "newImages" || key === "removedImages") {
            // Convert array data to JSON string
            if (key === "removedImages") {
              formData.append(key, JSON.stringify(value));
            } else {
              // For 'newImages', append each file separately
              if (value.length > 0) {
                value.forEach((file, index) => {
                  formData.append(`${key}[${index}]`, file);
                });
              }
            }
          } else {
            formData.append(key, value);
          }
        }

        // Send POST request with FormData
        const response = await apiClient.post(
          `/update-type/${typeData.get("id")}`,
          formData,
          {
            headers: { "Content-Type": "multipart/form-data" },
          }
        );

        // Find and update the item in the store
        const updatedTypeIndex = this.userTypes.findIndex(
          (type) => type.id === response.data.id
        );
        if (updatedTypeIndex !== -1) {
          this.userTypes[updatedTypeIndex] = response.data;
        }

        responseStore.setResponse(true, "Type updated successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("updateType");
      }
    },

    async deleteType(typeId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("deleteType");

      try {
        await apiClient.delete(`/types/${typeId}`);
        this.userTypes = this.userTypes.filter((type) => type.id !== typeId);
        this.totalUserTypes--;
        responseStore.setResponse(true, "Type deleted successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("deleteType");
      }
    },
  },
});
