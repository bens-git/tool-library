<template>
  <v-container class="d-flex justify-center">
    <v-card
      title="My Usages"
      :subtitle="userStore.user.name"
      flat
      style="min-width: 90vw; max-height: 88vh; min-height: 88vh"
    >
      <v-card-text>
        <v-row>
          <v-col>
            <UsageDialog aim="create" />
          </v-col>

          <v-col>
            <v-text-field
              density="compact"
              v-model="usageStore.myUsagesListFilters.search"
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
          :search="usageStore.myUsagesListFilters.search"
          v-model:items-per-page="usageStore.myUsagesListItemsPerPage"
          :items-length="usageStore.myUsagesListTotalUsages"
          :headers="headers"
          @update:options="usageStore.updateMyUsagesListOptions"
          :items="usageStore.myUsagesListUsages"
          item-value="name"
          mobile-breakpoint="sm"
          fixed-header
          :height="'60vh'"
        >
          <template v-slot:[`item.actions`]="{ item }">
            <UsageDialog
              v-if="userStore.user && item.created_by === userStore.user.id"
              :usage="item"
              aim="edit"
            />

            <DeleteUsageDialog
              v-if="userStore.user && item.created_by === userStore.user.id"
              :usage="item"
            />
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useUsageStore } from "@/stores/usage";
import { useUserStore } from "@/stores/user";
import _ from "lodash";
import DeleteUsageDialog from "./DeleteUsageDialog.vue";
import UsageDialog from "./UsageDialog.vue";

const usageStore = useUsageStore();
const userStore = useUserStore();

const headers = [
  { title: "Actions", value: "actions", sortable: false },
  { title: "Name", value: "name" },
];


onMounted(async () => {
  usageStore.fetchMyUsages();
 
});


const debounceSearch = _.debounce(() => {
  usageStore.page = 1;
  usageStore.fetchMyUsages();
}, 300);
</script>

<style scoped>
/* Add your scoped styles here */
</style>
