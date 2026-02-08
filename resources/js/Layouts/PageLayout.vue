<template>
    <v-app>
        <v-main>
            <!-- Page Menus (Top navigation) -->
            <PageMenus />

            <!-- Inertia page slot -->
                <slot />

            <!-- Snackbar for error messages -->
            <v-snackbar v-model="showError" color="error" multi-line :timeout="5000">
                {{ responseStore.response.message }}
                <template #actions>
                    <v-btn variant="text" @click="showError = false">Close</v-btn>
                </template>
            </v-snackbar>

            <!-- Snackbar for success messages -->
            <v-snackbar v-model="showSuccess" color="success" multi-line :timeout="5000">
                {{ responseStore.response.message }}
                <template #actions>
                    <v-btn variant="text" @click="showSuccess = false">Close</v-btn>
                </template>
            </v-snackbar>

            <!-- Loading Spinner -->
            <v-overlay :model-value="isLoading" class="align-center justify-center">
                <v-progress-circular color="primary" size="64" indeterminate />
            </v-overlay>
        </v-main>
    </v-app>
</template>

<script setup>
import PageMenus from '@/Layouts/PageMenus.vue';
import { ref, watch, computed } from 'vue';
import { useLoadingStore } from '@/Stores/loading';
import { useResponseStore } from '@/Stores/response';

// Pinia stores
const loadingStore = useLoadingStore();
const responseStore = useResponseStore();

// Snackbar visibility
const showError = ref(false);
const showSuccess = ref(false);

// Watch for response store changes
watch(
    () => responseStore.response,
    (newResponse) => {
        if (
            Object.keys(newResponse.errors || {}).length ||
            (!newResponse.success && newResponse.message)
        ) {
            showError.value = true;
        } else if (newResponse.success) {
            showSuccess.value = true;
        }
    },
    { deep: true }
);

// Computed property for loading state
const isLoading = computed(() => (loadingStore?.loadingProcesses?.length || 0) > 0);
</script>

<style scoped>
html,
body,
#app {
    height: 100%;
    margin: 0;
    overflow: hidden;
}

.v-container {
    height: 100%;
}

.v-card {
    max-height: 100%;
    max-width: 90vw;
    height: 90vh;
}

.v-card-text {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    overflow: auto;
}

.v-data-table-server {
    flex-grow: 1;
    overflow: auto;
}

.v-dialog {
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}



</style>
