<template>
    <v-container class="d-flex justify-center">
      <v-card
        title="My Archetypes"
        :subtitle="userStore.user.name"
        flat
        style="min-width: 90vw; max-height: 88vh; min-height: 88vh"
      >
        <v-card-text>
          <v-row>
            <v-col cols="3" md="3">
              <ItemDialog aim="create" />
            </v-col>
  
            <v-col cols="2" md="2">
              <v-text-field
                density="compact"
                v-model="itemStore.myItemsListFilters.search"
                label="Search"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                hide-details
                single-line
                @input="debounceSearch('items')"
              ></v-text-field>
            </v-col>
            <v-col cols="2" md="2">
              <v-autocomplete
                density="compact"
                v-model="itemStore.myItemsListFilters.archetypeId"
                :items="autocompleteArchetypes"
                label="Archetype"
                item-title="name"
                item-value="id"
                hide-no-data
                hide-details
                return-object
                clearable
                @update:model-value="debounceSearch('items')"
                @update:search="debouncedAutocompleteArchetypeSearch"
              ></v-autocomplete>
            </v-col>
            <v-col cols="2" md="2">
              <v-autocomplete
                density="compact"
                v-model="itemStore.myItemsListFilters.brandId"
                :items="autocompleteBrands"
                label="Brand"
                item-title="name"
                item-value="id"
                hide-no-data
                hide-details
                return-object
                clearable
                @update:model-value="debounceSearch('items')"
                @update:search="debouncedAutocompleteBrandSearch"
              ></v-autocomplete>
            </v-col>
            <v-col cols="3" md="3">
              <v-select
                density="compact"
                v-model="itemStore.myItemsListFilters.resource"
                :items="itemStore.resources"
                label="Resource"
                item-title="name"
                item-value="id"
                clearable
                @update:model-value="debounceSearch('items')"
              ></v-select>
            </v-col>
          </v-row>
  
          <v-data-table-server
            :search="itemStore.myItemsListFilters.search"
            v-model:items-per-page="itemStore.myItemsListItemsPerPage"
            :items-length="itemStore.myItemsListTotalItems"
            :headers="headers"
            @update:options="itemStore.updateMyItemsListOptions"
            :items="itemStore.myItemsListItems"
            item-value="name"
            mobile-breakpoint="sm"
            fixed-header
            :height="'60vh'"
          >
            <!-- Image column -->
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
              <!-- Fallback icon if no image is available -->
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
  
  const itemStore = useItemStore();
  const archetypeStore = useArchetypeStore();
  const categoryStore = useCategoryStore();
  const usageStore = useUsageStore();
  const brandStore = useBrandStore();
  const userStore = useUserStore();
  
  const { fullImageUrl } = useApi();
  
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
  
  onMounted(async () => {
    itemStore.fetchResources();
    archetypeStore.fetchMyArchetypes();
    categoryStore.fetchMyCategories();
    usageStore.fetchMyUsages ();
    brandStore.fetchMyBrands();
    autocompleteArchetypes.value =
      await archetypeStore.fetchAutocompleteSelectArchetypes();
    autocompleteBrands.value = await brandStore.fetchAutocompleteSelectBrands();
  });
  
  // Autocomplete Archetype Search handler
  const onAutocompleteArchetypeSearch = async (query) => {
    autocompleteArchetypes.value =
      await archetypeStore.fetchAutocompleteSelectArchetypes(query);
  };
  
  // Autocomplete brand Search handler
  const onAutocompleteBrandSearch = async (query) => {
    autocompleteBrands.value =
      await brandStore.fetchAutocompleteSelectBrands(query);
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
  
  const debounceSearch = _.debounce(() => {
    itemStore.page = 1;
    itemStore.fetchMyItems();
  }, 300);
  </script>
  
  <style scoped>
  /* Add your scoped styles here */
  </style>
  