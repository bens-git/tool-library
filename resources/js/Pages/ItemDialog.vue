<template>
    <v-dialog v-model="dialog" max-width="700" @open="onOpen">
        <template #activator="{ props: activatorProps }">
            <v-btn
                :color="aim === 'edit' || aim === 'view' ? 'primary' : 'success'"
                :prepend-icon="
                    aim === 'edit' ? 'mdi-pencil' : aim === 'view' ? 'mdi-eye' : 'mdi-plus'
                "
                :text="aim === 'edit' ? 'Edit Item' : aim === 'view' ? 'View Item' : 'Create Item'"
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
                <!-- Time Credits Required (View Mode) -->
                <div v-if="aim === 'view' && localItem.access_value?.current_daily_rate" class="mb-4">
                    <v-card variant="tonal" color="info" class="pa-3">
                        <div class="text-subtitle-2 mb-1">Rental Cost</div>
                        <div class="text-h5 font-weight-bold">
                            {{ localItem.access_value.current_daily_rate }}
                            <span class="text-body-2">credits / day</span>
                        </div>
                    </v-card>
                </div>

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

                <!-- Rental Section (View Mode) -->
                <div v-if="aim === 'view'" class="mt-4">
                    <v-divider class="mb-4"></v-divider>
                    <RentalDatesDialog :item="localItem" />
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
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { shallowRef, ref, watch } from 'vue';
import _ from 'lodash';
import ArchetypeDialog from './ArchetypeDialog.vue';
import RentalDatesDialog from './RentalDatesDialog.vue';
import { usePage } from '@inertiajs/vue3';
import api from '@/services/api';

const dialog = shallowRef(false);
const page = usePage();

const user = page.props.auth.user;

const localItem = ref(null);
const autocompleteArchetypes = ref([]);
const newImages = ref([]);
const formErrors = ref({});

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

const onClose = () => {};

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
