<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>My Rentals</v-card-title>
          <v-card-subtitle>List of your current and past rentals.</v-card-subtitle>

          <v-card-text>
            <v-list lines="five">
              <!-- Show rental items if any exist -->
              <template v-if="rentalStore?.rentals?.length">
                <v-list-item v-for="rental in rentalStore.rentals" :key="rental.id">
                  <v-list-item-title>
                    {{ items[rental.item_id] ? itemStore.itemCode(items[rental.item_id]) : 'Loading...' }}
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    <div>Status: {{ rental.status }} </div>
                    <div>Pickup Location:
                      {{ rental.location.city }},
                      {{ rental.location.state }},
                      {{ rental.location.country }},
                    </div>
                    <div>Pickup Time: {{ formatDate(rental.starts_at) }}</div>
                    <div>Return Time: {{ formatDate(rental.ends_at) }}</div>
                    <!-- <v-btn 
                      v-if="rental.status === 'booked' && isWithinRentalTime(rental.starts_at, rental.ends_at)"
                      @click="handlePunctuality(rental.id)"
                    >
                      Assign Punctuality
                    </v-btn> -->
                    <v-btn v-if="rental.status === 'booked'" @click="confirmCancel(rental.id)">
                      Cancel Rental
                    </v-btn>

                    <v-btn v-if="rental.status === 'booked' && new Date() >= new Date(rental.starts_at)"
                      @click="rentalStore.confirmRentalActive(rental.id)" color="green">
                      Confirm Rental Active
                    </v-btn>



                  </v-list-item-subtitle>
                </v-list-item>
              </template>

              <!-- Show message when no rentals are found -->
              <v-list-item v-else>No rentals found.</v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Confirmation Dialog -->
    <v-dialog v-model="showCancelDialog" max-width="400px">
      <v-card>
        <v-card-title class="headline">Confirm Cancellation</v-card-title>
        <v-card-text>
          Are you sure you want to cancel this rental?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showCancelDialog = false">Cancel</v-btn>
          <v-btn color="red" @click="handleConfirmCancel">Confirm</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRentalStore } from '@/stores/rental';
import { useItemStore } from '@/stores/item';

const rentalStore = useRentalStore();
const itemStore = useItemStore();
const rentals = ref([]);
const items = ref({}); // Store items by their ID
const showCancelDialog = ref(false); // State for dialog visibility
const rentalToCancel = ref(null); // ID of rental to cancel

const fetchRentals = async () => {
  await rentalStore.fetchRentals();

  rentals.value = rentalStore.rentals;

  // Fetch item details for each rental
  for (const rental of rentalStore.rentals) {
    if (!items.value[rental.item_id]) {
      await itemStore.fetchItemById(rental.item_id);
      items.value[rental.item_id] = itemStore.currentItem; // Update the item store with the fetched item
    }
  }
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

const isWithinRentalTime = (start, end) => {
  const now = new Date();
  return new Date(start) <= now && now <= new Date(end);
};

const handlePunctuality = (rentalId) => {
  // Implement logic to handle assigning punctuality
  console.log('Assign punctuality for rental:', rentalId);
  // You might want to call an API or update the store here
};

const confirmCancel = (rentalId) => {
  rentalToCancel.value = rentalId;
  showCancelDialog.value = true;
};

const handleConfirmCancel = async () => {
  if (rentalToCancel.value !== null) {
    await rentalStore.cancelRental(rentalToCancel.value);
    showCancelDialog.value = false;
    rentalToCancel.value = null;
  }
};

onMounted(() => {
  fetchRentals();
});
</script>
