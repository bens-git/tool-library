<template>
    <PageLayout title="My Loans">
        <Head title="My Loans" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%">
                <!-- Header -->
                <div class="d-flex justify-space-between align-center mb-4">
                    <div class="text-h5 font-weight-bold">My Loans</div>
                   
                </div>

                <v-list lines="five">
                    <!-- Show loan items if any exist -->
                    <template v-if="rentals?.length">
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

                                <!-- Renter Contact Info -->
                                <div v-if="rental.renter" class="mb-3 pa-2 bg-grey-lighten-4 rounded">
                                    <div class="text-caption font-weight-bold">Renter</div>
                                    <div class="text-body-2">{{ rental.renter.name }}</div>
                                    <div class="text-caption text-grey">{{ rental.renter.email }}</div>
                                </div>

                                <v-btn
                                    v-if="rental.conversation_id && (rental.status === 'booked' || rental.status === 'active' || rental.status === 'holding')"
                                    color="primary"
                                    size="small"
                                    class="mt-2 mr-2"
                                    :href="'/messages?conversation=' + rental.conversation_id"
                                >
                                    <v-icon start size="small">mdi-message</v-icon>
                                    Contact Renter
                                </v-btn>

                                <v-btn
                                    v-if="rental.status === 'active'"
                                    color="green"
                                    size="small"
                                    class="mt-2"
                                    @click="confirmLoanCompleted(rental.id)"
                                >
                                    Confirm Returned
                                </v-btn>

                                <v-btn
                                    v-if="rental.status === 'booked'"
                                    color="orange"
                                    size="small"
                                    class="mt-2 ml-2"
                                    @click="confirmLoanHolding(rental.id)"
                                >
                                    Allow Holding
                                </v-btn>

                                <v-btn
                                    v-if="rental.status === 'booked'"
                                    size="small"
                                    class="mt-2 ml-2"
                                    @click="confirmCancel(rental.id)"
                                >
                                    Cancel Loan
                                </v-btn>
                            </v-list-item-subtitle>
                        </v-list-item>
                    </template>

                    <!-- Show message when no rentals are found -->
                    <v-list-item v-else>No loans found.</v-list-item>
                </v-list>

                <!-- Confirmation Dialog -->
                <v-dialog v-model="showCancelDialog" max-width="400px">
                    <v-card>
                        <v-card-title class="headline">Confirm Cancellation</v-card-title>
                        <v-card-text> Are you sure you want to cancel this loan? </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn text @click="showCancelDialog = false">Cancel</v-btn>
                            <v-btn color="red" @click="handleConfirmCancel">Confirm</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </div>
        </v-container>
    </PageLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/PageLayout.vue';

const page = usePage();

const rentals = ref([]);
const showCancelDialog = ref(false);
const loanToCancel = ref(null);
const pageNumber = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref([]);
const totalRentals = ref(0);
const user = page.props.auth.user;

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
    const query = {
        page: pageNumber.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        ownerId: user.id,
    };

    const response = await api.get(route('rentals.index'), {
        params: query,
    });

    rentals.value = response.data;
    totalRentals.value = response.total;
};

const confirmCancel = (loanId) => {
    loanToCancel.value = loanId;
    showCancelDialog.value = true;
};

const handleConfirmCancel = async () => {
    if (loanToCancel.value !== null) {
        await api.delete(route('rentals.destroy', loanToCancel.value));

        showCancelDialog.value = false;
        loanToCancel.value = null;
    }
};

const confirmLoanCompleted = async (rentalId) => {
    try {
        await api.patch(route('rentals.update', rentalId), {
            status: 'completed'
        });
        refreshRentals();
    } catch (e) {
        console.error(e);
    }
};

const confirmLoanHolding = async (rentalId) => {
    try {
        await api.patch(route('rentals.update', rentalId), {
            status: 'holding'
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

