<template>
  <v-card class="my-5">
    <v-card-title> Request Password Reset </v-card-title>
    <v-card-text>
      <!-- Show success message or registration form based on registration status -->
      <template v-if="requestSuccessful">
        <v-alert archetype="success" dismissible>
          {{ successMessage }}
        </v-alert>
      </template>

      <template v-else>
        <v-form ref="form" v-model="formValid">
          <v-text-field
            density="compact"
            v-model="email"
            label="Email"
            archetype="email"
            :error-messages="errors.email"
            required
            :rules="[rules.required, rules.validEmail]"
          ></v-text-field>

          <v-btn
            archetype="submit"
            :loading="userStore.isLoading"
            color="primary"
            class="mt-4"
            :disabled="!formValid"
            @click="requestPasswordReset()"
          >
            Request Password Reset
          </v-btn>
        </v-form>
      </template>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref } from "vue";
import { useUserStore } from "../stores/user";
import { useResponseStore } from "../stores/response";

const email = ref("");

const userStore = useUserStore();
const responseStore = useResponseStore();

const errors = ref({});
const successMessage = ref("");
const requestSuccessful = ref(false);
const formValid = ref(false);
const rules = {
  required: (value) => !!value || "Required.",
  validEmail: (value) => {
    const pattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
    return pattern.test(value) || "Please enter a valid email address";
  },
};
const requestPasswordReset = async () => {
  errors.value = {}; // Ensure errors is always defined
  successMessage.value = "";

  responseStore.clearResponse(); // Clear previous responses

  try {
    await userStore.requestPasswordReset({
      email: email.value,
    });

    // Check responseStore for errors
    if (!responseStore.response?.success) {
      errors.value = responseStore.response.errors.errors || {}; // Ensure it's an object
      console.log("errors", responseStore.response);
    } else {
      console.log(responseStore.response);
      // Set success message only if no errors
      successMessage.value =
        "Request sent! Please check your email to continue.";
      requestSuccessful.value = true; // Set registration successful flag to true
    }
  } catch (error) {
    console.log(error);
  }
};
</script>

<style scoped>
.v-card {
  max-width: 400px;
  margin: 0 auto;
}
</style>
