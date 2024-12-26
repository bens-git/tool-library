<template>
    <v-container class="d-flex justify-center">
      <v-card title="Projects" flat style="min-width:90vw; min-height:90vh;">
  
        <template v-slot:text>
          <v-row>
            <!-- Search Field -->
            <v-col cols="12" md="3">
              <v-text-field density="compact" v-model="jobStore.search" label="Search" prepend-inner-icon="mdi-magnify"
                variant="outlined" hide-details single-line @input="debounceSearch" />
            </v-col>
  
            <!-- Project -->
            <v-col cols="12" md="4">
              <v-autocomplete density="compact" v-model="jobStore.selectedProjectId" :items="jobStore.projects"
                label="Project" item-title="name" item-value="id" :clearable="true" @update:modelValue="debounceSearch" />
            </v-col>
  
            <!-- Material -->
            <v-col cols="12" md="4">
              <v-autocomplete density="compact" v-model="jobStore.selectedMaterialId" :items="jobStore.materials"
                label="Material" item-title="name" item-value="id" :clearable="true"
                @update:modelValue="debounceSearch" />
            </v-col>

            
            <!-- Reset Button -->
            <v-col cols="12" md="1" class="d-flex align-center">
              <v-btn icon color="primary" @click="typeStore.resetFilters" class="mt-2">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-col>
  
          </v-row>
         
        </template>
        <v-data-table-server v-model:items-per-page="typeStore.itemsPerPage" :headers="headers"
          :items="typeStore.paginatedTypes" :items-length="typeStore.totalTypes" loading-text="Loading... Please wait"
          :search="typeStore.search" item-value="id" @update:options="typeStore.updateOptions" mobile-breakpoint="sm">
  
          <!-- Image column -->
          <template v-slot:[`item.image`]="{ item }">
            <v-img v-if="item.images.length > 0" :src="fullImageUrl(item.images[0].path)" max-height="200" max-width="200"
              min-height="200" min-width="200" alt="Type Image"></v-img>
            <v-icon v-else>mdi-image-off</v-icon> <!-- Fallback icon if no image is available -->
          </template>
  
          <!-- Locations column -->
          <template v-slot:[`item.locations`]="{ item }">
            <div v-html="formatLocation(item.locations)"></div>
          </template>
  
          <!--units-->
          <template v-slot:[`item.item_count`]="{ item }">
            <div v-if="item.available_item_count">{{
              item.available_item_count - (item.rented_item_count ? item.rented_item_count : 0) }}</div>
            <div v-if="item.rented_item_count">{{ item.rented_item_count }} (rented)</div>
          </template>
  
          <template v-slot:[`item.actions`]="{ item }">
            <v-btn icon @click="editType(item)" v-if="userStore.user">
              <v-icon>mdi-information</v-icon>
            </v-btn>
  
            <v-btn icon @click="goToLogin" v-else>
              <v-icon>mdi-login</v-icon>
            </v-btn>
          </template>
        </v-data-table-server>
      </v-card>
    </v-container>
  
    <!-- Dialog for item details -->
    <v-dialog v-model="dialog" :persistent="false" class="custom-dialog">
      <TypeDetail :type="selectedType" :action="'details'" v-on:closeDialog="dialog = false" />
    </v-dialog>
  </template>
  
  <script setup>
  import { ref, computed, watch, onMounted } from 'vue';
  import { useCategoryStore } from '@/stores/category';
  import { useUsageStore } from '@/stores/usage';
  import { useBrandStore } from '@/stores/brand';
  import { useTypeStore } from '@/stores/type';
  import { useUserStore } from '@/stores/user';
  import { useLocationStore } from '@/stores/location';
  import _ from 'lodash';
  import TypeDetail from './TypeDetail.vue';
  import { useRouter } from 'vue-router';
  import LocationPicker from './LocationPicker.vue'; // Import your location picker component
  
  const categoryStore = useCategoryStore();
  const usageStore = useUsageStore();
  const brandStore = useBrandStore();
  const typeStore = useTypeStore();
  const userStore = useUserStore();
  const locationStore = useLocationStore();
  const router = useRouter();
  
  const radius = ref(10); // Default radius in kilometers
  const latitude = ref(null);
  const longitude = ref(null);
  const locations = ref([]);
  const locationPickerDialog = ref(false);
  
  const openLocationPicker = () => {
    locationPickerDialog.value = true;
  };
  
  const handleLocationSelected = ({ lat, lng }) => {
    latitude.value = lat;
    longitude.value = lng;
    locationPickerDialog.value = false;
  };
  
  const apiHost = process.env.VUE_APP_API_HOST;
  const environment = process.env.VUE_APP_ENVIRONMENT;
  
  const baseURL =
    environment == "development"
      ? `http://${apiHost}`
      : `https://${apiHost}`;
  
  const fullImageUrl = (imagePath) => {
  
    return `${baseURL}/${imagePath}`;
  };
  
  const dialog = ref(false);
  const selectedType = ref(null);
  
  const headers = [
    {
      title: 'Image',
      align: 'start',
      sortable: false,
      key: 'image',
    },
    {
      title: 'Actions',
      align: 'start',
      sortable: false,
      key: 'actions',
    },
    {
      title: 'Type',
      align: 'start',
      sortable: true,
      key: 'name',
    },
    {
      title: 'Description',
      align: 'start',
      sortable: false,
      key: 'description',
    },
    {
      title: 'Categories',
      align: 'start',
      sortable: false,
      key: 'categories',
    },
    {
      title: 'Usages',
      align: 'start',
      sortable: false,
      key: 'usages',
    },
    {
      title: 'Brands',
      align: 'start',
      sortable: false,
      key: 'brand_names',
    },
    {
      title: 'Items',
      align: 'start',
      sortable: false,
      key: 'item_count',
    },
    {
      title: 'Locations',
      align: 'start',
      sortable: false,
      key: 'locations',
    },
  ];
  
  
  
  const debounceSearch = _.debounce(() => {
    typeStore.fetchPaginatedTypes();
  }, 300);
  
  const editType = (type) => {
    selectedType.value = type;
    dialog.value = true;
  };
  
  const goToLogin = () => {
    router.push({ name: 'login-form' }); // Adjust the route name as necessary
  };
  
  // Computed properties for date constraints
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  const startOfDayInMillis = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
  
  const minStartDate = computed(() => today);
  
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
  
  // Watch the store location for updates from the GeoSearchControl
  watch(() => typeStore.location, async (newLocation) => {
    if (newLocation) {
      // Call fetchPaginatedTypes whenever location changes
      typeStore.fetchPaginatedTypes();
    }
  });
  
  watch(() => typeStore.radius, async (newRadius) => {
    if (newRadius) {
      // Call fetchPaginatedTypes whenever location changes
      typeStore.fetchPaginatedTypes();
    }
  })
  
  const formatLocation = (locations) => {
    if (!locations) return 'No Location Available';
  
    // Split by semicolon to handle multiple addresses
    const locationArray = locations.split('; ').map(location => location.trim());
  
    // Generate a list of URLs for each location, separated by <hr>
    const locationLinks = locationArray
      .map(location => `<a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location)}" target="_blank">${location}</a>`)
      .join('<hr>');
  
    return `
          <div>
            ${locationLinks}
          </div>
        `;
  }
  
  
  const initializeLocation = async () => {
    if (userStore.user) {
      var location = await locationStore.fetchUserLocation()
    }
    else {
      var location = {
        city: "Brantford",
        country: "Canada",
        latitude: "43.1389629",
        longitude: "-80.2678869",
        state: "ON",
  
      }
    }
    typeStore.setLocation({ lat: location.latitude, lng: location.longitude })
    typeStore.setAddress(location.city + ' ' + location.state + ' ' + location.country)
  
  }
  
  onMounted(async () => {

  })
  </script>
  
  <style>
  .custom-dialog .v-overlay__content {
    pointer-events: none;
  }
  
  .custom-dialog .v-card {
    pointer-events: auto;
  }
  </style>
  