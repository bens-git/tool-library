<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          :color="aim == 'edit' ? 'primary' : 'success'"
          class="text-none font-weight-regular"
          :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
          :text="aim == 'edit' ? `Edit Archetype` : `Create Archetype`"
          variant="tonal"
          v-bind="activatorProps"
          block
        ></v-btn>
      </template>
      <v-card
        :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
        :title="aim == 'edit' ? `Edit Archetype` : `Create Archetype`"
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
                :multiple="true"
                v-model="localArchetype.category_ids"
                :items="categoryStore.categories"
                label="Category"
                item-title="name"
                item-value="id"
                :error-messages="responseStore?.response?.errors?.category_ids"
              ></v-autocomplete>
            </v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                :multiple="true"
                v-model="localArchetype.usage_ids"
                :items="usageStore.usages"
                label="Usage"
                item-title="name"
                item-value="id"
                :error-messages="responseStore?.response?.errors?.usage_ids"
              ></v-autocomplete>
            </v-col>

            <v-col cols="12" md="4" sm="6">
              <v-autocomplete
                density="compact"
                v-model="localArchetype.resource"
                :items="['TOOL', 'MATERIAL']"
                label="Resource"
                :error-messages="responseStore?.response?.errors?.resource"
              ></v-autocomplete>
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

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
  </div>
</template>
<script setup>
import { shallowRef, ref, watch, onMounted } from "vue";
import { useArchetypeStore } from "@/stores/archetype";
import { useCategoryStore } from "@/stores/category";
import { useUsageStore } from "@/stores/usage";
import { useResponseStore } from "@/stores/response";

const dialog = shallowRef(false);

const archetypeStore = useArchetypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const responseStore = useResponseStore();

const localArchetype = ref(null);

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

// Function to initialize
const initializeLocalArchetype = () => {
  console.log("init");
  if (props.aim == "edit" && props.archetype) {
    localArchetype.value = {
      ...props.archetype,
    };
  } else {
    localArchetype.value = {
      name: "",
      description: "",
      category: null,
      usage: null,
      notes: "",
      resource: "TOOL",
    };
  }
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  await categoryStore.fetchCategories();
  await usageStore.fetchUsages();
  initializeLocalArchetype();
  responseStore.$reset();
};

const onClose = () => {
  console.log("Dialog closed");
};

const save = async () => {
  const data = await archetypeStore.saveMyArchetype(localArchetype.value);

  if (data.success) {
    dialog.value = false;
  }
};

const create = async () => {
  const data = await archetypeStore.postArchetype(localArchetype.value);
  if (data.success) {
    dialog.value = false;
  }
};
</script>
