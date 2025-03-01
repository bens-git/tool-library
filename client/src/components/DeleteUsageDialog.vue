<template>
    <div class="pl-4 text-center">
      <v-dialog v-model="dialog" @open="onOpen">
        <template v-slot:activator="{ props: activatorProps }">
          <v-btn
            color="error"
            class="text-none font-weight-regular"
            prepend-icon="mdi-delete"
            text="Delete Usage"
            variant="tonal"
            v-bind="activatorProps"
            block
          ></v-btn>
        </template>
        <v-card
          v-if="localUsage"
          prepend-icon="mdi-delete"
          title="Delete Usage"
          :subtitle="localUsage.name"
        >
          <v-card-text> Are you sure you want to delete this usage? </v-card-text>
          <v-divider></v-divider>
  
          <v-card-actions>
            <v-spacer></v-spacer>
  
            <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>
  
            <v-btn
              color="error"
              text="Delete"
              variant="tonal"
              @click="deleteUsage"
            ></v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </template>
  <script setup>
  import { shallowRef, ref, watch } from "vue";
  import { useUsageStore } from "@/stores/usage";
  import { useResponseStore } from "@/stores/response";
  
  const dialog = shallowRef(false);
  
  const usageStore = useUsageStore();
  const responseStore = useResponseStore();
  
  const localUsage = ref(null);
  
  const props = defineProps({
    usage: Object,
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
    localUsage.value = {
      ...props.usage,
    };
  };
  
  // const emit = defineEmits(["update:modelValue", "close"]);
  
  const onOpen = async () => {
    initialize();
    responseStore.$reset();
  };
  
  const onClose = () => {};
  
  const deleteUsage = () => {
    console.log("delete");
    usageStore.deleteUsage(localUsage.value.id);
    dialog.value = false;
  };
  </script>
  