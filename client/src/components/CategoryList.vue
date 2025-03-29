<template>
  <v-container class="d-flex justify-center">
    <v-card
      title="My Categories"
      :subtitle="userStore.user.name"
      flat
      style="min-width: 90vw; max-height: 88vh; min-height: 88vh"
    >
      <v-card-text>
        <v-row>
          <v-col>
            <CategoryDialog aim="create" />
          </v-col>

          <v-col>
            <v-text-field
              density="compact"
              v-model="categoryStore.myCategoriesListFilters.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
              @input="debounceSearch()"
            ></v-text-field>
          </v-col>
        </v-row>

        <v-data-table-server
          :search="categoryStore.myCategoriesListFilters.search"
          v-model:items-per-page="categoryStore.myCategoriesListItemsPerPage"
          :items-length="categoryStore.myCategoriesListTotalCategories"
          :headers="headers"
          @update:options="categoryStore.updateMyCategoriesListOptions"
          :items="categoryStore.myCategoriesListCategories"
          item-value="name"
          mobile-breakpoint="sm"
          fixed-header
          :height="'60vh'"
        >
          <template v-slot:[`item.actions`]="{ item }">
            <CategoryDialog
              v-if="userStore.user && item.created_by === userStore.user.id"
              :category="item"
              aim="edit"
            />

            <DeleteCategoryDialog
              v-if="userStore.user && item.created_by === userStore.user.id"
              :category="item"
            />
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useCategoryStore } from "@/stores/category";
import { useUserStore } from "@/stores/user";
import _ from "lodash";
import DeleteCategoryDialog from "./DeleteCategoryDialog.vue";
import CategoryDialog from "./CategoryDialog.vue";

const categoryStore = useCategoryStore();
const userStore = useUserStore();

const headers = [
  { title: "Actions", value: "actions", sortable: false },
  { title: "Name", value: "name" },
];


onMounted(async () => {
  categoryStore.fetchMyCategories();
 
});


const debounceSearch = _.debounce(() => {
  categoryStore.page = 1;
  categoryStore.fetchMyCategories();
}, 300);
</script>

<style scoped>
/* Add your scoped styles here */
</style>
