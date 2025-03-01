<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          color="primary"
          class="text-none font-weight-regular"
          prepend-icon="mdi-calendar"
          text="Rental Dates"
          variant="tonal"
          block
          v-bind="activatorProps"
        ></v-btn>
      </template>
      <v-card
        v-if="localItem"
        prepend-icon="mdi-calendar"
        title="Rental Dates"
        :subtitle="localItem.code"
      >
        <v-card-text>
          <!-- Date Range Picker -->
          <v-row>
            <v-col cols="12" md="12">
              <v-date-input
                dense
                v-model="archetypeStore.dateRange"
                label="Dates"
                prepend-icon=""
                persistent-placeholder
                multiple="range"
                :min="minStartDate"
              ></v-date-input>
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

          <ConfirmRentalDialog :item="localItem" />
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script setup>
import { shallowRef, ref, computed, watch } from "vue";
import { useItemStore } from "@/stores/item";
import { useArchetypeStore } from "@/stores/archetype";
import { useResponseStore } from "@/stores/response";
import ConfirmRentalDialog from "./ConfirmRentalDialog.vue";

const dialog = shallowRef(false);

const itemStore = useItemStore();
const archetypeStore = useArchetypeStore();
const responseStore = useResponseStore();

const localItem = ref(null);
const rentedDates = ref([]);

// Computed properties for date constraints
const today = new Date();
today.setHours(0, 0, 0, 0);
const minStartDate = computed(() => today);

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

  await itemStore.fetchItemRentedDates(localItem.value);
  rentedDates.value = itemStore.rentedDates; // Assuming rentedDates are stored in itemStore
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initialize();
  responseStore.$reset();
};

const onClose = () => {};
</script>
