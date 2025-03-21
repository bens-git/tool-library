<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        :prepend-icon="isEdit ? 'mdi-pencil' : 'mdi-plus'"
        :text="isEdit ? 'Edit Job' : 'Create Job'"
        variant="tonal"
        :color="isEdit ? 'primary' : 'success'"
        block
        v-bind="activatorProps"
      ></v-btn>
    </template>
    <v-card
      :prepend-icon="isEdit ? 'mdi-pencil' : 'mdi-plus'"
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
              :error-messages="responseStore.response?.errors?.['base.id']"
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
              :error-messages="responseStore.response?.errors?.['component.id']"
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
              :error-messages="responseStore.response?.errors?.['product.id']"
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
              v-model="localJob.tool"
              :items="tools"
              label="Tool"
              item-title="name"
              item-value="id"
              :return-object="true"
              @update:search="debouncedAutocompleteToolSearch"
              :error-messages="responseStore.response?.errors?.['tool.id']"
            ></v-autocomplete
          ></v-col>

          <v-col cols="12" md="4" sm="6">
            <v-autocomplete
              density="compact"
              v-model="localJob.projects"
              :items="projects"
              label="Project(s)"
              item-title="name"
              item-value="id"
              :multiple="true"
              :return-object="true"
              @update:search="debouncedAutocompleteProjectSearch"
              :error-messages="responseStore.response?.errors?.projects"
            ></v-autocomplete
          ></v-col>

          <v-col cols="12" md="4" sm="6">
            <SubdivideJobDialog :job="localJob" />
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
</template>
<script setup>
import { shallowRef, ref, watch, onMounted } from "vue";
import { useJobStore } from "@/stores/job";
import { useProjectStore } from "@/stores/project";
import { useArchetypeStore } from "@/stores/archetype";
import { useResponseStore } from "@/stores/response";
import ArchetypeDialog from "./ArchetypeDialog.vue";
import SubdivideJobDialog from "./SubdivideJobDialog.vue";

const dialog = shallowRef(false);

const jobStore = useJobStore();
const archetypeStore = useArchetypeStore();
const projectStore = useProjectStore();
const responseStore = useResponseStore();

// const apiBaseUrl = process.env.VUE_APP_API_HOST;

const localJob = ref(null);
const tools = ref([]);
const materials = ref([]);
const projects = ref([]);
const autocompleteBases = ref([]);
const autocompleteComponents = ref([]);
const autocompleteProducts = ref([]);
const autocompleteTools = ref([]);
const autocompleteProjects = ref([]);

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
      base: props.base,
      product: null,
      tool: null,
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

const refreshProjects = async () => {
  projects.value = await projectStore.fetchAutocompleteProjects();
};

const onOpen = async () => {
  responseStore.$reset();
  //   tools.resource = "TOOL";
  // tools.value = (await archetypeStore.fetchArchetypes()).data;
  // tools.resource = "MATERIAL";
  await refreshMaterials();
  await refreshTools();
  await refreshProjects();
  initializeLocalJob();
  autocompleteBases.value = await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteComponents.value =
    await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteProducts.value =
    await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteTools.value = await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteProjects.value = await projectStore.fetchAutocompleteProjects();
};

const onClose = () => {};

const save = async () => {
  const data = await jobStore.putJob(localJob.value);

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

// Autocomplete tool Search handler
const onAutocompleteToolSearch = async (query) => {
  autocompleteTools.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};

// Autocomplete product Search handler
const onAutocompleteProjectSearch = async (query) => {
  autocompleteProduct.value =
    await projectStore.fetchAutocompleteProjects(query);
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

const debouncedAutocompleteToolSearch = _.debounce(
  onAutocompleteToolSearch,
  300
);

const debouncedAutocompleteProjectSearch = _.debounce(
  onAutocompleteProjectSearch,
  300
);
</script>
