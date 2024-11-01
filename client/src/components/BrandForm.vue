<template>
    <v-card>
        <v-card-title>{{ isEdit ? "Edit Brand" : "Create New Brand" }}</v-card-title>
        <v-card-text>
            <v-text-field v-model="localBrand.name" :error-messages="responseStore?.response?.errors[0]?.name"
                label="Name"></v-text-field>
        </v-card-text>
        <v-card-actions>
            <v-btn color="primary" @click="saveBrand">{{
                isEdit ? "Update" : "Create"
                }}</v-btn>
            <v-btn text @click="closeModal">Cancel</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script setup>
import { ref, watch } from "vue";
import { useResponseStore } from "@/stores/response";
import { useBrandStore } from "@/stores/brand";

const brandStore = useBrandStore();
const responseStore = useResponseStore();

// Define props
const props = defineProps({
    showBrandModal: Boolean,
    isEdit: Boolean,
    brand: Object,
});

const emit = defineEmits([
    "update-brand",
    "create-brand",
    "update:showBrandModal",
    "close-modal",
]);

watch(
    () => props.brand,
    (newBrand) => {
        localBrand.value = { ...newBrand };
    },
    { deep: true },
);

const localBrand = ref({ ...props.brand });

const saveBrand = async () => {
    const responseStore = useResponseStore();
    const formData = new FormData();

    // Append all properties of local type except images handling
    for (const [key, value] of Object.entries(localBrand.value)) {
        // Skip keys with null values or replace them with actual null
        if (value === null || value === undefined) {
            formData.append(key, "");
        } else {
            formData.append(key, value);
        }
    }

    try {
        if (props.isEdit) {
            await brandStore.updateBrand(formData);
        } else {
            await brandStore.createBrand(formData);
        }
        if (responseStore.response.success) {
            closeModal();
            brandStore.fetchUserBrands();
        } else {
            console.log("Error:", responseStore.response.message);
        }
    } catch (error) {
        console.error("Unexpected Error:", error);
        responseStore.setResponse(false, error.response.data.message, [
            error.response.data.errors,
        ]);
    }
};

// Close modal logic
const closeModal = () => {
    emit("close-modal");
};
</script>