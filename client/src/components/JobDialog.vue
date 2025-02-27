<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          class="text-none font-weight-regular"
          :prepend-icon="isEdit ? 'mdi-edit' : 'mdi-plus'"
          :text="isEdit ? 'Edit Job' : 'Create Job'"
          variant="tonal"
          v-bind="activatorProps"
        ></v-btn>
      </template>
      <v-card
        :prepend-icon="isEdit ? 'mdi-edit' : 'mdi-plus'"
        :title="isEdit ? 'Edit Job' : 'Create Job'"
      >
        <v-card-text v-if="localJob">
          <v-row dense>
            <v-col cols="12" md="4" sm="6">
              <v-text-field
                density="compact"
                v-model="localJob.name"
                label="Name"
                :error-messages="responseStore.response?.errors?.name"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4" sm="6">
              <v-textarea
                density="compact"
                v-model="localJob.description"
                label="Description"
                placeholder=""
                :error-messages="responseStore.response?.errors?.description"
              ></v-textarea>
            </v-col>
            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localJob.material_id"
                :items="materials"
                label="Material"
                clearable
                item-title="name"
                item-value="id"
                :error-messages="responseStore.response?.errors?.material_id"
              ></v-autocomplete>

              <!-- create material dialog -->
              <ResourceArchetypeDialog :isEdit="false" resource="MATERIAL" />
            </v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localJob.product_id"
                :items="materials"
                label="Product"
                clearable
                item-title="name"
                item-value="id"
                :error-messages="responseStore.response?.errors?.product_id"
              ></v-autocomplete
            ></v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localJob.resource_archetype_id"
                :items="toolResourceArchetypes"
                label="Tool ResourceArchetype"
                clearable
                item-title="name"
                item-value="id"
                :error-messages="responseStore.response?.errors?.resource_archetype_id"
              ></v-autocomplete
            ></v-col>

            <v-col v-if="localJob.image_path" cols="12" md="4" sm="6">
              <v-row>
                <v-col cols="4">
                  <v-img
                    :src="fullImageUrl(localJob.image_path)"
                    class="mb-2"
                    aspect-ratio="1"
                  >
                    <v-btn icon color="red" @click="removeImage" class="mt-2">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </v-img>
                </v-col>
              </v-row>
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-file-input
            density="compact"
            @change="handleFileChange"
            label="Upload Image"
            prepend-icon="mdi-camera"
            accept="image/*"
          ></v-file-input>

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
import { shallowRef, ref, watch, onMounted } from "vue";
import { useJobStore } from "@/stores/job";
import { useResourceArchetypeStore } from "@/stores/resource_archetype";
import { useResponseStore } from "@/stores/response";
import ResourceArchetypeDialog from "./ResourceArchetypeDialog.vue";

// Create a local state variable to sync with modelValue
//const localModelValue = ref(props.modelValue);

// // Watch for changes in modelValue from the parent
// watch(
//   () => props.modelValue,
//   (newValue) => {
//     localModelValue.value = newValue;
//   }
// );
const dialog = shallowRef(false);

const jobStore = useJobStore();
const resourcearchetypeStore = useResourceArchetypeStore();
const responseStore = useResponseStore();

// const apiBaseUrl = process.env.VUE_APP_API_HOST;

const localJob = ref(null);
const toolResourceArchetypes = ref([]);
const materials = ref([]);
const newImages = ref(null);

const props = defineProps({
  isEdit: Boolean,
  job: Object,
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
const initializeLocalJob = () => {
  if (props.isEdit && props.job) {
    localJob.value = {
      ...props.job,
    };
  } else {
    localJob.value = {
      name: "",
      description: "",
      material_id: null,
      product_id: null,
      tool_id: null,
      created_by: null,
      image_path: null,
    };
  }
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initializeLocalJob();
  responseStore.$reset();
  //   tools.resource = "TOOL";
  // tools.value = (await resourcearchetypeStore.fetchResourceArchetypes()).data;
  // tools.resource = "MATERIAL";
  materials.value = (await resourcearchetypeStore.fetchMaterialResources()).data;
  initializeLocalJob();
};

const onClose = () => {};

const save = () => {
  dialog.value = false;
};

const create = async () => {
  const newJob = await jobStore.postJob(localJob.value);
  if (newJob && newJob.id) {
    localJob.value = newJob;
  }

  //add new images
  if (localJob.value.id) {
    for (const image of newImages.value) {
      await jobStore.postImage(localJob.value.id, image);
    }
  }

  if (responseStore.response.success) {
    dialog.value = false;
  }
};

const handleFileChange = (event) => {
  const files = event.target.files;
  if (files.length) {
    newImages.value.push(...Array.from(files));
  }
};
</script>
