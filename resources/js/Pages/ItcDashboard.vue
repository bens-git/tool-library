<template>
  <PageLayout title="Time Credits Dashboard">
    <Head title="Time Credits Dashboard" />

    <v-container>
      <!-- Balance Card -->
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="pa-4" color="primary" dark>
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

        <v-col cols="12" md="6">
          <v-row>
            <v-col cols="6">
              <v-card class="pa-4" color="success" dark>
                <v-card-title class="text-h6">Lifetime Earned</v-card-title>
                <v-card-text class="text-h5">
                  +{{ formatNumber(stats.lifetime_earned) }}
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="6">
              <v-card class="pa-4" color="error" dark>
                <v-card-title class="text-h6">Lifetime Spent</v-card-title>
                <v-card-text class="text-h5">
                  -{{ formatNumber(stats.lifetime_spent) }}
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12">
              <v-card class="pa-4">
                <v-card-title class="text-h6">Total Transactions</v-card-title>
                <v-card-text class="text-h5">
                  {{ stats.transaction_count }}
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-col>
      </v-row>

      <!-- How it works -->
      <v-row class="mt-4">
        <v-col cols="12">
          <v-card>
            <v-card-title>How the Time Credit System Works</v-card-title>
            <v-card-text>
              <v-list>
                <v-list-item>
                  <template #prepend>
                    <v-icon color="success">mdi-plus-circle</v-icon>
                  </template>
                  <v-list-item-title>Earn Credits</v-list-item-title>
                  <v-list-item-subtitle>
                    Lend tools to others to earn credits. The more you share, the more you earn!
                  </v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend>
                    <v-icon color="error">mdi-minus-circle</v-icon>
                  </template>
                  <v-list-item-title>Spend Credits</v-list-item-title>
                  <v-list-item-subtitle>
                    Borrow tools using credits. Credits are deducted from your balance.
                  </v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend>
                    <v-icon color="warning">mdi-vote</v-icon>
                  </template>
                  <v-list-item-title>Vote on Rates</v-list-item-title>
                  <v-list-item-subtitle>
                    Participate in community voting to set credit rates for tools. Earn bonus credits for voting!
                  </v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend>
                    <v-icon color="info">mdi-clock-outline</v-icon>
                  </template>
                  <v-list-item-title>Decay</v-list-item-title>
                  <v-list-item-subtitle>
                    Credits may decay over time to prevent accumulation and encourage active participation.
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Recent Transactions -->
      <v-row class="mt-4">
        <v-col cols="12">
          <v-card>
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
      </v-row>
    </v-container>
  </PageLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
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

