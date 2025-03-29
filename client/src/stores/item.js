import { defineStore } from "pinia";
import apiClient from "@/axios";
import { useUserStore } from "./user";
import { useLoadingStore } from "./loading";
import { useArchetypeStore } from "./archetype";
import useApi from "@/stores/api";

export const useItemStore = defineStore("item", {
  state: () => ({
    itemListPage: 1,
    itemListItemsPerPage: 10,
    itemListSortBy: [{ key: "archetypes.name", order: "asc" }],
    itemListFilters: {
      archetypeId: null,
      brandId: null,
      search: null,
      radius: 10,
      location: null,
      userId: null,
    },
    itemListItems: [],
    itemListTotalItems: 0,

    resources: [],
    totalResources: 0,

    currentItem: null,
  }),

  actions: {
    async destroy(itemId) {
      const { sendRequest } = useApi();

      const data = await sendRequest(`items/${itemId}`, "DELETE");
      await this.index();
      return data;
    },

    async index() {
      const { fetchRequest } = useApi();

      const endPoint = this.itemListFilters.userId ? "me/items" : "/items";
      const data = await fetchRequest(endPoint, {
        page: this.itemListPage,
        itemsPerPage: this.itemListItemsPerPage,
        sortBy: this.itemListSortBy,
        categoryId: this.itemListFilters.category?.id,
        usageId: this.itemListFilters.usage?.id,
        archetypeId: this.itemListFilters.archetypeId,
        brandId: this.itemListFilters.brandId,
        resource: this.itemListFilters.resource,
        search: this.itemListFilters.search,
        userId: this.itemListFilters.userId,
        location: this.itemListFilters.location,
        radius: this.itemListFilters.radius
      });
      this.itemListItems = data.data;
      this.itemListTotalItems = data.total;
    },

    async indexItemUnavailableDates(itemId) {
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

    async indexItemRentedDates(itemId) {
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

    async bookRental(itemId, startDate, endDate) {
      const { sendRequest } = useApi();

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

      const data = await sendRequest("rentals", "post", {
        itemId,
        startDate: formattedStartDate,
        endDate: formattedEndDate,
      });
    },

    resetFilters() {
      const archetypeStore = useArchetypeStore();
      this.search = "";
      archetypeStore.dateRange = [
        new Date(new Date().setHours(9, 0, 0, 0)),
        new Date(new Date().setHours(17, 0, 0, 0)),
      ];
      this.fetchArchetypeDialogItemListItems();
    },

    // Action to fetch an item by its ID from the items array
    async fetchItemById(itemId) {
      const { fetchRequest } = useApi();

      const data = await fetchRequest(`items/${itemId}`);
      this.currentItem = data.data;
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

      // Get the archetype abbreviation
      const archetypeName = isRawItem
        ? item.raw.archetype_name
        : item.archetype_name;
      const archetypeAbbreviation = getAbbreviation(archetypeName);

      // Generate the item code
      const itemId = isRawItem ? item.raw.id : item.id;
      const itemCode = `${ownerAbbreviation}-${archetypeAbbreviation}-${itemId}`;

      return itemCode;
    },

    outputReadableDateRange() {
      const archetypeStore = useArchetypeStore();

      // Format the dates to "Day of Week, Month Day, Year"
      const formatDate = (date) => {
        return date.toLocaleDateString("en-US", {
          weekday: "long",
          year: "numeric",
          month: "long",
          day: "numeric",
        });
      };

      return `From: ${formatDate(archetypeStore.dateRange[0])} at 9:00 AM <br>
       To: ${formatDate(archetypeStore.dateRange[archetypeStore.dateRange.length - 1])} at 5:00 PM`;
    },

    async store(itemData) {
      const userStore = useUserStore();

      const { sendRequest } = useApi();

      const data = await sendRequest(`items`, "POST", itemData);
      this.itemListFilters.userId = userStore.user.id;
      await this.index();
      return data;
    },

    async update(item) {
      const { sendRequest } = useApi();

      const data = await sendRequest(
        `items/${item.id}`, // API endpoint
        "put", // HTTP method
        item // Payload
      );

      return data;
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

    updateItemListOptions({ page, itemsPerPage, sortBy }) {
      this.itemListPage = page;
      this.itemListItemsPerPage = itemsPerPage;
      this.itemListSortBy = sortBy;

      this.index();
    },
  },
});
