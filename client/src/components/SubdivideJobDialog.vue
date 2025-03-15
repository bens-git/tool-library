<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        prepend-icon="mdi-table-split-cell"
        text="Subdivide Job"
        variant="tonal"
        color="primary"
        block
        v-bind="activatorProps"
      ></v-btn>
    </template>
    <v-card
      prepend-icon="mdi-table-split-cell"
      title="Subdivide Job"
      :subtitle="localJob?.name"
    >
      <v-card-text v-if="localJob">
        <v-row>
          <v-col>
            <v-row
              ><v-col>
                <v-text-field
                  density="compact"
                  v-model="newJob1.name"
                  label="New Job Name #1"
                  :error-messages="
                    responseStore.response?.errors?.new_job_name_1
                  "
                ></v-text-field> </v-col></v-row
            ><v-row
              ><v-col><DisplayJob :job="job" /></v-col
            ></v-row>
          </v-col>
          <v-col>
            <v-autocomplete
              density="compact"
              v-model="newIntermediateProduct"
              :items="materials"
              label="New Intermediate Product"
              item-title="name"
              item-value="id"
              :return-object="true"
              @update:search="debouncedAutocompleteProductSearch"
              :error-messages="responseStore.response?.errors?.product_id"
            ></v-autocomplete>

            <ArchetypeDialog
              aim="create"
              resource="MATERIAL"
              @created="refreshMaterials()"
            />
          </v-col>
          <v-col>
            <v-row
              ><v-col>
                <v-text-field
                  density="compact"
                  v-model="newJob2.name"
                  label="New Job Name #2"
                  :error-messages="
                    responseStore.response?.errors?.new_job_name_2
                  "
                ></v-text-field> </v-col></v-row
            ><v-row
              ><v-col><DisplayJob :job="job" /></v-col
            ></v-row>
          </v-col>
        </v-row>
      </v-card-text>
      <v-divider></v-divider>

      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn text="Close" variant="plain" @click="dialog = false"></v-btn>

        <v-btn
          color="success"
          text="Subdivide"
          variant="tonal"
          @click="subdivide"
        ></v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
<script setup>
import { shallowRef, ref, watch } from "vue";
import { useJobStore } from "@/stores/job";
import { useProjectStore } from "@/stores/project";
import { useArchetypeStore } from "@/stores/archetype";
import { useResponseStore } from "@/stores/response";
import DisplayJob from "./DisplayJob.vue";
import ArchetypeDialog from "./ArchetypeDialog.vue";

const dialog = shallowRef(false);

const jobStore = useJobStore();
const archetypeStore = useArchetypeStore();
const projectStore = useProjectStore();
const responseStore = useResponseStore();

const localJob = ref(null);
const newJob1 = ref(null);
const newJob2 = ref(null);
const tools = ref([]);
const materials = ref([]);
const projects = ref([]);
const autocompleteBases = ref([]);
const autocompleteComponents = ref([]);
const autocompleteProducts = ref([]);
const autocompleteTools = ref([]);
const newJobName1 = ref("");
const newJobName2 = ref("");
const newIntermediateProduct = ref(null);

const props = defineProps({
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
  if (props.job) {
    localJob.value = {
      ...props.job,
    };

    newJob1.value = {
      ...props.job,
    };

    newJob2.value = {
      ...props.job,
    };

    newJob1.value.name = localJob.value.name + " A";
    newJob2.value.name = localJob.value.name + " B";
  }
};

// const emit = defineEmits(["update:modelValue", "close"]);
const refreshMaterials = async () => {
  materials.value = await archetypeStore.fetchAutocompleteArchetypes(
    null,
    "MATERIAL"
  );

  //remove original base
  materials.value = materials.value.filter(
    (material) =>
      material.id !== localJob?.value?.base.id &&
      material.id !== localJob?.value?.product?.id &&
      material.id !== localJob?.value?.component?.id
  );
};

const refreshTools = async () => {
  tools.value = await archetypeStore.fetchAutocompleteArchetypes(null, "TOOL");
};

const onOpen = async () => {
  responseStore.$reset();

  initializeLocalJob();

  await refreshMaterials();
  await refreshTools();

  autocompleteBases.value = await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteComponents.value =
    await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteProducts.value =
    await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteTools.value = await archetypeStore.fetchAutocompleteArchetypes();
};

const onClose = () => {};

const subdivide = async () => {
  const data = await jobStore.subdivideJob({
    originalJob: localJob.value,
    newJob1: newJob1.value,
    newJob2: newJob2.value,
    newIntermediateProduct: newIntermediateProduct.value
  });

  if (data?.success) {
    dialog.value = false;
  }
};

// Autocomplete product Search handler
const onAutocompleteProductSearch = async (query) => {
  autocompleteProducts.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);

  //remove original base
  autocompleteProducts.value = autocompleteProducts.value.filter(
    (product) => product.id !== localJob.value.base.id
  );
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
