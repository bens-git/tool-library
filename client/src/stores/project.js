import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useProjectStore = defineStore("project", {
  state: () => ({
    projectsListProjects: [],
    projectsListTotalProjects: 0,
    projectsListSelectedProject: null,
    projectsListFilters: { search: "" },
    projectsListPage: 1,
    projectsListItemsPerPage: 10,
    projectsListSortBy: [{ key: "name", order: "asc" }],
  }),
  getters: {},

  actions: {
    updateProjectsListOptions({ page, itemsPerPage, sortBy }) {
      this.projectsListPage = page;
      this.projectsListItemsPerPage = itemsPerPage;
      this.projectsListSortBy = sortBy;

      this.fetchProjects();
    },

    async fetchProjects() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "projects", // API endpoint
        {
          page: this.projectsListPage,
          itemsPerPage: this.projectsListItemsPerPage,
          sortBy: this.projectsListSortBy,
          search: this.projectsListFilters.search,
        }
      );
      this.projectsListProjects = data.data;
      this.projectsListTotalProjects = data.total;
    },

    async fetchAutocompleteProjects(search, resource = null) {
      const { fetchRequest } = useApi();

      const projects = await fetchRequest("projects", {
        itemsPerPage: 1000,
        sortBy: null,
        search: search,
        projectId: null,
      });

      return projects.data;
    },

    async postProject(formData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`projects`, "POST", formData);
      if (data?.success) {
        this.fetchProjects();
        return data;
      }
    },

    async putProject(formData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(
        `projects/${formData.id}`,
        "PUT",
        formData
      );

      if (data?.success) {
        this.fetchProjects();
        return data;
      }
    },

    async deleteProject(id) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`projects/${id}`, "DELETE");

      this.fetchProjects();
    },

    async show(id) {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(`projects/${id}`);

      return data?.data;
    },
  },
});
