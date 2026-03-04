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
                <v-row>
                    <v-col cols="12" md="6" sm="6">
                        <v-text-field
                            v-model="localArchetype.name"
                            density="comfortable"
                            :error-messages="responseStore?.response?.errors?.name"
                            label="Name"
                        />
                    </v-col>
                    <v-col cols="12" md="6" sm="6">
                        <v-select
                            v-model="localArchetype.resource"
                            :items="resourceOptions"
                            label="Type"
                            density="comfortable"
                            hide-details
                        ></v-select>
                    </v-col>
                    <v-col cols="12" md="6" sm="6">
                        <v-textarea
                            v-model="localArchetype.description"
                            density="comfortable"
                            label="Description"
                        ></v-textarea>
                    </v-col>

                    <v-col cols="12" md="6" sm="6">
                        <v-textarea
                            v-model="localArchetype.notes"
                            density="comfortable"
                            label="Notes"
                        ></v-textarea>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-divider></v-divider>

            <v-card-actions>
                <v-spacer></v-spacer>

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
import { useResponseStore } from '@/Stores/response';
const { mobile } = useDisplay();

const responseStore = useResponseStore();

const dialog = shallowRef(false);

const localArchetype = ref(null);

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
    // Clear previous error messages
    if (responseStore?.response?.errors) {
        responseStore.response.errors = {};
    }
    
    if (props.aim == 'edit' && props.archetype) {
        refreshLocalArchetype();
    } else {
        localArchetype.value = {
            name: '',
            description: '',
            notes: '',
            resource: props.resource ?? 'TOOL',
        };
    }
};

const emit = defineEmits(['stored', 'updated']);

const onOpen = async () => {
    initializeLocalArchetype();
};

const onClose = () => {
    console.log('Dialog closed');
};

const save = async () => {
    try {
        const response = await api.put(route('archetypes.update'), localArchetype.value);

        if (response.data.success) {
            responseStore.setSuccess('Archetype updated successfully');
            emit('updated');
            dialog.value = false;
        }
    } catch (error) {
        console.error('Error updating archetype:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to update archetype');
        if (error.response?.data?.errors) {
            responseStore.response.errors = error.response.data.errors;
        }
    }
};

const create = async () => {
    try {
        const response = await api.post(route('archetypes.store'), localArchetype.value);
        if (response?.data?.success) {
            responseStore.setSuccess('Archetype created successfully');
            emit('stored');
            dialog.value = false;
        }
    } catch (error) {
        console.error('Error creating archetype:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to create archetype');
        if (error.response?.data?.errors) {
            responseStore.response.errors = error.response.data.errors;
        }
    }
};


</script>

