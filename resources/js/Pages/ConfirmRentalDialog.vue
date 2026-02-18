<template>
    <div class="pl-4 text-center">
        <v-dialog v-model="dialog" @open="onOpen">
            <template #activator="{ props: activatorProps }">
                <v-btn
                    color="primary"
                    class="text-none font-weight-regular"
                    prepend-icon="mdi-check-circle"
                    text="Confirm Rental"
                    variant="tonal"
                    v-bind="activatorProps"
                ></v-btn>
            </template>
            <v-card
                v-if="localItem"
                prepend-icon="mdi-check-circle"
                title="Confirm Rental"
                :subtitle="localItem.code"
            >
                <v-card-text>
                    <div>
                        Selected Date Range: <br /><span
                            v-html="itemStore.outputReadableDateRange()"
                        />
                    </div>
                </v-card-text>
                <v-divider></v-divider>

                <v-card-actions>
                    <v-spacer></v-spacer>

                    <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

                    <v-btn
                        color="success"
                        class="text-none font-weight-regular"
                        text="Rent"
                        variant="tonal"
                        @click="rent"
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
const rentedDates = ref([]);
const dateRange = ref([]);
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
    localItem.value = {
        ...props.item,
    };

    rentedDates.value = api.get(route('item.index-rented-dates', localItem.value.id));
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
    initialize();
};

const onClose = () => {};

const rent = async () => {
    await api.post(route('rentals.store'), {
        item: localItem.value,
        dateRange: dateRange.value,
    });

    dialog.value = false;
    router.visit('/my-rentals');
};
</script>
