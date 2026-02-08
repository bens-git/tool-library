<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import PageLayout from '@/Layouts/PageLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
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
            <div class="text-center">
                <!-- Hero -->
                <div class="mb-8">
                    <v-icon size="72" color="primary" class="mb-2">mdi-login</v-icon>
                    <div class="text-h4 font-weight-bold mb-1">Login</div>
                </div>

                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="email" value="Email" />

                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="password" value="Password" />

                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            required
                            autocomplete="current-password"
                        />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="mt-4 block">
                        <label class="flex items-center">
                            <Checkbox v-model:checked="form.remember" name="remember" />
                            <span class="ms-2 text-sm text-gray-600">Remember me</span>
                        </label>
                    </div>

                    <div class="d-flex flex-column align-center mt-4">
                        <div class="mb-2">
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-decoration-none"
                            >
                                Forgot your password?
                            </Link>
                        </div>

                      

                        <v-btn color="primary" block type="submit"> Log in </v-btn>
                    </div>
                </form>
            </div>
        </v-container>
    </PageLayout>
</template>
