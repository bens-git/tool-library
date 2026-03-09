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
                    v-if="localItem.thumbnail_url"
                    :src="localItem.thumbnail_url"
                    height="200"
                    cover
                    lazy-src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTIeMmUyIi8+PC9zdmc+"
                    class="bg-grey-lighten-2"
                ></v-img>
                
                <v-card-text>
                    <!-- Title using archetype name -->
                    <div class="text-h6 mb-2">{{ localItem.archetype?.name || 'Item' }}</div>

                    <!-- Time Credits Required -->
                    <div v-if="localItem.access_value?.current_daily_rate" class="mb-4">
                        <v-card variant="tonal" color="info" class="pa-3">
                            <div class="text-subtitle-2 mb-1">Usage Cost</div>
                            <div class="text-h5 font-weight-bold">
                                {{ localItem.access_value.current_daily_rate }}
                                <span class="text-body-2">credits / day</span>
                            </div>
                        </v-card>
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
                        v-if="!isItemRented"
                        color="success"
                        text="Confirm Usage"
                        variant="tonal"
                        @click="confirmUsage"
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
const usageError = ref('');

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
    usageError.value = '';
    
    // Check if item has an active usage
    try {
        const response = await api.get(route('item.is-rented', localItem.value.id));
        // If there's an active usage
        isItemRented.value = response.data.data === true;
    } catch (error) {
        console.error('Error checking usage status:', error);
        isItemRented.value = false;
    }
};

const onOpen = async () => {
    initialize();
};

const onClose = () => {
    isItemRented.value = false;
    usageError.value = '';
};

const confirmUsage = () => {
    dialog.value = false;
    router.post(route('usages.store'), {
        item: localItem.value,
    }, {
        onSuccess: () => {
            // Redirect to My Usages where user can see contact info and message owner
            router.visit('/my-usages');
        },
        onError: (errors) => {
            console.error('Error creating usage:', errors);
            if (errors.message) {
                usageError.value = errors.message;
            }
        }
    });
};
</script>

