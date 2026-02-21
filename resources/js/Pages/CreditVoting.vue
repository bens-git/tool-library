<template>
  <PageLayout title="Vote on Credit Rates">
    <Head title="Vote on Credit Rates" />

    <v-container>
      <v-alert type="info" class="mb-4">
        Participate in community voting to set credit rates for tools. 
        You earn bonus credits for voting! Vote on how many credits it should cost to borrow each tool.
      </v-alert>

      <!-- Search and Filter -->
      <v-row class="mb-4">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="search"
            label="Search items..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            hide-details
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="6">
          <v-select
            v-model="sortBy"
            :items="sortOptions"
            label="Sort by"
            variant="outlined"
            hide-details
          ></v-select>
        </v-col>
      </v-row>

      <!-- Items Grid -->
      <v-row>
        <v-col v-for="item in filteredItems" :key="item.id" cols="12" md="6" lg="4">
          <v-card>
            <v-card-title>{{ item.name }}</v-card-title>
            <v-card-subtitle>
              Purchase Value: ${{ item.purchase_value || 'N/A' }}
            </v-card-subtitle>
            <v-card-text>
              <div class="mb-2">
                <strong>Current Rate:</strong> 
                <span class="text-primary">{{ item.current_daily_rate }} ITC/day</span>
              </div>
              <div class="mb-2">
                <strong>Base Rate:</strong> 
                {{ item.base_credit_value }} ITC/day
              </div>
              <div class="mb-2">
                <strong>Votes:</strong> {{ item.vote_count }}
              </div>
              
              <!-- User's Vote -->
              <div v-if="item.user_vote" class="mb-2">
                <v-chip color="success" size="small">
                  Your vote: {{ item.user_vote }} ITC/day
                </v-chip>
              </div>

              <!-- Vote Button -->
              <v-btn
                v-if="!item.can_vote"
                color="grey"
                size="small"
                disabled
              >
                Cooldown: {{ item.cooldown_days }} days
              </v-btn>
              <v-btn
                v-else
                color="primary"
                size="small"
                @click="openVoteDialog(item)"
              >
                {{ item.user_vote ? 'Change Vote' : 'Vote' }}
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- No Items Message -->
      <div v-if="!filteredItems.length" class="text-center pa-8">
        <v-icon size="64" color="grey">mdi-package-variant</v-icon>
        <p class="text-h6 mt-4">No items found</p>
      </div>

      <!-- Vote Dialog -->
      <v-dialog v-model="showVoteDialog" max-width="500px">
        <v-card>
          <v-card-title>Vote for {{ selectedItem?.name }}</v-card-title>
          <v-card-text>
            <v-slider
              v-model="voteValue"
              :min="0.1"
              :max="10"
              :step="0.1"
              label="Credit Rate (ITC/day)"
              thumb-label
              class="mt-4"
            ></v-slider>
            
            <div class="text-h6 text-center my-4">
              {{ voteValue }} ITC/day
            </div>

            <v-textarea
              v-model="voteReason"
              label="Reason for your vote (optional)"
              rows="2"
              variant="outlined"
            ></v-textarea>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn text @click="showVoteDialog = false">Cancel</v-btn>
            <v-btn color="primary" @click="submitVote">Submit Vote</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </PageLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/PageLayout.vue';
import axios from 'axios';

const items = ref([]);
const search = ref('');
const sortBy = ref('name');
const sortOptions = [
  { title: 'Name', value: 'name' },
  { title: 'Current Rate', value: 'current_daily_rate' },
  { title: 'Purchase Value', value: 'purchase_value' },
  { title: 'Vote Count', value: 'vote_count' },
];

const showVoteDialog = ref(false);
const selectedItem = ref(null);
const voteValue = ref(1);
const voteReason = ref('');

const filteredItems = computed(() => {
  let result = [...items.value];
  
  // Filter by search
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    result = result.filter(item => 
      item.name.toLowerCase().includes(searchLower)
    );
  }
  
  // Sort
  result.sort((a, b) => {
    if (sortBy.value === 'name') {
      return a.name.localeCompare(b.name);
    }
    return b[sortBy.value] - a[sortBy.value];
  });
  
  return result;
});

const loadItems = async () => {
  try {
    const response = await axios.get(route('library-catalog'));
    const catalogData = response.data;
    
    // Get items with their access values
    const itemsWithAccess = [];
    
    for (const item of catalogData.items || []) {
      try {
        const [voteResponse, canVoteResponse] = await Promise.all([
          axios.get(route('votes.user', { itemId: item.id })),
          axios.get(route('votes.can-vote', { itemId: item.id })).catch(() => ({ data: { can_vote: true } })),
        ]);
        
        itemsWithAccess.push({
          ...item,
          current_daily_rate: item.current_daily_rate || 1,
          base_credit_value: item.base_credit_value || 1,
          vote_count: item.vote_count || 0,
          user_vote: voteResponse.data.has_vote ? voteResponse.data.vote?.vote_value : null,
          can_vote: canVoteResponse.data.can_vote,
          cooldown_days: canVoteResponse.data.cooldown_days,
        });
      } catch {
        itemsWithAccess.push({
          ...item,
          current_daily_rate: 1,
          base_credit_value: 1,
          vote_count: 0,
          user_vote: null,
          can_vote: true,
          cooldown_days: 0,
        });
      }
    }
    
    items.value = itemsWithAccess;
  } catch (error) {
    console.error('Failed to load items:', error);
    // Fallback to empty
    items.value = [];
  }
};

const openVoteDialog = (item) => {
  selectedItem.value = item;
  voteValue.value = item.user_vote || item.current_daily_rate || 1;
  voteReason.value = '';
  showVoteDialog.value = true;
};

const submitVote = async () => {
  if (!selectedItem.value) return;
  
  try {
    await axios.post(route('votes.store'), {
      item_id: selectedItem.value.id,
      vote_value: voteValue.value,
      reason: voteReason.value,
    });
    
    showVoteDialog.value = false;
    
    // Reload items to get updated data
    await loadItems();
  } catch (error) {
    console.error('Failed to submit vote:', error);
  }
};

onMounted(() => {
  loadItems();
});
</script>

