<template>
  <PageLayout title="My Rentals">
    <Head title="My Rentals" />

    <v-container class="d-flex justify-center">
      <v-col cols="12" md="8">
        <v-list lines="five">
          <!-- Show rentals -->
          <template v-if="rentals.length">
            <v-list-item v-for="rental in rentals" :key="rental.id">
              <v-list-item-title>
                {{
                  items[rental.item_id]
                    ? itemStore.itemCode(items[rental.item_id])
                    : 'Loading...'
                }}
              </v-list-item-title>
              <v-list-item-subtitle>
                <div>Status: {{ rental.status }}</div>
                <div>
                  Pickup Location:
                  {{ rental.location.city }},
                  {{ rental.location.state }},
                  {{ rental.location.country }}
                </div>
                <div>Pickup Time: {{ formatDate(rental.starts_at) }}</div>
                <div>Return Time: {{ formatDate(rental.ends_at) }}</div>

                <v-btn
                  v-if="rental.status === 'booked'"
                  color="red"
                  small
                  @click="confirmCancel(rental.id)"
                >
                  Cancel Rental
                </v-btn>

                <v-btn
                  v-if="
                    rental.status === 'booked' &&
                    new Date() >= new Date(rental.starts_at)
                  "
                  color="green"
                  small
                  @click="confirmRentalActive(rental.id)"
                >
                  Confirm Rental Active
                </v-btn>
              </v-list-item-subtitle>
            </v-list-item>
          </template>

          <v-list-item v-else>No rentals found.</v-list-item>
        </v-list>

        <!-- Cancel Confirmation Dialog -->
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
      </v-col>
    </v-container>
  </PageLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/PageLayout.vue';
import axios from 'axios';

const rentals = ref([]);
const items = ref({});
const showCancelDialog = ref(false);
const rentalToCancel = ref(null);

const refreshRentals = async () => {
  try {
    const response = await axios.get(route('me.rentals.index'));
    rentals.value = response.data;
  } catch (e) {
    console.error(e);
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleString(undefined, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const confirmCancel = (rentalId) => {
  rentalToCancel.value = rentalId;
  showCancelDialog.value = true;
};

const handleConfirmCancel = async () => {
  try {
    await axios.delete(route('rentals.destroy', rentalToCancel.value));
    showCancelDialog.value = false;
    refreshRentals();
  } catch (e) {
    console.error(e);
  }
};

const confirmRentalActive = (rentalId) => {
  console.log('Confirm rental active for', rentalId);
};

onMounted(() => {
  refreshRentals();
});
</script>