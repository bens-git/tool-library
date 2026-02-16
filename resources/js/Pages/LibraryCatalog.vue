<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import ItemDialog from '@/Pages/ItemDialog.vue';
import AvailabilityDialog from '@/Pages/AvailabilityDialog.vue';
import DeleteItemDialog from '@/Pages/DeleteItemDialog.vue';
import LocationDialog from '@/Pages/LocationDialog.vue';
import { ref, onMounted, watch } from 'vue';
import _ from 'lodash';
import useApi from '@/Stores/api';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const items = ref([]);
const totalItems = ref(0);
const filters = ref({
    archetype: null,
    category: null,
    usage: null,
    brand: null,
    search: null,
    radius: 10,
    location: null,
    user_id: null,
});
const pageNumber = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref([]);
const advancedSearch = ref(false);

const { fullImageUrl } = useApi();

const toggleAdvancedSearch = () => (advancedSearch.value = !advancedSearch.value);

// Table headers
const headers = [
    { title: 'Actions', value: 'actions', sortable: false },
    { title: 'Code', value: 'code' },
    { title: 'Image', value: 'image' },
    { title: 'Archetype', value: 'archetype.name' },
    { title: 'Brand', value: 'brand.name' },
];

// Autocomplete arrays
const autocompleteArchetypes = ref([]);
const autocompleteBrands = ref([]);
const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);

const debounceSearch = _.debounce(() => {
    pageNumber.value = 1;
    refreshItems();
}, 300);

const handleSetLocation = (location, address, radius) => {
    filters.value.location = location;
    filters.value.radius = radius;
    refreshItems();
};

const refreshItems = async () => {
    const query = {
        page: pageNumber.value,
        brand_id: filters.value.brand?.id,
        archetype_id: filters.value?.archetype?.id,
        usage_id: filters.value?.usage?.id,
        category_id: filters.value?.category?.id,
    };

    if (filters.value.user_id) {
        const response = await axios.get(route('me.items.index'), {
            params: { query },
        });
        items.value = response.data.data;
        totalItems.value = response.data.total;
    } else {
        const response = await axios.get(route('items.index'), {
            params: query,
        });
        items.value = response.data.data;
        totalItems.value = response.data.total;
    }
};

const refreshAutocompleteArchetypes = async (query) => {
    if (query) {
        const response = await axios.get(route('archetypes.index'), {
            params: { query },
        });
        autocompleteArchetypes.value = response.data.data;
    }
};

const refreshAutocompleteBrands = async (query) => {
    const response = await axios.get(route('brands.index'), {
        params: { query },
    });
    autocompleteBrands.value = response.data.data;
};

const refreshAutocompleteCategories = async (query) => {
    const response = await axios.get(route('categories.index'), {
        params: { query },
    });
    autocompleteCategories.value = response.data.data;
};

const refreshAutocompleteUsages = async (query) => {
    const response = await axios.get(route('usages.index'), {
        params: { query },
    });
    autocompleteUsages.value = response.data.data;
};

const debouncedAutocompleteArchetypeSearch = _.debounce(refreshAutocompleteArchetypes, 300);
const debouncedAutocompleteBrandSearch = _.debounce(refreshAutocompleteBrands, 300);
const debouncedAutocompleteCategorySearch = _.debounce(refreshAutocompleteCategories, 300);
const debouncedAutocompleteUsageSearch = _.debounce(refreshAutocompleteUsages, 300);

// Load initial autocomplete values
onMounted(async () => {
    refreshAutocompleteArchetypes();

    watch(
        () => advancedSearch.value,
        async (isAdvanced) => {
            if (isAdvanced) {
                refreshAutocompleteBrands();
                refreshAutocompleteCategories();
                refreshAutocompleteUsages();
            }
        }
    );
});

const updateItemListOptions = (options) => {
    pageNumber.value = options.page;
    itemsPerPage.value = options.itemsPerPage;
    sortBy.value = options.sortBy;

    refreshAutocompleteArchetypes();
};
</script>

<template>
    <PageLayout>
        <Head title="Catalog" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%; max-width: 420px">
                <!-- Header -->
                <div class="d-flex justify-space-between align-center mb-4">
                    <div class="text-h5 font-weight-bold">Items</div>
                    <div>
                        <v-btn size="small" variant="outlined" @click="toggleAdvancedSearch">
                            {{ advancedSearch ? 'Hide Advanced' : 'Advanced Search' }}
                        </v-btn>
                        <ItemDialog v-if="user" aim="create" class="ml-2" />
                    </div>
                </div>

                <!-- Main search -->
                <v-autocomplete
                    v-model="filters.archetype"
                    density="compact"
                    :items="autocompleteArchetypes"
                    label="Search"
                    item-title="name"
                    item-value="id"
                    hide-no-data
                    hide-details
                    return-object
                    clearable
                    @update:model-value="debounceSearch"
                    @update:search="debouncedAutocompleteArchetypeSearch"
                />

                <!-- Advanced filters -->
                <v-expand-transition>
                    <div v-if="advancedSearch" class="mb-2">
                        <div v-if="user">
                            <v-checkbox
                                v-model="filters.user_id"
                                :true-value="user?.id"
                                :false-value="null"
                                label="Show only my items"
                                hide-details
                                density="compact"
                                @update:model-value="debounceSearch"
                            />
                        </div>

                        <v-autocomplete
                            v-model="filters.brand"
                            density="compact"
                            :items="autocompleteBrands"
                            label="Brand"
                            item-title="name"
                            item-value="id"
                            hide-no-data
                            hide-details
                            return-object
                            clearable
                            @update:model-value="debounceSearch"
                            @update:search="debouncedAutocompleteBrandSearch"
                        />

                        <v-autocomplete
                            v-model="filters.category"
                            density="compact"
                            :items="autocompleteCategories"
                            label="Category"
                            item-title="name"
                            item-value="id"
                            hide-no-data
                            hide-details
                            return-object
                            clearable
                            @update:model-value="debounceSearch"
                            @update:search="debouncedAutocompleteCategorySearch"
                        />

                        <v-autocomplete
                            v-model="filters.usage"
                            density="compact"
                            :items="autocompleteUsages"
                            label="Usage"
                            item-title="name"
                            item-value="id"
                            hide-no-data
                            hide-details
                            return-object
                            clearable
                            @update:model-value="debounceSearch"
                            @update:search="debouncedAutocompleteUsageSearch"
                        />

                        <div v-if="!filters.user_id" class="mt-3">
                            <LocationDialog @set-location="handleSetLocation" />
                        </div>
                    </div>
                </v-expand-transition>

                <!-- Items table -->
                <v-data-table-server
                    v-if="totalItems"
                    v-model:items-per-page="itemsPerPage"
                    :items-length="totalItems"
                    :headers="headers"
                    :items="items"
                    item-value="name"
                    fixed-header
                    class="mt-4 overflow-auto"
                    @update:options="updateItemListOptions"
                >
                    <template #[`item.image`]="{ item }">
                        <v-img
                            v-if="item.images?.length > 0"
                            :src="fullImageUrl(item.images[0].path)"
                            max-height="200"
                            max-width="200"
                            min-height="200"
                            min-width="200"
                            alt="Archetype Image"
                        />
                        <v-icon v-else>mdi-image-off</v-icon>
                    </template>

                    <template #[`item.actions`]="{ item }">
                        <AvailabilityDialog v-if="user && item.owned_by === user.id" :item="item" />
                        <ItemDialog
                            v-if="user && item.owned_by === user.id"
                            :item="item"
                            aim="edit"
                        />
                        <ItemDialog v-if="item.owned_by !== user.id" :item="item" aim="view" />
                        <DeleteItemDialog v-if="user && item.owned_by === user.id" :item="item" />
                    </template>
                </v-data-table-server>
            </div>
        </v-container>
    </PageLayout>
</template>

<style scoped>
/* Minimal spacing / centered container already handled inline */
</style>
