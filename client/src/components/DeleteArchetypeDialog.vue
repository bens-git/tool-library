<template>
    <div class="pl-4 text-center">
      <v-dialog v-model="dialog" @open="onOpen">
        <template v-slot:activator="{ props: activatorProps }">
          <v-btn
            color="error"
            class="text-none font-weight-regular"
            prepend-icon="mdi-delete"
            text="Delete Archetype"
            variant="tonal"
            v-bind="activatorProps"
            block
          ></v-btn>
        </template>
        <v-card
          v-if="localArchetype"
          prepend-icon="mdi-delete"
          title="Delete Archetype"
          :subtitle="localArchetype.name"
        >
          <v-card-text> Are you sure you want to delete this archetype? </v-card-text>
          <v-divider></v-divider>
  
          <v-card-actions>
            <v-spacer></v-spacer>
  
            <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>
  
            <v-btn
              color="error"
              text="Delete"
              variant="tonal"
              @click="deleteArchetype"
            ></v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </template>
  <script setup>
  import { shallowRef, ref, watch } from "vue";
  import { useArchetypeStore } from "@/stores/archetype";
  import { useResponseStore } from "@/stores/response";
  
  const dialog = shallowRef(false);
  
  const archetypeStore = useArchetypeStore();
  const responseStore = useResponseStore();
  
  const localArchetype = ref(null);
  
  const props = defineProps({
    archetype: Object,
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
    localArchetype.value = {
      ...props.archetype,
    };
  };
  
  // const emit = defineEmits(["update:modelValue", "close"]);
  
  const onOpen = async () => {
    initialize();
    responseStore.$reset();
  };
  
  const onClose = () => {};
  
  const deleteArchetype = () => {
    console.log("delete");
    archetypeStore.deleteArchetype(localArchetype.value.id);
    dialog.value = false;
  };
  </script>
  