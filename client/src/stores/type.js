import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useTypeStore = defineStore("type", {
  state: () => ({
    address: null,
    allTypes: [],
    dateRange: [
      new Date(new Date().setHours(9, 0, 0, 0)),
      new Date(new Date().setHours(17, 0, 0, 0)),
    ],
    itemsPerPage: 10,
    location: null,
    order: "asc",
    page: 1,
    paginatedTypes: [],
    radius: 10,
    search: "",
    selectedBrandId: null,
    selectedCategoryId: null,
    selectedTypeId: null,
    selectedUsageId: null,
    sortBy: "type_name",
    toolTypes: null,
    totalToolTypes: 0,
    totalTypesWithItems: 0,
    totalUserTypes: 0,
    typesWithItems: [],
    userTypes: [],
    resource: "TOOL",

    myTypesListPage: 1,
    myTypesListItemsPerPage: 10,
    myTypesListSortBy: [{ key: "name", order: "asc" }],
    myTypesListFilters: { categoryId: null, usageId: null, search: null },
    myTypesListTypes: [],
    myTypesListTotalTypes: 0,
    myTypesListSelectedType: null,
  }),
  actions: {
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

      this.fetchTypesWithItems();
    },

    setLocation(location) {
      this.location = location;
    },
    setAddress(address) {
      this.address = address;
    },
    updateTypesWithItemsOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchTypesWithItems();
    },

    updateMyTypesListOptions({ page, itemsPerPage, sortBy }) {
      this.myTypesListPage = page;
      this.myTypesListItemsPerPage = itemsPerPage;
      this.myTypesListSortBy = sortBy;

      this.fetchMyTypes();
    },

    async fetchAutocompleteSelectTypes(search) {
      const { fetchRequest } = useApi();

      const types = await fetchRequest(
        "types", // API endpoint
        {
          itemsPerPage: 1000,
          sortBy: null,
          search: search,
          categoryId: null,
          usageId: null,
          brandId: null,
          typeId: null,
          startDate: null,
          endDate: null,
          location: null,
          radius: null,
          resource: null,
        }
      );

      return types.data;
    },

    async fetchTypesWithItems() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "types-with-items", // API endpoint
        {
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
          resource: this.resource,
        }
      );

      this.typesWithItems = data.data;
      this.totalTypesWithItems = data.total;
    },

    async fetchTypes() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "types", // API endpoint
        {
          sortBy: this.sortBy,
          itemsPerPage: 1000,
        }
      );

      this.types = data.data;
      this.totalTypes = data.total;
    },

    async fetchMyTypes() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "me/types", // API endpoint
        {
          page: this.myTypesListPage,
          itemsPerPage: this.myTypesListItemsPerPage,
          sortBy: this.myTypesListSortBy,
          search: this.myTypesListFilters.search,
          categoryId: this.myTypesListFilters.categoryId,
          usageId: this.myTypesListFilters.usageId,
        }
      );
      this.myTypesListTypes = data?.data?.map((userTypes) => {
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

      this.myTypesListTotalTypes = data.total;
    },

    async createType(typeData) {
      const { sendRequest } = useApi();

      const data = await sendRequest("types", "post", typeData);

      if (data?.success) {
        this.myTypesListSelectedType = data.data;
        this.myTypesListTypes.push(data.data);
        this.myTypesListTotalTypes++;
        return data.data;
      }

    },

    async saveMyType(type) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `types/${type.id}`, // API endpoint
        "put", // HTTP method
        type // Payload
      );

      if (data?.success) {
        // Find and update the item in the store
        const updatedTypeIndex = this.myTypesListTypes.findIndex(
          (type) => type.id === data.data.id
        );
        if (updatedTypeIndex !== -1) {
          this.myTypesListTypes[updatedTypeIndex] = data.data;
        }
      }
    },

    

    async deleteUserType(typeId) {
      const { sendRequest } = useApi();
      await sendRequest(
        "delete", // HTTP method
        `types/${typeId}`, // API endpoint
        null, // Payload
        () => {
          this.userTypes = this.userTypes.filter((type) => type.id !== typeId);

          this.totalUserTypes -= 1;
          this.myTypesListSelectedType = null;
        }
      );
    },
  },
});
