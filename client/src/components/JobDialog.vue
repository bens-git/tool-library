<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" max-width="600px" @open="onOpen">
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
                v-model="localJob.name"
                label="Name"
                :error-messages="responseStore.errors?.name"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4" sm="6">
              <v-textarea
                density="compact"
                v-model="localJob.description"
                label="Description"
                placeholder=""
                :error-messages="responseStore.errors?.description"
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
                :error-messages="responseStore.errors?.material"
              ></v-autocomplete
            ></v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localJob.product_id"
                :items="materials"
                label="Product"
                clearable
                item-title="name"
                item-value="id"
                :error-messages="responseStore.errors?.product"
              ></v-autocomplete
            ></v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localJob.type_id"
                :items="toolTypes"
                label="Tool Type"
                clearable
                item-title="name"
                item-value="id"
                :error-messages="responseStore.errors?.type"
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
import { useTypeStore } from "@/stores/type";
import { useResponseStore } from "@/stores/response";

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
const typeStore = useTypeStore();
const responseStore = useResponseStore();

// const apiBaseUrl = process.env.VUE_APP_API_HOST;

const localJob = ref(null);
const toolTypes = ref([]);
const materials = ref([]);
const newImage=ref(null);

// onMounted(async () => {
//   console.log("mount");
//   tools.resource = "TOOL";
//   tools.value = (await typeStore.fetchTypes()).data;
//   tools.resource = "MATERIAL";
//   materials.value = (await typeStore.fetchTypes()).data;
//   initializeLocalJob();
// });

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
  console.log("init");
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

const onOpen = () => {
  console.log('test')
  initializeLocalJob();
};

const onClose = () => {
  console.log("Dialog closed");
};

const save = () => {
  console.log("save");
  dialog = false;
};

const create = () => {
  console.log("create");
  dialog = false;
};

const handleFileChange = (event) => {
  const file = event.target.file;
  if (file.length) {
    newImage.value = file;
  }
};



</script>
