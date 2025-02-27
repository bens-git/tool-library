<template>
  <v-container class="d-flex justify-center">
    <v-card title="Jobs" flat style="min-width: 88vw; min-height: 88vh">
      <template v-slot:text>
        <v-row>
          <!-- Search Field -->
          <v-col cols="12" md="3">
            <v-text-field
              density="compact"
              v-model="jobStore.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
              @input="debounceSearch"
            />
          </v-col>

          <!-- Project -->
          <v-col cols="12" md="4">
            <v-autocomplete
              density="compact"
              v-model="jobStore.selectedProjectId"
              :items="jobStore.projects"
              label="Project"
              item-title="name"
              item-value="id"
              :clearable="true"
              @update:modelValue="debounceSearch"
            />
          </v-col>

          <!-- Material -->
          <v-col cols="12" md="4">
            <v-autocomplete
              density="compact"
              v-model="jobStore.selectedMaterialId"
              :items="jobStore.materials"
              label="Material"
              item-title="name"
              item-value="id"
              :clearable="true"
              @update:modelValue="debounceSearch"
            />
          </v-col>
        </v-row>
        <v-row class="d-flex">
          <!-- Reset Button -->
          <v-col cols="12" md="12" class="d-flex">
            <v-btn
              class="text-none font-weight-regular"
              prepend-icon="mdi-refresh"
              text="Refresh"
              variant="tonal"
              @click="jobStore.resetFilters"
            ></v-btn>

            <!-- create job dialog -->
            <JobDialog :isEdit="false" />
          </v-col>
        </v-row>
      </template>
      <v-data-table-server
        v-model:items-per-page="jobStore.jobsListItemsPerPage"
        :headers="headers"
        :items="jobStore.jobsListJobs"
        :items-length="jobStore.jobsListTotalJobs"
        loading-text="Loading... Please wait"
        :search="jobStore.jobsListFilters.search"
        item-value="id"
        @update:options="jobStore.updateJobsListOptions"
        mobile-breakpoint="sm"
      >
        <!-- Image column -->
        <template v-slot:[`item.image`]="{ item }">
          <v-img
            v-if="item.images.length > 0"
            :src="fullImageUrl(item.images[0].path)"
            max-height="200"
            max-width="200"
            min-height="200"
            min-width="200"
            alt="ResourceArchetype Image"
          ></v-img>
          <v-icon v-else>mdi-image-off</v-icon>
          <!-- Fallback icon if no image is available -->
        </template>

        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon @click="editResourceArchetype(item)" v-if="userStore.user">
            <v-icon>mdi-information</v-icon>
          </v-btn>

          <v-btn icon @click="goToLogin" v-else>
            <v-icon>mdi-login</v-icon>
          </v-btn>
        </template>
      </v-data-table-server>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useJobStore } from "@/stores/job";
import _ from "lodash";
import JobDialog from "./JobDialog.vue";
import { useRouter } from "vue-router";

const jobStore = useJobStore();

const router = useRouter();

const apiHost = process.env.VUE_APP_API_HOST;
const environment = process.env.VUE_APP_ENVIRONMENT;

const baseURL =
  environment == "development" ? `http://${apiHost}` : `https://${apiHost}`;

const fullImageUrl = (imagePath) => {
  return `${baseURL}/${imagePath}`;
};

const isJobDialogOpen = ref(false);

const onJobDialogOpen = () => {
  fetchSomeData();
};

const headers = [
  {
    title: "Image",
    align: "start",
    sortable: false,
    key: "image",
  },
  {
    title: "Actions",
    align: "start",
    sortable: false,
    key: "actions",
  },
  {
    title: "Job",
    align: "start",
    sortable: true,
    key: "name",
  },

  {
    title: "Projects",
    align: "start",
    sortable: false,
    key: "projects",
  },
  {
    title: "Materials",
    align: "start",
    sortable: false,
    key: "materials",
  },
];

const debounceSearch = _.debounce(() => {
  jobStore.fetchJobs();
}, 300);

const editJob = (job) => {
  jobStore.selectedJob = job;
  editJobDialog.value = true;
};

const goToLogin = () => {
  router.push({ name: "login-form" }); // Adjust the route name as necessary
};

onMounted(async () => {});
</script>

<style>
.custom-dialog .v-overlay__content {
  pointer-events: none;
}

.custom-dialog .v-card {
  pointer-events: auto;
}
</style>
