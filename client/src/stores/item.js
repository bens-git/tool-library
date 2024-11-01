import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useResponseStore } from "./response";
import { useLoadingStore } from "./loading";
import { useTypeStore } from "./type";

export const useItemStore = defineStore("item", {
  state: () => ({
    items: [],
    totalItems: 0,
    rentedDates: [],
    search: "",
    currentItem: null,
    page: 1,
    itemsPerPage: 10,
    sortBy: "item_name",
    order: "asc",
    itemUnavailableDates: [],
  }),

  actions: {
    updateOptions({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.fetchUserItems();
    },

    async fetchItems(typeId, location, radius) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      const typeStore = useTypeStore();
      loadingStore.startLoading("fetchItems");

      try {
        const response = await apiClient.get("items", {
          params: {
            // hideOwn: true,
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            order: this.order,
            typeId: typeId,
            search: this.search,
            location: location,
            radius: radius,
            startDate: typeStore.dateRange[0],
            endDate: typeStore.dateRange[typeStore.dateRange.length - 1],
          },
        });
        this.items = response.data.items;
        this.totalItems = response.data.count;
      } catch (error) {
        console.log(error);
        responseStore.setResponse(
          false,
          error?.response?.data?.message || "Error fetching items",
          [error.message]
        );
      } finally {
        loadingStore.stopLoading("fetchItems");
      }
    },

    async fetchUserItems(typeId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      const typeStore = useTypeStore();
      loadingStore.startLoading("fetchUserItems");

      try {
        const response = await apiClient.get("user/items", {
          params: {
            paginate: true,
            page: this.page,
            itemsPerPage: this.itemsPerPage,
            sortBy: this.sortBy,
            order: this.order,
            typeId: typeId,
            search: this.search,
            startDate: typeStore.dateRange[0],
            endDate: typeStore.dateRange[typeStore.dateRange.length - 1],
          },
        });
        this.items = response.data.items;
        this.totalItems = response.data.count;
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchUserItems");
      }
    },

    async fetchItemUnavailableDates(itemId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();

      loadingStore.startLoading("fetchItemUnavailableDates");

      try {
        const response = await apiClient.get("item/unavailable-dates", {
          params: { itemId },
        });

        const localDateToUTC = (dateStr) => {
          const [year, month, day] = dateStr.split("-").map(Number);
          // Create a local date object
          const localDate = new Date(year, month - 1, day);

          // Get the local time offset in minutes
          const localOffset = localDate.getTimezoneOffset();

          // Convert local date to UTC by adding the offset
          const utcDate = new Date(
            localDate.getTime() + localOffset * 60 * 1000
          );

          return utcDate;
        };
        const sortDatesAscending = (dates) => {
          return dates.sort((a, b) => {
            return a - b; // Sort in ascending order
          });
        };

        const unavailableDates = sortDatesAscending(
          response.data.itemUnavailableDates.map(localDateToUTC)
        );

        return unavailableDates;
      } catch (error) {
        console.log(error);
        responseStore.setResponse(
          false,
          "Error fetching item unavailable dates",
          [error.message]
        );
      } finally {
        loadingStore.stopLoading("fetchItemUnavailableDates");
      }
    },
    async fetchRentedDates(typeId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();

      loadingStore.startLoading("fetchRentedDates");

      try {
        const response = await apiClient.get("rented-dates", {
          params: { typeId },
        });
        this.rentedDates = response.data.data;
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchRentedDates");
      }
    },

    async fetchItemRentedDates(itemId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();

      loadingStore.startLoading("fetchItemRentedDates");

      try {
        const response = await apiClient.get("item/rented-dates", {
          params: { itemId },
        });
        this.rentedDates = response.data.data;
        responseStore.setResponse(true, "Rented dates fetched successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchItemRentedDates");
      }
    },

    async bookRental(itemId, startDate, endDate) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      const typeStore = useTypeStore();

      loadingStore.startLoading("bookRental");

      try {
        const setToNineAm = (date) => {
          const adjustedDate = new Date(date);
          adjustedDate.setHours(9, 0, 0, 0); // Set time to 09:00:00
          return adjustedDate;
        };

        const setToFivePm = (date) => {
          const adjustedDate = new Date(date);
          adjustedDate.setHours(17, 0, 0, 0); // Set time to 17:00:00 (5 PM)
          return adjustedDate;
        };

        const formatToMySQLDateTime = (date) => {
          const year = date.getFullYear();
          const month = String(date.getMonth() + 1).padStart(2, "0"); // Months are zero-based
          const day = String(date.getDate()).padStart(2, "0");
          const hours = String(date.getHours()).padStart(2, "0");
          const minutes = String(date.getMinutes()).padStart(2, "0");
          const seconds = String(date.getSeconds()).padStart(2, "0");

          return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        };

        const adjustedStartDate = setToNineAm(startDate);
        const adjustedEndDate = setToFivePm(endDate);

        const formattedStartDate = formatToMySQLDateTime(adjustedStartDate);
        const formattedEndDate = formatToMySQLDateTime(adjustedEndDate);

        await apiClient.post("rentals", {
          itemId,
          startDate: formattedStartDate,
          endDate: formattedEndDate,
        });

        responseStore.setResponse(true, "Rental confirmed successfully");
        // Optionally, refresh items or rented dates
        await this.fetchItems(typeStore.typeId);
        await this.fetchRentedDates(typeStore.typeId);
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("bookRental");
      }
    },

    resetFilters() {
      const typeStore = useTypeStore();
      this.search = "";
      typeStore.dateRange = [
        new Date(new Date().setHours(9, 0, 0, 0)),
        new Date(new Date().setHours(17, 0, 0, 0)),
      ];
      this.fetchItems();
    },

    // Action to fetch an item by its ID from the items array
    async fetchItemById(itemId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();

      loadingStore.startLoading("fetchItemById");

      try {
        const response = await apiClient.get(`items/${itemId}`);
        this.currentItem = response.data;
        responseStore.setResponse(true, "Item fetched successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("fetchItemById");
      }
    },

    itemCode(item) {
      // Function to get the abbreviation of a name
      const getAbbreviation = (name) => {
        // Check if name is a valid string
        if (!name || typeof name !== "string") {
          return "";
        }

        // Split the name into words
        const words = name.split(" ");

        // If there's only one word, return its first letter
        if (words.length === 1) {
          return words[0][0].toUpperCase();
        }

        // Map the first letter of each word, join, and return uppercase
        return words
          .map((word) => word[0])
          .join("")
          .toUpperCase();
      };

      // Determine if item.raw exists
      const isRawItem = item.raw && typeof item.raw === "object";

      // Get the owner's name abbreviation
      const ownerName = isRawItem ? item.raw.owner_name : item.owner_name;
      const ownerAbbreviation = getAbbreviation(ownerName);

      // Get the tool type abbreviation
      const typeName = isRawItem ? item.raw.type_name : item.type_name;
      const typeAbbreviation = getAbbreviation(typeName);

      // Generate the item code
      const itemId = isRawItem ? item.raw.id : item.id;
      const itemCode = `${ownerAbbreviation}-${typeAbbreviation}-${itemId}`;

      return itemCode;
    },
    outputReadableDateRange() {
      const typeStore = useTypeStore();

      // Format the dates to "Day of Week, Month Day, Year"
      const formatDate = (date) => {
        return date.toLocaleDateString("en-US", {
          weekday: "long",
          year: "numeric",
          month: "long",
          day: "numeric",
        });
      };

      return `From: ${formatDate(typeStore.dateRange[0])} at 9:00 AM
       To: ${formatDate(typeStore.dateRange[typeStore.dateRange.length - 1])} at 5:00 PM`;
    },
    async createItem(itemData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("createItem");

      try {
        const response = await apiClient.post("/items", itemData, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        this.items.push(response.data);
        this.totalItems++;
        responseStore.setResponse(true, "Item created successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("createItem");
      }
    },

    async updateItem(itemData) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("updateItem");

      try {
        // Create a new FormData object
        const formData = new FormData();

        // Append all item data fields to formData
        for (const [key, value] of itemData.entries()) {
          if (key === "newImages" || key === "removedImages") {
            // Convert array data to JSON string
            if (key === "removedImages") {
              formData.append(key, JSON.stringify(value));
            } else {
              // For 'newImages', append each file separately
              if (value.length > 0) {
                value.forEach((file, index) => {
                  formData.append(`${key}[${index}]`, file);
                });
              }
            }
          } else {
            formData.append(key, value);
          }
        }

        // Send POST request with FormData
        const response = await apiClient.post(
          `/update-item/${itemData.get("id")}`,
          formData,
          {
            headers: { "Content-Type": "multipart/form-data" },
          }
        );

        // Find and update the item in the store
        const updatedItemIndex = this.items.findIndex(
          (item) => item.id === response.data.id
        );
        if (updatedItemIndex !== -1) {
          this.items[updatedItemIndex] = response.data;
        }

        responseStore.setResponse(true, "Item updated successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("updateItem");
      }
    },

    async updateItemAvailability(itemId, itemUnavailableDates) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();

      loadingStore.startLoading("updateItemAvailability");

      try {
        const formData = new FormData();

        itemUnavailableDates.forEach((date) => {
          const dateObject = new Date(date);
          // Format the date as yyyy-mm-dd
          const year = dateObject.getFullYear();
          const month = String(dateObject.getMonth() + 1).padStart(2, "0"); // Months are 0-based
          const day = String(dateObject.getDate()).padStart(2, "0");

          const formattedDate = `${year}-${month}-${day}`;

          formData.append("unavailableDates[]", formattedDate);
        });

        formData.append("id", itemId);

        const response = await apiClient.post(
          `/update-item-availability/${itemId}`,
          formData,
          {
            headers: { "Content-Type": "multipart/form-data" },
          }
        );

        const updatedItemIndex = this.items.findIndex(
          (item) => item.id === response.data.id
        );
        if (updatedItemIndex !== -1) {
          this.items[updatedItemIndex] = response.data;
        }

        responseStore.setResponse(
          true,
          "Item availability updated successfully"
        );
      } catch (error) {
        console.log(error);
        responseStore.setResponse(
          false,
          error.response?.data?.message || "An error occurred",
          [error.response?.data?.errors || []]
        );
      } finally {
        loadingStore.stopLoading("updateItemAvailability");
      }
    },

    async deleteItem(itemId) {
      const responseStore = useResponseStore();
      const loadingStore = useLoadingStore();
      loadingStore.startLoading("deleteItem");

      try {
        await apiClient.delete(`/items/${itemId}`);
        this.items = this.items.filter((item) => item.id !== itemId);
        this.totalItems--;
        responseStore.setResponse(true, "Item deleted successfully");
      } catch (error) {
        responseStore.setResponse(false, error.response.data.message, [
          error.response.data.errors,
        ]);
      } finally {
        loadingStore.stopLoading("deleteItem");
      }
    },
  },
});
