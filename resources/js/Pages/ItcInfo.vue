<template>
  <PageLayout title="Time Credits Info">
    <Head title="Time Credits System Info" />

    <v-container>
      <!-- Header -->
      <v-row>
        <v-col cols="12">
          <v-card class="pa-4" color="primary" dark>
            <div class="d-flex align-center">
              <v-icon size="40" class="mr-3">mdi-clock-outline</v-icon>
              <div>
                <v-card-title class="text-h5">The Time Credit System</v-card-title>
                <v-card-subtitle>A community-driven system based on time, not money</v-card-subtitle>
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- What are Time Credits -->
      <v-row class="mt-3">
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h6">
              <v-icon class="mr-2">mdi-help-circle</v-icon>
              What are Time Credits?
            </v-card-title>
            <v-card-text>
              <p class="text-body-2">
                Time Credits measure <strong>value in hours of work</strong> rather than monetary dollars. 
                One hour of your time is worth one hour of someone else's time.
              </p>
              <p class="text-body-2 mt-2">
                When you lend a tool, you earn credits. You can spend those credits to borrow other tools. 
                It's a fair exchange system that promotes sharing and community building.
              </p>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- How to Earn -->
      <v-row class="mt-3">
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h6">
              <v-icon color="success" class="mr-2">mdi-plus-circle</v-icon>
              How to Earn Credits
            </v-card-title>
            <v-card-text>
              <v-list density="compact">
                <v-list-item>
                  <template #prepend><v-icon color="success">mdi-gift</v-icon></template>
                  <v-list-item-title>Welcome Bonus</v-list-item-title>
                  <v-list-item-subtitle>New members get 50 ITC to get started</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend><v-icon color="success">mdi-handshake</v-icon></template>
                  <v-list-item-title>Lending Tools</v-list-item-title>
                  <v-list-item-subtitle>Earn credits when others borrow your tools. You receive 100% of the rental rate.</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend><v-icon color="success">mdi-vote</v-icon></template>
                  <v-list-item-title>Voting</v-list-item-title>
                  <v-list-item-subtitle>Earn a bonus credit for participating in credit rate voting</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- How to Spend -->
      <v-row class="mt-3">
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h6">
              <v-icon color="error" class="mr-2">mdi-minus-circle</v-icon>
              How to Spend Credits
            </v-card-title>
            <v-card-text>
              <v-list density="compact">
                <v-list-item>
                  <template #prepend><v-icon color="error">mdi-tools</v-icon></template>
                  <v-list-item-title>Borrowing Tools</v-list-item-title>
                  <v-list-item-subtitle>Use credits to borrow any tool. Cost is based on the daily rate set by community voting.</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <template #prepend><v-icon color="info">mdi-calculator</v-icon></template>
                  <v-list-item-title>How Costs Work</v-list-item-title>
                  <v-list-item-subtitle>Each tool has a daily rate (e.g., 2 ITC/day). Borrowing for 3 days costs 6 ITC.</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Not Money -->
      <v-row class="mt-3">
        <v-col cols="12">
          <v-card color="warning" variant="tonal">
            <v-card-title class="text-h6">
              <v-icon class="mr-2">mdi-currency-off</v-icon>
              Important: Not Money!
            </v-card-title>
            <v-card-text>
              <p class="text-body-2">
                <strong>Time Credits are NOT money and cannot be exchanged for cash.</strong> 
                Credits cannot be transferred to other users. Credits are extinguished upon use - 
                they disappear and cannot be saved or accumulated. This is based on the Integral 
                philosophy of reciprocity, not currency.
              </p>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Credit Decay -->
      <v-row class="mt-3">
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h6">
              <v-icon color="orange" class="mr-2">mdi-timer-sand</v-icon>
              Credit Decay
            </v-card-title>
            <v-card-text>
              <p class="text-body-2">
                Balances may decay over time to prevent accumulation and encourage active participation. 
                Up to 50% of balance can be lost monthly if inactive.
              </p>
              <p class="text-body-2 mt-2">
                Stay active by lending, borrowing, or voting to prevent decay.
              </p>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Community Voting -->
      <v-row class="mt-3">
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h6">
              <v-icon color="warning" class="mr-2">mdi-vote</v-icon>
              Community Voting
            </v-card-title>
            <v-card-text>
              <p class="text-body-2">
                Credit rates for tools are set by community voting. Every member can participate 
                to ensure fair rates for all tools in the library.
              </p>
              <v-btn color="primary" class="mt-2" size="small" @click="goToVoting">
                Go to Voting
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- FAQ -->
      <v-row class="mt-3">
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h6">
              <v-icon class="mr-2">mdi-frequently-asked-questions</v-icon>
              Frequently Asked Questions
            </v-card-title>
            <v-card-text>
              <v-expansion-panels>
                <v-expansion-panel title="Can I have negative credits?">
                  <v-expansion-panel-text>
                    No. Your balance cannot go negative. If you don't have enough credits, 
                    rentals will be declined.
                  </v-expansion-panel-text>
                </v-expansion-panel>
                <v-expansion-panel title="Do credits expire?">
                  <v-expansion-panel-text>
                    Credits don't expire, but balances may decay if inactive. Stay active to maintain your balance.
                  </v-expansion-panel-text>
                </v-expansion-panel>
                <v-expansion-panel title="How is the daily rate determined?">
                  <v-expansion-panel-text>
                    Rates are determined by community voting on tool types (archetypes). 
                    Each archetype has a credit rate that applies to all items of that type. 
                    Members vote on fair rates considering purchase value, maintenance needs, and usage.
                  </v-expansion-panel-text>
                </v-expansion-panel>
                <v-expansion-panel title="Can I transfer credits to another user?">
                  <v-expansion-panel-text>
                    No. Credits cannot be transferred between users. This is intentional - the Integral 
                    system prevents accumulation and ensures fairness. Credits can only be earned 
                    through lending or voting, and spent through borrowing.
                  </v-expansion-panel-text>
                </v-expansion-panel>
                <v-expansion-panel title="What if a borrowed tool is damaged?">
                  <v-expansion-panel-text>
                    Credit system is separate from tool condition. Resolve issues through messaging. 
                    Document damage and communicate with lender.
                  </v-expansion-panel-text>
                </v-expansion-panel>
                <v-expansion-panel title="Where can I see my transactions?">
                  <v-expansion-panel-text>
                    View your complete history on the ITC Dashboard. Shows all earned, spent, 
                    bonuses, and decay.
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Tips -->
      <v-row class="mt-3">
        <v-col cols="12">
          <v-card color="grey-lighten-4">
            <v-card-title class="text-h6">
              <v-icon color="primary" class="mr-2">mdi-lightbulb</v-icon>
              Tips for Success
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="6">
                  <v-card variant="outlined" class="pa-3">
                    <div class="text-subtitle-2 font-weight-bold">List Your Tools</div>
                    <div class="text-caption">Share tools to earn credits passively</div>
                  </v-card>
                </v-col>
                <v-col cols="12" md="6">
                  <v-card variant="outlined" class="pa-3">
                    <div class="text-subtitle-2 font-weight-bold">Vote on Rates</div>
                    <div class="text-caption">Help set fair rates for everyone</div>
                  </v-card>
                </v-col>
                <v-col cols="12" md="6">
                  <v-card variant="outlined" class="pa-3">
                    <div class="text-subtitle-2 font-weight-bold">Stay Active</div>
                    <div class="text-caption">Regular activity prevents decay</div>
                  </v-card>
                </v-col>
                <v-col cols="12" md="6">
                  <v-card variant="outlined" class="pa-3">
                    <div class="text-subtitle-2 font-weight-bold">Check Dashboard</div>
                    <div class="text-caption">Track your balance and transactions</div>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Navigation -->
      <v-row class="mt-4 mb-8">
        <v-col cols="12" class="text-center">
          <v-btn color="primary" class="mr-2" @click="goToDashboard">
            Back to Dashboard
          </v-btn>
          <v-btn color="secondary" @click="goToVoting">
            Vote on Rates
          </v-btn>
        </v-col>
      </v-row>
    </v-container>
  </PageLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/PageLayout.vue';

const goToDashboard = () => {
  router.visit(route('itc'));
};

const goToVoting = () => {
  router.visit(route('credit-voting'));
};
</script>

