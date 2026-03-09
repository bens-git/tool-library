<template>
  <PageLayout title="My Usage">
    <Head title="My Usage" />

    <v-container class="d-flex justify-center">
      <v-col cols="12" md="8">
        <v-list lines="five">
          <!-- Show usage items if any exist -->
          <template v-if="usages.length">
            <v-list-item v-for="usage in usages" :key="usage.id">
              <v-list-item-title class="text-h6 mb-1">
                {{ usage.item?.name || usage.item?.archetype?.name || 'Loading...' }}
              </v-list-item-title>
              <v-list-item-subtitle>
                <div class="mb-2">
                  <v-chip 
                    :color="getStatusColor(usage.status)" 
                    size="small"
                    class="mr-2"
                  >
                    {{ usage.status }}
                  </v-chip>
                  <span class="text-caption text-grey">
                    Used: {{ formatDate(usage.rented_at) }}
                  </span>
                </div>

                <!-- Owner Contact Info -->
                <div v-if="usage.owner" class="mb-3 pa-2 bg-grey-lighten-4 rounded">
                  <div class="text-caption font-weight-bold">Owner</div>
                  <div class="text-body-2">{{ usage.owner.name }}</div>
                  <div class="text-caption text-grey">{{ usage.owner.email }}</div>
                </div>

                <v-btn
                  v-if="usage.conversation_id && (usage.status === 'booked' || usage.status === 'active' || usage.status === 'holding')"
                  color="primary"
                  size="small"
                  class="mt-2 mr-2"
                  :href="'/messages?conversation=' + usage.conversation_id"
                >
                  <v-icon start size="small">mdi-message</v-icon>
                  Contact Owner
                </v-btn>

                <v-btn
                  v-if="usage.status === 'active'"
                  color="green"
                  size="small"
                  class="mt-2"
                  @click="confirmLoanCompleted(usage.id)"
                >
                  Confirm Returned
                </v-btn>

                <v-btn
                  v-if="usage.status === 'booked'"
                  color="orange"
                  size="small"
                  class="mt-2 ml-2"
                  @click="confirmLoanHolding(usage.id)"
                >
                  Allow Holding
                </v-btn>

                <v-btn
                  v-if="usage.status === 'booked'"
                  size="small"
                  class="mt-2 ml-2"
                  @click="confirmCancel(usage.id)"
                >
                  Cancel Usage
                </v-btn>
              </v-list-item-subtitle>
            </v-list-item>
          </template>

          <!-- Show message when no usages are found -->
          <v-list-item v-else>No usage found.</v-list-item>
        </v-list>

        <!-- Confirmation Dialog -->
        <v-dialog v-model="showCancelDialog" max-width="400px">
          <v-card>
            <v-card-title class="headline">Confirm Cancellation</v-card-title>
            <v-card-text>Are you sure you want to cancel this usage?</v-card-text>
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
import api from '@/services/api';
import { useResponseStore } from '@/Stores/response';

const responseStore = useResponseStore();

const usages = ref([]);
const showCancelDialog = ref(false);
const usageToCancel = ref(null);

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

const refreshUsages = async () => {
  try {
    const response = await api.get(route('me.usages.index'));
    usages.value = response.data.data || [];
  } catch (e) {
    console.error(e);
  }
};

const confirmCancel = (usageId) => {
  usageToCancel.value = usageId;
  showCancelDialog.value = true;
};

const handleConfirmCancel = async () => {
  try {
    await api.delete(route('usages.destroy', usageToCancel.value));
    showCancelDialog.value = false;
    responseStore.setSuccess('Usage cancelled successfully');
    refreshUsages();
  } catch (e) {
    console.error(e);
    responseStore.setError(e.response?.data?.message || 'Failed to cancel usage');
  }
};

const confirmLoanCompleted = async (usageId) => {
  try {
    await api.patch(route('usages.update', usageId), {
      status: 'completed'
    });
    responseStore.setSuccess('Usage marked as completed');
    refreshUsages();
  } catch (e) {
    console.error(e);
    responseStore.setError(e.response?.data?.message || 'Failed to complete usage');
  }
};

const confirmLoanHolding = async (usageId) => {
  try {
    await api.patch(route('usages.update', usageId), {
      status: 'holding'
    });
    responseStore.setSuccess('Usage marked as holding');
    refreshUsages();
  } catch (e) {
    console.error(e);
    responseStore.setError(e.response?.data?.message || 'Failed to update usage');
  }
};

onMounted(() => {
  refreshUsages();
});
</script>

