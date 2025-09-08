<template>
  <v-container>
    <v-card>
      <v-card-title>Reset Password</v-card-title>
      <v-card-text>
        <v-form ref="form" v-model="isValid">
          <input
            type="text"
            id="hidden-username"
            name="username"
            autocomplete="username"
          />

          <!-- New Password -->
          <v-text-field
            autocomplete="new-password"
            v-model="newPassword"
            label="New Password"
            type="password"
            :rules="[rules.required, validatePassword]"
            required
          ></v-text-field>

          <!-- Confirm New Password -->
          <v-text-field
            autocomplete="new-password"
            v-model="confirmPassword"
            label="Confirm New Password"
            type="password"
            :rules="[rules.required, confirmPasswordMatch]"
            required
          ></v-text-field>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn
          color="blue darken-1"
          @click="changePassword"
          :disabled="!isValid"
        >
          Change Password
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useUserStore } from "@/stores/user";
import { useRoute } from "vue-router";

const userStore = useUserStore();
const route = useRoute();

// Extract the token from the URL parameters
const token = ref(route.query.password_reset_token);

const isValid = ref(false);
const rules = {
  required: (value) => !!value || "Required.",
};

const currentPassword = ref("");
const newPassword = ref("");
const confirmPassword = ref("");

// Change password
const changePassword = async () => {
  if (!isValid.value) return;

  // Implement your password change logic here
  await userStore.changePasswordWithToken(
    newPassword.value,
    confirmPassword.value,
    token.value
  );
};

// Validate new password (example rule)
const validatePassword = (value) => {
  return value.length >= 8 || "Password must be at least 8 characters long.";
};

// Confirm new password matches
const confirmPasswordMatch = (value) => {
  return value === newPassword.value || "Passwords must match.";
};
</script>

<style scoped>
/* Add your custom styles here */
</style>
