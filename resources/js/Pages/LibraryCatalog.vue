<script setup>
/* global IntersectionObserver */
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import ItemDialog from '@/Pages/ItemDialog.vue';
import AvailabilityDialog from '@/Pages/AvailabilityDialog.vue';
import DeleteItemDialog from '@/Pages/DeleteItemDialog.vue';
import { ref, onMounted, watch, onUnmounted } from 'vue';
import _ from 'lodash';
import api from '@/services/api';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

// Search results state
const items = ref([])
const pageNumber = ref(1)
const itemsPerPage = ref(9)
const loading = ref(false)
const hasMore = ref(true)
const loadTrigger = ref(null)
let observer = null

// Filters
const filters = ref({
    archetype: null,
    category: null,
    usage: null,
    brand: null,
    search: null,
    user_id: null,
});
const advancedSearch = ref(false);

const toggleAdvancedSearch = () => (advancedSearch.value = !advancedSearch.value);

// Autocomplete data
const autocompleteArchetypes = ref([]);
const autocompleteBrands = ref([]);
const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);

const debounceSearch = _.debounce(() => {
    pageNumber.value = 1;
    items.value = [];
    hasMore.value = true;
    loadItems();
}, 300);

const loadItems = async () => {
    if (loading.value || !hasMore.value) return;
    
    loading.value = true;
    
    const query = {
        page: pageNumber.value,
        itemsPerPage: itemsPerPage.value,
        brand_id: filters.value.brand?.id,
        archetype_id: filters.value?.archetype?.id,
        usage_id: filters.value?.usage?.id,
        category_id: filters.value?.category?.id,
        search: filters.value?.search,
    };

    // Add user_id filter if "Show only my tools" is checked
    if (filters.value.user_id) {
        query.user_id = filters.value.user_id;
    }

    try {
        const response = await api.get(route('items.index'), {
            params: query,
        });
        
        const newItems = response.data.data || [];
        const total = response.data.total || 0;
        
        // Append new items
        items.value = [...items.value, ...newItems];
        
        // Check if there are more items
        hasMore.value = items.value.length < total;
        pageNumber.value++;
    } catch (error) {
        console.error('Error loading items:', error);
    } finally {
        loading.value = false;
    }
};

const setupIntersectionObserver = () => {
    if (observer) observer.disconnect();
    
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !loading.value && hasMore.value) {
            loadItems();
        }
    }, { threshold: 0.1 });
    
    if (loadTrigger.value) {
        observer.observe(loadTrigger.value);
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

// Initial load
onMounted(async () => {
    loadItems();
    setupIntersectionObserver();
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

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
    <PageLayout>
        <Head title="Catalog" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%">
                <!-- Header -->
                <div class="d-flex justify-space-between align-center mb-4">
                    <div class="text-h5 font-weight-bold">Tools</div>
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
                                label="Show only my tools"
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
                    </div>
                </v-expand-transition>

                <!-- Search Results as Card Grid -->
                <div class="mt-6">
                    <v-row>
                        <v-col v-for="item in items" :key="item.id" cols="12" sm="6" md="4">
                            <v-card class="pa-2" elevation="2">
                                <v-img
                                    v-if="item.images?.length"
                                    :src="item.images[0].url"
                                    height="180"
                                    cover
                                    lazy-src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTIeMmUyIi8+PC9zdmc+"
                                />
                                <v-icon v-else size="80" class="ma-4">mdi-image-off</v-icon>

                                <v-card-title class="text-subtitle-1">
                                    {{ item.archetype?.name }}
                                </v-card-title>

                                <v-card-subtitle>
                                    {{ item.brand?.name }}
                                </v-card-subtitle>

                                <v-card-actions class="flex-wrap">
                                    <div class="d-flex flex-wrap ga-2" style="width: 100%">
                                        <AvailabilityDialog v-if="user && item.owned_by === user.id" :item="item" />
                                        <ItemDialog
                                            v-if="user && item.owned_by === user.id"
                                            :item="item"
                                            aim="edit"
                                        />
                                        <ItemDialog v-if="!user || item.owned_by !== user.id" :item="item" aim="view" />
                                        <DeleteItemDialog v-if="user && item.owned_by === user.id" :item="item" />
                                    </div>
                                </v-card-actions>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- Load more trigger for infinite scroll -->
                    <div ref="loadTrigger" class="text-center pa-4">
                        <v-progress-circular
                            v-if="loading"
                            indeterminate
                            color="primary"
                        ></v-progress-circular>
                        <p v-else-if="hasMore" class="text-grey">
                            Scroll for more...
                        </p>
                        <p v-else-if="items.length" class="text-grey">
                            No more tools
                        </p>
                    </div>
                </div>

                <!-- No Results Message -->
                <div v-if="!items.length && !loading" class="mt-6 text-center">
                    <v-icon size="64" color="grey">mdi-magnify</v-icon>
                    <div class="text-h6 mt-2">No tools found</div>
                </div>
            </div>
        </v-container>
    </PageLayout>
</template>

<style scoped>
/* Minimal spacing / centered container already handled inline */
</style>

