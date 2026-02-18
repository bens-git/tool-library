<template>
    <v-dialog v-model="dialog" @open="onOpen">
        <template #activator="{ props: dialogProps }">
            <v-tooltip
                :text="
                    aim == 'edit'
                        ? `Edit` + (resource ? ' ' + resource : ' ') + ' Archetype'
                        : `Create` + (resource ? ' ' + resource : ' ') + ' Archetype'
                "
            >
                <template #activator="{ props: tooltipProps }">
                    <v-btn
                        :color="aim == 'edit' ? 'primary' : 'success'"
                        :icon="mobile"
                        v-bind="{ ...dialogProps, ...tooltipProps }"
                        variant="tonal"
                    >
                        <v-icon>{{ aim == 'edit' ? 'mdi-pencil' : 'mdi-plus' }}</v-icon>
                        <span v-if="!mobile" class="ml-2">
                            {{
                                aim == 'edit'
                                    ? `Edit` + (resource ? ' ' + resource : '') + ' Archetype'
                                    : `Create` + (resource ? ' ' + resource : '') + ' Archetype'
                            }}
                        </span>
                    </v-btn>
                </template>
            </v-tooltip>
        </template>

        <v-card
            :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
            :title="
                aim == 'edit'
                    ? `Edit` + (resource ? ' ' + resource : '') + ' Archetype'
                    : `Create` + (resource ? ' ' + resource : '') + ' Archetype'
            "
        >
            <v-card-text v-if="localArchetype">
                <v-row dense>
                    <v-col cols="12" md="4" sm="6">
                        <v-text-field
                            v-model="localArchetype.name"
                            density="compact"
                            :error-messages="responseStore?.response?.errors?.name"
                            label="Name"
                        />
                    </v-col>
                    <v-col cols="12" md="4" sm="6">
                        <v-textarea
                            v-model="localArchetype.description"
                            density="compact"
                            label="Description"
                        ></v-textarea>
                    </v-col>

                    <v-col cols="12" md="4" sm="6">
                        <v-textarea
                            v-model="localArchetype.notes"
                            density="compact"
                            label="Notes"
                        ></v-textarea>
                    </v-col>

                    <v-col cols="12" md="4" sm="6">
                        <v-text-field
                            v-model="localArchetype.code"
                            density="compact"
                            label="Code"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" md="4" sm="6">
                        <v-autocomplete
                            v-model="localArchetype.categories"
                            density="compact"
                            :items="autocompleteCategories"
                            label="Category"
                            item-title="name"
                            item-value="id"
                            hide-no-data
                            hide-details
                            return-object
                            multiple
                            variant="outlined"
                            clearable
                            :error-messages="responseStore?.response?.errors?.category_ids"
                            @update:search="debouncedAutocompleteCategorySearch"
                        ></v-autocomplete>
                    </v-col>

                    <v-col cols="12" md="4" sm="6">
                        <v-autocomplete
                            v-model="localArchetype.usages"
                            density="compact"
                            :items="autocompleteUsages"
                            label="Usage"
                            item-title="name"
                            item-value="id"
                            hide-no-data
                            hide-details
                            return-object
                            multiple
                            variant="outlined"
                            clearable
                            @update:search-value="debouncedAutocompleteUsageSearch"
                        ></v-autocomplete>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-divider></v-divider>

            <v-card-actions>
                <v-spacer></v-spacer>

                <v-btn text="My Items" variant="plain" @click="myItems()"></v-btn>

                <v-btn text="Close" variant="plain" @click="dialog = false"></v-btn>

                <v-btn
                    v-if="aim == 'edit'"
                    color="primary"
                    text="Save"
                    variant="tonal"
                    @click="save"
                ></v-btn>

                <v-btn v-else color="primary" text="Create" variant="tonal" @click="create"></v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
<script setup>
import { shallowRef, ref, watch } from 'vue';
import { useDisplay } from 'vuetify';
import api from '@/services/api';
import _ from 'lodash';
const { mobile } = useDisplay();

const dialog = shallowRef(false);

const localArchetype = ref(null);
const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);

const props = defineProps({
    aim: { type: String, default: 'Create' },
    archetype: { type: Object, default: null },
    resource: { type: String, default: '' },
});

// Watch the dialog's state
watch(dialog, (newVal) => {
    if (newVal) {
        onOpen();
    } else {
        onClose();
    }
});

const refreshLocalArchetype = async () => {
    const response = await api.get(route('archetypes.show', props.archetype.id));

    localArchetype.value = response.data.data;
};

// Function to initialize
const initializeLocalArchetype = () => {
    if (props.aim == 'edit' && props.archetype) {
        refreshLocalArchetype();
    } else {
        localArchetype.value = {
            name: '',
            description: '',
            category: null,
            usage: null,
            notes: '',
            resource: props.resource ?? 'TOOL',
        };
    }
};

const emit = defineEmits(['stored', 'updated']);

const refreshAutocompleteCategories = async (query) => {
    const response = await api.get(route('categories.index'), {
        params: { query },
    });
    autocompleteCategories.value = response.data.data;
};

const refreshAutocompleteUsages = async (query) => {
    const response = await api.get(route('usages.index'), {
        params: { query },
    });
    autocompleteUsages.value = response.data.data;
};

const onOpen = async () => {
    refreshAutocompleteCategories();
    refreshAutocompleteUsages();
    initializeLocalArchetype();
};

const debouncedAutocompleteCategorySearch = _.debounce(refreshAutocompleteCategories, 300);
const debouncedAutocompleteUsageSearch = _.debounce(refreshAutocompleteUsages, 300);

const onClose = () => {
    console.log('Dialog closed');
};

const save = async () => {
    const response = await api.put(route('archetypes.update'), localArchetype.value);

    if (response.success) {
        emit('updated');
        dialog.value = false;
    }
};

const create = async () => {
    const response = await api.post(route('archetypes.store'), localArchetype.value);
    if (response?.success) {
        emit('stored');
        dialog.value = false;
    }
};


</script>
