import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useProjectStore = defineStore("project", {
  state: () => ({
    projectListProjects: [],
    projectListTotalProjects: 0,
    projectListSelectedProject: null,
    projectListFilters: { search: "", userId: null, archetype:null },
    projectListPage: 1,
    projectListItemsPerPage: 10,
    projectListSortBy: [{ key: "name", order: "asc" }],
  }),
  getters: {},

  actions: {
    async destroy(id) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`projects/${id}`, "DELETE");

      this.index();
      return data;
    },

    async index() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "projects", // API endpoint
        {
          page: this.projectListPage,
          itemsPerPage: this.projectListItemsPerPage,
          sortBy: this.projectListSortBy,
          search: this.projectListFilters.search,
        }
      );
      this.projectListProjects = data.data;
      this.projectListTotalProjects = data.total;
      return data;
    },

    async indexForAutocomplete(search, resource = null) {
      const { fetchRequest } = useApi();

      const projects = await fetchRequest("projects", {
        itemsPerPage: 1000,
        sortBy: null,
        search: search,
        projectId: null,
      });

      return projects.data;
    },

    async show(id) {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(`projects/${id}`);

      return data?.data;
    },

    async store(formData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`projects`, "POST", formData);
      if (data?.success) {
        this.index();
      }
      return data;
    },

    async update(formData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(
        `projects/${formData.id}`,
        "PUT",
        formData
      );

      if (data?.success) {
        this.index();
      }
      return data;
    },

    updateprojectListOptions({ page, itemsPerPage, sortBy }) {
      this.projectListPage = page;
      this.projectListItemsPerPage = itemsPerPage;
      this.projectListSortBy = sortBy;

      this.index();
    },
  },
});
