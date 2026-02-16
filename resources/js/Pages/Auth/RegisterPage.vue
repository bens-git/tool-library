<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <PageLayout>
        <Head title="Register" />

        <v-container fluid class="d-flex align-center justify-center pa-4">
            <div style="width: 100%; max-width: 420px;">
                <!-- Hero -->
                <div class="text-center mb-6">
                    <v-icon size="72" color="primary" class="mb-2">
                        mdi-account-plus
                    </v-icon>
                    <div class="text-h4 font-weight-bold">Register</div>
                </div>

                <v-form @submit.prevent="submit">
                    <v-text-field
                        v-model="form.name"
                        label="Name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        :error-messages="form.errors.name"
                        color="primary"
                        variant="outlined"
                        class="mb-3"
                    />

                    <v-text-field
                        v-model="form.email"
                        label="Email"
                        type="email"
                        required
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
                        autocomplete="new-password"
                        :error-messages="form.errors.password"
                        color="primary"
                        variant="outlined"
                        class="mb-3"
                    />

                    <v-text-field
                        v-model="form.password_confirmation"
                        label="Confirm Password"
                        type="password"
                        required
                        autocomplete="new-password"
                        :error-messages="form.errors.password_confirmation"
                        color="primary"
                        variant="outlined"
                        class="mb-4"
                    />

                    <div class="d-flex justify-space-between align-center">
                        <Link
                            :href="route('login')"
                            class="text-decoration-none"
                        >
                            Already registered?
                        </Link>

                        <v-btn
                            color="primary"
                            type="submit"
                            :loading="form.processing"
                        >
                            Register
                        </v-btn>
                    </div>
                </v-form>
            </div>
        </v-container>
    </PageLayout>
</template>
