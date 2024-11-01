import { defineStore } from "pinia";

export const useLoadingStore = defineStore("loading", {
  state: () => ({
    loadingProcesses: [],
  }),
  actions: {
    startLoading(process) {
      if (!this.loadingProcesses.includes(process)) {
        this.loadingProcesses.push(process);
      }
    },
    stopLoading(process) {
      this.loadingProcesses = this.loadingProcesses.filter(
        (p) => p !== process,
      );
    },
    isLoading(process) {
      return this.loadingProcesses.includes(process);
    },
  },
});
