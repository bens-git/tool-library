<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <PageLayout>
        <Head title="Forgot Password" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%; max-width: 420px;">
                <div class="text-center mb-6">
                    <v-icon size="72" color="primary" class="mb-2">
                        mdi-lock-reset
                    </v-icon>
                    <div class="text-h4 font-weight-bold">Forgot Password</div>
                </div>

                <div class="mb-4 text-body-2">
                    Forgot your password? No problem. Just enter your email
                    address and we will email you a password reset link to choose a new one.
                </div>

                <div v-if="status" class="mb-4 text-body-2 font-medium" style="color: #16a34a;">
                    {{ status }}
                </div>

                <v-form @submit.prevent="submit">
                    <v-text-field
                        v-model="form.email"
                        label="Email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        :error-messages="form.errors.email"
                        color="primary"
                        variant="outlined"
                        class="mb-4"
                    />

                    <div class="d-flex justify-end">
                        <v-btn
                            color="primary"
                            type="submit"
                            :loading="form.processing"
                        >
                            Email Password Reset Link
                        </v-btn>
                    </div>
                </v-form>
            </div>
        </v-container>
    </PageLayout>
</template>
