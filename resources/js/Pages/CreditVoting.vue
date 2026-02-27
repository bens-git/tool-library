<template>
  <PageLayout title="Vote on Credit Rates">
    <Head title="Vote on Credit Rates" />

    <v-container>
      <v-alert type="info" class="mb-4">
        Participate in community voting to set credit rates for tool types (archetypes). 
        You earn bonus credits for voting! Vote on how many credits it should cost to borrow each type of tool.
      </v-alert>

      <!-- Search and Filter -->
      <v-row class="mb-4">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="search"
            label="Search archetypes..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            hide-details
            @update:model-value="resetAndSearch"
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

      <!-- Archetypes Grid -->
      <v-row>
        <v-col v-for="archetype in filteredArchetypes" :key="archetype.id" cols="12" md="6" lg="4">
          <v-card>
            <v-card-title>{{ archetype.name }}</v-card-title>
            <v-card-subtitle v-if="archetype.resource">
              Type: {{ archetype.resource }}
            </v-card-subtitle>
            <v-card-text>
              <div class="mb-2">
                <strong>Current Rate:</strong> 
                <span class="text-primary">{{ archetype.current_daily_rate }} ITC/day</span>
              </div>
              <div class="mb-2">
                <strong>Base Rate:</strong> 
                {{ archetype.base_credit_value || 1 }} ITC/day
              </div>
              <div class="mb-2">
                <strong>Votes:</strong> {{ archetype.vote_count || 0 }}
              </div>
              
              <!-- User's Vote -->
              <div v-if="archetype.user_vote" class="mb-2">
                <v-chip color="success" size="small">
                  Your vote: {{ archetype.user_vote }} ITC/day
                </v-chip>
              </div>

              <!-- Vote Button -->
              <v-btn
                v-if="!archetype.can_vote"
                color="grey"
                size="small"
                disabled
              >
                Cooldown: {{ archetype.cooldown_days }} days
              </v-btn>
              <v-btn
                v-else
                color="primary"
                size="small"
                @click="openVoteDialog(archetype)"
              >
                {{ archetype.user_vote ? 'Change Vote' : 'Vote' }}
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Load More / Infinite Scroll -->
      <div ref="loadTrigger" class="text-center pa-4">
        <v-progress-circular
          v-if="loading"
          indeterminate
          color="primary"
        ></v-progress-circular>
        <p v-else-if="hasMore" class="text-grey">
          Scroll for more...
        </p>
        <p v-else-if="archetypes.length" class="text-grey">
          No more archetypes
        </p>
      </div>

      <!-- No Archetypes Message -->
      <div v-if="!filteredArchetypes.length && !loading" class="text-center pa-8">
        <v-icon size="64" color="grey">mdi-package-variant</v-icon>
        <p class="text-h6 mt-4">No archetypes found</p>
      </div>

      <!-- Vote Dialog -->
      <v-dialog v-model="showVoteDialog" max-width="500px">
        <v-card>
          <v-card-title>Vote for {{ selectedArchetype?.name }}</v-card-title>
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
/* global IntersectionObserver */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/PageLayout.vue';
import axios from 'axios';

const archetypes = ref([]);
const search = ref('');
const sortBy = ref('name');
const sortOptions = [
  { title: 'Name', value: 'name' },
  { title: 'Current Rate', value: 'current_daily_rate' },
];

// Pagination
const page = ref(1);
const itemsPerPage = 20;
const loading = ref(false);
const hasMore = ref(true);
const loadTrigger = ref(null);
let observer = null;

const showVoteDialog = ref(false);
const selectedArchetype = ref(null);
const voteValue = ref(1);
const voteReason = ref('');

const filteredArchetypes = computed(() => {
  let result = [...archetypes.value];
  
  // Sort (applied to loaded items)
  result.sort((a, b) => {
    if (sortBy.value === 'name') {
      return a.name.localeCompare(b.name);
    }
    return b[sortBy.value] - a[sortBy.value];
  });
  
  return result;
});

const resetAndSearch = () => {
  // Debounce search - reset and reload
  archetypes.value = [];
  page.value = 1;
  hasMore.value = true;
  loadArchetypes();
};

const loadArchetypes = async () => {
  if (loading.value || !hasMore.value) return;
  
  loading.value = true;
  
  try {
    const response = await axios.get(route('archetypes.index'), {
      params: {
        page: page.value,
        itemsPerPage: itemsPerPage,
        search: search.value || undefined,
      }
    });
    
    const newArchetypes = response.data.data || [];
    const totalItems = response.data.total || 0;
    
    // Get user votes and can-vote status for new archetypes
    const archetypesWithVotes = await Promise.all(
      newArchetypes.map(async (archetype) => {
        try {
          const [voteResponse, canVoteResponse] = await Promise.all([
            axios.get(route('votes.user', { archetypeId: archetype.id })),
            axios.get(route('votes.can-vote', { archetypeId: archetype.id })).catch(() => ({ data: { can_vote: true } })),
          ]);
          
          return {
            id: archetype.id,
            name: archetype.name,
            resource: archetype.resource,
            current_daily_rate: archetype.current_daily_rate || 1,
            base_credit_value: archetype.base_credit_value || 1,
            vote_count: archetype.vote_count || 0,
            user_vote: voteResponse.data.has_vote ? voteResponse.data.vote?.vote_value : null,
            can_vote: canVoteResponse.data.can_vote,
            cooldown_days: canVoteResponse.data.cooldown_days || 0,
          };
        } catch {
          return {
            id: archetype.id,
            name: archetype.name,
            resource: archetype.resource,
            current_daily_rate: 1,
            base_credit_value: 1,
            vote_count: 0,
            user_vote: null,
            can_vote: true,
            cooldown_days: 0,
          };
        }
      })
    );
    
    // Append new archetypes
    archetypes.value = [...archetypes.value, ...archetypesWithVotes];
    
    // Check if there are more items
    hasMore.value = archetypes.value.length < totalItems;
    page.value++;
    
  } catch (error) {
    console.error('Failed to load archetypes:', error);
  } finally {
    loading.value = false;
  }
};

const setupIntersectionObserver = () => {
  if (observer) observer.disconnect();
  
  observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && !loading.value && hasMore.value) {
      loadArchetypes();
    }
  }, { threshold: 0.1 });
  
  if (loadTrigger.value) {
    observer.observe(loadTrigger.value);
  }
};

const openVoteDialog = (archetype) => {
  selectedArchetype.value = archetype;
  voteValue.value = archetype.user_vote || archetype.current_daily_rate || 1;
  voteReason.value = '';
  showVoteDialog.value = true;
};

const submitVote = async () => {
  if (!selectedArchetype.value) return;
  
  try {
    await axios.post(route('votes.store'), {
      archetype_id: selectedArchetype.value.id,
      vote_value: voteValue.value,
      reason: voteReason.value,
    });
    
    showVoteDialog.value = false;
    
    // Update the voted archetype in the list
    const index = archetypes.value.findIndex(a => a.id === selectedArchetype.value.id);
    if (index !== -1) {
      archetypes.value[index].user_vote = voteValue.value;
      archetypes.value[index].can_vote = false;
      archetypes.value[index].cooldown_days = 7;
    }
  } catch (error) {
    console.error('Failed to submit vote:', error);
  }
};

onMounted(() => {
  loadArchetypes();
  setupIntersectionObserver();
});

onUnmounted(() => {
  if (observer) observer.disconnect();
});
</script>

