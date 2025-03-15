<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          class="text-none font-weight-regular"
          :prepend-icon="isEdit ? 'mdi-pencil' : 'mdi-plus'"
          :text="isEdit ? 'Edit Project' : 'Create Project'"
          variant="tonal"
          block
          :color="isEdit ? 'primary' : 'success'"
          v-bind="activatorProps"
        ></v-btn>
      </template>
      <v-card
        :prepend-icon="isEdit ? 'mdi-edit' : 'mdi-plus'"
        :title="isEdit ? 'Edit Project' : 'Create Project'"
      >
        <v-card-text v-if="localProject">
          <v-row dense>
            <v-col cols="12" md="4" sm="6">
              <v-text-field
                density="compact"
                v-model="localProject.name"
                label="Name"
                :error-messages="responseStore.response?.errors?.name"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4" sm="6">
              <v-textarea
                density="compact"
                v-model="localProject.description"
                label="Description"
                placeholder=""
                :error-messages="responseStore.response?.errors?.description"
              ></v-textarea>
            </v-col>

            
            <v-col cols="12" md="4" sm="6">
              <v-select
                density="compact"
                v-model="newJob"
                :items="jobs"
                label="Add Job"
                item-title="name"
                item-value="id"
                :return-object="true"
                :error-messages="responseStore.response?.errors?.new_job"
              ></v-select>

              <v-btn
              prepend-icon="mdi-plus"

                color="primary"
                text="Add Job to Project"
                variant="tonal"
                block
                @click="addJob()"
              ></v-btn>

              <!-- create job dialog -->

              <JobDialog
                aim="create"
                :base="finalJob?.product"
                @created="refreshJobs()"
              />
            </v-col>
            </v-row>
            <v-row>

            <v-col >
              <v-alert
                color="error"
                v-if="responseStore.response?.errors?.jobs"
                >{{ responseStore.response?.errors?.jobs[0] }}</v-alert
              >
              <v-list>
                <v-list-item
                  v-if="localProject.jobs"
                  v-for="job in localProject.jobs"
                  :key="job.id"
                >
                  <v-list-item-title
                    >{{ job.pivot?.order ?? job.order }}
                  </v-list-item-title>
                  <v-list-item-subtitle
                    class="mb-1 text-high-emphasis opacity-100"
                    >{{ job.name }}
                  </v-list-item-subtitle>

                  <v-list-item-subtitle class="text-high-emphasis"
                    >{{ job.base.name }} <v-icon>mdi-arrow-right</v-icon>
                    {{ job.product.name }}</v-list-item-subtitle
                  >

                  <template v-slot:prepend="{}">
                    <v-list-item-action class="flex-column align-end">

                        <v-btn
                          v-if="job.id == finalJob.id"
                          prepend-icon="mdi-delete"

                          color="error"
                          text="Remove"
                          variant="tonal"
                          block
                          @click="removeFinalJob"
                        ></v-btn>
                        <JobDialog :job="job" :isEdit="true"/>
                        <SubdivideJobDialog :job="job" />
                   
                      <v-spacer></v-spacer>
                    </v-list-item-action>
                  </template>
                </v-list-item>
              </v-list>
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Close" variant="plain" @click="dialog = false"></v-btn>

          <v-btn
            v-if="props.isEdit"
            color="primary"
            text="Save"
            variant="tonal"
            @click="save"
          ></v-btn>

          <v-btn
            v-else
            color="primary"
            text="Create"
            variant="tonal"
            @click="create"
          ></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script setup>
import { shallowRef, ref, watch, computed } from "vue";
import { useProjectStore } from "@/stores/project";
import { useJobStore } from "@/stores/job";
import { useResponseStore } from "@/stores/response";
import JobDialog from "./JobDialog.vue";
import SubdivideJobDialog from "./SubdivideJobDialog.vue";

const dialog = shallowRef(false);

const projectStore = useProjectStore();
const jobStore = useJobStore();
const responseStore = useResponseStore();

// const apiBaseUrl = process.env.VUE_APP_API_HOST;

const localProject = ref(null);
const jobs = ref([]);
const newJob = ref(null);

const props = defineProps({
  isEdit: Boolean,
  project: Object,
});

// Watch the dialog's state
watch(dialog, (newVal) => {
  if (newVal) {
    onOpen();
  } else {
    onClose();
  }
});

// Function to initialize
const initializeLocalProject = () => {
  if (props.isEdit && props.project) {
    localProject.value = {
      ...props.project,
    };
  } else {
    localProject.value = {
      name: "",
      description: "",
      jobs: [],
      created_by: null,
    };
  }
};

const finalJob = computed(() => {
  return localProject.value?.jobs?.length
    ? localProject.value.jobs[localProject.value.jobs.length - 1]
    : null;
});

// const emit = defineEmits(["update:modelValue", "close"]);
const refreshJobs = async () => {
  jobs.value = await jobStore.fetchAutocompleteJobs({
    baseId: finalJob?.value?.product_id,
  });
};

const onOpen = async () => {
  initializeLocalProject();
  responseStore.$reset();
  //   tools.resource = "TOOL";
  // tools.value = (await archetypeStore.fetchArchetypes()).data;
  // tools.resource = "MATERIAL";
  await refreshJobs();
  initializeLocalProject();
};

const onClose = () => {};

const save = async () => {
  await projectStore.putProject(localProject.value);
  if (responseStore.response.success) {
    dialog.value = false;
  }
};

const create = async () => {
  await projectStore.postProject(localProject.value);
  if (responseStore.response.success) {
    dialog.value = false;
  }
};

const addJob = async () => {
  responseStore.clearResponse();

  const newOrder = finalJob.value?.pivot?.order
    ? finalJob.value.pivot.order + 1
    : finalJob.value?.order
      ? +finalJob.value?.order + 1
      : 1;

  if (!newJob.value) {
    responseStore.setResponse(false, "Unable to add job", {
      new_job: "Please select a job to add to the project",
    });
  } else {
    newJob.value.order = newOrder;
    localProject.value.jobs.push(newJob.value);
    newJob.value = null;
    refreshJobs();
  }
};

const removeFinalJob = async () => {
  localProject.value.jobs.pop();
  newJob.value = null;
  refreshJobs();
};
</script>
