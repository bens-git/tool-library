import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useResponseStore } from "./response";
import { useLoadingStore } from "./loading";
import { useTypeStore } from "./type";
import useApi from "@/stores/api";

export const useItemStore = defineStore("item", {
  state: () => ({
    myItemsListPage: 1,
    myItemsListItemsPerPage: 10,
    myItemsListSortBy: [{ key: "name", order: "asc" }],
    myItemsListFilters: { typeId: null, brandId: null, search: null },
    myItemsListItems: [],
    myItemsListTotalItems: 0,

    typeDialogItemListPage: 1,
    typeDialogItemListItemsPerPage: 10,
    typeDialogItemListSortBy: [{ key: "name", order: "asc" }],
    typeDialogItemListFilters: {
      typeId: null,
      brandId: null,
      search: null,
      dateRange: null,
    },
    typeDialogItemListItems: [],
    totalTypeDialogItemListItems: 0,

    resources: [],
    totalResources: 0,

    currentItem: null,
  }),

  actions: {
    updateMyItemsListOptions({ page, itemsPerPage, sortBy }) {
      this.myItemsListPage = page;
      this.itemsPerPage = itemsPerPage;
      this.myItemsListSortBy = sortBy;

      this.fetchMyItems();
    },

    async fetchTypeDialogItemListItems(typeId, location, radius) {
      const { fetchRequest } = useApi();
      const typeStore = useTypeStore();

      const data = await fetchRequest("items", {
        search: this.typeDialogItemListFilters.search,
        dateRange: this.typeDialogItemListFilters.dateRange,
        page: this.typeDialogItemsListPage,
        itemsPerPage: this.typeDialogItemsListItemsPerPage,
        sortBy: this.typeDialogItemsListSortBy,
        typeId: typeId,
        location: location,
        radius: radius,
        startDate: typeStore.dateRange[0],
        endDate: typeStore.dateRange[typeStore.dateRange.length - 1],
      });
      this.typeDialogItemListItems = data.data;
      this.totalTypeDialogItemListItems = data.total;
    },

    async fetchMyItems() {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(
        "me/items", // API endpoint
        {
          page: this.myItemsListPage,
          itemsPerPage: this.myItemsListItemsPerPage,
          sortBy: this.myItemsListSortBy,
          typeId: this.myItemsListFilters.typeId,
          brandId: this.myItemsListFilters.brandId,
          resource: this.myItemsListFilters.resource,
          search: this.myItemsListFilters.search,
        }
      );
      this.myItemsListItems = data.data;
      this.myItemsListTotalItems = data.total;
    },

    async fetchItemUnavailableDates(itemId) {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(`items/${itemId}/unavailable-dates`);

      const localDateToUTC = (dateStr) => {
        const [year, month, day] = dateStr.split("-").map(Number);
        // Create a local date object
        const localDate = new Date(year, month - 1, day);

        // Get the local time offset in minutes
        const localOffset = localDate.getTimezoneOffset();

        // Convert local date to UTC by adding the offset
        const utcDate = new Date(localDate.getTime() + localOffset * 60 * 1000);

        return utcDate;
      };
      const sortDatesAscending = (dates) => {
        return dates.sort((a, b) => {
          return a - b; // Sort in ascending order
        });
      };

      const unavailableDates = sortDatesAscending(
        data.data.map(localDateToUTC)
      );

      return unavailableDates;
    },

    async fetchItemRentedDates(itemId) {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(`items/${itemId}/rented-dates`);

      const localDateToUTC = (dateStr) => {
        const [year, month, day] = dateStr.split("-").map(Number);
        // Create a local date object
        const localDate = new Date(year, month - 1, day);

        // Get the local time offset in minutes
        const localOffset = localDate.getTimezoneOffset();

        // Convert local date to UTC by adding the offset
        const utcDate = new Date(localDate.getTime() + localOffset * 60 * 1000);

        return utcDate;
      };
      const sortDatesAscending = (dates) => {
        return dates.sort((a, b) => {
          return a - b; // Sort in ascending order
        });
      };

      const rentedDates = sortDatesAscending(data.data.map(localDateToUTC));

      return rentedDates;
    },

    async fetchResources() {
      const { fetchRequest } = useApi();
      const data = await fetchRequest(`resources`);
      this.resources = data.data;
      this.totalResources = data.total;
    },

    async bookRental(itemId, startDate, endDate) {
      const { fetchRequest } = useApi();

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

      const response = await sendRequest("post", "rentals", {
        itemId,
        startDate: formattedStartDate,
        endDate: formattedEndDate,
      });
    },

    resetFilters() {
      const typeStore = useTypeStore();
      this.search = "";
      typeStore.dateRange = [
        new Date(new Date().setHours(9, 0, 0, 0)),
        new Date(new Date().setHours(17, 0, 0, 0)),
      ];
      this.fetchTypeDialogItemListItems();
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
      const { sendRequest } = useApi();

      const data = await sendRequest(`items`, "POST", itemData);
      if (data?.success) {
        this.myItemsListSelectedItem = data.data;
        this.myItemsListItems.push(data.data);
        this.myItemsListTotalItems++;
        return data.data;
      }
    },

    async updateMyItem(item) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `items/${item.id}`, // API endpoint
        "put", // HTTP method
        item // Payload
      );

      if (data?.success) {
        // Find and update the item in the store
        const updatedItemIndex = this.myItemsListItems.findIndex(
          (item) => item.id === data.data.id
        );
        if (updatedItemIndex !== -1) {
          this.myItemsListItems[updatedItemIndex] = data.data;
        }
      }
    },

    async addMyItemImage(itemId, image) {
      const { sendRequest } = useApi();

      const formData = new FormData();
      formData.append("image", image);

      const data = await sendRequest(
        `items/${itemId}/image`, // API endpoint
        "post", // HTTP method
        formData // Payload
      );
    },

    async updateItemAvailability(itemId, itemUnavailableDates) {
      const { sendRequest } = useApi();

      var params = { unavailableDates: [] };
      itemUnavailableDates.forEach((date) => {
        console.log(date);
        const dateObject = new Date(date);
        // Format the date as yyyy-mm-dd
        const year = dateObject.getFullYear();
        const month = String(dateObject.getMonth() + 1).padStart(2, "0"); // Months are 0-based
        const day = String(dateObject.getDate()).padStart(2, "0");

        const formattedDate = `${year}-${month}-${day}`;

        params.unavailableDates.push(formattedDate);
      });

      params.itemId = itemId;

      const data = await sendRequest(
        `items/${itemId}/availability`,
        "PATCH",
        params
      );

      if (data?.data) {
        const updatedItemIndex = this.items.findIndex(
          (item) => item.id === data.data.id
        );
        if (updatedItemIndex !== -1) {
          this.myItemsListItems[updatedItemIndex] = data.data;
        }
      }
    },

    async deleteItem(itemId) {
      const { sendRequest } = useApi();

      await sendRequest("delete", `items/${itemId}`, null);
    },
  },
});
