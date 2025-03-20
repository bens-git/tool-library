import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useJobStore = defineStore("job", {
  state: () => ({
    jobsListJobs: [],
    jobsListTotalJobs: 0,
    jobsListSelectedJob: null,
    jobsListFilters: { search: "", archetype: null, project: null },
    jobsListPage: 1,
    jobsListItemsPerPage: 10,
    jobsListSortBy: [{ key: "name", order: "asc" }],
  }),
  getters: {},

  actions: {
    updateJobsListOptions({ page, itemsPerPage, sortBy }) {
      this.jobsListPage = page;
      this.jobsListItemsPerPage = itemsPerPage;
      this.jobsListSortBy = sortBy;

      this.fetchJobs();
    },

    async fetchJobs() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(
        "jobs", // API endpoint
        {
          page: this.jobsListPage,
          itemsPerPage: this.jobsListItemsPerPage,
          sortBy: this.jobsListSortBy,
          search: this.jobsListFilters.search,
          archetypeId: this.jobsListFilters.archetype?.id,
          projectId: this.jobsListFilters.project?.id,
        }
      );
      this.jobsListJobs = data.data;
      this.jobsListTotalJobs = data.total;
    },

    async fetchAutocompleteJobs({ search: search, baseId: baseId }) {
      const { fetchRequest } = useApi();
      const jobs = await fetchRequest("jobs", {
        itemsPerPage: 1000,
        sortBy: null,
        search: search,
        baseId: baseId,
      });

      return jobs.data;
    },

    async postJob(formData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`jobs`, "POST", formData);
      if (data?.success) {
        this.fetchJobs();

        return data.data;
      }
    },

    async subdivideJob(formData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(
        `subdivide-job/${formData.originalJob.id}`,
        "POST",
        formData
      );
      if (data?.success) {
        this.fetchJobs();
      }
      return data;
    },

    async putJob(formData) {
      console.log(formData);
      const { sendRequest } = useApi();
      const data = await sendRequest(`jobs/${formData.id}`, "PUT", {
        name: formData.name,
        description: formData.description,
        base_id: formData.base?.id,
        component_id: formData.component?.id,
        product_id: formData.product?.id,
        tool_id: formData.tool?.id,
      });

      if (data?.success) {
        this.fetchJobs();

        return data.data;
      }
    },

    async deleteJob(jobId) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`jobs/${jobId}`, "DELETE");

      this.fetchJobs();
    },
  },
});
