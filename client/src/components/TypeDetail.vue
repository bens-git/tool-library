<template>
  <v-container>
    <v-card v-if="localType" style="height:90vh; overflow-y:scroll">
      <v-card-title>
        <span class="headline">{{ localType.name }} -- Available Items</span>
      </v-card-title>

      <v-card-subtitle>
        {{ localType.description }}
      </v-card-subtitle>

      <v-card-text>
        {{ localType.notes }}

        <v-data-iterator v-if="itemStore.items" :items="itemStore.items" :items-per-page="itemsPerPage">
          <template v-slot:header="{ page, pageCount, prevPage, nextPage }">
            <div>
              <v-row>
                <v-col cols="12" md="3">
                  <v-text-field v-model="itemStore.search" placeholder="Search" prepend-inner-icon="mdi-magnify"
                    style="max-width: 300px; min-width:150px" variant="solo" clearable hide-details
                    @input="debounceSearch"></v-text-field>
                </v-col>

                <!-- Date Range Picker -->

                <v-col cols="12" md="4">
                  <v-date-input dense v-model="typeStore.dateRange" label="Dates" prepend-icon="" persistent-placeholder
                    multiple="range" :min="minStartDate" @update:modelValue="debounceSearch"></v-date-input>
                </v-col>




                <v-col cols="12" md="4">
                  <v-btn class="me-8" variant="text" @click="onClickSeeAll">
                    <span class="text-decoration-underline text-none">See all</span>
                  </v-btn>

                  <div class="d-inline-flex">
                    <v-btn :disabled="page === 1" class="me-2" icon="mdi-arrow-left" size="small" variant="tonal"
                      @click="prevPage"></v-btn>

                    <v-btn :disabled="page === pageCount" icon="mdi-arrow-right" size="small" variant="tonal"
                      @click="nextPage"></v-btn>
                  </div>
                </v-col>
              </v-row>
            </div>
          </template>

          <template v-slot:default="{ items }">
            <v-row>
              <v-col v-for="(item, i) in items" :key="i" cols="12" sm="6" xl="3">
                <v-sheet border>

                  <div style="min-height:150px">
                    <div v-if="item.raw.images && item.raw.images.length">
                      <v-carousel v-if="item.raw.images.length > 1" show-arrows hide-delimiter-background height="150">
                        <v-carousel-item v-for="(image, index) in item.raw.images" :key="index">
                          <v-img :gradient="`to top right, rgba(255, 255, 255, .1), rgba(${item.raw.color}, .15)`"
                            :src="fullImageUrl(image.path)" height="150" cover></v-img>
                        </v-carousel-item>
                      </v-carousel>

                      <v-img v-else :gradient="`to top right, rgba(255, 255, 255, .1), rgba(${item.raw.color}, .15)`"
                        :src="fullImageUrl(item.raw.images[0].path)" height="150" cover></v-img>
                    </div>
                  </div>
                  <v-list-item :title="itemStore.itemCode(item)" density="comfortable" lines="two"
                    :subtitle="item.raw.description">
                    <template v-slot:title>
                      <strong class="text-h6">
                        {{ item.raw.item_name }}
                      </strong>
                    </template>
                  </v-list-item>

                  <v-table class="text-caption" density="compact">
                    <tbody>
                      <tr align="right">
                        <th>Location:</th>
                        <td>{{ item.raw.location }}</td>
                      </tr>


                      <tr align="right">
                        <th>Brand:</th>
                        <td>{{ item.raw.brand_name }}</td>
                      </tr>

                      <tr align="right">
                        <th>Serial:</th>
                        <td>{{ item.raw.serial }}</td>
                      </tr>

                      <tr align="right">
                        <th>Purchase Value:</th>
                        <td>${{ item.raw.purchase_value }}</td>
                      </tr>

                      <tr align="right">
                        <th>Purchased At:</th>
                        <td>{{ item.raw.purchased_at }}</td>
                      </tr>

                      <tr align="right">
                        <th>Manufactured At:</th>
                        <td>{{ item.raw.manufactured_at }}</td>
                      </tr>

                      <tr align="right">
                        <th>Owned By:</th>
                        <td>{{ item.raw.owner_name }}</td>
                      </tr>
                    </tbody>
                  </v-table>

                  <div class="d-flex justify-space-between pa-4">
                    <v-btn class="text-none" size="small" text="Rent" border flat
                      :disabled="item.raw.owned_by == userStore.user.id" @click="() => openCalendarDialog(item)">
                      Rent
                    </v-btn>
                  </div>
                </v-sheet>
              </v-col>
            </v-row>
          </template>

          <template v-slot:footer="{ page, pageCount, items }">
            <v-footer class="justify-space-between text-body-2 mt-4" color="surface-variant">
              Total items: {{ items.length }}

              <div>
                Page {{ page }} of {{ pageCount }}
              </div>
            </v-footer>
          </template>
        </v-data-iterator>

      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn text @click="closeDialog">Close</v-btn>
      </v-card-actions>
    </v-card>

    <!-- Calendar Dialog -->
    <v-dialog v-model="showCalendarDialog" max-width="500px">
      <v-card>
        <v-card-title>Select Rental Dates</v-card-title>
        <v-card-text>
          <!-- Date Range Picker -->
          <v-row>
            <v-col cols="12" md="12">
              <v-date-input dense v-model="typeStore.dateRange" label="Dates" prepend-icon="" persistent-placeholder
                multiple="range" :min="minStartDate" @update:modelValue="debounceSearch"></v-date-input>
            </v-col>

          </v-row>

        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showCalendarDialog = false">Cancel</v-btn>
          <v-btn text @click="openConfirmationDialog">Submit</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Confirmation Dialog -->
    <v-dialog v-model="showConfirmationDialog" max-width="500px">
      <v-card>
        <v-card-title>Request Rental</v-card-title>
        <v-card-text>
          <div>Selected Date Range: <br>{{ itemStore.outputReadableDateRange() }}</div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showConfirmationDialog = false">Cancel</v-btn>
          <v-btn text @click="bookRental" color="primary">Book Dates</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useItemStore } from '@/stores/item';
import { useTypeStore } from '@/stores/type';
import { useUserStore } from '@/stores/user';
import { useRouter } from 'vue-router';
const router = useRouter();

const props = defineProps({ type: Object });
const emit = defineEmits(['closeDialog']);
const itemStore = useItemStore();
const typeStore = useTypeStore();
const userStore = useUserStore();

const localType = ref(null);
const localItem = ref(null);
const search = ref("");
const itemsPerPage = ref(2);

const showCalendarDialog = ref(false);
const showConfirmationDialog = ref(false);
const selectedDateRange = ref([]);
const rentedDates = ref([]);

const apiBaseUrl = process.env.VUE_APP_API_HOST;


const fullImageUrl = (imagePath) => {
  return `https://${apiBaseUrl}/${imagePath}`;
};
const setupForm = async () => {

  if (props.type) {
    localType.value = props.type;
    await itemStore.fetchItems(localType.value.id, typeStore.location, typeStore.radius);
  } else {
    localType.value = { name: null };
  }

};

const debounceSearch = _.debounce(() => {
  itemStore.fetchItems(localType.value.id);
}, 300);

const openCalendarDialog = async (item) => {
  localItem.value = item
  await itemStore.fetchItemRentedDates(item.value);
  rentedDates.value = itemStore.rentedDates; // Assuming rentedDates are stored in itemStore
  showCalendarDialog.value = true;
};

const openConfirmationDialog = () => {
  showCalendarDialog.value = false;
  //if no discord link, route to discord auth
  if (!userStore.user.discord_user_id) {
    router.push({ name: "route-to-discord-link" });

    return false
  }
  showConfirmationDialog.value = true;
};

const bookRental = async () => {
  await itemStore.bookRental(localItem.value.value, typeStore.dateRange[0], typeStore.dateRange[typeStore.dateRange.length - 1]);
  showConfirmationDialog.value = false;
  router.push('/my-rentals');
};




// Computed properties for date constraints
const today = new Date();
today.setHours(0, 0, 0, 0);

const startOfDayInMillis = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

const minStartDate = computed(() => today);

const minEndDate = computed(() => {
  if (typeStore.dateRange[0]) {
    return typeStore.dateRange[0];
  }
  return today;
});

const maxStartDate = computed(() => {
  if (typeStore.dateRange[typeStore.dateRange.length - 1]) {
    const endDate = new Date(typeStore.dateRange[typeStore.dateRange.length - 1]);
    endDate.setHours(0, 0, 0, 0); // Set time to 00:00:00
    return endDate;
  }
  return null;
});

// Watchers to ensure dates are correctly updated
watch(() => typeStore.dateRange[0], (newStartDate) => {
  if (newStartDate > typeStore.dateRange[typeStore.dateRange.length - 1]) {
    typeStore.dateRange[typeStore.dateRange.length - 1] = new Date(newStartDate.getTime() + startOfDayInMillis);
  }
});

watch(() => typeStore.dateRange[typeStore.dateRange.length - 1], (newEndDate) => {
  if (newEndDate < typeStore.dateRange[0]) {
    typeStore.dateRange[0] = new Date(newEndDate.getTime() - startOfDayInMillis);
  }
});

const onClickSeeAll = () => {
  itemsPerPage.value = itemsPerPage.value === 2 ? itemStore.items.length : 2;
};

const closeDialog = () => {
  emit("closeDialog");
};

onMounted(() => {
  setupForm();
});
</script>
