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

    archetypesListPage: 1,
    archetypesListItemsPerPage: 10,
    archetypesListSortBy: [{ key: "name", order: "asc" }],
    archetypesListFilters: {
      usage: null,
      category: null,
      search: null,
      resource: null,
    },
    archetypesListArchetypes: [],
    archetypesListTotalArchetypes: 0,
    archetypesListSelectedArchetype: null,
  }),
  actions: {
    resetFilters() {
      this.archetypesListFilters.search = "";
      this.archetypesListFilters.category = null;
      this.archetypesListFilters.usage = null;

      this.index();
    },

    async destroy(archetypeId) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`archetypes/${archetypeId}`, "delete");
      await this.index();
      return data;
    },

    async index() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest("archetypes", {
        page: this.archetypesListPage,
        itemsPerPage: this.archetypesListItemsPerPage,
        sortBy: this.archetypesListSortBy,
        search: this.archetypesListFilters.search,
        categoryId: this.archetypesListFilters.category?.id,
        usageId: this.archetypesListFilters.usage?.id,
        resource: this.archetypesListFilters.resource,
      });
      this.archetypesListArchetypes = data?.data?.map((userArchetypes) => {
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

      this.archetypesListTotalArchetypes = data.total;
    },

    async indexForAutocomplete(search, resource = null) {
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

    async indexResources(resource) {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "resources" // API endpoint
      );

      return data.data;
    },

    async show(id) {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(`archetypes/${id}`);
      return data?.data;
    },

    async store(data) {
      const { sendRequest } = useApi();

      const response = await sendRequest("archetypes", "post", data);

      await this.index();

      return response;
    },

    async update(archetype) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `archetypes/${archetype.id}`,
        "put",
        archetype
      );

      await this.index();

      return data;
    },

    updateArchetypesListOptions({ page, itemsPerPage, sortBy, resource }) {
      this.archetypesListPage = page;
      this.archetypesListItemsPerPage = itemsPerPage;
      this.archetypesListSortBy = sortBy;
      this.archetypesListFilters.resource = resource;

      this.index();
    },
  },
});
