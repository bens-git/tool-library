<template>
    <div>
        <!-- Map container with adjusted size -->
        <div id="map" style="height: 500px; width: 100%;"></div>

        <!-- Radius input field -->
        <div class="radius-input">
            <br>
            <v-slider label="Radius (km)" show-ticks="always" tick-size="10" v-model="archetypeStore.radius" step="10"
                thumb-label="always" :max="100" :min="1"></v-slider>
        </div>


    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { GeoSearchControl, OpenStreetMapProvider } from 'leaflet-geosearch';
import 'leaflet-geosearch/dist/geosearch.css';
import axios from 'axios'; // For reverse geocoding requests

import { useArchetypeStore } from '@/stores/archetype.js'; // Your Pinia store

const archetypeStore = useArchetypeStore(); // Access the Pinia store
const address = ref(''); // To store the resolved address
let circleMarker = null; // Store the circle marker for later updates

const reverseGeocode = async (lat, lng) => {
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/reverse`, {
            params: {
                format: 'json',
                lat,
                lon: lng
            }
        });

        const data = response.data;
        if (data && data.address) {
            // Create a readable address from the reverse geocoding result
            const { road, city, state, postcode, country } = data.address;
            const fullAddress = `${road || ''}, ${city || ''}, ${state || ''}, ${postcode || ''}, ${country || ''}`.trim();
            return fullAddress;
        } else {
            return 'Address not found';
        }
    } catch (error) {
        console.error('Error with reverse geocoding:', error);
        return 'Error fetching address';
    }
};


onMounted(() => {
    // Define a default location in case the store location is not set
    const defaultLatLng = [51.505, -0.09];

    // Check if archetypeStore.location is available
    const initialLocation = archetypeStore.location || { lat: defaultLatLng[0], lng: defaultLatLng[1] };

    const addRadius = () => {
        console.log('addradius')
        // Update or create the circle marker
        if (circleMarker) {
            map.removeLayer(circleMarker);
        }

        circleMarker = L.circle([archetypeStore.location.lat, archetypeStore.location.lng], {
            radius: archetypeStore.radius * 1000,
            color: 'blue',
            fillOpacity: 0.2
        }).addTo(map);
    }



    const map = L.map('map').setView([initialLocation.lat, initialLocation.lng], 13);



    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
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
        retainZoomLevel: false
    });

    map.addControl(searchControl);

    addRadius()

    // Event listener for map clicks to select location
    map.on('click', async (e) => {
        const { lat, lng } = e.latlng;

        // Store the clicked location in the Pinia store
        archetypeStore.setLocation({ lat, lng });

        // Fetch address using reverse geocoding
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);
            const data = await response.json();
            const address = data.address ? `${data.address.road || ''}, ${data.address.city || ''}, ${data.address.state || ''}, ${data.address.country || ''}` : 'Address not found';

            // Store the address in the Pinia store
            archetypeStore.setAddress(address);
        } catch (error) {
            console.error('Error fetching address:', error);
            archetypeStore.setAddress('Error fetching address');
        }

        // Reverse geocode the coordinates to get an address
        address.value = await reverseGeocode(lat, lng);

        addRadius()
    });

    // Listen for GeoSearchControl results
    map.on('geosearch/showlocation', async (event) => {
        const { location } = event;
        const { x: lng, y: lat } = location;

        // Update the Pinia store with the new location
        archetypeStore.setLocation({ lat, lng });

        // Reverse geocode the new location
        address.value = await reverseGeocode(lat, lng);
        archetypeStore.setAddress(address.value);

        // Update the map and circle marker
        //     updateMap(lat, lng);
    });

    // Watch the store location for updates from the GeoSearchControl
    watch(() => archetypeStore.location, async (newLocation) => {
        if (newLocation) {
            const { lat, lng } = newLocation;

            // Center the map to the selected location
            map.setView([lat, lng], 13);

            // Reverse geocode the selected location to get an address
            address.value = await reverseGeocode(lat, lng);

            addRadius()
        }
    });

    // Watch the radius for updates and adjust the circle marker accordingly
    watch(
        () => archetypeStore.radius,
        (newRadius) => {
            addRadius()
        }
    );
});
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