import { defineStore } from 'pinia';
import useApi from '@/Stores/api';
import { ref } from 'vue';

export const useArchetypeStore = defineStore('archetype', () => {
    const address = ref(null);
    const dateRange = ref([
        new Date(new Date().setHours(9, 0, 0, 0)),
        new Date(new Date().setHours(17, 0, 0, 0)),
    ]);
    const order = ref('asc');
    const paginatedArchetypes = ref([]);
    const radius = ref(10);
    const selectedBrandId = ref(null);
    const selectedCategoryId = ref(null);
    const selectedArchetypeId = ref(null);
    const selectedUsageId = ref(null);
    const sortBy = ref([{ key: 'name', order: 'asc' }]);
    const toolArchetypes = ref(null);
    const totalToolArchetypes = ref(0);
    const totalArchetypesWithItems = ref(0);
    const totalUserArchetypes = ref(0);
    const archetypesWithItems = ref([]);
    const userArchetypes = ref([]);
    const resource = ref('TOOL');
    const archetypesListPage = ref(1);
    const archetypesListItemsPerPage = ref(10);
    const archetypesListSortBy = ref([{ key: 'name', order: 'asc' }]);
    const archetypesListFilters = ref({
        usage: null,
        category: null,
        search: null,
        resource: null,
    });
    const archetypesListArchetypes = ref([]);
    const archetypesListTotalArchetypes = ref(0);
    const archetypesListSelectedArchetype = ref(null);

    const destroy = async (archetypeId) => {
        const { sendRequest } = useApi();
        const data = await sendRequest(`archetypes/${archetypeId}`, 'delete');
        await index();
        return data;
    };

    const index = async (params) => {
        const { fetchRequest } = useApi();
        const data = await fetchRequest('archetypes', params);
        archetypesListArchetypes.value = data?.data?.map((userArchetypes) => {
            return {
                ...userArchetypes,
                category_ids: userArchetypes.category_ids
                    ? userArchetypes.category_ids.split(',').map((id) => Number(id.trim()))
                    : [],
                usage_ids: userArchetypes.usage_ids
                    ? userArchetypes.usage_ids.split(',').map((id) => Number(id.trim()))
                    : [],
            };
        });

        return data;
    };

    const indexForAutocomplete = async (search, resource = null) => {
        const { fetchRequest } = useApi();

        const archetypes = await fetchRequest(
            'archetypes', // API endpoint
            {
                itemsPerPage: 1000,
                sortBy: null,
                search: search,
                categoryId: null,
                usageId: null,
                brandId: null,
                archetypeId: null,
                resource: resource,
            }
        );

        return archetypes.data;
    };

    const indexResources = async () => {
        const { fetchRequest } = useApi();

        const data = await fetchRequest(
            'resources' // API endpoint
        );

        return data.data;
    };

    const show = async (id) => {
        const { fetchRequest } = useApi();
        const data = await fetchRequest(`archetypes/${id}`);
        return data?.data;
    };

    const store = async (data) => {
        const { sendRequest } = useApi();

        const response = await sendRequest('archetypes', 'post', data);

        await index();

        return response;
    };

    const update = async (archetype) => {
        const { sendRequest } = useApi();

        const data = await sendRequest(`archetypes/${archetype.id}`, 'put', archetype);

        await index();

        return data;
    };

    return {
        address,
        dateRange,
        order,
        paginatedArchetypes,
        radius,
        selectedBrandId,
        selectedCategoryId,
        selectedArchetypeId,
        selectedUsageId,
        sortBy,
        toolArchetypes,
        totalToolArchetypes,
        totalArchetypesWithItems,
        totalUserArchetypes,
        archetypesWithItems,
        userArchetypes,
        resource,
        archetypesListPage,
        archetypesListItemsPerPage,
        archetypesListSortBy,
        archetypesListFilters,
        archetypesListArchetypes,
        archetypesListTotalArchetypes,
        archetypesListSelectedArchetype,
        destroy,
        index,
        indexForAutocomplete,
        indexResources,
        show,
        store,
        update,
    };
});
