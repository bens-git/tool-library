import { defineStore } from "pinia";
import useApi from "@/stores/api";

export const useJobStore = defineStore("job", {
  state: () => ({
    jobsListJobs: [],
    jobsListTotalJobs: 0,
    jobsListSelectedJob: null,
    jobsListFilters: { search: "" },
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
        }
      );
      this.jobsListJobs = data.data;
      this.jobsListTotalJobs = data.total;
    },

    async postJob(formData) {
      const { sendRequest } = useApi();
      const data = await sendRequest(`jobs`, "POST", formData);
      if (data?.success) {
        this.jobsListSelecteJob = data.data;
        this.jobsListJobs.push(data.data);
        this.jobsListTotalJobs++;
        return data.data;
      }
    },

    async saveJob(formData) {
      const { sendRequest } = useApi();
      await sendRequest(
        "put", // HTTP method
        `jobs/${this.selectedJob.id}`, // API endpoint
        formData, // Payload
        (data) => {
          this.selectedJob = data;
        }
      );
    },

    async deleteJob() {
      const { sendRequest } = useApi();
      await sendRequest(
        "delete", // HTTP method
        `jobs/${this.selectedJob.id}`, // API endpoint
        null, // Payload
        () => {
          this.jobs = this.jobs.filter(
            (jobs) => jobs.id !== this.selectedJob.id
          );
          this.totalJobs -= 1;
          this.selectedJob = null;
        }
      );
    },
  },
});
