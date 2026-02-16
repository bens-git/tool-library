<script setup>
import { ref, onMounted } from 'vue';
import _ from 'lodash';
import debounce from 'lodash/debounce';
import ArchetypeDialog from './ArchetypeDialog.vue';
import PageLayout from '@/Layouts/PageLayout.vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';


const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);
const autocompleteBrands = ref([]);

const options = ref({
    page: 1,
    itemsPerPage: 10,
    sortBy: [
        { key: 'updated_at', order: 'desc' },
        { key: 'name', order: 'asc' },
    ],
});

const search = ref('');
const archetypes = ref([]);
const totalArchetypes = ref(0);

const selectedCategoryId = ref(null);
const selectedUsageId = ref(null);
const selectedResourceId = ref(null);
const selectedBrandId = ref(null);



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
const debouncedAutocompleteCategorySearch = debounce(refreshAutocompleteCategories, 300);
const debouncedAutocompleteBrandSearch = debounce(refreshAutocompleteBrands, 300);
const debouncedAutocompleteUsageSearch = debounce(refreshAutocompleteUsages, 300);

const debounceSearch = _.debounce(() => {
    refreshArchetypes();
}, 300);

onMounted(async () => {
    refreshAutocompleteCategories();
    refreshAutocompleteBrands();
    refreshAutocompleteUsages();
});

const showOwnedOnly = ref(false);

const toggleOwned = () => {
    showOwnedOnly.value = !showOwnedOnly.value;
};

async function refreshArchetypes() {
    const params = {
        page: options.value.page,
        itemsPerPage: options.value.itemsPerPage,
        sortBy: options.value.sortBy,
        search: search.value,
        categoryId: selectedCategoryId.value,
        usageId: selectedUsageId.value,
        resource: selectedResourceId.value,
    };
    const response = await axios.get(route('archetypes.index'), {
        params: params,
    });

    archetypes.value = response.data;
    totalArchetypes.value = response.total;
}

const resetFilters = async () => {
    search.value = '';
    selectedCategoryId.value = null;
    selectedUsageId.value = null;
    selectedResourceId.value = null;

    await refreshArchetypes();
};
</script>

<template>
    <PageLayout>
        <Head title="Types" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%; max-width: 420px">
                <!-- Header -->
                <div class="d-flex justify-space-between align-center mb-4">
                    <div class="text-h5 font-weight-bold">Types</div>
                </div>

                <v-text-field
                    v-model="search"
                    density="compact"
                    label="Search"
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    hide-details
                    single-line
                    @input="debounceSearch"
                />

                <!-- Category -->
                <v-autocomplete
                    v-model="selectedCategoryId"
                    density="compact"
                    :items="autocompleteCategories"
                    label="Category"
                    item-title="name"
                    item-value="id"
                    :clearable="true"
                    @update:model-value="debounceSearch"
                    @update:search="debouncedAutocompleteCategorySearch"
                />

                <!-- Usage -->
                <v-autocomplete
                    v-model="selectedUsageId"
                    density="compact"
                    :items="autocompleteUsages"
                    label="Usage"
                    item-title="name"
                    item-value="id"
                    :clearable="true"
                    @update:model-value="debounceSearch"
                    @update:search="debouncedAutocompleteUsageSearch"
                />

                <!-- Brand -->
                <v-autocomplete
                    v-model="selectedBrandId"
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
                ></v-autocomplete>

                <v-btn
                    :color="showOwnedOnly ? 'primary' : 'default'"
                    variant="outlined"
                    @click="toggleOwned"
                >
                    <v-icon>{{ showOwnedOnly ? 'mdi-check' : 'mdi-filter-outline' }}</v-icon>
                    Show Mine
                </v-btn>

                <ArchetypeDialog aim="create" />

                <!-- Reset Button -->
                <v-btn
                    variant="tonal"
                    class="text-none font-weight-regular"
                    color="primary"
                    @click="resetFilters"
                >
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>

                <!-- <v-data-table-server
                    v-model:options="options"
                    :headers="headers"
                    :items="archetypes"
                    :items-length="totalArchetypes"
                    loading-text="Loading... Please wait"
                    item-value="id"
                    mobile-breakpoint="sm"
                    fixed-header
                    :height="'50vh'"
                    @update:options="refreshArchetypes"
                >
                    <template #[`item.actions`]="{ item }">
                        <ArchetypeDialog
                            v-if="user && item.created_by == user.id"
                            :archetype="item"
                        />
                    </template>

                    <template #[`item.image`]="{ item }">
                        <v-img
                            v-if="item.images?.length > 0"
                            :src="fullImageUrl(item.images[0].path)"
                            max-height="200"
                            max-width="200"
                            min-height="200"
                            min-width="200"
                            alt="Archetype Image"
                        ></v-img>
                        <v-icon v-else>mdi-image-off</v-icon>
                    </template>

                    <template #[`item.locations`]="{ item }">
                        <div v-html="formatLocation(item.locations)"></div>
                    </template>

                    <template #[`item.item_count`]="{ item }">
                        <div v-if="item.available_item_count">
                            {{
                                item.available_item_count -
                                (item.rented_item_count ? item.rented_item_count : 0)
                            }}
                        </div>
                        <div v-if="item.rented_item_count">
                            {{ item.rented_item_count }} (rented)
                        </div>
                    </template>
                </v-data-table-server> -->
            </div>
        </v-container>
    </PageLayout>
</template>

<style>
.custom-dialog .v-overlay__content {
    pointer-events: none;
}

.custom-dialog .v-card {
    pointer-events: auto;
}
</style>
