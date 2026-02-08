import { defineStore } from "pinia";
import useApi from "@/Stores/api";

export const useCategoryStore = defineStore("category", () => {
  const destroy = async (categoryId) => {
    const { sendRequest } = useApi();
    const data = await sendRequest(`categories/${categoryId}`, "delete");
    await index();
    return data;
  };

  const index = async (params) => {
    const { fetchRequest } = useApi();
    const data = await fetchRequest(
      "categories", 
      params,
    );

    return data;
  };

  const indexForAutocomplete = async (search) => {
    const { fetchRequest } = useApi();

    const categories = await fetchRequest(
      "categories", // API endpoint
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

    return categories.data;
  };

  const store = async (categoryData) => {
    const { sendRequest } = useApi();

    const data = await sendRequest("categories", "post", categoryData);

    await index();

    return data;
  };

  const update = async (category) => {
    const { sendRequest } = useApi();

    const data = await sendRequest(
      `categories/${category.id}`,
      "put",
      category,
    );

    await index();

    return data;
  };
  return {
    destroy,
    update,
    store,
    indexForAutocomplete,
    index,
  };
});
