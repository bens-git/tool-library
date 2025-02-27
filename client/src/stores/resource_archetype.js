import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useResourceArchetypeStore = defineStore("resourcearchetype", {
  state: () => ({
    address: null,
    allResourceArchetypes: [],
    dateRange: [
      new Date(new Date().setHours(9, 0, 0, 0)),
      new Date(new Date().setHours(17, 0, 0, 0)),
    ],
    itemsPerPage: 10,
    location: null,
    order: "asc",
    page: 1,
    paginatedResourceArchetypes: [],
    radius: 10,
    search: "",
    selectedBrandId: null,
    selectedCategoryId: null,
    selectedResourceArchetypeId: null,
    selectedUsageId: null,
    sortBy: [{ key: "name", order: "asc" }],
    toolResourceArchetypes: null,
    totalToolResourceArchetypes: 0,
    totalResourceArchetypesWithItems: 0,
    totalUserResourceArchetypes: 0,
    resourcearchetypesWithItems: [],
    userResourceArchetypes: [],
    resource: "TOOL",

    myResourceArchetypesListPage: 1,
    myResourceArchetypesListItemsPerPage: 10,
    myResourceArchetypesListSortBy: [{ key: "name", order: "asc" }],
    myResourceArchetypesListFilters: { categoryId: null, usageId: null, search: null },
    myResourceArchetypesListResourceArchetypes: [],
    myResourceArchetypesListTotalResourceArchetypes: 0,
    myResourceArchetypesListSelectedResourceArchetype: null,
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

      this.fetchResourceArchetypesWithItems();
    },

    setLocation(location) {
      this.location = location;
    },
    setAddress(address) {
      this.address = address;
    },
    updateResourceArchetypesWithItemsOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchResourceArchetypesWithItems();
    },

    updateMyResourceArchetypesListOptions({ page, itemsPerPage, sortBy }) {
      this.myResourceArchetypesListPage = page;
      this.myResourceArchetypesListItemsPerPage = itemsPerPage;
      this.myResourceArchetypesListSortBy = sortBy;

      this.fetchMyResourceArchetypes();
    },

    async fetchAutocompleteSelectResourceArchetypes(search) {
      const { fetchRequest } = useApi();

      const resourceArchetypes = await fetchRequest(
        "resource-archetypes", // API endpoint
        {
          itemsPerPage: 1000,
          sortBy: null,
          search: search,
          categoryId: null,
          usageId: null,
          brandId: null,
          resourcearchetypeId: null,
          startDate: null,
          endDate: null,
          location: null,
          radius: null,
          resource: null,
        }
      );

      return resourceArchetypes.data;
    },

    async fetchResourceArchetypesWithItems() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "resource-archetypes-with-items", // API endpoint
        {
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
          order: this.order,
          search: this.search,
          categoryId: this.selectedCategoryId,
          usageId: this.selectedUsageId,
          brandId: this.selectedBrandId,
          resourcearchetypeId: this.selectedResourceArchetypeId,
          startDate: this.dateRange[0].toISOString(),
          endDate: this.dateRange[this.dateRange.length - 1].toISOString(),
          location: this.location,
          radius: this.radius,
          resource: this.resource,
        }
      );

      this.resourcearchetypesWithItems = data.data;
      this.totalResourceArchetypesWithItems = data.total;
    },

    async fetchResourceArchetypes() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "resource-archetypes", // API endpoint
        {
          sortBy: this.sortBy,
          itemsPerPage: 1000,
        }
      );

      this.resourceArchetypes = data.data;
      this.totalResourceArchetypes = data.total;
    },

    async fetchMaterialResources() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "resource-archetypes", // API endpoint
        {
          sortBy: this.sortBy,
          itemsPerPage: 1000,
          resource: "material",
        }
      );

      return data;
    },

    async fetchMyResourceArchetypes() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "me/resource-archetypes", // API endpoint
        {
          page: this.myResourceArchetypesListPage,
          itemsPerPage: this.myResourceArchetypesListItemsPerPage,
          sortBy: this.myResourceArchetypesListSortBy,
          search: this.myResourceArchetypesListFilters.search,
          categoryId: this.myResourceArchetypesListFilters.categoryId,
          usageId: this.myResourceArchetypesListFilters.usageId,
        }
      );
      this.myResourceArchetypesListResourceArchetypes = data?.data?.map((userResourceArchetypes) => {
        return {
          ...userResourceArchetypes,
          category_ids: userResourceArchetypes.category_ids
            ? userResourceArchetypes.category_ids.split(",").map((id) => Number(id.trim()))
            : [],
          usage_ids: userResourceArchetypes.usage_ids
            ? userResourceArchetypes.usage_ids.split(",").map((id) => Number(id.trim()))
            : [],
        };
      });

      this.myResourceArchetypesListTotalResourceArchetypes = data.total;
    },

    async postResourceArchetype(resourcearchetypeData) {
      const { sendRequest } = useApi();

      const data = await sendRequest("resource-archetypes", "post", resourcearchetypeData);

      if (data?.success) {
        this.myResourceArchetypesListSelectedResourceArchetype = data.data;
        this.myResourceArchetypesListResourceArchetypes.push(data.data);
        this.myResourceArchetypesListTotalResourceArchetypes++;
        return data.data;
      }
    },

    async saveMyResourceArchetype(resourcearchetype) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `resource-archetypes/${resourcearchetype.id}`, // API endpoint
        "put", // HTTP method
        resourcearchetype // Payload
      );

      if (data?.success) {
        // Find and update the item in the store
        const updatedResourceArchetypeIndex = this.myResourceArchetypesListResourceArchetypes.findIndex(
          (resourcearchetype) => resourcearchetype.id === data.data.id
        );
        if (updatedResourceArchetypeIndex !== -1) {
          this.myResourceArchetypesListResourceArchetypes[updatedResourceArchetypeIndex] = data.data;
        }
      }
    },

    async deleteUserResourceArchetype(resourcearchetypeId) {
      console.log("delete", resourcearchetypeId);

      const { sendRequest } = useApi();
      const data = await sendRequest(
        `resource-archetypes/${resourcearchetypeId}`, // API endpoint
        "delete" // HTTP method
      );
      if (data?.success) {
        this.myResourceArchetypesListResourceArchetypes = this.myResourceArchetypesListResourceArchetypes.filter(
          (resourcearchetype) => resourcearchetype.id !== resourcearchetypeId
        );

        this.myResourceArchetypesListTotalResourceArchetypes -= 1;
        this.myResourceArchetypesListSelectedResourceArchetype = null;
      }
    },
  },
});
