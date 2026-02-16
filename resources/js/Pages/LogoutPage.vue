<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import PageLayout from '@/Layouts/PageLayout.vue';

const form = useForm({}); // empty form for logout

const logout = () => {
    // Submit POST to Laravel logout route
    form.post(route('logout'), {
        onSuccess: () => {
            // Redirect handled by Laravel (usually to login)
        },
    });
};
</script>

<template>
     <PageLayout>
   <Head title="Logout" />

    <v-container fluid class="fill-height d-flex justify-center align-center pa-4">
        <div style="width: 100%; max-width: 420px; text-align: center;">
            <v-icon size="72" color="primary" class="mb-4">
                mdi-logout
            </v-icon>

            <div class="text-h5 font-weight-bold mb-2">Logout</div>

            <v-alert type="info" outlined class="mb-4">
                Are you sure you want to logout?
            </v-alert>

            <div class="d-flex justify-space-between">
                <Link
                    :href="route('landing-page')" 
                    class="text-decoration-none"
                >
                    <v-btn variant="outlined" color="primary">
                        Cancel
                    </v-btn>
                </Link>

                <v-btn color="error" @click="logout">
                    Logout
                </v-btn>
            </div>
        </div>
    </v-container>
    </PageLayout>
</template>
