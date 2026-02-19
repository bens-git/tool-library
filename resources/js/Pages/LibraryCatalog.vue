<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import ItemDialog from '@/Pages/ItemDialog.vue';
import AvailabilityDialog from '@/Pages/AvailabilityDialog.vue';
import DeleteItemDialog from '@/Pages/DeleteItemDialog.vue';
import LocationDialog from '@/Pages/LocationDialog.vue';
import { ref, onMounted, watch } from 'vue';
import _ from 'lodash';
import api from '@/services/api';
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
const itemsPerPage = ref(9);
const advancedSearch = ref(false);
const hasSearchResults = ref(false);

const toggleAdvancedSearch = () => (advancedSearch.value = !advancedSearch.value);

// Autocomplete data
const autocompleteArchetypes = ref([]);
const autocompleteBrands = ref([]);
const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);
const featuredItems = ref([])

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
        itemsPerPage: itemsPerPage.value,
        brand_id: filters.value.brand?.id,
        archetype_id: filters.value?.archetype?.id,
        usage_id: filters.value?.usage?.id,
        category_id: filters.value?.category?.id,
        search: filters.value?.search,
        radius: filters.value?.radius,
        location_id: filters.value?.location?.id,
        user_id: filters.value?.user_id,
    };

    // Check if any search filter is active
    hasSearchResults.value = !!(query.brand_id || query.archetype_id || query.usage_id || 
        query.category_id || query.search || query.location_id || query.user_id);

    if (filters.value.user_id) {
        const response = await api.get(route('me.items.index'), {
            params: { query },
        });
        items.value = response.data.data;
        totalItems.value = response.data.total;
    } else {
        const response = await api.get(route('items.index'), {
            params: query,
        });
        items.value = response.data.data;
        totalItems.value = response.data.total;
    }
};

const refreshAutocompleteArchetypes = async (query) => {
    if (query) {
        const response = await api.get(route('archetypes.index'), {
            params: { query },
        });
        autocompleteArchetypes.value = response.data.data;
    }
};

const refreshAutocompleteBrands = async (query) => {
    const response = await api.get(route('brands.index'), {
        params: { query },
    });
    autocompleteBrands.value = response.data.data;
};

const refreshAutocompleteCategories = async (query) => {
    const response = await api.get(route('categories.index'), {
        params: { query },
    });
    autocompleteCategories.value = response.data.data;
};

const refreshAutocompleteUsages = async (query) => {
    const response = await api.get(route('usages.index'), {
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
    refreshFeaturedItems();
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

const refreshFeaturedItems = async () => {
    const response = await api.get(route('items.featured'));
    featuredItems.value = response.data.data;
};
</script>

<template>
    <PageLayout>
        <Head title="Catalog" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%">
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

                <!-- Featured Tools -->
                <div v-if="featuredItems.length && !hasSearchResults" class="mt-6">
                    <div class="text-h6 font-weight-bold mb-3">Featured Tools</div>

                    <v-row>
                        <v-col v-for="item in featuredItems" :key="item.id" cols="12" sm="6" md="4">
                            <v-card class="pa-2" elevation="2">
                                <v-img
                                    v-if="item.images?.length"
                                    :src="item.images[0].url"
                                    height="180"
                                    cover
                                />
                                <v-icon v-else size="80" class="ma-4">mdi-image-off</v-icon>

                                <v-card-title class="text-subtitle-1">
                                    {{ item.archetype?.name }}
                                </v-card-title>

                                <v-card-subtitle>
                                    {{ item.brand?.name }}
                                </v-card-subtitle>

                                <v-card-actions>
                                    <ItemDialog :item="item" aim="view" />
                                </v-card-actions>
                            </v-card>
                        </v-col>
                    </v-row>
                </div>

                <!-- Search Results as Card Grid -->
                <div v-if="hasSearchResults && items.length" class="mt-6">
                    <div class="text-h6 font-weight-bold mb-3">Search Results</div>

                    <v-row>
                        <v-col v-for="item in items" :key="item.id" cols="12" sm="6" md="4">
                            <v-card class="pa-2" elevation="2">
                                <v-img
                                    v-if="item.images?.length"
                                    :src="item.images[0].url"
                                    height="180"
                                    cover
                                />
                                <v-icon v-else size="80" class="ma-4">mdi-image-off</v-icon>

                                <v-card-title class="text-subtitle-1">
                                    {{ item.archetype?.name }}
                                </v-card-title>

                                <v-card-subtitle>
                                    {{ item.brand?.name }}
                                </v-card-subtitle>

                                <v-card-actions>
                                    <AvailabilityDialog v-if="user && item.owned_by === user.id" :item="item" />
                                    <ItemDialog
                                        v-if="user && item.owned_by === user.id"
                                        :item="item"
                                        aim="edit"
                                    />
                                    <ItemDialog v-if="item.owned_by !== user.id" :item="item" aim="view" />
                                    <DeleteItemDialog v-if="user && item.owned_by === user.id" :item="item" />
                                </v-card-actions>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- Pagination -->
                    <div class="d-flex justify-center mt-4">
                        <v-pagination
                            v-model="pageNumber"
                            :length="Math.ceil(totalItems / itemsPerPage)"
                            :total-visible="5"
                            @update:model-value="refreshItems"
                        />
                    </div>
                </div>

                <!-- No Results Message -->
                <div v-if="hasSearchResults && !items.length" class="mt-6 text-center">
                    <v-icon size="64" color="grey">mdi-magnify</v-icon>
                    <div class="text-h6 mt-2">No items found</div>
                </div>
            </div>
        </v-container>
    </PageLayout>
</template>

<style scoped>
/* Minimal spacing / centered container already handled inline */
</style>
