<template>
  <v-container class="d-flex justify-center fill-height">
    <v-card class="d-flex flex-column flex-grow-1" flat>
      <template #title>
        <div class="d-flex justify-space-between align-center">Items</div>
      </template>

      <v-card-text class="d-flex flex-column flex-grow-1 overflow-hidden">
        <v-row class="justify-center align-center">
          <v-col cols="12" md="8">
            <v-row>
              <v-col cols="12">
                <v-autocomplete
                  density="compact"
                  v-model="itemStore.itemListFilters.archetype"
                  :items="autocompleteArchetypes"
                  label="Search"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  clearable
                  @update:model-value="debounceSearch()"
                  @update:search="debouncedAutocompleteArchetypeSearch"
                ></v-autocomplete>
              </v-col>
              <v-col cols="6" md="6">
                <v-btn
                  size="small"
                  @click="toggleAdvancedSearch"
                  variant="outlined"
                >
                  {{ advancedSearch ? "Hide Advanced" : "Advanced Search" }}
                </v-btn>
              </v-col>
              <v-col cols="6" md="6" v-if="userStore.user">
                <ItemDialog aim="create" />
              </v-col>
            </v-row>
          </v-col>
        </v-row>
        <v-expand-transition class="mt-2">
          <div v-if="advancedSearch">
            <v-row>
              <v-col cols="12" md="4">
                <v-checkbox
                  v-if="userStore.user"
                  v-model="itemStore.itemListFilters.userId"
                  :true-value="userStore.user?.id"
                  :false-value="null"
                  label="Show only my items"
                  hide-details
                  density="compact"
                  @update:model-value="debounceSearch()"
                ></v-checkbox>
              </v-col>

              <v-col cols="12" md="4">
                <v-autocomplete
                  density="compact"
                  v-model="itemStore.itemListFilters.brand"
                  :items="autocompleteBrands"
                  label="Brand"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  variant="outlined"
                  clearable
                  @update:model-value="debounceSearch()"
                  @update:search="debouncedAutocompleteBrandSearch"
                ></v-autocomplete>
              </v-col>

              <v-col cols="12" md="4">
                <v-select
                  density="compact"
                  v-model="itemStore.itemListFilters.resource"
                  :items="resources"
                  label="Resource"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  variant="outlined"
                  clearable
                  @update:model-value="debounceSearch()"
                ></v-select>
              </v-col>

              <v-col cols="12" md="4">
                <v-autocomplete
                  density="compact"
                  v-model="itemStore.itemListFilters.category"
                  :items="autocompleteCategories"
                  label="Category"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  variant="outlined"
                  clearable
                  @update:model-value="debounceSearch()"
                  @update:search="debouncedAutocompleteCategorySearch"
                ></v-autocomplete>
              </v-col>

              <v-col cols="12" md="4">
                <v-autocomplete
                  density="compact"
                  v-model="itemStore.itemListFilters.usage"
                  :items="autocompleteUsages"
                  label="Usage"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  variant="outlined"
                  clearable
                  @update:model-value="debounceSearch()"
                  @update:search-value="debouncedAutocompleteUsageSearch"
                ></v-autocomplete>
              </v-col>

              <v-col cols="12" md="4" v-if="!itemStore.itemListFilters.userId">
                <LocationDialog @setLocation="handleSetLocation" />
              </v-col>
            </v-row>
          </div>
        </v-expand-transition>

        <!-- table top  fields-->

        <v-data-table-server
          v-if="itemStore.itemListTotalItems"
          v-model:items-per-page="itemStore.itemListItemsPerPage"
          :items-length="itemStore.itemListTotalItems"
          :headers="headers"
          @update:options="itemStore.updateItemListOptions"
          :items="itemStore.itemListItems"
          item-value="name"
          fixed-header
          class="flex-grow-1 overflow-auto"
        >
          <template v-slot:[`item.image`]="{ item }">
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
          <template v-slot:[`item.actions`]="{ item }">
            <AvailabilityDialog
              v-if="userStore.user && item.owned_by === userStore.user.id"
              :item="item"
            />

            <ItemDialog
              v-if="userStore.user && item.owned_by === userStore.user.id"
              :item="item"
              aim="edit"
            />

            <ItemDialog
              v-if="item.owned_by !== userStore.user.id"
              :item="item"
              aim="view"
            />

            <DeleteItemDialog
              v-if="userStore.user && item.owned_by === userStore.user.id"
              :item="item"
            />
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useItemStore } from "@/stores/item";
import { useArchetypeStore } from "@/stores/archetype";
import { useCategoryStore } from "@/stores/category";
import { useBrandStore } from "@/stores/brand";
import { useUsageStore } from "@/stores/usage";
import { useUserStore } from "@/stores/user";
import _ from "lodash";
import useApi from "@/stores/api";
import DeleteItemDialog from "./DeleteItemDialog.vue";
import ItemDialog from "./ItemDialog.vue";
import AvailabilityDialog from "./AvailabilityDialog.vue";
import LocationDialog from "./LocationDialog.vue";

const advancedSearch = ref(false);

const itemStore = useItemStore();
const archetypeStore = useArchetypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const brandStore = useBrandStore();
const userStore = useUserStore();

const { fullImageUrl } = useApi();
const toggleAdvancedSearch = () => {
  advancedSearch.value = !advancedSearch.value;
};
const headers = [
  { title: "Actions", value: "actions", sortable: false },
  { title: "Code", value: "code" },
  { title: "Image", value: "image" },
  { title: "Archetype", value: "archetype.name" },
  { title: "Brand", value: "brand.name" },
  { title: "Resource", value: "resource" },
];

const autocompleteArchetypes = ref([]);
const autocompleteBrands = ref([]);
const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);
const resources = ref([]);

onMounted(async () => {
  archetypeStore.index();
  resources.value = await archetypeStore.indexResources();
  categoryStore.index();
  usageStore.index();
  brandStore.index();
  autocompleteArchetypes.value = await archetypeStore.indexForAutocomplete();
  autocompleteBrands.value = await brandStore.indexForAutocomplete();
  autocompleteCategories.value = await categoryStore.indexForAutocomplete();
  autocompleteUsages.value = await usageStore.indexForAutocomplete();
});

// Autocomplete Archetype Search handler
const onAutocompleteArchetypeSearch = async (query) => {
  autocompleteArchetypes.value =
    await archetypeStore.indexForAutocomplete(query);
};

// Autocomplete brand Search handler
const onAutocompleteBrandSearch = async (query) => {
  autocompleteBrands.value = await brandStore.indexForAutocomplete(query);
};

// Autocomplete category Search handler
const onAutocompleteCategorySearch = async (query) => {
  autocompleteCategories.value =
    await categoryStore.indexForAutocomplete(query);
};
// Autocomplete usage Search handler
const onAutocompleteUsageSearch = async (query) => {
  autocompleteUsages.value = await usageStore.indexForAutocomplete(query);
};

// Debounced search function
const debouncedAutocompleteArchetypeSearch = _.debounce(
  onAutocompleteArchetypeSearch,
  300
);
const debouncedAutocompleteBrandSearch = _.debounce(
  onAutocompleteBrandSearch,
  300
);

const debouncedAutocompleteCategorySearch = _.debounce(
  onAutocompleteCategorySearch,
  300
);

const debouncedAutocompleteUsageSearch = _.debounce(
  onAutocompleteUsageSearch,
  300
);

const debounceSearch = _.debounce(() => {
  itemStore.page = 1;
  itemStore.index();
}, 300);

const handleSetLocation = (location, address, radius) => {
  console.log(location, radius);
  itemStore.itemListFilters.location = location;
  itemStore.itemListFilters.radius = radius;
  itemStore.index();
};
</script>

<style scoped>
/* Add your scoped styles here */
</style>
