<template>
  <v-container class="d-flex justify-center">
    <v-card
      title="Materials & Tools"
      flat
      style="min-width: 90vw; max-height: 88vh; min-height: 88vh"
    >
      <v-card-text>
        <v-row>
          <!-- Search Field -->
          <v-col>
            <v-text-field
              density="compact"
              v-model="archetypeStore.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
              @input="debounceSearch"
            />
          </v-col>

          <!-- Archetype -->
          <v-col v-if="!mobile">
            <v-autocomplete
              density="compact"
              v-model="archetypeStore.selectedArchetypeId"
              :items="autocompleteArchetypes"
              label="Archetype"
              item-title="name"
              item-value="id"
              hide-no-data
              hide-details
              return-object
              clearable
              @update:model-value="debounceSearch"
              @update:search="debouncedAutocompleteArchetypeSearch"
            ></v-autocomplete>
          </v-col>

          <!-- Category -->
          <v-col v-if="!mobile">
            <v-autocomplete
              density="compact"
              v-model="archetypeStore.selectedCategoryId"
              :items="categoryStore.categories"
              label="Category"
              item-title="name"
              item-value="id"
              :clearable="true"
              @update:modelValue="debounceSearch"
            />
          </v-col>

          <!-- Usage -->
          <v-col v-if="!mobile">
            <v-autocomplete
              density="compact"
              v-model="archetypeStore.selectedUsageId"
              :items="usageStore.usages"
              label="Usage"
              item-title="name"
              item-value="id"
              :clearable="true"
              @update:modelValue="debounceSearch"
            />
          </v-col>

          <!-- Brand -->
          <v-col v-if="!mobile">
            <v-autocomplete
              density="compact"
              v-model="archetypeStore.selectedBrandId"
              :items="autocompleteBrands"
              label="Brand"
              item-title="name"
              item-value="id"
              hide-no-data
              hide-details
              return-object
              clearable
              @update:model-value="debounceSearch"
              @update:search="debouncedAutocompleteBrandSearch"
            ></v-autocomplete>
          </v-col>

          <v-col v-if="!mobile">
            <v-date-input
              density="compact"
              v-model="archetypeStore.dateRange"
              label="Dates"
              prepend-icon=""
              persistent-placeholder
              multiple="range"
              :min="minStartDate"
              @update:modelValue="debounceSearch"
            ></v-date-input>
          </v-col>

          <!-- Location Picker -->
          <v-col>
            <v-text-field
              density="compact"
              label="Select location"
              v-model="archetypeStore.address"
              readonly
              @click="openLocationPicker"
            ></v-text-field>
          </v-col>

          <!-- Location Picker Dialog -->
          <v-dialog v-model="locationPickerDialog" max-width="600px">
            <v-card>
              <v-card-title>Select Location on Map</v-card-title>
              <v-card-text>
                <!-- Use a map component for location selection -->
                <LocationPicker @location-selected="handleLocationSelected" />
              </v-card-text>
              <v-card-actions>
                <v-btn color="primary" @click="locationPickerDialog = false"
                  >OK</v-btn
                >
              </v-card-actions>
            </v-card>
          </v-dialog>
          <!-- Reset Button -->
          <v-col>
            <v-btn
              variant="tonal"
              class="text-none font-weight-regular"
              color="primary"
              @click="archetypeStore.resetFilters"
            >
              <v-icon>mdi-refresh</v-icon>
            </v-btn>
          </v-col>
        </v-row>

        <v-data-table-server
          v-model:items-per-page="archetypeStore.itemsPerPage"
          :headers="headers"
          :items="archetypeStore.archetypesWithItems"
          :items-length="archetypeStore.totalArchetypesWithItems"
          loading-text="Loading... Please wait"
          :search="archetypeStore.search"
          item-value="id"
          @update:options="archetypeStore.updateArchetypesWithItemsOptions"
          mobile-breakpoint="sm"
          fixed-header
          :height="'60vh'"
        >
          <!-- Actions column -->
          <template v-slot:[`item.actions`]="{ item }">
            <ArchetypeItemsDialog :archetype="item" v-if="userStore.user" />
            <LoginDialog  v-else/>
           
          </template>

          <!-- Image column -->
          <template v-slot:[`item.image`]="{ item }">
            <v-img
              v-if="item.images?.length > 0"
              :src="fullImageUrl(item.images[0].path)"
              max-height="200"
              max-width="200"
              min-height="200"
              min-width="200"
              alt="Archetype Image"
            ></v-img>
            <v-icon v-else>mdi-image-off</v-icon>
            <!-- Fallback icon if no image is available -->
          </template>

          <!-- Locations column -->
          <template v-slot:[`item.locations`]="{ item }">
            <div v-html="formatLocation(item.locations)"></div>
          </template>

          <!--units-->
          <template v-slot:[`item.item_count`]="{ item }">
            <div v-if="item.available_item_count">
              {{
                item.available_item_count -
                (item.rented_item_count ? item.rented_item_count : 0)
              }}
            </div>
            <div v-if="item.rented_item_count">
              {{ item.rented_item_count }} (rented)
            </div>
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useCategoryStore } from "@/stores/category";
import { useUsageStore } from "@/stores/usage";
import { useBrandStore } from "@/stores/brand";
import { useArchetypeStore } from "@/stores/archetype";
import { useUserStore } from "@/stores/user";
import { useLocationStore } from "@/stores/location";
import _ from "lodash";
import ArchetypeItemsDialog from "./ArchetypeItemsDialog.vue";
import { useRouter } from "vue-router";
import LocationPicker from "./LocationPicker.vue"; // Import your location picker component
import debounce from "lodash/debounce";
import useApi from "@/stores/api";
import { useDisplay } from "vuetify";
import LoginDialog from "./LoginDialog.vue";

const { mobile } = useDisplay();

const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const brandStore = useBrandStore();
const archetypeStore = useArchetypeStore();
const userStore = useUserStore();
const locationStore = useLocationStore();
const router = useRouter();

const latitude = ref(null);
const longitude = ref(null);
const locationPickerDialog = ref(false);
const autocompleteArchetypes = ref([]);
const autocompleteBrands = ref([]);

const openLocationPicker = () => {
  locationPickerDialog.value = true;
};

const handleLocationSelected = ({ lat, lng }) => {
  latitude.value = lat;
  longitude.value = lng;
  locationPickerDialog.value = false;
};

const { fullImageUrl } = useApi();

const dialog = ref(false);
const selectedArchetype = ref(null);

const headers = [
  {
    title: "Image",
    align: "start",
    sortable: false,
    key: "image",
  },
  {
    title: "Actions",
    align: "start",
    sortable: false,
    key: "actions",
  },
  {
    title: "Archetype",
    align: "start",
    sortable: true,
    key: "name",
  },
  {
    title: "Description",
    align: "start",
    sortable: false,
    key: "description",
  },
  {
    title: "Categories",
    align: "start",
    sortable: false,
    key: "categories",
  },
  {
    title: "Usages",
    align: "start",
    sortable: false,
    key: "usages",
  },
  {
    title: "Brands",
    align: "start",
    sortable: false,
    key: "brand_names",
  },
  {
    title: "Items",
    align: "start",
    sortable: false,
    key: "item_count",
  },
  {
    title: "Locations",
    align: "start",
    sortable: false,
    key: "locations",
  },
];

// Autocomplete Archetype Search handler
const onAutocompleteArchetypeSearch = async (query) => {
  autocompleteArchetypes.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};
const onAutocompleteBrandSearch = async (query) => {
  autocompleteBrands.value =
    await brandStore.fetchAutocompleteSelectBrands(query);
};

const debouncedAutocompleteArchetypeSearch = debounce(
  onAutocompleteArchetypeSearch,
  300
);
const debouncedAutocompleteBrandSearch = debounce(
  onAutocompleteBrandSearch,
  300
);

const debounceSearch = _.debounce(() => {
  archetypeStore.fetchArchetypesWithItems();
}, 300);

const editArchetype = (archetype) => {
  selectedArchetype.value = archetype;
  dialog.value = true;
};



// Computed properties for date constraints
const today = new Date();
today.setHours(0, 0, 0, 0);

const startOfDayInMillis = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

const minStartDate = computed(() => today);

// Watchers to ensure dates are correctly updated
watch(
  () => archetypeStore.dateRange[0],
  (newStartDate) => {
    if (
      newStartDate >
      archetypeStore.dateRange[archetypeStore.dateRange.length - 1]
    ) {
      archetypeStore.dateRange[archetypeStore.dateRange.length - 1] = new Date(
        newStartDate.getTime() + startOfDayInMillis
      );
    }
  }
);

watch(
  () => archetypeStore.dateRange[archetypeStore.dateRange.length - 1],
  (newEndDate) => {
    if (newEndDate < archetypeStore.dateRange[0]) {
      archetypeStore.dateRange[0] = new Date(
        newEndDate.getTime() - startOfDayInMillis
      );
    }
  }
);

// Watch the store location for updates from the GeoSearchControl
watch(
  () => archetypeStore.location,
  async (newLocation) => {
    if (newLocation) {
      // Call fetchArchetypes whenever location changes
      archetypeStore.fetchArchetypesWithItems();
    }
  }
);

watch(
  () => archetypeStore.radius,
  async (newRadius) => {
    if (newRadius) {
      // Call fetchArchetypes whenever location changes
      archetypeStore.fetchArchetypesWithItems();
    }
  }
);

const formatLocation = (locations) => {
  if (!locations) return "No Location Available";

  // Split by semicolon to handle multiple addresses
  const locationArray = locations
    .split("; ")
    .map((location) => location.trim());

  // Generate a list of URLs for each location, separated by <hr>
  const locationLinks = locationArray
    .map(
      (location) =>
        `<a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location)}" target="_blank">${location}</a>`
    )
    .join("<hr>");

  return `
        <div>
          ${locationLinks}
        </div>
      `;
};

const initializeLocation = async () => {
  if (userStore.user) {
    var location = await locationStore.fetchUserLocation();
  }

  if (!location) {
    var location = {
      city: "Brantford",
      country: "Canada",
      latitude: "43.1389629",
      longitude: "-80.2678869",
      state: "ON",
    };
  }
  archetypeStore.setLocation({
    lat: location.latitude,
    lng: location.longitude,
  });
  archetypeStore.setAddress(
    location.city + " " + location.state + " " + location.country
  );
};

onMounted(async () => {
  initializeLocation();
  categoryStore.fetchCategories();
  usageStore.fetchUsages();
  autocompleteArchetypes.value =
    await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteBrands.value = await brandStore.fetchAutocompleteSelectBrands();
});
</script>

<style>
.custom-dialog .v-overlay__content {
  pointer-events: none;
}

.custom-dialog .v-card {
  pointer-events: auto;
}
</style>
