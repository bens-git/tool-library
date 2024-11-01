<template>
  <v-card>
    <v-card-title>Unavailable Dates - {{ localItem.item_name }}</v-card-title>
    <v-card-text>
      <v-date-input 
        v-model="localItem.unavailableDates" 
        label="Select Unavailable Dates"
        :min="new Date().toISOString().substr(0, 10)" 
        multiple
      ></v-date-input>
      <div v-if="localItem.unavailableDates && localItem.unavailableDates.length">
        <v-row>
          
          <v-col v-for="(date, index) in localItem.unavailableDates" :key="index" cols="4">
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
    <v-card-actions>
      <v-btn color="primary" @click="saveItem">Update</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useItemStore } from '@/stores/item';
import { useResponseStore } from '@/stores/response';

const itemStore = useItemStore();
const responseStore = useResponseStore();

const props = defineProps({
  showAvailabilityModal: Boolean,
  item: Object, // Item ID to fetch unavailable dates
});

const emit = defineEmits(['update-item-availability', 'update:showAvailabilityModal', 'close-modal']);

const localItem = ref({
  ...props.item,
  unavailableDates: [], // Initialize with an empty array
});

onMounted(async () => {
  if (localItem.value.id) {
    try {
      const dates = await itemStore.fetchItemUnavailableDates(localItem.value.id);
      localItem.value.unavailableDates = dates;
    } catch (error) {
      console.error('Failed to fetch unavailable dates:', error);
    }
  }
});

const saveItem = async () => {
  try {
    await itemStore.updateItemAvailability(localItem.value.id, localItem.value.unavailableDates);

    if (responseStore.response.success) {
      closeModal();
    } else {
      console.log('Error:', responseStore.response.message);
    }
  } catch (error) {
    console.error('Unexpected Error:', error);
    responseStore.setResponse(false, error.response?.data?.message || 'An unexpected error occurred', [error.response?.data?.errors || []]);
  }
};

const removeUnavailableDate = (index) => {
  if (index >= 0 && index < localItem.value.unavailableDates.length) {
    localItem.value.unavailableDates.splice(index, 1);
  }
};

const closeModal = () => {
  emit('close-modal');
};
</script>
