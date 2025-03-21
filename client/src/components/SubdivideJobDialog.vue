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
                    responseStore.response?.errors?.new_job_1_name
                  "
                ></v-text-field> </v-col
            ></v-row>
            <v-row
              ><v-col>
                <v-autocomplete
                  density="compact"
                  v-model="newJob1.tool"
                  :items="tools"
                  label="Job 1 Tool"
                  item-title="name"
                  item-value="id"
                  :return-object="true"
                  @update:search="debouncedAutocompleteTool1Search"
                  :error-messages="responseStore.response?.errors?.job_1_tool"
                ></v-autocomplete> </v-col
            ></v-row>
            <v-row
              ><v-col><DisplayJob :job="newJob1" /></v-col
            ></v-row>
          </v-col>
          <v-col>
            <v-autocomplete
              density="compact"
              v-model="newIntermediateProduct"
              :items="autocompleteIntermediateProducts"
              label="New Intermediate Product"
              item-title="name"
              item-value="id"
              :return-object="true"
              @update:search="debouncedAutocompleteIntermediateProductSearch"
              :error-messages="responseStore.response?.errors?.newIntermediateProduct"
            ></v-autocomplete>

            <ArchetypeDialog
              aim="create"
              resource="MATERIAL"
              @created="debouncedAutocompleteIntermediateProductSearch()"
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
                    responseStore.response?.errors?.new_job_2_name
                  "
                ></v-text-field> </v-col
            ></v-row>
            <v-row
              ><v-col>
                <v-autocomplete
                  density="compact"
                  v-model="newJob2.tool"
                  :items="tools"
                  label="Job 2 Tool"
                  item-title="name"
                  item-value="id"
                  :return-object="true"
                  @update:search="debouncedAutocompleteTool2Search"
                  :error-messages="responseStore.response?.errors?.job_2_tool"
                ></v-autocomplete> </v-col
            ></v-row>
            <v-row
              ><v-col><DisplayJob :job="newJob2" /></v-col
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
const autocompleteTools1 = ref([]);
const autocompleteTools2 = ref([]);
const autocompleteIntermediateProducts = ref([]);
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
    newJob1.value.product = newIntermediateProduct.value;
    newJob1.value.tool = localJob.value.tool;
    newJob1.value.product = newIntermediateProduct.value;
    newJob2.value.base = newIntermediateProduct.value;
    newJob2.value.tool = null;
  }
};

watch(
  () => newIntermediateProduct.value,
  (newVal) => {
    if (newVal) {
      console.log('new int prod')
      newJob1.value.product = newIntermediateProduct.value;
      newJob2.value.base = newIntermediateProduct.value;
    }
  }
);

const onOpen = async () => {
  responseStore.$reset();

  initializeLocalJob();

  autocompleteIntermediateProducts.value =
    await archetypeStore.fetchAutocompleteArchetypes(null, "MATERIAL");
  autocompleteTools1.value = await archetypeStore.fetchAutocompleteArchetypes(
    null,
    "TOOL"
  );
  autocompleteTools2.value = await archetypeStore.fetchAutocompleteArchetypes(
    null,
    "TOOL"
  );
};

const onClose = () => {};

const subdivide = async () => {
  const data = await jobStore.subdivideJob({
    originalJob: localJob.value,
    newJob1: newJob1.value,
    newJob2: newJob2.value,
    newIntermediateProduct: newIntermediateProduct.value,
  });

  if (data?.success) {
    dialog.value = false;
  }
};

// Autocomplete product Search handler
const onAutocompleteIntermediateProductSearch = async (query) => {
  autocompleteIntermediateProducts.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};

// Autocomplete tool Search handler
const onAutocompleteTools1Search = async (query) => {
  autocompleteTools1.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};

// Autocomplete tool Search handler
const onAutocompleteTools2Search = async (query) => {
  autocompleteTools2.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};

// Debounced search function
const debouncedAutocompleteIntermediateProductSearch = _.debounce(
  onAutocompleteIntermediateProductSearch,
  300
);

const debouncedAutocompleteTool1Search = _.debounce(
  onAutocompleteTools1Search,
  300
);

const debouncedAutocompleteTool2Search = _.debounce(
  onAutocompleteTools2Search,
  300
);
</script>
