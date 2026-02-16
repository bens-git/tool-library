<template>
    <v-dialog v-model="dialog" @open="onOpen">
        <template #activator="{ props: activatorProps }">
            <v-text-field
                v-model="localAddress"
                density="compact"
                variant="outlined"
                label="Select location"
                prepend-inner-icon="mdi-map-marker"
                readonly
                v-bind="activatorProps"
                @click="activatorProps.onClick"
            ></v-text-field>
        </template>
        <v-card prepend-icon="mdi-map-marker" title="Select Location on Map">
            <v-card-text>
                <!-- Map container with adjusted size -->
                <div ref="mapContainer" style="height: 500px; width: 100%"></div>

                <!-- Radius input field -->
                <div class="radius-input">
                    <br />
                    <v-slider
                        v-model="radius"
                        label="Radius (km)"
                        show-ticks="always"
                        tick-size="10"
                        step="10"
                        thumb-label="always"
                        :max="100"
                        :min="1"
                    ></v-slider>
                </div>

                {{ localAddress }}
            </v-card-text>
            <v-divider></v-divider>

            <v-card-actions>
                <v-spacer></v-spacer>

                <v-btn text="Close" variant="plain" @click="dialog = false"></v-btn>

                <v-btn color="primary" text="Okay" variant="tonal" @click="setLocation()"></v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
<script setup>
import { shallowRef, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { GeoSearchControl, OpenStreetMapProvider } from 'leaflet-geosearch';
import 'leaflet-geosearch/dist/geosearch.css';
import axios from 'axios'; // For reverse geocoding requests
import { nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const dialog = shallowRef(false);

// Watch the dialog's state
watch(dialog, (newVal) => {
    if (newVal) {
        onOpen();
    } else {
        onClose();
    }
});

const onClose = () => {};

const localAddress = ref(
    user.location.city + ', ' + user.location.state + ', ' + user.location.country
);

let circleMarker = null; // Store the circle marker for later updates

const localLocation = ref(null);

const radius = ref(10);

const reverseGeocode = async (lat, lng) => {
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/reverse`, {
            params: {
                format: 'json',
                lat,
                lon: lng,
            },
        });

        const data = response.data;
        if (data && data.address) {
            // Create a readable address from the reverse geocoding result
            const { town, city, state, country } = data.address;
            const fullAddress = `${(city ?? town) || ''}, ${state || ''}, ${country || ''}`.trim();
            return fullAddress;
        } else {
            return 'Address not found';
        }
    } catch {
        return 'Error fetching address';
    }
};

const mapContainer = ref(null);

const onOpen = async () => {
    await nextTick(); // Ensures the DOM is updated before accessing it
    // Define a default location in case the store location is not set

    localAddress.value = user
        ? user.location.city + ', ' + user.location.state + ', ' + user.location.country
        : '';

    if (!localLocation.value) {
        localLocation.value = {
            lat: user.location.latitude,
            lng: user.location.longitude,
        };
    }

    const addRadius = () => {
        // Update or create the circle marker
        if (circleMarker) {
            map.removeLayer(circleMarker);
        }

        circleMarker = L.circle([localLocation.value.lat, localLocation.value.lng], {
            radius: radius.value * 1000,
            color: 'blue',
            fillOpacity: 0.2,
        }).addTo(map);
    };

    const map = L.map(mapContainer.value).setView(
        [localLocation.value.lat, localLocation.value.lng],
        13
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Initialize the geo-search control
    const provider = new OpenStreetMapProvider();
    const searchControl = new GeoSearchControl({
        provider: provider,
        style: 'bar',
        autoComplete: true,
        autoCompleteDelay: 250,
        searchLabel: 'Enter address',
        placeholder: 'Search for an address',
        retainZoomLevel: false,
    });

    map.addControl(searchControl);

    addRadius();

    // Event listener for map clicks to select location
    map.on('click', async (e) => {
        const { lat, lng } = e.latlng;

        // Store the clicked location in the Pinia store
        localLocation.value = { lat, lng };

        // Fetch address using reverse geocoding

        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`
        );
        const data = await response.json();
        const newAddress = data.address
            ? `${data.address.road || ''}, ${data.address.city || ''}, ${data.address.state || ''}, ${data.address.country || ''}`
            : 'Address not found';

        localAddress.value = newAddress;
        // Store the address in the Pinia store

        // Reverse geocode the coordinates to get an address
        localAddress.value = await reverseGeocode(lat, lng);

        addRadius();
    });

    // Listen for GeoSearchControl results
    map.on('geosearch/showlocation', async (event) => {
        const { location } = event;
        const { x: lng, y: lat } = location;

        // Update the Pinia store with the new location
        localLocation.value = { lat, lng };

        // Reverse geocode the new location
        localAddress.value = await reverseGeocode(lat, lng);

        // Update the map and circle marker
        //     updateMap(lat, lng);
    });

    // Watch the store location for updates from the GeoSearchControl
    watch(
        () => localLocation.value,
        async (newLocation) => {
            if (newLocation) {
                const { lat, lng } = newLocation;

                // Center the map to the selected location
                map.setView([lat, lng], 13);

                // Reverse geocode the selected location to get an address
                localAddress.value = await reverseGeocode(lat, lng);

                addRadius();
            }
        }
    );

    // Watch the radius for updates and adjust the circle marker accordingly
    watch(
        () => radius.value,
        () => {
            addRadius();
        }
    );
};

const emit = defineEmits(['setLocation']);

const setLocation = () => {
    emit('setLocation', localLocation.value, localAddress.value, radius.value);

    dialog.value = false;
};
</script>

<style>
#map {
    height: 500px;
    width: 100%;
}

.radius-input {
    margin: 10px 0;
}

.radius-input input {
    padding: 5px;
    font-size: 1em;
}

.selected-address {
    margin: 10px 0;
}

/* Ensure the GeoSearchControl container is styled correctly */
.leaflet-control-geosearch {
    color: black !important;
    background-color: white !important;
}

/* Style input fields within GeoSearchControl */
.leaflet-control-geosearch input {
    color: black !important;
    background-color: white !important;
}

/* Ensure placeholder text is styled */
.leaflet-control-geosearch input::placeholder {
    color: black !important;
}

/* Target inner elements if needed */
.leaflet-control-geosearch .some-inner-class {
    color: black !important;
}
</style>
