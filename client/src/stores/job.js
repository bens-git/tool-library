import { defineStore } from "pinia";
import useApi  from "@/stores/api";

export const useJobStore = defineStore("job", {
  state: () => ({
    jobs: [],
    totalJobs: 0,
    selectedJob: null,
    search: "",
    page: 1,
    itemsPerPage: 10,
    sortBy: [{ key: "name", order: "asc" }],
  }),
  getters: {},

  actions: {
    updateOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchJobs();
    },

    async fetchJobs() {
      const { fetchRequest } = useApi();
      await fetchRequest(
        "jobs", // API endpoint
        {
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
          search: this.search,
        },
        (data) => {
          this.jobs = data.data;
          this.totalJobs = data.count;
        }
      );
    },

    async createJob(formData) {
      const { sendRequest } = useApi();
      await sendRequest(
        "post", // HTTP method
        "jobs", // API endpoint
        formData, // Payload
        (data) => {
          this.selectedJob = data;
          this.jobs.push(data);
          this.totalJobs++;
        }
      );
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
