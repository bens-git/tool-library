<template>
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          color="primary"
          prepend-icon="mdi-calendar"
          text="Item Availability"
          variant="tonal"
          v-bind="activatorProps"
          block
          size="small"
        ></v-btn>
      </template>
      <v-card
        v-if="localItem"
        prepend-icon="mdi-calendar"
        title="Item Availability"
        :subtitle="localItem.code"
      >
        <v-card-text>
          <v-date-input
            v-model="localItem.unavailableDates"
            label="Select Unavailable Dates"
            :min="new Date().toISOString().substr(0, 10)"
            multiple
          ></v-date-input>
          <div
            v-if="
              localItem.unavailableDates && localItem.unavailableDates.length
            "
          >
            <v-row>
              <v-col
                v-for="(date, index) in localItem.unavailableDates"
                :key="index"
                cols="4"
              >
                <v-chip class="ma-2" color="red lighten-2">
                  {{ new Date(date).toLocaleDateString() }}
                  <v-btn icon small @click="removeUnavailableDate(index)">
                    <v-icon>mdi-close</v-icon>
                  </v-btn>
                </v-chip>
              </v-col>
            </v-row>
          </div>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

          <v-btn
            color="success"
            text="Save"
            variant="tonal"
            @click="saveItem"
          ></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
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
const initialize = async () => {
  localItem.value = {
    ...props.item,
  };

  if (localItem.value.id) {
    try {
      const dates = await itemStore.indexItemUnavailableDates(
        localItem.value.id
      );
      localItem.value.unavailableDates = dates;
    } catch (error) {
      console.error("Failed to fetch unavailable dates:", error);
    }
  }
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initialize();
  responseStore.$reset();
};

const onClose = () => {};

const saveItem = async () => {
  await itemStore.updateItemAvailability(
    localItem.value.id,
    localItem.value.unavailableDates
  );

  if (responseStore.response.success) {
    dialog.value = false;
  }
};

const removeUnavailableDate = (index) => {
  if (index >= 0 && index < localItem.value.unavailableDates.length) {
    localItem.value.unavailableDates.splice(index, 1);
  }
};
</script>
