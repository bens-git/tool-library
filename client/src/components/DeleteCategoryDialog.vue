<template>
    <div class="pl-4 text-center">
      <v-dialog v-model="dialog" @open="onOpen">
        <template v-slot:activator="{ props: activatorProps }">
          <v-btn
            color="error"
            class="text-none font-weight-regular"
            prepend-icon="mdi-delete"
            text="Delete Category"
            variant="tonal"
            v-bind="activatorProps"
            block
          ></v-btn>
        </template>
        <v-card
          v-if="localCategory"
          prepend-icon="mdi-delete"
          title="Delete Category"
          :subtitle="localCategory.name"
        >
          <v-card-text> Are you sure you want to delete this category? </v-card-text>
          <v-divider></v-divider>
  
          <v-card-actions>
            <v-spacer></v-spacer>
  
            <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>
  
            <v-btn
              color="error"
              text="Delete"
              variant="tonal"
              @click="deleteCategory"
            ></v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </template>
  <script setup>
  import { shallowRef, ref, watch } from "vue";
  import { useCategoryStore } from "@/stores/category";
  import { useResponseStore } from "@/stores/response";
  
  const dialog = shallowRef(false);
  
  const categoryStore = useCategoryStore();
  const responseStore = useResponseStore();
  
  const localCategory = ref(null);
  
  const props = defineProps({
    category: Object,
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
    localCategory.value = {
      ...props.category,
    };
  };
  
  // const emit = defineEmits(["update:modelValue", "close"]);
  
  const onOpen = async () => {
    initialize();
    responseStore.$reset();
  };
  
  const onClose = () => {};
  
  const deleteCategory = () => {
    console.log("delete");
    categoryStore.deleteCategory(localCategory.value.id);
    dialog.value = false;
  };
  </script>
  