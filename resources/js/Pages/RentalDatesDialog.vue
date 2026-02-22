<template>
    <div class="text-center">
        <v-dialog v-model="dialog" max-width="500" @open="onOpen">
            <template #activator="{ props: activatorProps }">
                <v-btn
                    color="success"
                    class="text-none font-weight-regular"
                    prepend-icon="mdi-check-circle"
                    text="Rent this item"
                    variant="tonal"
                    block
                    v-bind="activatorProps"
                ></v-btn>
            </template>
            
            <v-card v-if="localItem">
                <!-- Item Image -->
                <v-img
                    v-if="localItem.images?.length"
                    :src="localItem.images[0].url"
                    height="200"
                    cover
                    class="bg-grey-lighten-2"
                ></v-img>
                
                <v-card-text>
                    <!-- Title using archetype name -->
                    <div class="text-h6 mb-2">{{ localItem.archetype?.name || 'Item' }}</div>

                    <!-- Time Credits Required -->
                    <div v-if="localItem.access_value?.current_daily_rate" class="mb-4">
                        <v-card variant="tonal" color="info" class="pa-3">
                            <div class="text-subtitle-2 mb-1">Rental Cost</div>
                            <div class="text-h5 font-weight-bold">
                                {{ localItem.access_value.current_daily_rate }}
                                <span class="text-body-2">credits / day</span>
                            </div>
                        </v-card>
                    </div>

                    <!-- Unavailable Alert -->
                    <v-alert 
                        v-if="localItem.make_item_unavailable" 
                        color="warning" 
                        variant="tonal" 
                        class="mb-4"
                        density="compact"
                    >
                        This item is currently marked as unavailable
                    </v-alert>

                    <!-- Availability Status -->
                    <div v-if="!localItem.make_item_unavailable && !isItemRented" class="mb-4">
                        <v-chip color="success" variant="flat">
                            <v-icon start icon="mdi-check"></v-icon>
                            Available
                        </v-chip>
                    </div>

                    <!-- Already Rented Alert -->
                    <v-alert 
                        v-if="isItemRented" 
                        color="error" 
                        variant="tonal" 
                        class="mb-4"
                        density="compact"
                    >
                        This item is currently rented
                    </v-alert>
                </v-card-text>
                
                <v-divider></v-divider>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>
                    <v-btn
                        v-if="!isItemRented && !localItem.make_item_unavailable"
                        color="success"
                        text="Confirm Rental"
                        variant="tonal"
                        @click="confirmRental"
                    ></v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { shallowRef, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import api from '@/services/api';

const dialog = shallowRef(false);

const localItem = ref(null);
const isItemRented = ref(false);
const rentalError = ref('');

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
    localItem.value = { ...props.item };
    isItemRented.value = false;
    rentalError.value = '';
    
    // Check if item has an active rental
    try {
        const response = await api.get(route('item.is-rented', localItem.value.id));
        // If there's an active rental
        isItemRented.value = response.data.data === true;
    } catch (error) {
        console.error('Error checking rental status:', error);
        isItemRented.value = false;
    }
};

const onOpen = async () => {
    initialize();
};

const onClose = () => {
    isItemRented.value = false;
    rentalError.value = '';
};

const confirmRental = async () => {
    try {
        await api.post(route('rentals.store'), {
            item: localItem.value,
        });
        
        dialog.value = false;
        router.visit('/my-rentals');
    } catch (error) {
        console.error('Error creating rental:', error);
        if (error.response?.data?.message) {
            rentalError.value = error.response.data.message;
        }
    }
};
</script>

