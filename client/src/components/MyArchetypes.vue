<template>
  <v-container class="d-flex justify-center">
    <v-card
      :title="'My ' + route.query.resource + ' Archetypes'"
      :subtitle="userStore.user.name"
      flat
      style="min-width: 90vw; max-height: 88vh; min-height: 88vh"
    >
      <v-card-text>
        <v-row>
          <v-col>
            <ArchetypeDialog aim="create" :resource="route.query.resource" />
          </v-col>

          <v-col>
            <v-text-field
              density="compact"
              v-model="archetypeStore.myArchetypesListFilters.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
              @input="debounceSearch('items')"
            ></v-text-field>
          </v-col>
          <v-col>
            <v-autocomplete
              density="compact"
              v-model="archetypeStore.myArchetypesListFilters.category"
              :items="autocompleteCategories"
              label="Category"
              item-title="name"
              item-value="id"
              hide-no-data
              hide-details
              return-object
              clearable
              @update:model-value="debounceSearch()"
              @update:search="debouncedAutocompleteCategorySearch"
            ></v-autocomplete>
          </v-col>
          <v-col>
            <v-autocomplete
              density="compact"
              v-model="archetypeStore.myArchetypesListFilters.usage"
              :items="autocompleteUsages"
              label="Usage"
              item-title="name"
              item-value="id"
              hide-no-data
              hide-details
              return-object
              clearable
              @update:model-value="debounceSearch()"
              @update:search="debouncedAutocompleteUsageSearch"
            ></v-autocomplete>
          </v-col>
        </v-row>

        <v-data-table-server
          :search="archetypeStore.myArchetypesListFilters.search"
          v-model:items-per-page="archetypeStore.myArchetypesListItemsPerPage"
          :items-length="archetypeStore.myArchetypesListTotalArchetypes"
          :headers="headers"
          @update:options="archetypeStore.updateMyArchetypesListOptions({resource: route.query.resource})"
          :items="archetypeStore.myArchetypesListArchetypes"
          item-value="name"
          mobile-breakpoint="sm"
          fixed-header
          :height="'60vh'"
        >
          <template v-slot:[`item.actions`]="{ item }">
            <ArchetypeDialog
              v-if="userStore.user && item.created_by === userStore.user.id"
              :archetype="item"
              aim="edit"
              :resource="route.query.resource"
            />

            <DeleteArchetypeDialog
              v-if="userStore.user && item.created_by === userStore.user.id"
              :archetype="item"
            />
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { useItemStore } from "@/stores/item";
import { useArchetypeStore } from "@/stores/archetype";
import { useCategoryStore } from "@/stores/category";
import { useUsageStore } from "@/stores/usage";
import { useUserStore } from "@/stores/user";
import _ from "lodash";
import DeleteArchetypeDialog from "./DeleteArchetypeDialog.vue";
import ArchetypeDialog from "./ArchetypeDialog.vue";
import { useRoute } from "vue-router";

const itemStore = useItemStore();
const archetypeStore = useArchetypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const userStore = useUserStore();
const route = useRoute();

const headers = [
  { title: "Actions", value: "actions", sortable: false },
  { title: "Name", value: "name" },
  { title: "Categories", value: "categories" },
  { title: "Usages", value: "usages" },
];

const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);

const initialize = async () => {
  debounceSearch()
  categoryStore.fetchMyCategories();
  usageStore.fetchMyUsages();
  autocompleteCategories.value =
    await archetypeStore.fetchAutocompleteSelectCategories();
  autocompleteUsages.value =
    await archetypeStore.fetchAutocompleteSelectUsages();
};
onMounted(async () => {
  initialize();
});

watch(
  () => route.query.resource,
  (newResource) => {
    console.log(newResource)
    initialize();
  }
);

// Autocomplete Category Search handler
const onAutocompleteCategorySearch = async (query) => {
  autocompleteCategories.value =
    await archetypeStore.fetchAutocompleteSelectCategories(query);
};

// Autocomplete usage Search handler
const onAutocompleteUsageSearch = async (query) => {
  autocompleteUsages.value =
    await archetypeStore.fetchAutocompleteSelectUsages(query);
};

// Debounced search function
const debouncedAutocompleteCategorySearch = _.debounce(
  onAutocompleteCategorySearch,
  300
);
const debouncedAutocompleteUsageSearch = _.debounce(
  onAutocompleteUsageSearch,
  300
);

const debounceSearch = _.debounce(() => {
  archetypeStore.myArchetypesListFilters.resource = route.query.resource;

  archetypeStore.page = 1;
  archetypeStore.fetchMyArchetypes();
}, 300);
</script>

<style scoped>
/* Add your scoped styles here */
</style>
