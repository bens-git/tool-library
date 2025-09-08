<template>
  <v-container fluid class="fill-height d-flex justify-center align-center">
    <v-card
      v-if="!userStore.user"
      prepend-icon="mdi-login"
      title="Login"
      class="pa-4"
      max-width="1200px"
      width="100%"
      height="80vh"
    >
      <v-card-text>
        <v-text-field
          v-model="email"
          name="email"
          autocomplete="email"
          label="Email"
          :error-messages="responseStore?.response?.errors?.email"
          required
        />

        <v-text-field
          v-model="password"
          name="password"
          autocomplete="current-password"
          label="Password"
          type="password"
          :error-messages="responseStore?.response?.errors?.password"
          required
        />
      </v-card-text>
      <v-divider></v-divider>

      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

        <v-btn
          color="success"
          text="Login"
          variant="tonal"
          @click="login"
        ></v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>
<script setup>
import { shallowRef, ref } from "vue";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
import { useRouter } from "vue-router";
const dialog = shallowRef(false);
const router = useRouter();
const responseStore = useResponseStore();

const props = defineProps({});
const email = ref("");
const password = ref("");

const userStore = useUserStore();
const emit = defineEmits(["logged_in"]);

const login = async () => {
  responseStore.clearResponse(); // Clear previous responses

  const data = await userStore.login(email.value, password.value);
  if (data?.success) {
    emit("logged_in");
    router.push({ name: "item-list" }); // Use router to navigate to login page after logout
  }
};
</script>
