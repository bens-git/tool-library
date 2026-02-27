<template>
  <PageLayout title="Time Credits Dashboard">
    <Head title="Time Credits Dashboard" />

    <v-container fluid>
      <!-- Balance Card and Stats Row -->
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="pa-4 fill-height" color="primary" dark>
            <v-card-title class="text-h4">
              <v-icon size="32" class="mr-2">mdi-currency-usd</v-icon>
              Current Balance
            </v-card-title>
            <v-card-text class="text-h2 font-weight-bold">
              {{ formatNumber(balance) }} ITC
            </v-card-text>
            <v-card-subtitle class="text-body-1">
              Welcome to the Time Credit System!
            </v-card-subtitle>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card class="pa-4 fill-height" color="success" dark>
            <v-card-title class="text-h6">Lifetime Earned</v-card-title>
            <v-card-text class="text-h5">
              +{{ formatNumber(stats.lifetime_earned) }}
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card class="pa-4 fill-height" color="error" dark>
            <v-card-title class="text-h6">Lifetime Spent</v-card-title>
            <v-card-text class="text-h5">
              -{{ formatNumber(stats.lifetime_spent) }}
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Second Row - Transactions and Info -->
      <v-row class="mt-4">
        <v-col cols="12" md="8">
          <v-card class="fill-height">
            <v-card-title>Recent Transactions</v-card-title>
            <v-card-text>
              <v-list v-if="transactions.length">
                <v-list-item v-for="tx in transactions" :key="tx.id">
                  <template #prepend>
                    <v-icon :color="tx.amount >= 0 ? 'success' : 'error'">
                      {{ tx.amount >= 0 ? 'mdi-plus' : 'mdi-minus' }}
                    </v-icon>
                  </template>
                  <v-list-item-title>
                    <span :class="tx.amount >= 0 ? 'text-success' : 'text-error'">
                      {{ formatNumber(tx.amount) }} ITC
                    </span>
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    {{ tx.description || tx.category }} - {{ formatDate(tx.created_at) }}
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>
              <div v-else class="text-center pa-4">
                No transactions yet. Start by borrowing or lending tools!
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card class="fill-height d-flex flex-column" min-height="300">
            <v-card-title class="d-flex align-center justify-space-between flex-grow-0 flex-wrap">
              <span>How it Works</span>
              <v-btn
                color="primary"
                variant="tonal"
                prepend-icon="mdi-information"
                size="small"
                class="ml-2"
                @click="goToInfo"
              >
                Learn More
              </v-btn>
            </v-card-title>
            <v-card-text class="flex-grow-1 overflow-auto">
              <v-list density="compact">
                <v-list-item>
                  <template #prepend>
                    <v-icon color="success" size="small">mdi-plus-circle</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">Earn Credits</v-list-item-title>
                  <v-list-item-subtitle class="text-caption">
                    Lend tools to others to earn credits.
                  </v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend>
                    <v-icon color="error" size="small">mdi-minus-circle</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">Spend Credits</v-list-item-title>
                  <v-list-item-subtitle class="text-caption">
                    Borrow tools using credits.
                  </v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend>
                    <v-icon color="warning" size="small">mdi-vote</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">Vote on Rates</v-list-item-title>
                  <v-list-item-subtitle class="text-caption">
                    Participate in community voting.
                  </v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend>
                    <v-icon color="info" size="small">mdi-clock-outline</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">Decay</v-list-item-title>
                  <v-list-item-subtitle class="text-caption">
                    Credits may decay over time.
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Total Transactions Card -->
      <v-row class="mt-4">
        <v-col cols="12">
          <v-card class="pa-4">
            <v-card-title class="text-h6">Total Transactions</v-card-title>
            <v-card-text class="text-h5">
              {{ stats.transaction_count }}
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </PageLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/PageLayout.vue';
import axios from 'axios';

const balance = ref(0);
const stats = ref({
  lifetime_earned: 0,
  lifetime_spent: 0,
  transaction_count: 0,
});
const transactions = ref([]);

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num || 0);
};

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const goToInfo = () => {
  router.visit(route('itc.info'));
};

const loadDashboard = async () => {
  try {
    const response = await axios.get(route('itc.dashboard'));
    balance.value = response.data.balance;
    stats.value = response.data.stats;
    transactions.value = response.data.recent_transactions || [];
  } catch (error) {
    console.error('Failed to load ITC dashboard:', error);
  }
};

onMounted(() => {
  loadDashboard();
});
</script>

