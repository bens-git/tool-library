import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useArchetypeStore = defineStore("archetype", {
  state: () => ({
    address: null,
    allArchetypes: [],
    dateRange: [
      new Date(new Date().setHours(9, 0, 0, 0)),
      new Date(new Date().setHours(17, 0, 0, 0)),
    ],
    itemsPerPage: 10,
    location: null,
    order: "asc",
    page: 1,
    paginatedArchetypes: [],
    radius: 10,
    search: "",
    selectedBrandId: null,
    selectedCategoryId: null,
    selectedArchetypeId: null,
    selectedUsageId: null,
    sortBy: [{ key: "name", order: "asc" }],
    toolArchetypes: null,
    totalToolArchetypes: 0,
    totalArchetypesWithItems: 0,
    totalUserArchetypes: 0,
    archetypesWithItems: [],
    userArchetypes: [],
    resource: "TOOL",

    myArchetypesListPage: 1,
    myArchetypesListItemsPerPage: 10,
    myArchetypesListSortBy: [{ key: "name", order: "asc" }],
    myArchetypesListFilters: {
      usage: null,
      category: null,
      search: null,
      resource: null,
    },
    myArchetypesListArchetypes: [],
    myArchetypesListTotalArchetypes: 0,
    myArchetypesListSelectedArchetype: null,
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

      this.fetchArchetypesWithItems();
    },

    setLocation(location) {
      this.location = location;
    },
    setAddress(address) {
      this.address = address;
    },
    updateArchetypesWithItemsOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchArchetypesWithItems();
    },

    updateMyArchetypesListOptions({ page, itemsPerPage, sortBy, resource }) {
      this.myArchetypesListPage = page;
      this.myArchetypesListItemsPerPage = itemsPerPage;
      this.myArchetypesListSortBy = sortBy;
      this.myArchetypesListFilters.resource = resource;

      this.fetchMyArchetypes();
    },

    async fetchAutocompleteArchetypes(search, resource = null) {
      const { fetchRequest } = useApi();

      const archetypes = await fetchRequest(
        "archetypes", // API endpoint
        {
          itemsPerPage: 1000,
          sortBy: null,
          search: search,
          categoryId: null,
          usageId: null,
          brandId: null,
          archetypeId: null,
          resource: resource,
        }
      );

      return archetypes.data;
    },

    async fetchAutocompleteSelectCategories(search) {
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

    async fetchAutocompleteSelectUsages(search) {
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

    async fetchArchetypesWithItems() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "archetypes-with-items", // API endpoint
        {
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
          order: this.order,
          search: this.search,
          categoryId: this.selectedCategoryId,
          usageId: this.selectedUsageId,
          brandId: this.selectedBrandId,
          archetypeId: this.selectedArchetypeId,
          startDate: this.dateRange[0].toISOString(),
          endDate: this.dateRange[this.dateRange.length - 1].toISOString(),
          location: this.location,
          radius: this.radius,
          resource: this.resource,
        }
      );

      this.archetypesWithItems = data.data;
      this.totalArchetypesWithItems = data.total;
    },

    async fetchArchetypes() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "archetypes", // API endpoint
        {
          sortBy: this.sortBy,
          itemsPerPage: 1000,
        }
      );

      this.archetypes = data.data;
      this.totalArchetypes = data.total;
    },

    async fetchMaterialResources() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "archetypes", // API endpoint
        {
          sortBy: this.sortBy,
          itemsPerPage: 1000,
          resource: "material",
        }
      );

      return data;
    },

    async fetchMyArchetypes() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "me/archetypes", // API endpoint
        {
          page: this.myArchetypesListPage,
          itemsPerPage: this.myArchetypesListItemsPerPage,
          sortBy: this.myArchetypesListSortBy,
          search: this.myArchetypesListFilters.search,
          categoryId: this.myArchetypesListFilters.category?.id,
          usageId: this.myArchetypesListFilters.usage?.id,
          resource: this.myArchetypesListFilters.resource,
        }
      );
      this.myArchetypesListArchetypes = data?.data?.map((userArchetypes) => {
        return {
          ...userArchetypes,
          category_ids: userArchetypes.category_ids
            ? userArchetypes.category_ids
                .split(",")
                .map((id) => Number(id.trim()))
            : [],
          usage_ids: userArchetypes.usage_ids
            ? userArchetypes.usage_ids.split(",").map((id) => Number(id.trim()))
            : [],
        };
      });

      this.myArchetypesListTotalArchetypes = data.total;
    },

    async postArchetype(archetypeData) {
      const { sendRequest } = useApi();

      const data = await sendRequest("archetypes", "post", archetypeData);

      await this.fetchMyArchetypes();

      return data;
    },

    async saveMyArchetype(archetype) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `archetypes/${archetype.id}`, // API endpoint
        "put", // HTTP method
        archetype // Payload
      );

      await this.fetchMyArchetypes();

      return data;
    },

    async deleteArchetype(archetypeId) {
      const { sendRequest } = useApi();
      await sendRequest(
        `archetypes/${archetypeId}`, // API endpoint
        "delete" // HTTP method
      );
      await this.fetchMyArchetypes();
    },
  },
});
