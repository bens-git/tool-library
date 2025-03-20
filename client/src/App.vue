<template>
  <v-app>
    <v-main>
      <TopMenu />

      <router-view />

      <!-- Snackbar for error messages -->
      <v-snackbar v-model="showError" color="error" multi-line :timeout="5000">
        {{ responseStore.response.message }}
        <template v-slot:actions>
          <v-btn variant="text" @click="showError = false"> Close </v-btn>
        </template>
      </v-snackbar>

      <!-- Snackbar for success messages -->
      <v-snackbar
        v-model="showSuccess"
        multi-line
        :timeout="5000"
        color="success"
      >
        {{ responseStore.response.message }}

        <template v-slot:actions>
          <v-btn variant="text" @click="showSuccess = false"> Close </v-btn>
        </template>
      </v-snackbar>

      <!-- Loading Spinner -->
      <v-overlay :model-value="isLoading" class="align-center justify-center">
        <v-progress-circular color="primary" size="64" indeterminate />
      </v-overlay>
    </v-main>
  </v-app>
</template>

<script>
import TopMenu from "./components/TopMenu.vue";
import { ref, watch, computed } from "vue";
import { useUserStore } from "./stores/user";
import { useLoadingStore } from "./stores/loading";
import { useResponseStore } from "./stores/response";

export default {
  components: {
    TopMenu,
  },
  setup() {
    const userStore = useUserStore();
    const loadingStore = useLoadingStore();
    const responseStore = useResponseStore();
    const showError = ref(false);
    const showSuccess = ref(false);

    // Watch for changes in the responseStore to display the appropriate snackbar
    watch(
      () => responseStore.response,
      (newResponse) => {
        if (Object.keys(newResponse.errors).length || (!newResponse.success && newResponse.message)) {
          showError.value = true;
        } else if (newResponse.success) {
          showSuccess.value = true;
        }
      },
      { deep: true }
    );

    // Computed property to determine if any process is loading
    const isLoading = computed(
      () => loadingStore?.loadingProcesses?.length > 0
    );

    return {
      showError,
      showSuccess,
      userStore,
      loadingStore,
      responseStore,
      isLoading,
    };
  },
};
</script>

<style>
html,
body,
#app {
  height: 100%;
  margin: 0;
  overflow: hidden !important; /* Disable vertical scrolling for the entire app */
}

.v-application {
  height: 100%;
  display: flex;
  flex-direction: column;
}
</style>
