<template>
    <v-dialog v-model="dialog" max-width="700" @open="onOpen">
        <template #activator="{ props: activatorProps }">
            <v-tooltip v-if="iconOnly" location="top">
                <template #activator="{ props: tooltipProps }">
                    <v-btn
                        :color="buttonColor"
                        variant="tonal"
                        :size="size"
                        v-bind="{ ...activatorProps, ...tooltipProps }"
                    >
                        <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                </template>
                <span>Edit</span>
            </v-tooltip>
            <v-btn
                v-else
                :color="buttonColor"
                :prepend-icon="actionButtonIcon"
                :text="actionButtonText"
                variant="tonal"
                v-bind="activatorProps"
                :size="size"
            ></v-btn>
        </template>

        <v-card v-if="localItem">
            <!-- Header with Image -->
            <div v-if="localItem.thumbnail_url || aim !== 'view'" class="item-header">
                <v-img
                    v-if="localItem.thumbnail_url"
                    :src="localItem.thumbnail_url"
                    height="280"
                    cover
                >
                    <template #placeholder>
                        <div class="d-flex align-center justify-center fill-height bg-grey-lighten-2">
                            <v-progress-circular indeterminate color="grey-lighten-1"></v-progress-circular>
                        </div>
                    </template>
                </v-img>
                <v-card-title class="d-flex align-center justify-space-between pb-4">
                    <span class="text-h6">{{ aim === 'view' ? localItem.archetype?.name : (aim === 'edit' ? 'Edit Item' : 'Create Item') }}</span>
                </v-card-title>
            </div>
            <v-card-title v-else class="d-flex align-center justify-space-between">
                <span class="text-h6">{{ aim === 'view' ? localItem.archetype?.name : (aim === 'edit' ? 'Edit Item' : 'Create Item') }}</span>
            </v-card-title>

            <!-- Wizard Steps for Create Mode -->
            <div v-if="aim === 'create'" class="wizard-container">
                <!-- Step Indicator -->
                <v-tabs v-model="createStep" align-tabs="center" color="primary" class="mb-4">
                    <v-tab :value="1">1. Resource Type</v-tab>
                    <v-tab :value="2">2. Archetype</v-tab>
                    <v-tab :value="3">3. Details</v-tab>
                </v-tabs>

                <!-- Step 1: Resource Type -->
                <v-card-text v-if="createStep === 1">
                    <div class="text-subtitle-1 mb-4">Select Resource Type</div>
                    <v-select
                        v-model="selectedResourceType"
                        :items="resourceOptions"
                        label="Resource Type"
                        item-title="title"
                        item-value="value"
                        hide-details
                        return-object
                        @update:model-value="onResourceTypeChange"
                    ></v-select>
                </v-card-text>

                <!-- Step 2: Archetype -->
                <v-card-text v-if="createStep === 2">
                    <div class="text-subtitle-1 mb-4">Select Archetype</div>
                    <v-autocomplete
                        v-model="localItem.archetype"
                        :items="autocompleteArchetypes"
                        label="Archetype"
                        item-title="name"
                        item-value="id"
                        hide-no-data
                        :return-object="true"
                        :loading="archetypesLoading"
                        :error-messages="formErrors['archetype.id']"
                        @update:search="debouncedAutocompleteArchetypeSearch"
                    ></v-autocomplete>
                    
<!-- Archetype Dialog (has built-in button activator) -->
                    <ArchetypeDialog
                        v-if="selectedResourceType"
                        ref="archetypeDialogRef"
                        aim="create"
                        :resource="selectedResourceType.value"
                        @stored="onArchetypeStored"
                    />
                </v-card-text>

                <!-- Step 3: Details -->
                <v-card-text v-if="createStep === 3">
                    <div class="text-subtitle-1 mb-4">Add Description and Image</div>
                    
                    <!-- Description -->
                    <v-textarea
                        v-model="localItem.description"
                        label="Description"
                        rows="2"
                        hide-details
                        class="mb-2"
                    ></v-textarea>

                    <!-- File upload -->
                    <v-file-input
                        label="Upload Image"
                        prepend-icon="mdi-camera"
                        accept="image/*"
                        show-size
                        class="mt-4"
                        @change="handleFileChange"
                    ></v-file-input>
                </v-card-text>
            </div>

            <!-- Regular Form for Edit/View Mode -->
            <v-card-text v-else class="pt-2">
                <!-- Archetype (edit only) -->
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

                <!-- Description -->
                <v-textarea
                    v-if="aim !== 'view'"
                    v-model="localItem.description"
                    label="Description"
                    rows="2"
                    hide-details
                    class="mb-2"
                ></v-textarea>

                <!-- Serial Number -->
                <v-text-field
                    v-if="aim !== 'view'"
                    v-model="localItem.serial"
                    label="Serial Number"
                    hide-details
                    class="mb-2"
                ></v-text-field>

                <!-- Purchase Value -->
                <v-text-field
                    v-if="aim !== 'view'"
                    v-model="localItem.purchase_value"
                    label="Purchase Value"
                    type="number"
                    prefix="$"
                    hide-details
                    class="mb-2"
                ></v-text-field>

                <!-- Purchased At (Required for create) -->
                <v-text-field
                    v-if="aim !== 'view'"
                    v-model="localItem.purchased_at"
                    label="Purchase Date"
                    type="date"
                    hide-details
                    class="mb-2"
                    :error-messages="formErrors['purchased_at']"
                ></v-text-field>

                <!-- Manufactured At -->
                <v-text-field
                    v-if="aim !== 'view'"
                    v-model="localItem.manufactured_at"
                    label="Manufactured Date"
                    type="date"
                    hide-details
                    class="mb-2"
                ></v-text-field>

                <ArchetypeDialog
                    v-if="aim !== 'view' && localItem.archetype?.created_by === user.id"
                    aim="edit"
                    :archetype="localItem.archetype"
                    class="mb-2"
                />
                
                <!-- Archetype Dialog for creating new archetype in edit mode -->
                <ArchetypeDialog
                    v-if="aim === 'edit'"
                    aim="create"
                    @stored="onArchetypeStoredEdit"
                />

                <!-- Rental Section (View Mode) -->
                <div v-if="aim === 'view'" class="mt-4">
                    <v-divider class="mb-4"></v-divider>
                    
                    <!-- Item Details -->
                    <div v-if="localItem.description" class="mb-4">
                        <div class="text-subtitle-2 mb-1">Description</div>
                        <div class="text-body-1">{{ localItem.description }}</div>
                    </div>
                    
                    <div v-if="localItem.serial" class="mb-4">
                        <div class="text-subtitle-2 mb-1">Serial Number</div>
                        <div class="text-body-1">{{ localItem.serial }}</div>
                    </div>
                    
                    <div v-if="localItem.purchase_value" class="mb-4">
                        <div class="text-subtitle-2 mb-1">Purchase Value</div>
                        <div class="text-body-1">${{ localItem.purchase_value }}</div>
                    </div>
                    
                    <div v-if="localItem.purchased_at" class="mb-4">
                        <div class="text-subtitle-2 mb-1">Purchase Date</div>
                        <div class="text-body-1">{{ localItem.purchased_at }}</div>
                    </div>
                    
                    <div v-if="localItem.manufactured_at" class="mb-4">
                        <div class="text-subtitle-2 mb-1">Manufactured Date</div>
                        <div class="text-body-1">{{ localItem.manufactured_at }}</div>
                    </div>
                    
                    <v-divider class="mb-4"></v-divider>
                    
                    <!-- Duration Selection -->
                    <v-select
                        v-model="rentalDuration"
                        :items="durationOptions"
                        label="Duration"
                        density="compact"
                        hide-details
                        class="mb-4"
                    ></v-select>
                    
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

                <!-- File upload (edit only) -->
                <v-file-input
                    v-if="aim !== 'view'"
                    label="Upload Image"
                    prepend-icon="mdi-camera"
                    accept="image/*"
                    show-size
                    class="mt-4"
                    @change="handleFileChange"
                ></v-file-input>
            </v-card-text>

            <v-divider></v-divider>
            <v-card-actions>
                <!-- Navigation buttons for Create Wizard -->
                <template v-if="aim === 'create'">
                    <v-btn
                        v-if="createStep > 1"
                        text="Back"
                        variant="plain"
                        @click="createStep--"
                    ></v-btn>
                    <v-spacer></v-spacer>
                    <v-btn
                        v-if="createStep < 3"
                        text="Next"
                        variant="tonal"
                        color="primary"
                        :disabled="!canProceed"
                        @click="createStep++"
                    ></v-btn>
                    <v-btn
                        v-if="createStep === 3"
                        color="success"
                        text="Create"
                        variant="tonal"
                        @click="createItem"
                    ></v-btn>
                </template>

                <!-- Regular buttons for Edit/View -->
                <template v-else>
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
                        v-if="aim === 'view' && !isItemRented"
                        color="success"
                        :text="getConfirmButtonText()"
                        variant="tonal"
                        :loading="isConfirmingRental"
                        @click="confirmRental"
                    ></v-btn>
                </template>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { shallowRef, ref, watch, computed } from 'vue';
import _ from 'lodash';
import ArchetypeDialog from './ArchetypeDialog.vue';
import { usePage, router } from '@inertiajs/vue3';
import api from '@/services/api';
import { useResponseStore } from '@/Stores/response';

const responseStore = useResponseStore();

const dialog = shallowRef(false);
const page = usePage();

const user = page.props.auth.user;

const localItem = ref(null);
const autocompleteArchetypes = ref([]);
const newImages = ref([]);
const formErrors = ref({});

// Ref for archetype dialog
const archetypeDialogRef = ref(null);

// Wizard state for create mode
const createStep = ref(1);
const selectedResourceType = ref(null);
const archetypesLoading = ref(false);

// Resource type options - comprehensive list for community sharing
const resourceOptions = [
    { title: 'Tool', value: 'TOOL', icon: 'mdi-tools', description: 'Power tools, hand tools, garden equipment' },
    { title: 'Material', value: 'MATERIAL', icon: 'mdi-cube', description: 'Building materials, craft supplies, raw materials' },
    { title: 'Labor/Service', value: 'LABOR', icon: 'mdi-account-hard-hat', description: 'Manual labor, help with tasks, skilled work' },
    { title: 'Rideshare', value: 'RIDESHARE', icon: 'mdi-car', description: 'Transportation, carpooling, delivery' },
    { title: 'Furniture', value: 'FURNITURE', icon: 'mdi-table-furniture', description: 'Tables, chairs, desks, event furniture' },
    { title: 'Kitchen', value: 'KITCHEN', icon: 'mdi-blender', description: 'Kitchen equipment, appliances, cookware' },
    { title: 'Electronics', value: 'ELECTRONICS', icon: 'mdi-television', description: 'Gadgets, devices, AV equipment' },
    { title: 'Sports', value: 'SPORTS', icon: 'mdi-basketball', description: 'Sports equipment, fitness gear' },
    { title: 'Outdoor', value: 'OUTDOOR', icon: 'mdi-tent', description: 'Camping gear, outdoor equipment' },
    { title: 'Party', value: 'PARTY', icon: 'mdi-party-popper', description: 'Party supplies, decorations, event equipment' },
    { title: 'Books', value: 'BOOKS', icon: 'mdi-book', description: 'Educational materials, textbooks, manuals' },
    { title: 'Other', value: 'OTHER', icon: 'mdi-dots-horizontal', description: 'Miscellaneous items' },
];

// Check if can proceed to next step
const canProceed = computed(() => {
    if (createStep.value === 1) {
        return !!selectedResourceType.value;
    }
    if (createStep.value === 2) {
        return !!localItem.value?.archetype;
    }
    return true;
});

// Rental state
const isItemRented = ref(false);
const rentalError = ref('');
const isConfirmingRental = ref(false);

// Rental duration
const rentalDuration = ref(1); // default to 1 day
const durationOptions = [
    { title: '1 day', value: 1 },
    { title: '2 days', value: 2 },
    { title: '3 days', value: 3 },
    { title: '4 days', value: 4 },
    { title: '5 days', value: 5 },
    { title: '6 days', value: 6 },
    { title: '1 week', value: 7 },
    { title: '2 weeks', value: 14 },
    { title: '3 weeks', value: 21 },
    { title: '4 weeks', value: 28 },
];

// Get resource type from archetype
const resourceType = computed(() => {
    return localItem.value?.archetype?.resource || 'TOOL';
});

// Get button text based on resource type
const actionButtonText = computed(() => {
    if (aim.value === 'edit') return 'Edit Item';
    if (aim.value === 'create') return 'Create Item';
    
    // For view mode, text depends on resource type
    switch (resourceType.value) {
        case 'TOOL':
            return 'Rent Item';
        case 'MATERIAL':
            return 'Request Material';
        case 'LABOR':
            return 'Book Service';
        case 'RIDESHARE':
            return 'Request Ride';
        case 'FURNITURE':
            return 'Reserve Furniture';
        case 'KITCHEN':
            return 'Borrow Kitchen Item';
        case 'ELECTRONICS':
            return 'Borrow Electronics';
        case 'SPORTS':
            return 'Borrow Sports Gear';
        case 'OUTDOOR':
            return 'Borrow Outdoor Gear';
        case 'PARTY':
            return 'Reserve Party Supplies';
        case 'BOOKS':
            return 'Borrow Book';
        case 'OTHER':
        default:
            return 'Request Item';
    }
});

// Get icon based on resource type
const actionButtonIcon = computed(() => {
    if (aim.value === 'edit') return 'mdi-pencil';
    if (aim.value === 'create') return 'mdi-plus';
    
    switch (resourceType.value) {
        case 'TOOL':
            return 'mdi-tools';
        case 'MATERIAL':
            return 'mdi-cube';
        case 'LABOR':
            return 'mdi-account-hard-hat';
        case 'RIDESHARE':
            return 'mdi-car';
        case 'FURNITURE':
            return 'mdi-table-furniture';
        case 'KITCHEN':
            return 'mdi-blender';
        case 'ELECTRONICS':
            return 'mdi-television';
        case 'SPORTS':
            return 'mdi-basketball';
        case 'OUTDOOR':
            return 'mdi-tent';
        case 'PARTY':
            return 'mdi-party-popper';
        case 'BOOKS':
            return 'mdi-book';
        case 'OTHER':
        default:
            return 'mdi-hand-extended';
    }
});

// Get confirm button text based on resource type and duration
const getConfirmButtonText = () => {
    const duration = durationOptions.find(d => d.value === rentalDuration.value)?.title || '1 day';
    const durationText = duration.replace('day', 'day rental').replace('week', 'week rental');
    
    switch (resourceType.value) {
        case 'TOOL':
            return `Rent for ${duration}`;
        case 'MATERIAL':
            return `Request ${durationText}`;
        case 'LABOR':
            return `Book service for ${duration}`;
        case 'RIDESHARE':
            return `Request ride (${duration})`;
        case 'FURNITURE':
            return `Reserve for ${duration}`;
        case 'KITCHEN':
            return `Borrow for ${duration}`;
        case 'ELECTRONICS':
            return `Borrow for ${duration}`;
        case 'SPORTS':
            return `Borrow for ${duration}`;
        case 'OUTDOOR':
            return `Borrow for ${duration}`;
        case 'PARTY':
            return `Reserve for ${duration}`;
        case 'BOOKS':
            return `Borrow for ${duration}`;
        case 'OTHER':
        default:
            return `Confirm ${durationText}`;
    }
};

// Alias for template
const aim = computed(() => props.aim);

const props = defineProps({
    item: { type: Object, default: null },
    archetype: { type: Object, default: null },
    aim: { type: String, required: true },
    iconOnly: { type: Boolean, default: false },
    buttonColor: { type: String, default: 'primary' },
    size: { type: String, default: 'small' },
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

// Watch for step changes to load archetypes when entering step 2
watch(createStep, (newStep) => {
    if (newStep === 2 && selectedResourceType.value) {
        refreshAutocompleteArchetypes('', selectedResourceType.value);
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
            description: '',
            serial: '',
            purchase_value: null,
            purchased_at: new Date().toISOString().split('T')[0],
            manufactured_at: null,
            created_at: props.item?.created_at || new Date().toISOString(),
        };
        // Reset wizard state for create mode
        createStep.value = 1;
        selectedResourceType.value = null;
    }
};

const onOpen = async () => {
    initialize();
    newImages.value = [];
    
    // For edit mode, load archetypes normally
    // For create mode, archetypes are loaded in step 2
    if (props.aim === 'edit') {
        refreshAutocompleteArchetypes();
    }
};

const onClose = () => {
    isItemRented.value = false;
    rentalError.value = '';
};

const onResourceTypeChange = () => {
    // Clear archetype selection when resource type changes
    if (localItem.value) {
        localItem.value.archetype = null;
    }
    autocompleteArchetypes.value = [];
};

const createItem = async () => {
    formErrors.value = {};
    try {
        const payload = {
            archetype: localItem.value.archetype,
            description: localItem.value.description,
            // These fields are omitted in create wizard - they can be added later in edit mode
            serial: '',
            purchase_value: null,
            purchased_at: new Date().toISOString().split('T')[0],
            manufactured_at: null,
        };
        
        const response = await api.post(route('items.store'), payload);
        
        if (response?.data?.success && response.data.data?.id) {
            localItem.value = response.data.data;

            // Add new images - must use FormData with 'image' key
            for (const image of newImages.value) {
                const formData = new FormData();
                formData.append('image', image);
                await api.post(route('item-images.store', localItem.value.id), formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
            }
            
            responseStore.setSuccess('Item created successfully');
            emit('stored');
            dialog.value = false;
        }
    } catch (error) {
        console.error('Error creating item:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to create item');
        if (error.response?.data?.errors) {
            formErrors.value = error.response.data.errors;
        }
    }
};

const saveItem = async () => {
    formErrors.value = {};
    try {
        const response = await api.put(route('items.update', localItem.value.id), localItem.value);

        // Add new images
        if (response?.data?.success) {
            for (const image of newImages.value) {
                // Must use FormData with 'image' key for the API
                const formData = new FormData();
                formData.append('image', image);
                await api.post(route('item-images.store', localItem.value.id), formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
            }

            responseStore.setSuccess('Item updated successfully');
            emit('updated');
            dialog.value = false;
        }
    } catch (error) {
        console.error('Error updating item:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to update item');
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
            duration: rentalDuration.value,
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

const refreshAutocompleteArchetypes = async (query, resource = null) => {
    const params = { query };
    if (resource) {
        params.resource = resource;
    }
    archetypesLoading.value = true;
    try {
        const response = await api.get(route('archetypes.index'), {
            params,
        });
        autocompleteArchetypes.value = response.data.data;
    } finally {
        archetypesLoading.value = false;
    }
};

// Debounced search function
const debouncedAutocompleteArchetypeSearch = _.debounce((query) => {
    const resource = props.aim === 'create' ? selectedResourceType.value : null;
    refreshAutocompleteArchetypes(query, resource);
}, 300);

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        newImages.value = [file];
    }
};


// Handle archetype created event - refresh the list
const onArchetypeStored = async () => {
    // Refresh the archetype list to include the new one
    await refreshAutocompleteArchetypes('', selectedResourceType.value);
};


// Handle archetype created event in edit mode - refresh the list
const onArchetypeStoredEdit = async () => {
    // Refresh the archetype list to include the new one
    await refreshAutocompleteArchetypes();
};
</script>

