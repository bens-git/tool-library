<template>
    <v-dialog v-model="dialog" max-width="700" @open="onOpen">
        <template #activator="{ props: activatorProps }">
            <v-btn
                :color="aim === 'edit' || aim === 'view' ? 'primary' : 'success'"
                :prepend-icon="
                    aim === 'edit' ? 'mdi-pencil' : aim === 'view' ? 'mdi-cart' : 'mdi-plus'
                "
                :text="aim === 'edit' ? 'Edit Item' : aim === 'view' ? 'Rent Item' : 'Create Item'"
                variant="tonal"
                block
                v-bind="activatorProps"
                size="small"
            ></v-btn>
        </template>

        <v-card v-if="localItem">
            <!-- Header with Image -->
            <div v-if="localItem.images?.length || aim !== 'view'" class="item-header">
                <v-carousel
                    v-if="localItem.images?.length"
                    hide-delimiters
                    height="280"
                    show-arrows="hover"
                >
                    <template #placeholder>
                        <div class="d-flex align-center justify-center fill-height bg-grey-lighten-2">
                            <v-progress-circular indeterminate color="grey-lighten-1"></v-progress-circular>
                        </div>
                    </template>
                    <v-carousel-item
                        v-for="(image, index) in localItem.images"
                        :key="index"
                        :src="image.url"
                        cover
                    ></v-carousel-item>
                </v-carousel>
                <v-card-title class="d-flex align-center justify-space-between pb-4">
                    <span class="text-h6">{{ aim === 'view' ? localItem.archetype?.name : (aim === 'edit' ? 'Edit Item' : 'Create Item') }}</span>
                </v-card-title>
            </div>
            <v-card-title v-else class="d-flex align-center justify-space-between">
                <span class="text-h6">{{ aim === 'view' ? localItem.archetype?.name : (aim === 'edit' ? 'Edit Item' : 'Create Item') }}</span>
            </v-card-title>

            <v-card-text class="pt-2">
                <!-- Archetype (edit/create only) -->
                <v-autocomplete
                    v-if="aim !== 'view'"
                    v-model="localItem.archetype"
                    :items="autocompleteArchetypes"
                    label="Archetype"
                    item-title="name"
                    item-value="id"
                    hide-no-data
                    :return-object="true"
                    :readonly="aim === 'view'"
                    :error-messages="formErrors['archetype.id']"
                    @update:search="debouncedAutocompleteArchetypeSearch"
                ></v-autocomplete>

                <ArchetypeDialog
                    v-if="aim !== 'view' && localItem.archetype?.created_by === user.id"
                    aim="edit"
                    :archetype="localItem.archetype"
                    class="mb-2"
                />

                <!-- Unavailable checkbox (edit only) -->
                <v-checkbox
                    v-if="aim === 'edit'"
                    v-model="localItem.make_item_unavailable"
                    label="Make item unavailable"
                    :true-value="1"
                    :false-value="0"
                    class="mb-2"
                ></v-checkbox>

                <!-- Rental Section (View/Rent Mode) -->
                <div v-if="aim === 'view'" class="mt-4">
                    <v-divider class="mb-4"></v-divider>
                    
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

                    <!-- Error Message -->
                    <v-alert 
                        v-if="rentalError" 
                        color="error" 
                        variant="tonal" 
                        class="mb-4"
                        density="compact"
                    >
                        {{ rentalError }}
                    </v-alert>
                </div>

                <!-- File upload (edit/create only) -->
                <v-file-input
                    v-if="aim !== 'view'"
                    label="Upload Image"
                    prepend-icon="mdi-camera"
                    accept="image/*"
                    multiple
                    show-size
                    class="mt-4"
                    @change="handleFileChange"
                ></v-file-input>
            </v-card-text>

            <v-divider></v-divider>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

                <v-btn
                    v-if="aim === 'edit'"
                    color="success"
                    text="Save"
                    variant="tonal"
                    @click="saveItem"
                ></v-btn>

                <v-btn
                    v-if="aim === 'create'"
                    color="success"
                    text="Create"
                    variant="tonal"
                    @click="createItem"
                ></v-btn>

                <v-btn
                    v-if="aim === 'view' && !isItemRented && !localItem.make_item_unavailable"
                    color="success"
                    text="Confirm Rental"
                    variant="tonal"
                    :loading="isConfirmingRental"
                    @click="confirmRental"
                ></v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { shallowRef, ref, watch } from 'vue';
import _ from 'lodash';
import ArchetypeDialog from './ArchetypeDialog.vue';
import { usePage, router } from '@inertiajs/vue3';
import api from '@/services/api';

const dialog = shallowRef(false);
const page = usePage();

const user = page.props.auth.user;

const localItem = ref(null);
const autocompleteArchetypes = ref([]);
const newImages = ref([]);
const formErrors = ref({});

// Rental state
const isItemRented = ref(false);
const rentalError = ref('');
const isConfirmingRental = ref(false);

const props = defineProps({
    item: { type: Object, default: null },
    archetype: { type: Object, default: null },
    aim: { type: String, required: true },
});
const emit = defineEmits(['stored', 'updated']);

// Watch the dialog's state
watch(dialog, (newVal) => {
    if (newVal) {
        onOpen();
    } else {
        onClose();
    }
});

const refreshLocalItem = async () => {
    const response = await api.get(route('items.show', props.item.id));

    localItem.value = response.data.data;
    
    // Check rental status
    await checkRentalStatus();
};

// Check if item has an active rental
const checkRentalStatus = async () => {
    isItemRented.value = false;
    try {
        const response = await api.get(route('item.is-rented', localItem.value.id));
        isItemRented.value = response.data.data === true;
    } catch (error) {
        console.error('Error checking rental status:', error);
        isItemRented.value = false;
    }
};

// Function to initialize
const initialize = () => {
    if ((props.aim == 'edit' || props.aim == 'view') && props.item) {
        refreshLocalItem();
    } else {
        localItem.value = {
            archetype: props.archetype ?? null,
            created_at: props.item?.created_at || new Date().toISOString(),
        };
    }
};

const onOpen = async () => {
    initialize();
    newImages.value = [];
    refreshAutocompleteArchetypes();
};

const onClose = () => {
    isItemRented.value = false;
    rentalError.value = '';
};

const createItem = async () => {
    formErrors.value = {};
    try {
        const response = await api.post(route('items.store'), localItem.value);
        if (response?.success && response.data.id) {
            localItem.value = response.data;

            //add new images
            for (const image of newImages.value) {
                await api.post(route('item-images.store', localItem.value.id), image);
            }
            emit('stored');
            dialog.value = false;
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            formErrors.value = error.response.data.errors;
        }
    }
};

const saveItem = async () => {
    formErrors.value = {};
    try {
        const response = await api.put(route('items.update'), localItem.value);

        //add new images
        if (response?.success) {
            for (const image of newImages.value) {
                //console.log(image)
                await api.post(route('item-images.store', localItem.value.id), image);
            }

            emit('updated');
            dialog.value = false;
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            formErrors.value = error.response.data.errors;
        }
    }
};

const confirmRental = async () => {
    isConfirmingRental.value = true;
    rentalError.value = '';
    
    try {
        await api.post(route('rentals.store'), {
            item: localItem.value,
        });
        
        // Success - close dialog and redirect to My Rentals
        dialog.value = false;
        router.visit('/my-rentals');
    } catch (error) {
        console.error('Error creating rental:', error);
        if (error.response?.data?.message) {
            rentalError.value = error.response.data.message;
        } else if (error.response?.data?.errors) {
            rentalError.value = Object.values(error.response.data.errors).flat().join(', ');
        } else {
            rentalError.value = 'Failed to create rental. Please try again.';
        }
    } finally {
        isConfirmingRental.value = false;
    }
};

const refreshAutocompleteArchetypes = async (query) => {
    if (query) {
        const response = await api.get(route('archetypes.index'), {
            params: { query },
        });
        autocompleteArchetypes.value = response.data.data;
    }
};

// Debounced search function
const debouncedAutocompleteArchetypeSearch = _.debounce(refreshAutocompleteArchetypes, 300);

const handleFileChange = (event) => {
    const files = event.target.files;
    if (files.length) {
        newImages.value.push(...Array.from(files));
    }
};
</script>
