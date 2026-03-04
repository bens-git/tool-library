<template>
    <v-dialog v-model="dialog" @open="onOpen">
        <template #activator="{ props: activatorProps }">
            <v-tooltip v-if="iconOnly" location="top">
                <template #activator="{ props: tooltipProps }">
                    <v-btn
                        :color="buttonColor"
                        variant="tonal"
                        :size="size"
                        v-bind="{ ...activatorProps, ...tooltipProps }"
                    >
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
                </template>
                <span>Delete</span>
            </v-tooltip>
            <v-btn
                v-else
                :color="buttonColor"
                prepend-icon="mdi-delete"
                text="Delete Item"
                variant="tonal"
                v-bind="activatorProps"
                :block="block"
                :size="size"
            ></v-btn>
        </template>
        <v-card
            v-if="localItem"
            prepend-icon="mdi-delete"
            title="Delete Item"
        >
            <v-card-text> Are you sure you want to delete this item? </v-card-text>
            <v-divider></v-divider>

            <v-card-actions>
                <v-spacer></v-spacer>

                <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

                <v-btn color="error" text="Delete" variant="tonal" @click="deleteItem"></v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
<script setup>
import { shallowRef, ref, watch } from 'vue';
import { useResponseStore } from '@/Stores/response';
import api from '@/services/api'

const dialog = shallowRef(false);

const responseStore = useResponseStore();

const localItem = ref(null);

const props = defineProps({
    item: { type: Object, required: true },
    iconOnly: { type: Boolean, default: false },
    buttonColor: { type: String, default: 'error' },
    size: { type: String, default: 'small' },
    block: { type: Boolean, default: false },
});

// Define emit to notify parent component when item is deleted
const emit = defineEmits(['deleted']);

// Watch the dialog's state
watch(dialog, (newVal) => {
    if (newVal) {
        onOpen();
    } else {
        onClose();
    }
});

// Function to initialize
const initialize = () => {
    localItem.value = {
        ...props.item,
    };
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
    initialize();
    responseStore.$reset();
};

const onClose = () => {};

const deleteItem = async() => {
    try {
        await api.delete(route('items.destroy', localItem.value.id));
        
        dialog.value = false;
        
        // Show success message
        responseStore.setSuccess('Item deleted successfully');
        
        // Emit event to notify parent to refresh the list
        emit('deleted', localItem.value.id);
    } catch (error) {
        console.error('Error deleting item:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to delete item');
    }
};
</script>
