<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          color="primary"
          class="text-none font-weight-regular"
          prepend-icon="mdi-check-circle"
          text="Confirm Rental"
          variant="tonal"
          v-bind="activatorProps"
        ></v-btn>
      </template>
      <v-card
        v-if="localItem"
        prepend-icon="mdi-check-circle"
        title="Confirm Rental"
        :subtitle="localItem.code"
      >
        <v-card-text>
          <div>
            Selected Date Range: <br /><span v-html="itemStore.outputReadableDateRange()"/>
          </div>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

          <v-btn
            color="success"
            class="text-none font-weight-regular"
            text="Rent"
            variant="tonal"
            @click="rent"
          ></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script setup>
import { shallowRef, ref, watch } from "vue";
import { useItemStore } from "@/stores/item";
import { useArchetypeStore } from "@/stores/archetype";
import { useResponseStore } from "@/stores/response";
import { useRouter } from "vue-router";

const router = useRouter();

const dialog = shallowRef(false);

const itemStore = useItemStore();
const archetypeStore = useArchetypeStore();
const responseStore = useResponseStore();

const localItem = ref(null);
const rentedDates = ref([]);

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
const initialize = async () => {
  localItem.value = {
    ...props.item,
  };

  await itemStore.indexItemRentedDates(localItem.value);
  rentedDates.value = itemStore.rentedDates; // Assuming rentedDates are stored in itemStore
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initialize();
  responseStore.$reset();
};

const onClose = () => {};

const rent = async () => {
  console.log(localItem)
  await itemStore.bookRental(
    localItem.value.id,
    archetypeStore.dateRange[0],
    archetypeStore.dateRange[archetypeStore.dateRange.length - 1]
  );
  dialog.value = false;
  router.push("/my-rentals");
};
</script>
