import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useLoadingStore } from "./loading";
import { useResponseStore } from "./response";

export const useRentalStore = defineStore("rental", {
  state: () => ({
    rentals: [],
    loans: [],
  }),

  actions: {
    async fetchRentals() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();

      loadingStore.startLoading("fetchRentals");

      try {
        const response = await apiClient.get("/user/rentals");
        this.rentals = response.data; // Adjust based on the API response structure
        responseStore.setResponse(true, "Rentals fetched successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchRentals");
      }
    },

    async fetchLoans() {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();

      loadingStore.startLoading("fetchLoans");

      try {
        const response = await apiClient.get("/user/loans");
        this.loans = response.data; // Adjust based on the API response structure
        responseStore.setResponse(true, "Loans fetched successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchLoans");
      }
    },

    async cancelRental(rentalId) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();

      loadingStore.startLoading("cancelRental");

      try {
        await apiClient.delete(`/user/rentals/${rentalId}`);
        this.rentals = this.rentals.filter((rental) => rental.id !== rentalId); // Remove rental from local state
        responseStore.setResponse(true, "Rental cancelled successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("cancelRental");
      }
    },

    async cancelLoan(loanId) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();

      loadingStore.startLoading("cancelLoan");

      try {
        await apiClient.delete(`/user/rentals/${loanId}`);
        this.loans = this.loans.filter((loans) => loans.id !== loanId); // Remove rental from local state
        responseStore.setResponse(true, "Rental cancelled successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("cancelLoan");
      }
    },

    async confirmRentalActive(rentalId) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      const process = "confirmRentalActive";

      loadingStore.startLoading(process);

      try {
        const response = await apiClient.patch(`/rentals/${rentalId}`, {
          status: "active",
        });

        const index = this.rentals.findIndex(
          (rental) => rental.id === rentalId
        );

        if (index !== -1) {
          // Update the rental's status
          this.rentals[index].status = "active";
        }

        responseStore.setResponse(true, "Rental activated successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading(process);
      }
    },

    async confirmLoanCompleted(rentalId) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      const process = "confirmLoanCompleted";

      loadingStore.startLoading(process);

      try {
        const response = await apiClient.patch(`/rentals/${rentalId}`, {
          status: "completed",
        });

        const index = this.loans.findIndex((rental) => rental.id === rentalId);

        if (index !== -1) {
          // Update the rental's status
          this.loans[index].status = "completed";
        }

        responseStore.setResponse(true, "Rental completed successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading(process);
      }
    },

    async confirmLoanHolding(rentalId) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();
      const process = "confirmLoanHolding";

      loadingStore.startLoading(process);

      try {
        const response = await apiClient.patch(`/rentals/${rentalId}`, {
          status: "holding",
        });

        const index = this.loans.findIndex((rental) => rental.id === rentalId);

        if (index !== -1) {
          // Update the rental's status
          this.loans[index].status = "holding";
          console.log(this.rentals);
        }

        responseStore.setResponse(
          true,
          "Rental confirmed as holding by renter."
        );
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading(process);
      }
    },

    async assignPunctuality(rentalId) {
      const loadingStore = useLoadingStore();
      const responseStore = useResponseStore();

      loadingStore.startLoading("assignPunctuality");

      try {
        await apiClient.post(`/user/rentals/${rentalId}/punctuality`);
        // Handle response and update local state if needed
        responseStore.setResponse(true, "Punctuality assigned successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("assignPunctuality");
      }
    },
  },
});
