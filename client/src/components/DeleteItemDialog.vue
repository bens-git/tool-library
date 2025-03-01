<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          color="error"
          class="text-none font-weight-regular"
          prepend-icon="mdi-delete"
          text="Delete Item"
          variant="tonal"
          v-bind="activatorProps"
          block
        ></v-btn>
      </template>
      <v-card
        v-if="localItem"
        prepend-icon="mdi-delete"
        title="Delete Item"
        :subtitle="localItem.code"
      >
        <v-card-text> Are you sure you want to delete this item? </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

          <v-btn
            color="error"
            text="Delete"
            variant="tonal"
            @click="deleteItem"
          ></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script setup>
import { shallowRef, ref, watch } from "vue";
import { useItemStore } from "@/stores/item";
import { useResponseStore } from "@/stores/response";

const dialog = shallowRef(false);

const itemStore = useItemStore();
const responseStore = useResponseStore();

const localItem = ref(null);

const props = defineProps({
  item: Object,
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
  localItem.value = {
    ...props.item,
  };
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initialize();
  responseStore.$reset();
};

const onClose = () => {};

const deleteItem = () => {
  console.log("delete");
  itemStore.deleteItem(localItem.value.id);
  dialog.value = false;
};
</script>
