<script setup>
/* global IntersectionObserver */
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import ItemDialog from '@/Pages/ItemDialog.vue';
import DeleteItemDialog from '@/Pages/DeleteItemDialog.vue';
import { ref, onMounted, onUnmounted } from 'vue';
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
    search: null,
    user_id: null,
    resource: null,
});

// Resource type options - comprehensive list for community sharing
const resourceOptions = [
    { title: 'All Types', value: null },
    { title: 'Tools', value: 'TOOL', icon: 'mdi-tools' },
    { title: 'Materials', value: 'MATERIAL', icon: 'mdi-cube' },
    { title: 'Labor/Services', value: 'LABOR', icon: 'mdi-account-hard-hat' },
    { title: 'Rideshare', value: 'RIDESHARE', icon: 'mdi-car' },
    { title: 'Furniture', value: 'FURNITURE', icon: 'mdi-table-furniture' },
    { title: 'Kitchen', value: 'KITCHEN', icon: 'mdi-blender' },
    { title: 'Electronics', value: 'ELECTRONICS', icon: 'mdi-television' },
    { title: 'Sports', value: 'SPORTS', icon: 'mdi-basketball' },
    { title: 'Outdoor', value: 'OUTDOOR', icon: 'mdi-tent' },
    { title: 'Party', value: 'PARTY', icon: 'mdi-party-popper' },
    { title: 'Books', value: 'BOOKS', icon: 'mdi-book' },
    { title: 'Other', value: 'OTHER', icon: 'mdi-dots-horizontal' },
];

// Debounced search for items (searches by archetype name)
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
        search: filters.value?.search,
        resource: filters.value?.resource,
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

// Initial load
onMounted(async () => {
    loadItems();
    setupIntersectionObserver();
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// Handle item deleted from DeleteItemDialog
const handleItemDeleted = (deletedItemId) => {
    // Remove the deleted item from the items array
    items.value = items.value.filter(item => item.id !== deletedItemId);
};
</script>

<template>
    <PageLayout>
        <Head title="Catalog" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%">
                <!-- Header -->
                <div class="d-flex justify-space-between align-center mb-4">
                    <div class="text-h5 font-weight-bold">Catalog</div>
                    <ItemDialog v-if="user" aim="create" />
                </div>

                <!-- Search and Filters -->
                <v-row class="mb-2">
                    <v-col cols="12" md="8">
                        <v-text-field
                            v-model="filters.search"
                            density="compact"
                            label="Search items..."
                            hide-details
                            clearable
                            @update:model-value="debounceSearch"
                            @click:clear="filters.search = null; debounceSearch();"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-select
                            v-model="filters.resource"
                            :items="resourceOptions"
                            label="Type"
                            density="compact"
                            hide-details
                            clearable
                            @update:model-value="debounceSearch"
                        />
                    </v-col>
                </v-row>

                <div v-if="user" class="mb-4">
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

                <!-- Search Results as Card Grid -->
                <div class="mt-6">
                    <v-row>
                        <v-col v-for="item in items" :key="item.id" cols="12" sm="6" md="4">
                            <v-card class="pa-2" elevation="2">
                                <v-img
                                    v-if="item.thumbnail_url"
                                    :src="item.thumbnail_url"
                                    height="180"
                                    cover
                                    lazy-src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTIeMmUyIi8+PC9zdmc+"
                                />
                                <v-icon v-else size="80" class="ma-4">mdi-image-off</v-icon>

                                <v-card-title class="text-subtitle-1">
                                    {{ item.archetype?.name }}
                                </v-card-title>

                                <v-card-actions class="flex-wrap">
                                    <div class="d-flex flex-wrap ga-2" style="width: 100%">
                                        <ItemDialog
                                            v-if="user && item.owned_by === user.id"
                                            :item="item"
                                            aim="edit"
                                            icon-only
                                        />
                                        <ItemDialog v-if="!user || item.owned_by !== user.id" :item="item" aim="view" />
                                        <DeleteItemDialog 
                                            v-if="user && item.owned_by === user.id" 
                                            :item="item" 
                                            icon-only
                                            @deleted="handleItemDeleted"
                                        />
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
                            No more items
                        </p>
                    </div>
                </div>

                <!-- No Results Message -->
                <div v-if="!items.length && !loading" class="mt-6 text-center">
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

