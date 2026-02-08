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
import { useArchetypeStore } from '@/Stores/archetype';
import { useItemStore } from '@/Stores/item';
import { useCategoryStore } from '@/Stores/category';
import { useUsageStore } from '@/Stores/usage';
import { useResponseStore } from '@/Stores/response';
import { useUserStore } from '@/Stores/user';
import { useDisplay } from 'vuetify';

const { mobile } = useDisplay();

const dialog = shallowRef(false);

const archetypeStore = useArchetypeStore();
const itemStore = useItemStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const responseStore = useResponseStore();
const userStore = useUserStore();

const localArchetype = ref(null);
const autocompleteCategories = ref([]);
const autocompleteUsages = ref([]);
import debounce from 'lodash/debounce';
import { router } from '@inertiajs/vue3'

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
    localArchetype.value = await archetypeStore.show(props.archetype.id);
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

const emit = defineEmits(['created', 'saved']);

const onOpen = async () => {
    await categoryStore.index();
    await usageStore.index();
    autocompleteCategories.value = await categoryStore.indexForAutocomplete();
    autocompleteUsages.value = await usageStore.indexForAutocomplete();

    initializeLocalArchetype();
    responseStore.$reset();
};

// Autocomplete category Search handler
const onAutocompleteCategorySearch = async (query) => {
    autocompleteCategories.value = await categoryStore.indexForAutocomplete(query);
};
// Autocomplete usage Search handler
const onAutocompleteUsageSearch = async (query) => {
    autocompleteUsages.value = await usageStore.indexForAutocomplete(query);
};

const debouncedAutocompleteCategorySearch = debounce(onAutocompleteCategorySearch, 300);

const debouncedAutocompleteUsageSearch = debounce(onAutocompleteUsageSearch, 300);

const onClose = () => {
    console.log('Dialog closed');
};

const save = async () => {
    const data = await archetypeStore.update(localArchetype.value);

    if (data.success) {
        emit('saved');
        dialog.value = false;
    }
};

const create = async () => {
    const data = await archetypeStore.store(localArchetype.value);
    if (data?.success) {
        emit('created');
        dialog.value = false;
    }
};

const myItems = () => {
    itemStore.itemListFilters.archetype = localArchetype.value;
    itemStore.itemListFilters.userId = userStore.user?.id;
    itemStore.index();
    router.visit({ path: '/item-list' });
    dialog.value = false;
};
</script>
