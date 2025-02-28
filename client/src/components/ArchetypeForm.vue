<template>
  <v-card>
    <v-card-title>{{ isEdit ? "Edit Archetype" : "Create New Archetype" }}</v-card-title>
    <v-card-text>
      <!-- Form Inputs for Creating/Updating an archetype -->
      <v-text-field
        density="compact"
        v-model="localArchetype.name"
        :error-messages="responseStore?.response?.errors?.name"
        label="Name"
      />
      <v-textarea
        density="compact"
        v-model="localArchetype.description"
        label="Description"
      ></v-textarea>
      <v-textarea
        density="compact"
        v-model="localArchetype.notes"
        label="Notes"
      ></v-textarea>
      <v-text-field
        density="compact"
        v-model="localArchetype.code"
        label="Code"
      ></v-text-field>

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

      

    </v-card-text>
    <v-card-actions>
      <v-btn color="primary" @click="saveArchetype">{{
        isEdit ? "Update" : "Create"
      }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useResponseStore } from "@/stores/response";
import { useArchetypeStore } from "@/stores/archetype";
import { useCategoryStore } from "@/stores/category";
import { useUsageStore } from "@/stores/usage";

const archetypeStore = useArchetypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const responseStore = useResponseStore();



// Define props
const props = defineProps({
  showArchetypeModal: Boolean,
  isEdit: Boolean,
  archetype: Object,
});

const emit = defineEmits([
  "update-archetype",
  "create-archetype",
  "update:showArchetypeModal",
  "close-modal",
]);

const localArchetype = ref({});

// Function to initialize localArchetype
const initializeLocalArchetype = () => {
  if (props.isEdit && props.archetype) {
    localArchetype.value = {
      ...props.archetype,
      category_ids: props.archetype.category_ids || [], // Default to empty array if undefined
      usage_ids: props.archetype.usage_ids || [], // Default to empty array if undefined
    };
  } else {
    localArchetype.value = {
      name: "",
      description: "",
      notes: "",
      code: "",
      category_ids: [], // Default to empty array for new archetype
      usage_ids: [], // Default to empty array for new archetype
    };
  }
};

// Initialize localArchetype on component mount or when the archetype changes
onMounted(() => {
  initializeLocalArchetype();
  categoryStore.fetchCategories();
  usageStore.fetchUsages();
});

watch(
  () => props.archetype,
  () => {
    initializeLocalArchetype();
  },
  { deep: true }
);

const saveArchetype = async () => {
 
  if (props.isEdit) {
    await archetypeStore.saveMyArchetype(localArchetype.value);
  } else {
    const newArchetype = await archetypeStore.postArchetype(localArchetype.value);
    if(newArchetype && newArchetype.id){
      localArchetype.value=newArchetype
    }
  }


  if (responseStore.response.success) {
    closeModal();
    archetypeStore.fetchMyArchetypes();
  }
};







// Close modal logic
const closeModal = () => {
  emit("close-modal");
};
</script>

<style scoped>
/* Add any styles specific to this component here */
</style>
