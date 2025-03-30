<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        :color="aim == 'edit' ? 'primary' : 'success'"
        :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
        :text="
          aim == 'edit'
            ? `Edit` + (resource ? ' ' + resource : '') + ' Archetype'
            : `Create` + (resource ? ' ' + resource : '') + ' Archetype'
        "
        variant="tonal"
        v-bind="activatorProps"
        block
        size="small"

      ></v-btn>
    </template>
    <v-card
      :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
      :title="
        aim == 'edit'
          ? `Edit` + (resource ? ' ' + resource : '') + ' Archetype'
          : `Create` + (resource ? ' ' + resource : '') + ' Archetype'
      "
    >
      <v-card-text v-if="localArchetype">
        <v-row dense>
          <v-col cols="12" md="4" sm="6">
            <v-text-field
              density="compact"
              v-model="localArchetype.name"
              :error-messages="responseStore?.response?.errors?.name"
              label="Name"
            />
          </v-col>
          <v-col cols="12" md="4" sm="6">
            <v-textarea
              density="compact"
              v-model="localArchetype.description"
              label="Description"
            ></v-textarea>
          </v-col>

          <v-col cols="12" md="4" sm="6">
            <v-textarea
              density="compact"
              v-model="localArchetype.notes"
              label="Notes"
            ></v-textarea>
          </v-col>

          <v-col cols="12" md="4" sm="6">
            <v-text-field
              density="compact"
              v-model="localArchetype.code"
              label="Code"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="4" sm="6">
            <v-autocomplete
              density="compact"
              v-model="localArchetype.categories"
              :items="autocompleteCategories"
              label="Category"
              item-title="name"
              item-value="id"
              hide-no-data
              hide-details
              return-object
              multiple
              variant="outlined"
              clearable
              @update:search="debouncedAutocompleteCategorySearch"
              :error-messages="responseStore?.response?.errors?.category_ids"
            ></v-autocomplete>
          </v-col>

          <v-col cols="12" md="4" sm="6">
            <v-autocomplete
              density="compact"
              v-model="localArchetype.usages"
              :items="autocompleteUsages"
              label="Usage"
              item-title="name"
              item-value="id"
              hide-no-data
              hide-details
              return-object
              multiple
              variant="outlined"
              clearable
              @update:search-value="debouncedAutocompleteUsageSearch"
            ></v-autocomplete>
          </v-col>
        </v-row>
      </v-card-text>
      <v-divider></v-divider>

      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn text="My Items" variant="plain" @click="myItems()"></v-btn>

        <v-btn text="Close" variant="plain" @click="dialog = false"></v-btn>

        <v-btn
          v-if="aim == 'edit'"
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
import { useArchetypeStore } from "@/stores/archetype";
import { useItemStore } from "@/stores/item";
import { useCategoryStore } from "@/stores/category";
import { useUsageStore } from "@/stores/usage";
import { useResponseStore } from "@/stores/response";
import { useUserStore } from "@/stores/user";
import { useRouter, useRoute } from "vue-router";

const router = useRouter();

const dialog = shallowRef(false);

const archetypeStore = useArchetypeStore();
const itemStore = useItemStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const responseStore = useResponseStore();
const userStore = useUserStore();

const localArchetype = ref(null);
const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);

const props = defineProps({
  aim: String,
  archetype: Object,
  resource: String,
});

// Watch the dialog's state
watch(dialog, (newVal) => {
  if (newVal) {
    onOpen();
  } else {
    onClose();
  }
});

const refreshLocalArchetype = async () => {
  localArchetype.value = await archetypeStore.show(props.archetype.id);
};
// Function to initialize
const initializeLocalArchetype = () => {
  if (props.aim == "edit" && props.archetype) {
    refreshLocalArchetype();
  } else {
    localArchetype.value = {
      name: "",
      description: "",
      category: null,
      usage: null,
      notes: "",
      resource: props.resource ?? "TOOL",
    };
  }
};

const emit = defineEmits(["created", "saved"]);

const onOpen = async () => {
  await categoryStore.index();
  await usageStore.index();
  autocompleteCategories.value = await categoryStore.indexForAutocomplete();
  autocompleteUsages.value = await usageStore.indexForAutocomplete();

  initializeLocalArchetype();
  responseStore.$reset();
};

// Autocomplete category Search handler
const onAutocompleteCategorySearch = async (query) => {
  autocompleteCategories.value =
    await categoryStore.indexForAutocomplete(query);
};
// Autocomplete usage Search handler
const onAutocompleteUsageSearch = async (query) => {
  autocompleteUsages.value = await usageStore.indexForAutocomplete(query);
};

const debouncedAutocompleteCategorySearch = _.debounce(
  onAutocompleteCategorySearch,
  300
);

const debouncedAutocompleteUsageSearch = _.debounce(
  onAutocompleteUsageSearch,
  300
);

const onClose = () => {
  console.log("Dialog closed");
};

const save = async () => {
  const data = await archetypeStore.update(localArchetype.value);

  if (data.success) {
    emit("saved");
    dialog.value = false;
  }
};

const create = async () => {
  const data = await archetypeStore.store(localArchetype.value);
  if (data?.success) {
    emit("created");
    dialog.value = false;
  }
};

const myItems = () => {
  itemStore.itemListFilters.archetype = localArchetype.value;
  itemStore.itemListFilters.userId = userStore.user?.id;
  itemStore.index()
  router.push({ path: "/item-list" });
  dialog.value=false
};
</script>
