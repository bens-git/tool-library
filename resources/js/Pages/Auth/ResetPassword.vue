<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { VTextField, VBtn, VContainer } from 'vuetify/components';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
  <PageLayout>
    <Head title="Reset Password" />

    <v-container fluid class="pa-4 d-flex justify-center">
      <div style="width: 100%; max-width: 400px;">
        <!-- Page Hero -->
        <div class="text-center mb-8">
          <v-icon size="72" color="primary" class="mb-2">mdi-lock-reset</v-icon>
          <div class="text-h4 font-weight-bold mb-1">Reset Password</div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit">
          <!-- Email -->
          <v-text-field
            v-model="form.email"
            label="Email"
            type="email"
            required
            autofocus
            autocomplete="username"
            :error="!!form.errors.email"
            :error-messages="form.errors.email"
            class="mb-4"
          />

          <!-- Password -->
          <v-text-field
            v-model="form.password"
            label="Password"
            type="password"
            required
            autocomplete="new-password"
            :error="!!form.errors.password"
            :error-messages="form.errors.password"
            class="mb-4"
          />

          <!-- Password Confirmation -->
          <v-text-field
            v-model="form.password_confirmation"
            label="Confirm Password"
            type="password"
            required
            autocomplete="new-password"
            :error="!!form.errors.password_confirmation"
            :error-messages="form.errors.password_confirmation"
            class="mb-6"
          />

          <!-- Submit Button -->
          <v-btn
            color="primary"
            block
            type="submit"
            :loading="form.processing"
            :disabled="form.processing"
          >
            Reset Password
          </v-btn>
        </form>
      </div>
    </v-container>
  </PageLayout>
</template>

<style scoped>
/* You can add small adjustments here if needed */
</style>
