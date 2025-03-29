<template>
  <v-container class="d-flex justify-center fill-height">
    <v-card class="d-flex flex-column flex-grow-1" flat>
      <template #title>
        <div class="d-flex justify-space-between align-center">Projects</div>
      </template>

      <v-card-text class="d-flex flex-column flex-grow-1 overflow-hidden">
        <v-row class="justify-center align-center">
          <v-col cols="12" md="8">
            <v-row>
              <v-col cols="12">
                <v-autocomplete
                  density="compact"
                  v-model="projectStore.projectListFilters.project"
                  :items="autocompleteProjects"
                  label="Project"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  clearable
                  @update:model-value="debounceSearch"
                  @update:search="debouncedAutocompleteProjectSearch"
                />
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
                <ProjectDialog aim="create" />
              </v-col>
            </v-row>
          </v-col>
        </v-row>
        <v-expand-transition>
          <div v-if="advancedSearch">
            <v-row>
              <v-col cols="12" md="3">
                <v-checkbox
                  v-if="userStore.user"
                  v-model="projectStore.projectListFilters.userId"
                  :true-value="userStore.user?.id"
                  :false-value="null"
                  label="Show only my projects"
                  hide-details
                  density="compact"
                  @update:model-value="debounceSearch"
                ></v-checkbox>
              </v-col>

              <v-col cols="12" md="3">
                <v-autocomplete
                  density="compact"
                  v-model="projectStore.projectListFilters.archettype"
                  :items="autocompleteArchetypes"
                  label="Archetype"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  variant="outlined"
                  clearable
                  @update:model-value="debounceSearch"
                  @update:search="debouncedAutocompleteArchetypeSearch"
                ></v-autocomplete>
              </v-col>
            </v-row>
          </div>
        </v-expand-transition>

        <v-data-table-server
          v-model:items-per-page="projectStore.projectListItemsPerPage"
          :headers="headers"
          :items="projectStore.projectListProjects"
          :items-length="projectStore.projectListTotalProjects"
          loading-text="Loading... Please wait"
          :search="projectStore.projectListFilters.search"
          item-value="id"
          @update:options="projectStore.updateprojectListOptions"
          class="flex-grow-1 overflow-auto"
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
            <!-- create project dialog -->
            <DeleteProjectDialog
              :project="item"
              v-if="userStore.user?.id == item.created_by"
            />

            <ProjectDialog
              :project="item"
              :isEdit="true"
              v-if="userStore.user?.id == item.created_by"
            />

            <LoginDialog v-else />
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useArchetypeStore } from "@/stores/archetype";
import { useJobStore } from "@/stores/job";
import { useProjectStore } from "@/stores/project";
import { useUserStore } from "@/stores/user";
import _ from "lodash";
import ProjectDialog from "./ProjectDialog.vue";
import DeleteProjectDialog from "./DeleteProjectDialog.vue";
import { useRouter } from "vue-router";
import debounce from "lodash/debounce";
import { useDisplay } from "vuetify";
import LoginDialog from "./LoginDialog.vue";

const advancedSearch = ref(false);

const archetypeStore = useArchetypeStore();
const jobStore = useJobStore();
const projectStore = useProjectStore();
const userStore = useUserStore();

const router = useRouter();

const apiHost = process.env.VUE_APP_API_HOST;
const environment = process.env.VUE_APP_ENVIRONMENT;
const toggleAdvancedSearch = () => {
  advancedSearch.value = !advancedSearch.value;
};
const baseURL =
  environment == "development" ? `http://${apiHost}` : `https://${apiHost}`;

const fullImageUrl = (imagePath) => {
  return `${baseURL}/${imagePath}`;
};

const isProjectDialogOpen = ref(false);

const onProjectDialogOpen = () => {
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
    title: "Project",
    align: "start",
    sortable: true,
    key: "name",
  },
];

const autocompleteArchetypes = ref([]);
const autocompleteProjects = ref([]);

const debounceSearch = _.debounce(() => {
  projectStore.fetchProjects();
}, 300);

onMounted(async () => {
  autocompleteArchetypes.value = await archetypeStore.indexForAutocomplete();

  autocompleteProjects.value = await projectStore.indexForAutocomplete();
});

const onAutocompleteArchetypeSearch = async (query) => {
  autocompleteArchetypes.value =
    await archetypeStore.indexForAutocomplete(query);
};

const onAutocompleteProjectSearch = async (query) => {
  autocompleteProjects.value = await projectStore.indexForAutocomplete(query);
};

const debouncedAutocompleteArchetypeSearch = debounce(
  onAutocompleteArchetypeSearch,
  300
);

const debouncedAutocompleteProjectSearch = debounce(
  onAutocompleteProjectSearch,
  300
);
const jobList = () => {
  router.push({ path: "/job-list" });
};
</script>

<style>
.custom-dialog .v-overlay__content {
  pointer-events: none;
}

.custom-dialog .v-card {
  pointer-events: auto;
}
</style>
