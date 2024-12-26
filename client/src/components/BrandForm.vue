<template>
  <v-card>
    <v-card-title>{{
      isEdit ? "Edit Brand" : "Create New Brand"
    }}</v-card-title>
    <v-card-text>
      <v-text-field
        v-model="localBrand.name"
        :error-messages="responseStore?.response?.errors?.name"
        label="Name"
      ></v-text-field>
    </v-card-text>
    <v-card-actions>
      <v-btn color="primary" @click="saveBrand">{{
        isEdit ? "Update" : "Create"
      }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch } from "vue";
import { useResponseStore } from "@/stores/response";
import { useBrandStore } from "@/stores/brand";

const brandStore = useBrandStore();
const responseStore = useResponseStore();

// Define props
const props = defineProps({
  showBrandModal: Boolean,
  isEdit: Boolean,
  brand: Object,
});

const emit = defineEmits([
  "update-brand",
  "create-brand",
  "update:showBrandModal",
  "close-modal",
]);

watch(
  () => props.brand,
  (newBrand) => {
    localBrand.value = { ...newBrand };
  },
  { deep: true }
);

const localBrand = ref({ ...props.brand });

const saveBrand = async () => {
  if (props.isEdit) {
    await brandStore.updateMyBrand(localBrand.value);
  } else {
    const newBrand = await brandStore.createBrand(localBrand.value);
    if (newBrand && newBrand.id) {
      localBrand.value = newBrand;
    }
  }

  if (responseStore.response.success) {
    closeModal();
    brandStore.fetchMyBrands();
  }
};

// Close modal logic
const closeModal = () => {
  emit("close-modal");
};
</script>
