<template>
  <v-card>
    <v-card-title>{{ isEdit ? "Edit Archetype" : "Create New Usage" }}</v-card-title>
    <v-card-text>
      <v-text-field v-model="localUsage.name" :error-messages="responseStore?.response?.errors[0]?.name"
        label="Name"></v-text-field>
    </v-card-text>
    <v-card-actions>
      <v-btn color="primary" @click="saveUsage">{{
        isEdit ? "Update" : "Create"
        }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch } from "vue";
import { useResponseStore } from "@/stores/response";
import { useUsageStore } from "@/stores/usage";

const usageStore = useUsageStore();
const responseStore = useResponseStore();

// Define props
const props = defineProps({
  showUsageModal: Boolean,
  isEdit: Boolean,
  usage: Object,
});

const emit = defineEmits([
  "update-usage",
  "create-usage",
  "update:showUsageModal",
  "close-modal",
]);

watch(
  () => props.usage,
  (newUsage) => {
    localUsage.value = { ...newUsage };
  },
  { deep: true },
);

const localUsage = ref({ ...props.usage });

const saveUsage = async () => {
  const responseStore = useResponseStore();
  const formData = new FormData();

  // Append all properties of local archetype except images handling
  for (const [key, value] of Object.entries(localUsage.value)) {
    // Skip keys with null values or replace them with actual null
    if (value === null || value === undefined) {
      formData.append(key, "");
    } else {
      formData.append(key, value);
    }
  }

  try {
    if (props.isEdit) {
      await usageStore.updateUsage(formData);
    } else {
      await usageStore.createUsage(formData);
    }
    if (responseStore.response.success) {
      closeModal();
      usageStore.fetchUserUsages();
    } else {
      console.log("Error:", responseStore.response.message);
    }
  } catch (error) {
    console.error("Unexpected Error:", error);
    responseStore.setResponse(false, error.response.data.message, [
      error.response.data.errors,
    ]);
  }
};

// Close modal logic
const closeModal = () => {
  emit("close-modal");
};
</script>
