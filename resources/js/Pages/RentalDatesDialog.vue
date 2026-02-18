<template>
    <div class="pl-4 text-center">
        <v-dialog v-model="dialog" @open="onOpen">
            <template #activator="{ props: activatorProps }">
                <v-btn
                    color="primary"
                    class="text-none font-weight-regular"
                    prepend-icon="mdi-calendar"
                    text="Rental Dates"
                    variant="tonal"
                    block
                    v-bind="activatorProps"
                ></v-btn>
            </template>
            <v-card
                v-if="localItem"
                prepend-icon="mdi-calendar"
                title="Rental Dates"
                :subtitle="localItem.code"
            >
                <v-card-text>
                    <!-- Date Range Picker -->
                    <v-row>
                        <v-col cols="12" md="12">
                            <v-date-input
                                v-model="archetypeStore.dateRange"
                                dense
                                label="Dates"
                                prepend-icon=""
                                persistent-placeholder
                                multiple="range"
                                :min="minStartDate"
                            ></v-date-input>
                        </v-col>
                    </v-row>

                    <v-alert v-if="localItem.make_item_unavailable" color="error"
                        >Unavailable</v-alert
                    >
                    <div v-if="unavailableDates && unavailableDates.length">
                        <v-row>
                            <v-col v-for="(date, index) in unavailableDates" :key="index" cols="4">
                                <v-chip class="ma-2" color="red lighten-2">
                                    {{ new Date(date).toLocaleDateString() }}
                                </v-chip>
                            </v-col>
                        </v-row>
                    </div>

                    <div v-if="rentedDates && rentedDates.length">
                        <v-row>
                            <v-col v-for="(date, index) in rentedDates" :key="index" cols="4">
                                <v-chip class="ma-2" color="red lighten-2">
                                    {{ new Date(date).toLocaleDateString() }}
                                </v-chip>
                            </v-col>
                        </v-row>
                    </div>
                </v-card-text>
                <v-divider></v-divider>

                <v-card-actions>
                    <v-spacer></v-spacer>

                    <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>
                    <ConfirmRentalDialog :item="localItem" />
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>
<script setup>
import { shallowRef, ref, computed, watch } from 'vue';
import ConfirmRentalDialog from './ConfirmRentalDialog.vue';
import api from '@/services/api';

const dialog = shallowRef(false);

const localItem = ref(null);
const rentedDates = ref([]);
const unavailableDates = ref([]);

// Computed properties for date constraints
const today = new Date();
today.setHours(0, 0, 0, 0);
const minStartDate = computed(() => today);

const props = defineProps({
    item: { type: Object, required: true },
});

// Watch the dialog's state
watch(dialog, (newVal) => {
    if (newVal) {
        onOpen();
    } else {
        onClose();
    }
});

// Function to initialize
const initialize = async () => {
    localItem.value = {
        ...props.item,
    };

    rentedDates.value = api.get(route('item.index-rented-dates', localItem.value.id));
    unavailableDates.value = api.get(route('item.index-unavailable-dates', localItem.value.id));
};

const onOpen = async () => {
    initialize();
};

const onClose = () => {};
</script>
