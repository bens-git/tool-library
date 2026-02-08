import { defineStore } from "pinia";
import useApi from "@/Stores/api";
import { ref } from "vue";

export const useUsageStore = defineStore("usage", () => {
  const usageListPage = ref(1);
  const usageListItemsPerPage = ref(10);
  const usageListSortBy = ref([{ key: "name", order: "asc" }]);
  const usageListFilters = ref({ search: null });
  const usageListUsages = ref([]);
  const usageListTotalUsages = ref(0);
  const usageListSelectedUsages = ref(null);

  const destroy = async (usageId) => {
    const { sendRequest } = useApi();
    const data = await sendRequest(`usages/${usageId}`, "delete");
    await index();
    return data;
  };

  const index = async () => {
    const { fetchRequest } = useApi();
    const data = await fetchRequest("usages", {
      page: usageListPage.value,
      itemsPerPage: usageListItemsPerPage.value,
      sortBy: usageListSortBy.value,
      search: usageListFilters.value.search,
    });
    usageListUsages.value = data?.data;
    usageListTotalUsages.value = data?.total;
  };

  const indexForAutocomplete = async (search) => {
    const { fetchRequest } = useApi();

    const usages = await fetchRequest(
      "usages", // API endpoint
      {
        itemsPerPage: 1000,
        sortBy: null,
        search: search,
        categoryId: null,
        usageId: null,
        brandId: null,
        archetypeId: null,
        startDate: null,
        endDate: null,
        location: null,
        radius: null,
        resource: null,
      },
    );

    return usages.data;
  };

  const store = async (usageData) => {
    const { sendRequest } = useApi();

    const data = await sendRequest("usages", "post", usageData);

    await index();

    return data;
  };

  const update = async (usage) => {
    const { sendRequest } = useApi();

    const data = await sendRequest(`usages/${usage.id}`, "put", usage);

    await index();

    return data;
  };

  return {
    usageListPage,
    usageListItemsPerPage,
    usageListSortBy,
    usageListFilters,
    usageListUsages,
    usageListTotalUsages,
    usageListSelectedUsages,
    destroy,
    index,
    indexForAutocomplete,
    store,
    update,
  };
});
