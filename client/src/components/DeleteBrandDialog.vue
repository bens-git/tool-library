<template>
    <div class="pl-4 text-center">
      <v-dialog v-model="dialog" @open="onOpen">
        <template v-slot:activator="{ props: activatorProps }">
          <v-btn
            color="error"
            class="text-none font-weight-regular"
            prepend-icon="mdi-delete"
            text="Delete Brand"
            variant="tonal"
            v-bind="activatorProps"
            block
          ></v-btn>
        </template>
        <v-card
          v-if="localBrand"
          prepend-icon="mdi-delete"
          title="Delete Brand"
          :subtitle="localBrand.name"
        >
          <v-card-text> Are you sure you want to delete this brand? </v-card-text>
          <v-divider></v-divider>
  
          <v-card-actions>
            <v-spacer></v-spacer>
  
            <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>
  
            <v-btn
              color="error"
              text="Delete"
              variant="tonal"
              @click="deleteBrand"
            ></v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </template>
  <script setup>
  import { shallowRef, ref, watch } from "vue";
  import { useBrandStore } from "@/stores/brand";
  import { useResponseStore } from "@/stores/response";
  
  const dialog = shallowRef(false);
  
  const brandStore = useBrandStore();
  const responseStore = useResponseStore();
  
  const localBrand = ref(null);
  
  const props = defineProps({
    brand: Object,
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
  const initialize = () => {
    localBrand.value = {
      ...props.brand,
    };
  };
  
  // const emit = defineEmits(["update:modelValue", "close"]);
  
  const onOpen = async () => {
    initialize();
    responseStore.$reset();
  };
  
  const onClose = () => {};
  
  const deleteBrand = () => {
    console.log("delete");
    brandStore.deleteBrand(localBrand.value.id);
    dialog.value = false;
  };
  </script>
  