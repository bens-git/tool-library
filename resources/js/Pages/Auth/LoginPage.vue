<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean, default: null },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <PageLayout>
        <Head title="Log in" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%; max-width: 420px;">
                <div class="text-center mb-6">
                    <v-icon size="72" color="primary" class="mb-2">
                        mdi-login
                    </v-icon>
                    <div class="text-h4 font-weight-bold">Login</div>
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
                        class="mb-3"
                    />

                    <v-text-field
                        v-model="form.password"
                        label="Password"
                        type="password"
                        required
                        autocomplete="current-password"
                        :error-messages="form.errors.password"
                        color="primary"
                        variant="outlined"
                        class="mb-2"
                    />

                    <v-checkbox
                        v-model="form.remember"
                        label="Remember me"
                        color="primary"
                        hide-details
                    />

                    <div class="d-flex flex-column align-center mt-4">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-decoration-none mb-3"
                        >
                            Forgot your password?
                        </Link>

                        <v-btn
                            color="primary"
                            block
                            type="submit"
                            :loading="form.processing"
                        >
                            Log in
                        </v-btn>
                    </div>
                </v-form>
            </div>
        </v-container>
    </PageLayout>
</template>
