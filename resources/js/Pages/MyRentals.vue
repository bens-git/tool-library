<template>
  <PageLayout title="My Rentals">
    <Head title="My Rentals" />

    <v-container class="d-flex justify-center">
      <v-col cols="12" md="8">
        <v-list lines="five">
          <!-- Show rentals -->
          <template v-if="rentals.length">
            <v-list-item v-for="rental in rentals" :key="rental.id">
              <v-list-item-title class="text-h6 mb-1">
                {{ rental.item?.name || rental.item?.archetype?.name || 'Loading...' }}
              </v-list-item-title>
              <v-list-item-subtitle>
                <div class="mb-2">
                  <v-chip 
                    :color="getStatusColor(rental.status)" 
                    size="small"
                    class="mr-2"
                  >
                    {{ rental.status }}
                  </v-chip>
                  <span class="text-caption text-grey">
                    Rented: {{ formatDate(rental.rented_at) }}
                  </span>
                </div>

                <!-- Owner Contact Info -->
                <div v-if="rental.owner" class="mb-3 pa-2 bg-grey-lighten-4 rounded">
                  <div class="text-caption font-weight-bold">Owner</div>
                  <div class="text-body-2">{{ rental.owner.name }}</div>
                  <div class="text-caption text-grey">{{ rental.owner.email }}</div>
                </div>

                <v-btn
                  v-if="rental.conversation_id && (rental.status === 'booked' || rental.status === 'active' || rental.status === 'holding')"
                  color="primary"
                  size="small"
                  class="mt-2 mr-2"
                  :href="'/messages?conversation=' + rental.conversation_id"
                >
                  <v-icon start size="small">mdi-message</v-icon>
                  Contact Owner
                </v-btn>

                <v-btn
                  v-if="rental.status === 'booked'"
                  color="red"
                  small
                  class="mt-2"
                  @click="confirmCancel(rental.id)"
                >
                  Cancel Rental
                </v-btn>

                <v-btn
                  v-if="rental.status === 'booked'"
                  color="green"
                  small
                  class="mt-2 ml-2"
                  @click="confirmRentalActive(rental.id)"
                >
                  Mark as Active
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
const showCancelDialog = ref(false);
const rentalToCancel = ref(null);

const getStatusColor = (status) => {
  const colors = {
    'booked': 'blue',
    'active': 'green',
    'completed': 'grey',
    'cancelled': 'red',
    'holding': 'orange',
  };
  return colors[status] || 'grey';
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString();
};

const refreshRentals = async () => {
  try {
    const response = await axios.get(route('me.rentals.index'));
    rentals.value = response.data.data || [];
  } catch (e) {
    console.error(e);
  }
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

const confirmRentalActive = async (rentalId) => {
  try {
    await axios.patch(route('rentals.update', rentalId), {
      status: 'active'
    });
    refreshRentals();
  } catch (e) {
    console.error(e);
  }
};

onMounted(() => {
  refreshRentals();
});
</script>

