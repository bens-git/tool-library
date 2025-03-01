<template>
  <v-container class="d-flex justify-center">
    <v-card
      title="My Brands"
      :subtitle="userStore.user.name"
      flat
      style="min-width: 90vw; max-height: 88vh; min-height: 88vh"
    >
      <v-card-text>
        <v-row>
          <v-col>
            <BrandDialog aim="create" />
          </v-col>

          <v-col>
            <v-text-field
              density="compact"
              v-model="brandStore.myBrandsListFilters.search"
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
          :search="brandStore.myBrandsListFilters.search"
          v-model:items-per-page="brandStore.myBrandsListItemsPerPage"
          :items-length="brandStore.myBrandsListTotalBrands"
          :headers="headers"
          @update:options="brandStore.updateMyBrandsListOptions"
          :items="brandStore.myBrandsListBrands"
          item-value="name"
          mobile-breakpoint="sm"
          fixed-header
          :height="'60vh'"
        >
          <template v-slot:[`item.actions`]="{ item }">
            <BrandDialog
              v-if="userStore.user && item.created_by === userStore.user.id"
              :brand="item"
              aim="edit"
            />

            <DeleteBrandDialog
              v-if="userStore.user && item.created_by === userStore.user.id"
              :brand="item"
            />
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useBrandStore } from "@/stores/brand";
import { useUserStore } from "@/stores/user";
import _ from "lodash";
import DeleteBrandDialog from "./DeleteBrandDialog.vue";
import BrandDialog from "./BrandDialog.vue";

const brandStore = useBrandStore();
const userStore = useUserStore();

const headers = [
  { title: "Actions", value: "actions", sortable: false },
  { title: "Name", value: "name" },
];


onMounted(async () => {
  brandStore.fetchMyBrands();
 
});


const debounceSearch = _.debounce(() => {
  brandStore.page = 1;
  brandStore.fetchMyBrands();
}, 300);
</script>

<style scoped>
/* Add your scoped styles here */
</style>
