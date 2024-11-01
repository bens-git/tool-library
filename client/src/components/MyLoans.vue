<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>My Loans</v-card-title>
          <v-card-subtitle>List of your current and past loans.</v-card-subtitle>

          <v-card-text>
            <v-list lines="five">
              <!-- Show loan items if any exist -->
              <template v-if="rentalStore?.loans?.length">
                <v-list-item v-for="rental in rentalStore.loans" :key="rental.id">
                  <v-list-item-title>
                    {{ items[rental.item_id] ? itemStore.itemCode(items[rental.item_id]) : 'Loading...' }}
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    <div>Status: {{ rental.status }} </div>
                    <div>Pickup Location:
                      {{ rental.location.city }},
                      {{ rental.location.state }},
                      {{ rental.location.country }}
                    </div>
                    <div>Pickup Time: {{ formatDate(rental.starts_at) }}</div>
                    <div>Return Time: {{ formatDate(rental.ends_at) }}</div>


                    <v-btn v-if="rental.status === 'active' || rental.status === 'overdue'"
                      @click="rentalStore.confirmLoanCompleted(rental.id)" color="green">
                      Confirm Returned
                    </v-btn>

                    <v-btn v-if="rental.status === 'active' || rental.status === 'overdue'"
                      @click="rentalStore.confirmLoanHolding(rental.id)" color="green">
                      Allow Holding
                    </v-btn>


                    <v-btn v-if="rental.status === 'booked'" @click="confirmCancel(rental.id)">
                      Cancel Loan
                    </v-btn>
                  </v-list-item-subtitle>
                </v-list-item>
              </template>

              <!-- Show message when no rentals are found -->
              <v-list-item v-else>No loans found.</v-list-item>
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
          Are you sure you want to cancel this loan?
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
const loans = ref([]);
const items = ref({}); // Store items by their ID
const showCancelDialog = ref(false); // State for dialog visibility
const loanToCancel = ref(null); // ID of rental to cancel

const fetchLoans = async () => {
  await rentalStore.fetchLoans();


  // Fetch item details for each rental
  for (const rental of rentalStore.loans) {
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



const confirmCancel = (loanId) => {
  loanToCancel.value = loanId;
  showCancelDialog.value = true;
};

const handleConfirmCancel = async () => {
  if (loanToCancel.value !== null) {
    await rentalStore.cancelLoan(loanToCancel.value);
    showCancelDialog.value = false;
    loanToCancel.value = null;
  }
};

onMounted(() => {
  fetchLoans();
});
</script>