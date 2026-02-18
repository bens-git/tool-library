<template>
    <v-dialog v-model="dialog" @open="onOpen">
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
            <v-card-title>
                <v-icon left>
                    {{ aim === 'edit' ? 'mdi-pencil' : aim === 'view' ? 'mdi-eye' : 'mdi-plus' }}
                </v-icon>
                {{ aim === 'edit' ? 'Edit Item' : aim === 'view' ? 'View Item' : 'Create Item' }}
            </v-card-title>
            <v-card-subtitle v-if="localItem.code">{{ localItem.code }}</v-card-subtitle>

            <v-card-text>
                <!-- Archetype -->
                <v-autocomplete
                    v-if="aim !== 'view' || localItem.archetype"
                    v-model="localItem.archetype"
                    density="compact"
                    :items="autocompleteArchetypes"
                    label="Archetype"
                    item-title="name"
                    item-value="id"
                    hide-no-data
                    :return-object="true"
                    :readonly="aim === 'view'"
                    :error-messages="responseStore.response?.errors?.['archetype.id']"
                    @update:search="debouncedAutocompleteArchetypeSearch"
                ></v-autocomplete>

                <ArchetypeDialog
                    v-if="localItem.archetype?.created_by === user.id"
                    aim="edit"
                    :archetype="localItem.archetype"
                />
                <br v-if="localItem.archetype?.created_by === user.id" />

                <!-- Brand -->
                <v-autocomplete
                    v-if="aim !== 'view' || localItem.brand"
                    v-model="localItem.brand"
                    density="compact"
                    :items="autocompleteBrands"
                    label="Brand"
                    clearable
                    item-title="name"
                    item-value="id"
                    hide-no-data
                    hide-details
                    :return-object="true"
                    :readonly="aim === 'view'"
                    @update:search="debouncedAutocompleteBrandSearch"
                ></v-autocomplete>
                <br v-if="localItem.brand" />

                <!-- Description -->
                <v-textarea
                    v-if="aim !== 'view' || localItem.description"
                    v-model="localItem.description"
                    density="compact"
                    :readonly="aim === 'view'"
                    label="Description"
                    placeholder="Add a description"
                ></v-textarea>

                <!-- Unavailable checkbox (edit only) -->
                <v-checkbox
                    v-if="aim === 'edit'"
                    v-model="localItem.make_item_unavailable"
                    label="Make item unavailable"
                    density="compact"
                    :true-value="1"
                    :false-value="0"
                ></v-checkbox>

                <!-- Serial -->
                <v-text-field
                    v-if="aim !== 'view' || localItem.serial"
                    v-model="localItem.serial"
                    density="compact"
                    label="Serial"
                    :readonly="aim === 'view'"
                ></v-text-field>

                <!-- Purchase Value -->
                <v-text-field
                    v-if="aim !== 'view' || localItem.purchase_value"
                    v-model="localItem.purchase_value"
                    density="compact"
                    label="Purchase Value"
                    type="number"
                    :readonly="aim === 'view'"
                    :error-messages="responseStore.response?.errors?.purchase_value"
                ></v-text-field>

                <!-- Dates -->
                <v-date-input
                    v-if="aim !== 'view' || localItem.created_at"
                    v-model="localItem.created_at"
                    density="compact"
                    label="Created At"
                    :disabled="true"
                    :readonly="aim === 'view'"
                    persistent-placeholder
                    :error-messages="responseStore.response?.errors?.purchased_at"
                ></v-date-input>

                <v-date-input
                    v-if="aim !== 'view' || localItem.manufactured_at"
                    v-model="localItem.manufactured_at"
                    density="compact"
                    label="Manufactured At"
                    :readonly="aim === 'view'"
                    persistent-placeholder
                ></v-date-input>

                <!-- Images -->
                <div v-if="localItem.images?.length">
                    <v-row>
                        <v-col v-for="(image, index) in localItem.images" :key="index" cols="4">
                            <v-img :src="fullImageUrl(image.path)" class="mb-2" aspect-ratio="1">
                                <v-btn
                                    v-if="aim !== 'view'"
                                    icon
                                    color="red"
                                    class="mt-2"
                                    @click="removeImage(index)"
                                >
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </v-img>
                        </v-col>
                    </v-row>
                </div>

                <!-- File upload (edit/create only) -->
                <v-file-input
                    v-if="aim !== 'view'"
                    density="compact"
                    label="Upload Image"
                    prepend-icon="mdi-camera"
                    accept="image/*"
                    multiple
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

                <RentalDatesDialog v-if="aim === 'view'" :item="localItem" />
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
const autocompleteBrands = ref([]);
const newImages = ref([]);
const removedImages = ref([]);

const props = defineProps({
    item: { type: Object, default: null },
    arthetype: { type: Object, default: null },
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
    refreshAutocompleteBrands();
};

const onClose = () => {};

const createItem = async () => {
    const response = await api.post(route('items.store'), localItem.value);
    if (response?.success && response.data.id) {
        localItem.value = response.data;

        //add new images
        for (const image of newImages.value) {
            await api.post(route('item-images.store'), image);
        }
        emit('stored');
        dialog.value = false;
    }
};

const saveItem = async () => {
    const response = await api.put(route('items.update'), localItem.value);

    //add new images
    if (response?.success) {
        for (const image of newImages.value) {
            //console.log(image)
            await api.post(route('item-images.store'), image);
        }

        emit('updated');
        dialog.value = false;
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

const refreshAutocompleteBrands = async (query) => {
    const response = await api.get(route('brands.index'), {
        params: { query },
    });
    autocompleteBrands.value = response.data.data;
};

// Debounced search function
const debouncedAutocompleteArchetypeSearch = _.debounce(refreshAutocompleteArchetypes, 300);
const debouncedAutocompleteBrandSearch = _.debounce(refreshAutocompleteBrands, 300);

const handleFileChange = (event) => {
    const files = event.target.files;
    if (files.length) {
        newImages.value.push(...Array.from(files));
    }
};

const removeImage = (index) => {
    if (index >= 0 && index < localItem.value.images.length) {
        const removedImage = localItem.value.images.splice(index, 1)[0];
        if (removedImage && removedImage.id) {
            removedImages.value.push(removedImage.id);
        }
    }
};
</script>
