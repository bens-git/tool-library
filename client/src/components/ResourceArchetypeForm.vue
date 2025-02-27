<template>
  <v-card>
    <v-card-title>{{ isEdit ? "Edit ResourceArchetype" : "Create New ResourceArchetype" }}</v-card-title>
    <v-card-text>
      <!-- Form Inputs for Creating/Updating a ResourceArchetype -->
      <v-text-field
        density="compact"
        v-model="localResourceArchetype.name"
        :error-messages="responseStore?.response?.errors?.name"
        label="Name"
      />
      <v-textarea
        density="compact"
        v-model="localResourceArchetype.description"
        label="Description"
      ></v-textarea>
      <v-textarea
        density="compact"
        v-model="localResourceArchetype.notes"
        label="Notes"
      ></v-textarea>
      <v-text-field
        density="compact"
        v-model="localResourceArchetype.code"
        label="Code"
      ></v-text-field>

      <v-autocomplete
        density="compact"
        :multiple="true"
        v-model="localResourceArchetype.category_ids"
        :items="categoryStore.categories"
        label="Category"
        item-title="name"
        item-value="id"
        :error-messages="responseStore?.response?.errors?.category_ids"
      ></v-autocomplete>

      <v-autocomplete
        density="compact"
        :multiple="true"
        v-model="localResourceArchetype.usage_ids"
        :items="usageStore.usages"
        label="Usage"
        item-title="name"
        item-value="id"
        :error-messages="responseStore?.response?.errors?.usage_ids"
      ></v-autocomplete>

      

    </v-card-text>
    <v-card-actions>
      <v-btn color="primary" @click="saveResourceArchetype">{{
        isEdit ? "Update" : "Create"
      }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useResponseStore } from "@/stores/response";
import { useResourceArchetypeStore } from "@/stores/resource_archetype";
import { useCategoryStore } from "@/stores/category";
import { useUsageStore } from "@/stores/usage";

const resourcearchetypeStore = useResourceArchetypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const responseStore = useResponseStore();



// Define props
const props = defineProps({
  showResourceArchetypeModal: Boolean,
  isEdit: Boolean,
  resourcearchetype: Object,
});

const emit = defineEmits([
  "update-resourcearchetype",
  "create-resourcearchetype",
  "update:showResourceArchetypeModal",
  "close-modal",
]);

const localResourceArchetype = ref({});

// Function to initialize localResourceArchetype
const initializeLocalResourceArchetype = () => {
  if (props.isEdit && props.resourcearchetype) {
    localResourceArchetype.value = {
      ...props.resourcearchetype,
      category_ids: props.resourcearchetype.category_ids || [], // Default to empty array if undefined
      usage_ids: props.resourcearchetype.usage_ids || [], // Default to empty array if undefined
    };
  } else {
    localResourceArchetype.value = {
      name: "",
      description: "",
      notes: "",
      code: "",
      category_ids: [], // Default to empty array for new resourcearchetype
      usage_ids: [], // Default to empty array for new resourcearchetype
    };
  }
};

// Initialize localResourceArchetype on component mount or when the resourcearchetype changes
onMounted(() => {
  initializeLocalResourceArchetype();
  categoryStore.fetchCategories();
  usageStore.fetchUsages();
});

watch(
  () => props.resourcearchetype,
  () => {
    initializeLocalResourceArchetype();
  },
  { deep: true }
);

const saveResourceArchetype = async () => {
 
  if (props.isEdit) {
    await resourcearchetypeStore.saveMyResourceArchetype(localResourceArchetype.value);
  } else {
    const newResourceArchetype = await resourcearchetypeStore.postResourceArchetype(localResourceArchetype.value);
    if(newResourceArchetype && newResourceArchetype.id){
      localResourceArchetype.value=newResourceArchetype
    }
  }


  if (responseStore.response.success) {
    closeModal();
    resourcearchetypeStore.fetchMyResourceArchetypes();
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
