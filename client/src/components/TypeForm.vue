<template>
  <v-card>
    <v-card-title>{{ isEdit ? "Edit Type" : "Create New Type" }}</v-card-title>
    <v-card-text>
      <!-- Form Inputs for Creating/Updating a Type -->
      <v-text-field
        density="compact"
        v-model="localType.name"
        :error-messages="responseStore?.response?.errors?.name"
        label="Name"
      />
      <v-textarea
        density="compact"
        v-model="localType.description"
        label="Description"
      ></v-textarea>
      <v-textarea
        density="compact"
        v-model="localType.notes"
        label="Notes"
      ></v-textarea>
      <v-text-field
        density="compact"
        v-model="localType.code"
        label="Code"
      ></v-text-field>

      <v-autocomplete
        density="compact"
        :multiple="true"
        v-model="localType.category_ids"
        :items="categoryStore.categories"
        label="Category"
        item-title="name"
        item-value="id"
        :error-messages="responseStore?.response?.errors?.category_ids"
      ></v-autocomplete>

      <v-autocomplete
        density="compact"
        :multiple="true"
        v-model="localType.usage_ids"
        :items="usageStore.usages"
        label="Usage"
        item-title="name"
        item-value="id"
        :error-messages="responseStore?.response?.errors?.usage_ids"
      ></v-autocomplete>

      

    </v-card-text>
    <v-card-actions>
      <v-btn color="primary" @click="saveType">{{
        isEdit ? "Update" : "Create"
      }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useResponseStore } from "@/stores/response";
import { useTypeStore } from "@/stores/type";
import { useCategoryStore } from "@/stores/category";
import { useUsageStore } from "@/stores/usage";

const typeStore = useTypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const responseStore = useResponseStore();



// Define props
const props = defineProps({
  showTypeModal: Boolean,
  isEdit: Boolean,
  type: Object,
});

const emit = defineEmits([
  "update-type",
  "create-type",
  "update:showTypeModal",
  "close-modal",
]);

const localType = ref({});

// Function to initialize localType
const initializeLocalType = () => {
  if (props.isEdit && props.type) {
    localType.value = {
      ...props.type,
      category_ids: props.type.category_ids || [], // Default to empty array if undefined
      usage_ids: props.type.usage_ids || [], // Default to empty array if undefined
    };
  } else {
    localType.value = {
      name: "",
      description: "",
      notes: "",
      code: "",
      category_ids: [], // Default to empty array for new type
      usage_ids: [], // Default to empty array for new type
    };
  }
};

// Initialize localType on component mount or when the type changes
onMounted(() => {
  initializeLocalType();
  categoryStore.fetchCategories();
  usageStore.fetchUsages();
});

watch(
  () => props.type,
  () => {
    initializeLocalType();
  },
  { deep: true }
);

const saveType = async () => {
 
  if (props.isEdit) {
    await typeStore.saveMyType(localType.value);
  } else {
    const newType = await typeStore.postType(localType.value);
    if(newType && newType.id){
      localType.value=newType
    }
  }


  if (responseStore.response.success) {
    closeModal();
    typeStore.fetchMyTypes();
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
