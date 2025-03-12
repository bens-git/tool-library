<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          class="text-none font-weight-regular"
          :prepend-icon="isEdit ? 'mdi-edit' : 'mdi-plus'"
          :text="isEdit ? 'Edit Job' : 'Create Job'"
          variant="tonal"
          :color="isEdit ? 'primary' : 'success'"
          block
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
              <!-- create material dialog -->
              <ArchetypeDialog
                aim="create"
                resource="MATERIAL"
                @created="refreshMaterials()"
              />
            </v-col>
            <v-col cols="12" md="4" sm="6">
          
              <v-autocomplete
                density="compact"
                v-model="localJob.base"
                :items="materials"
                label="Base"
                item-title="name"
                item-value="id"
                :return-object="true"
                @update:search="debouncedAutocompleteBaseSearch"
                :error-messages="responseStore.response?.errors?.base"
                :disabled="Boolean(base)"
              ></v-autocomplete>
            </v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localJob.component"
                :items="materials"
                label="Component"
                clearable
                item-title="name"
                item-value="id"
                :return-object="true"
                @update:search="debouncedAutocompleteComponentSearch"
                :error-messages="responseStore.response?.errors?.component_id"
              ></v-autocomplete>
            </v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localJob.product"
                :items="materials"
                label="Product"
                item-title="name"
                item-value="id"
                :return-object="true"
                @update:search="debouncedAutocompleteProductSearch"
                :error-messages="responseStore.response?.errors?.product"
              ></v-autocomplete>
              <ArchetypeDialog
                aim="edit"
                resource="MATERIAL"
                :archetype="localJob.product"
                @modified="refreshMaterials()"
              />

            </v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localJob.archetype_id"
                :items="tools"
                label="Tool"
                clearable
                item-title="name"
                item-value="id"
                :error-messages="responseStore.response?.errors?.archetype_id"
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
import { useArchetypeStore } from "@/stores/archetype";
import { useResponseStore } from "@/stores/response";
import ArchetypeDialog from "./ArchetypeDialog.vue";

const dialog = shallowRef(false);

const jobStore = useJobStore();
const archetypeStore = useArchetypeStore();
const responseStore = useResponseStore();

// const apiBaseUrl = process.env.VUE_APP_API_HOST;

const localJob = ref(null);
const tools = ref([]);
const materials = ref([]);
const autocompleteBases = ref([]);
const autocompleteComponents = ref([]);
const autocompleteProducts = ref([]);

const props = defineProps({
  isEdit: Boolean,
  job: Object,
  base: Object,
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
      material_ids: [],
      base_id: props.base?.id,
      base: props.base,
      product_id: null,
      tool_id: null,
      created_by: null,
      image_path: null,
    };
  }
};

// const emit = defineEmits(["update:modelValue", "close"]);
const refreshMaterials = async () => {
  materials.value = await archetypeStore.fetchAutocompleteArchetypes(
    null,
    "MATERIAL"
  );
};

const refreshTools = async () => {
  tools.value = await archetypeStore.fetchAutocompleteArchetypes(null, "TOOL");
};

const onOpen = async () => {
  responseStore.$reset();
  //   tools.resource = "TOOL";
  // tools.value = (await archetypeStore.fetchArchetypes()).data;
  // tools.resource = "MATERIAL";
  await refreshMaterials();
  await refreshTools();
  initializeLocalJob();
  autocompleteBases.value = await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteComponents.value =
    await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteProducts.value =
    await archetypeStore.fetchAutocompleteArchetypes();
};

const onClose = () => {};

const save = async () => {
  const newJob = await jobStore.putJob(localJob.value);

  if (responseStore.response.success) {
    dialog.value = false;
  }
};

const create = async () => {
  const newJob = await jobStore.postJob(localJob.value);

  if (responseStore.response.success) {
    dialog.value = false;
  }
};

// Autocomplete product Search handler
const onAutocompleteProductSearch = async (query) => {
  autocompleteProducts.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};

// Autocomplete component Search handler
const onAutocompleteComponentSearch = async (query) => {
  autocompleteComponents.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};

// Autocomplete component Search handler
const onAutocompleteBaseSearch = async (query) => {
  autocompleteBases.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};

// Debounced search function
const debouncedAutocompleteProductSearch = _.debounce(
  onAutocompleteProductSearch,
  300
);
const debouncedAutocompleteComponentSearch = _.debounce(
  onAutocompleteComponentSearch,
  300
);

const debouncedAutocompleteBaseSearch = _.debounce(
  onAutocompleteBaseSearch,
  300
);
</script>
